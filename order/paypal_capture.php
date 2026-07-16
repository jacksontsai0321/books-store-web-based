<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/paypal.php';

$input = json_decode(file_get_contents('php://input'), true);
$paypalOrderId = isset($input['paypal_order_id']) ? sanitize($input['paypal_order_id']) : '';
$orderId = isset($input['order_id']) ? sanitize($input['order_id']) : '';

if (empty($paypalOrderId) || empty($orderId)) {
    http_response_code(400);
    echo json_encode(['error' => 'paypal_order_id and order_id are required']);
    exit;
}

try {
    $order = getOrderDetails($pdo, $orderId);
    if (!$order || $order['member_id'] != $_SESSION['member_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Order not found or access denied']);
        exit;
    }

    $accessToken = paypal_get_access_token();
    $url = paypal_api_base() . '/v2/checkout/orders/' . urlencode($paypalOrderId) . '/capture';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        http_response_code(500);
        echo json_encode(['error' => 'PayPal capture request failed: ' . $err]);
        exit;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($resp, true);
    if ($httpCode >= 200 && $httpCode < 300 && isset($data['status']) && in_array($data['status'], ['COMPLETED','PAYER_ACTION_REQUIRED','APPROVED'])) {
        // Get capture id (first capture found)
        $captureId = null;
        if (!empty($data['purchase_units'][0]['payments']['captures'][0]['id'])) {
            $captureId = $data['purchase_units'][0]['payments']['captures'][0]['id'];
        }

        // Insert payment record into order_payment
        try {
            $pdo->beginTransaction();
            $paymentId = generateSequentialId($pdo, 'P', 'order_payment', 'payment_id');
            $paymentStatus = 'Completed';
            if (isset($data['status']) && $data['status'] !== 'COMPLETED') {
                $paymentStatus = $data['status'];
            }
            $stmt = $pdo->prepare("INSERT INTO order_payment (payment_id, order_id, amount, method, payment_status, fake_transaction_ref, payment_date, created_by) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
            $createdBy = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
            $stmt->execute([
                $paymentId,
                $orderId,
                $order['total_amount'],
                'PayPal',
                $paymentStatus,
                ($captureId ? 'paypal_capture:' . $captureId : 'paypal_order:' . $paypalOrderId),
                $createdBy
            ]);

            // Update order status
            $stmt = $pdo->prepare("UPDATE order_header SET status = 'Processing' WHERE order_id = ?");
            $stmt->execute([$orderId]);

            // Apply stock changes and clear cart items now that payment is completed
            try {
                $orderItems = getOrderItems($pdo, $orderId);
                // Reduce stock for each ordered item
                foreach ($orderItems as $it) {
                    updateStock($pdo, $it['product_id'], $it['quantity']);
                }
                // Remove those products from member's shopping cart (if present)
                if (!empty($orderItems)) {
                    $productIds = array_map(function($i){ return $i['product_id']; }, $orderItems);
                    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                    $params = $productIds;
                    $params[] = $_SESSION['member_id'];
                    $stmt = $pdo->prepare("DELETE FROM shopping_cart WHERE product_id IN ($placeholders) AND member_id = ?");
                    $stmt->execute($params);
                    // Clear selected items from session now that they were removed from cart
                    if (isset($_SESSION['selected_cart_ids'])) {
                        unset($_SESSION['selected_cart_ids']);
                    }
                }
            } catch (Exception $inner) {
                // Non-fatal; log and continue committing payment record
                error_log('[paypal_capture] post-capture stock/cart update failed: ' . $inner->getMessage());
            }

            $pdo->commit();

            echo json_encode(['success' => true, 'payment_id' => $paymentId, 'capture' => $data]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to record payment: ' . $e->getMessage()]);
            exit;
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'PayPal capture failed', 'details' => $data]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

?>