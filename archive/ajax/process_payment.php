<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$memberId = $_SESSION['member_id'] ?? null;
if (!$memberId) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$orderId = isset($_POST['order_id']) ? sanitize($_POST['order_id']) : '';
$paymentMethod = isset($_POST['payment_method']) ? sanitize($_POST['payment_method']) : '';
$cardNumber = isset($_POST['card_number']) ? preg_replace('/\s+/', '', $_POST['card_number']) : '';
$expiry = isset($_POST['expiry_date']) ? sanitize($_POST['expiry_date']) : '';
$cvv = isset($_POST['cvv']) ? sanitize($_POST['cvv']) : '';
$cardName = isset($_POST['card_name']) ? sanitize($_POST['card_name']) : '';

$errors = [];

if (empty($orderId)) {
    $errors[] = 'Order ID missing.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM order_header WHERE order_id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order || $order['member_id'] != $memberId) {
        $errors[] = 'Order not found or access denied.';
    }
}

if (empty($paymentMethod)) {
    $errors[] = 'Payment method is required.';
}

if (stripos($paymentMethod, 'card') !== false) {
    // Basic card checks
    $digits = preg_replace('/\D/', '', $cardNumber);
    if (strlen($digits) < 13 || strlen($digits) > 19) {
        $errors[] = 'Card number appears invalid.';
    }
    if (empty($cardName)) {
        $errors[] = 'Cardholder name is required.';
    }
    // expiry format YYYY-MM
    if (empty($expiry) || !preg_match('/^\d{4}-\d{2}$/', $expiry)) {
        $errors[] = 'Expiry date is required and must be in YYYY-MM format.';
    } else {
        $parts = explode('-', $expiry);
        $year = (int)$parts[0];
        $month = (int)$parts[1];
        if ($month < 1 || $month > 12) {
            $errors[] = 'Expiry month invalid.';
        } else {
            $expiryTs = strtotime(sprintf('%04d-%02d-01', $year, $month));
            $lastOfMonth = strtotime('+1 month', $expiryTs) - 1;
            if ($lastOfMonth < time()) {
                $errors[] = 'Card has expired.';
            }
        }
    }
    if (empty($cvv) || !preg_match('/^\d{3,4}$/', $cvv)) {
        $errors[] = 'CVV is required and must be 3 or 4 digits.';
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors, 'error' => implode(' ', $errors)]);
    exit;
}

// Simulate an external gateway call here if needed. For now, return success when validations pass.
echo json_encode(['success' => true, 'message' => 'Payment data validated.']);
exit;
?>