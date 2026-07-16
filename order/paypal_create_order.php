<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/paypal.php';

$input = json_decode(file_get_contents('php://input'), true);
$requestOrderId = isset($input['order_id']) ? sanitize($input['order_id']) : '';
try {
    // If an order_id was provided, validate it belongs to the user and is pending
    if (!empty($requestOrderId)) {
        $order = getOrderDetails($pdo, $requestOrderId);
        if (!$order || $order['member_id'] != $_SESSION['member_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Order not found or access denied']);
            exit;
        }
        $orderId = $requestOrderId;
        $orderAmount = $order['total_amount'];
        $currency = $order['currency'] ?? (defined('PAYPAL_CURRENCY') ? PAYPAL_CURRENCY : 'USD');
    } else {
        // No pre-existing order: create a new 'Pending' order using the selected cart items from session
        $selectedCartIds = isset($_SESSION['selected_cart_ids']) ? $_SESSION['selected_cart_ids'] : [];
        $cartItemsAll = getCartItems($pdo, $_SESSION['member_id']);
        $cartItems = [];
        if (!empty($selectedCartIds)) {
            foreach ($cartItemsAll as $item) {
                if (in_array((string)$item['cart_id'], $selectedCartIds, true)) {
                    $cartItems[] = $item;
                }
            }
        } else {
            $cartItems = $cartItemsAll;
        }
        if (empty($cartItems)) {
            http_response_code(400);
            echo json_encode(['error' => 'Cart empty']);
            exit;
        }

        $cartTotal = 0;
        foreach ($cartItems as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
        }

        // Create DB order (header + details) but do NOT update stock or clear cart yet
        try {
            $pdo->beginTransaction();
            $orderId = generateSequentialId($pdo, 'O', 'order_header', 'order_id');
            $hasShippingCol = $pdo->query("SHOW COLUMNS FROM order_header LIKE 'shipping_address'")->fetch();
            $member = getMemberById($pdo, $_SESSION['member_id']);
            if ($hasShippingCol) {
                $stmt = $pdo->prepare("INSERT INTO order_header (order_id, member_id, total_amount, payment_method, status, order_date, shipping_address) VALUES (?, ?, ?, ?, 'Pending', NOW(), ?)");
                $stmt->execute([$orderId, $_SESSION['member_id'], $cartTotal, 'PayPal', $member['address']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO order_header (order_id, member_id, total_amount, payment_method, status, order_date) VALUES (?, ?, ?, ?, 'Pending', NOW())");
                $stmt->execute([$orderId, $_SESSION['member_id'], $cartTotal, 'PayPal']);
            }
            foreach ($cartItems as $item) {
                $detailId = generateSequentialId($pdo, 'DTL', 'order_detail', 'detail_id');
                $stmt = $pdo->prepare("INSERT INTO order_detail (detail_id, order_id, product_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$detailId, $orderId, $item['product_id'], $item['quantity'], $item['price']]);
            }
            $pdo->commit();
            $orderAmount = $cartTotal;
            $currency = defined('PAYPAL_CURRENCY') ? PAYPAL_CURRENCY : 'USD';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create order: ' . $e->getMessage()]);
            exit;
        }
    }

    // Build PayPal order and include return/cancel URLs
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $accessToken = paypal_get_access_token();
    $url = paypal_api_base() . '/v2/checkout/orders';
    $body = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $orderId,
            'amount' => [
                'currency_code' => $currency,
                'value' => number_format((float)$orderAmount, 2, '.', '')
            ]
        ]],
        'application_context' => [
            'return_url' => $baseUrl . '/order/payment.php?order_id=' . urlencode($orderId),
            'cancel_url' => $baseUrl . '/order/paypal_cancel.php?order_id=' . urlencode($orderId)
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        // Attempt to clean up created order when PayPal request fails and we created it here
        if (empty($requestOrderId)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM order_detail WHERE order_id = ?");
                $stmt->execute([$orderId]);
                $stmt = $pdo->prepare("DELETE FROM order_header WHERE order_id = ?");
                $stmt->execute([$orderId]);
            } catch (Exception $inner) {
                // ignore cleanup errors
            }
        }
        http_response_code(500);
        echo json_encode(['error' => 'PayPal order request failed: ' . $err]);
        exit;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($resp, true);
    if ($httpCode >= 200 && $httpCode < 300 && !empty($data['id'])) {
        echo json_encode(['id' => $data['id'], 'links' => $data['links'] ?? null, 'order_id' => $orderId]);
        exit;
    } else {
        // cleanup if we created the order here
        if (empty($requestOrderId)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM order_detail WHERE order_id = ?");
                $stmt->execute([$orderId]);
                $stmt = $pdo->prepare("DELETE FROM order_header WHERE order_id = ?");
                $stmt->execute([$orderId]);
            } catch (Exception $inner) {
                // ignore
            }
        }
        http_response_code(500);
        echo json_encode(['error' => 'PayPal create order failed', 'details' => $data]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>