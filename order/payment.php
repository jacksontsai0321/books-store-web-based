<?php
$pageTitle = 'Payment';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$orderId = isset($_GET['order_id']) ? sanitize($_GET['order_id']) : '';
$errors = [];
$showCartMode = false;

// Local debug log for payment diagnostics (file in project logs/)
$paymentLogFile = __DIR__ . '/../logs/payment_debug.log';
$logPayment = function($msg) use ($paymentLogFile) {
    // Try project logs directory first
    @mkdir(dirname($paymentLogFile), 0777, true);
    $written = @file_put_contents($paymentLogFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
    if ($written === false) {
        // Fallback to system temp directory
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'payment_debug.log';
        @file_put_contents($tmp, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
        error_log('[payment] Could not write to ' . $paymentLogFile . ', wrote to ' . $tmp);
    }
};

if (empty($orderId)) {
    // No pre-created order — display cart summary and let the client create the DB order when PayPal button is clicked.
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
        $_SESSION['error'] = 'Your cart is empty.';
        redirect('/cart/view.php');
    }

    $cartTotal = 0;
    foreach ($cartItems as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
    }

    // Populate a minimal $order and $orderItems to reuse the template below
    $order = [
        'order_date' => date('Y-m-d H:i'),
        'status' => 'Pending',
        'currency' => defined('PAYPAL_CURRENCY') ? PAYPAL_CURRENCY : 'USD',
        'total_amount' => $cartTotal,
    ];
    $orderItems = $cartItems;
    $showCartMode = true;
} else {
    // Get order details for the existing record
    $order = getOrderDetails($pdo, $orderId);
    if (!$order || $order['member_id'] != $_SESSION['member_id']) {
        // If order was removed (e.g., user cancelled at PayPal), send them back to cart so they can see their items still present
        $_SESSION['error'] = 'Order not found or access denied.';
        redirect('/cart/view.php');
    }
    
    // Check if payment already exists
    try {
        $stmt = $pdo->prepare("SELECT * FROM order_payment WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $existingPayment = $stmt->fetch();
        
        if ($existingPayment && $existingPayment['payment_status'] == 'Completed') {
            $_SESSION['success'] = 'Payment already completed for this order.';
            redirect('/order/detail.php?id=' . $orderId);
        }
    } catch (PDOException $e) {
        $errors[] = 'Error checking payment status.';
    }
    
    $orderItems = getOrderItems($pdo, $orderId);
    $paymentMethodFromOrder = $order['payment_method'] ?? 'Online Payment';
}

// Determine shipping address to show in the order summary
$shippingAddress = '';
// Prefer an order-specific shipping_address column, fall back to older 'address' column, then member profile
if (!empty($order['shipping_address'] ?? '')) {
    $shippingAddress = $order['shipping_address'];
} elseif (!empty($order['address'] ?? '')) {
    $shippingAddress = $order['address'];
} else {
    $member = getMemberById($pdo, $_SESSION['member_id']);
    $shippingAddress = trim($member['address'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = '[payment] POST received for order_id=' . $orderId . ' member_id=' . ($_SESSION['member_id'] ?? 'null');
    error_log($msg);
    $logPayment($msg);
    $msg = '[payment] POST data: ' . json_encode($_POST);
    error_log($msg);
    $logPayment($msg);
    $shippingAddress = sanitize($_POST['shipping_address'] ?? '');
    $shippingLat = sanitize($_POST['shipping_address_lat'] ?? '');
    $shippingLng = sanitize($_POST['shipping_address_lng'] ?? '');

    $paymentMethodInput = sanitize($_POST['payment_method'] ?? '');
    $paymentMethod = !empty($paymentMethodInput) ? $paymentMethodInput : ($paymentMethodFromOrder ?: 'Online Payment');
    $cardNumber = sanitize($_POST['card_number'] ?? '');
    $cardName = sanitize($_POST['card_name'] ?? '');
    $expiryDate = sanitize($_POST['expiry_date'] ?? '');
    $cvv = sanitize($_POST['cvv'] ?? '');
    
    if (empty($paymentMethod)) {
        $errors[] = 'Payment method is required.';
    }
    
    // Server-side validation for payment fields (mirror ajax/process_payment.php)
    if (empty($errors)) {
        if (stripos($paymentMethod, 'card') !== false) {
            $digits = preg_replace('/\D/', '', $cardNumber);
            if (strlen($digits) < 13 || strlen($digits) > 19) {
                $errors[] = 'Card number appears invalid.';
            }
            if (empty($cardName)) {
                $errors[] = 'Cardholder name is required.';
            }
            if (empty($expiryDate) || !preg_match('/^\d{4}-\d{2}$/', $expiryDate)) {
                $errors[] = 'Expiry date is required and must be in YYYY-MM format.';
            } else {
                $parts = explode('-', $expiryDate);
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

        // Shipping address length check
        if (!empty($shippingAddress) && strlen($shippingAddress) > 255) {
            $errors[] = 'Shipping address is too long.';
        }
    }

    // If validation passed, process payment (fake success)
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Generate fake transaction reference
            $fakeTransactionRef = 'TXN' . strtoupper(uniqid()) . time();
            
            // Determine payment status (for fake payment, always success)
            $paymentStatus = 'Completed';
            
            // Create payment record
            $paymentId = generateSequentialId($pdo, 'P', 'order_payment', 'payment_id');
            $stmt = $pdo->prepare("INSERT INTO order_payment 
                (payment_id, order_id, amount, method, payment_status, fake_transaction_ref, payment_date, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
            // For payments initiated by members, leave `created_by` NULL because
            // the `order_payment.created_by` FK references `admin.admin_id`.
            // Only set `created_by` when an admin is creating the payment.
            $createdBy = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
            $params = [
                $paymentId,
                $orderId,
                $order['total_amount'],
                $paymentMethod,
                $paymentStatus,
                $fakeTransactionRef,
                $createdBy
            ];
            $msg = '[payment] Inserting payment with params: ' . json_encode($params);
            error_log($msg);
            $logPayment($msg);
            $stmt->execute($params);
            if ($stmt->rowCount() === 0) {
                // Log and throw to trigger rollback
                $msg = '[payment] INSERT affected 0 rows for order_id=' . $orderId . ' payment_id=' . $paymentId . ' stmt_error: ' . json_encode($stmt->errorInfo());
                error_log($msg);
                $logPayment($msg);
                throw new Exception('Failed to insert payment record.');
            } else {
                $msg = '[payment] INSERT success, rows=' . $stmt->rowCount() . ' payment_id=' . $paymentId;
                error_log($msg);
                $logPayment($msg);
            }
            
            // Update order shipping address if provided
            if (!empty($shippingAddress)) {
                try {
                    // Prefer 'shipping_address' column, fall back to 'address' when older schema uses it
                    $col = null;
                    $colCheck = $pdo->query("SHOW COLUMNS FROM order_header LIKE 'shipping_address'")->fetch();
                    if ($colCheck) {
                        $col = 'shipping_address';
                    } else {
                        $colCheck = $pdo->query("SHOW COLUMNS FROM order_header LIKE 'address'")->fetch();
                        if ($colCheck) {
                            $col = 'address';
                        }
                    }

                    if ($col) {
                        $stmt = $pdo->prepare("UPDATE order_header SET $col = ? WHERE order_id = ?");
                        $stmt->execute([$shippingAddress, $orderId]);
                    } else {
                        error_log('[payment] No shipping/address column found on order_header; skipping address update');
                    }
                } catch (PDOException $e) {
                    error_log('[payment] shipping address update failed: ' . $e->getMessage());
                    // Don't halt payment flow for address update failures
                }
            }

            // Update order status to Processing
            $stmt = $pdo->prepare("UPDATE order_header SET status = 'Processing' WHERE order_id = ?");
            $stmt->execute([$orderId]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Payment completed successfully!';
            $_SESSION['payment_id'] = $paymentId;
            redirect('/order/payment_success.php?order_id=' . $orderId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Payment processing failed. Please try again.';
            $msg = 'Payment error: ' . $e->getMessage() . '\nTrace: ' . $e->getTraceAsString();
            error_log($msg);
            $logPayment($msg);

            // If statement exists, log its errorInfo for more details
            if (isset($stmt) && is_object($stmt)) {
                try {
                    $stmtErr = $stmt->errorInfo();
                    $logPayment('[payment] stmt errorInfo: ' . json_encode($stmtErr));
                } catch (Exception $inner) {
                    // ignore
                }
            }

            // Show debug details on page only when explicitly requested
            if (isset($_GET['debug_payment']) && $_GET['debug_payment'] === '1') {
                $errors[] = 'DEBUG: ' . $e->getMessage();
                $errors[] = 'DEBUG TRACE: ' . $e->getTraceAsString();
                if (isset($stmtErr)) $errors[] = 'DEBUG STMT INFO: ' . json_encode($stmtErr);
            }
        }
    }
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Payment</h1>
        
        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
            <!-- Order Summary -->
            <div>
                <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2>Order Summary</h2>
                    <p><strong>Order Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                    
                    <h3 style="margin-top: 1rem;">Shipping Address</h3>
                    <?php if (!empty($shippingAddress)): ?>
                        <p style="white-space: pre-wrap; margin: 0;"><?php echo nl2br(htmlspecialchars($shippingAddress)); ?></p>
                    <?php else: ?>
                        <p style="color: #666; margin: 0;">No shipping address provided.</p>
                    <?php endif; ?>
                    
                    <h3 style="margin-top: 1.5rem;">Order Items</h3>
                    <div style="margin-top: 1rem;">
                        <?php foreach ($orderItems as $item): ?>
                            <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f5f5f5; margin-bottom: 0.5rem; border-radius: 4px;">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                    <p style="margin: 0; color: #666;">Qty: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?></p>
                                </div>
                                <div>
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid #ddd;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold;">
                            <span>Total Amount:</span>
                            <span><?php echo formatPrice($order['total_amount']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Form -->
            <div>
                <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2>Payment Information</h2>
                    <p style="color: #666; margin-bottom: 1.5rem;">This is for assignment purpose only(no real money will be conduct here).</p>
                    
                    <!-- Pay with PayPal only: shipping address and other payment fields removed -->
                    <div class="pay-with-paypal" style="background: white; padding: 1.5rem; border-radius: 8px;">
                        <p style="color: #666; margin-bottom: 1rem;"><strong>Pay with PayPal</strong></p>
                        <div id="paypal-button-container"></div>
                        <div style="margin-top: 1rem;">
                            <a href="<?php echo $orderId ? '/order/detail.php?id=' . htmlspecialchars($orderId) : '/cart/view.php'; ?>" class="btn btn-secondary member-btn-secondary">Cancel</a>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
    </div>


</main>



<!-- PayPal SDK and render logic -->
<!-- Put your PayPal client ID in `config/paypal.php` by replacing the placeholder PAYPAL_CLIENT_ID -->
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo htmlspecialchars(PAYPAL_CLIENT_ID); ?>&currency=<?php echo htmlspecialchars($order['currency'] ?? (defined('PAYPAL_CURRENCY') ? PAYPAL_CURRENCY : 'USD')); ?>"></script> 
<script>
// Render PayPal buttons and integrate with server endpoints
if (typeof paypal !== 'undefined') {
    paypal.Buttons({
        style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'paypal' },
        createOrder: function(data, actions) {
            return fetch('/order/paypal_create_order.php', {
                method: 'post',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: '<?php echo $orderId; ?>' })
            }).then(function(res){ return res.json(); })
              .then(function(data){ 
                  if (data && data.id) { 
                      // store our server-side order id so we can reference it in capture/cancel callbacks
                      window.__appOrderId = data.order_id || '<?php echo $orderId; ?>';
                      return data.id; 
                  } else { console.error('Create order failed', data); throw new Error('Create order failed'); } 
              });
        },
        onApprove: function(data, actions) {
            var appOrderId = window.__appOrderId || '<?php echo $orderId; ?>';
            return fetch('/order/paypal_capture.php', {
                method: 'post',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ paypal_order_id: data.orderID, order_id: appOrderId })
            }).then(function(res){ return res.json(); })
              .then(function(resp){
                  if (resp.success) {
                      window.location = '/order/payment_success.php?order_id=' + encodeURIComponent(appOrderId);
                  } else {
                      alert('Payment failed. Please contact support.');
                      console.error(resp);
                  }
              });
        },
        onCancel: function(data) {
            var appOrderId = window.__appOrderId || '<?php echo $orderId; ?>';
            // User cancelled the PayPal flow — inform server and return to cart without showing payment failure
            if (appOrderId) {
                fetch('/order/paypal_cancel.php', {
                    method: 'post',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: appOrderId })
                }).finally(function(){
                    // Signal cancellation to cart view for a friendly message
                    window.location = '/cart/view.php?payment_cancel=1';
                });
            } else {
                window.location = '/cart/view.php?payment_cancel=1';
            }
        },
        onError: function(err) {
            console.error('PayPal error', err);
            alert('An error occurred during the PayPal transaction.');
        }
    }).render('#paypal-button-container');
} else {
    console.warn('PayPal SDK not loaded. Make sure PAYPAL_CLIENT_ID in `config/paypal.php` is set and network is available.');
}
</script>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

