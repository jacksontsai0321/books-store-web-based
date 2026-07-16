<?php
$pageTitle = 'View Order';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

$orderId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$returnPage = isset($_GET['page']) ? (int)$_GET['page'] : null;
$returnSort = isset($_GET['sort']) ? sanitize($_GET['sort']) : '';
$returnOrder = isset($_GET['order']) ? sanitize($_GET['order']) : '';
$returnSearch = isset($_GET['search']) ? sanitize($_GET['search']) : '';

if (empty($orderId)) {
    $_SESSION['error'] = 'Order ID is required.';
    $redirectUrl = '/admin/order/list.php';
    if ($returnPage) {
        $redirectUrl .= '?page=' . $returnPage;
    }
    redirect($redirectUrl);
}

$order = getOrderDetails($pdo, $orderId);

if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    $redirectUrl = '/admin/order/list.php';
    if ($returnPage) {
        $redirectUrl .= '?page=' . $returnPage;
    }
    redirect($redirectUrl);
}

$orderItems = getOrderItems($pdo, $orderId);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = sanitize($_POST['status'] ?? '');
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] === '1');

    if (empty($status)) {
        $errors[] = 'Status is required';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Status is required']);
            exit();
        }
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE order_header SET status = ? WHERE order_id = ?");
            $stmt->execute([$status, $orderId]);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'status' => $status, 'message' => 'Order status updated successfully!']);
                exit();
            }

            $_SESSION['success'] = 'Order status updated successfully!';
            $redirectUrl = '/admin/order/view.php?id=' . urlencode($orderId);
            if ($returnPage) {
                $redirectUrl .= '&page=' . $returnPage;
            }
            if ($returnSort) {
                $redirectUrl .= '&sort=' . urlencode($returnSort);
            }
            if ($returnOrder) {
                $redirectUrl .= '&order=' . urlencode($returnOrder);
            }
            if ($returnSearch) {
                $redirectUrl .= '&search=' . urlencode($returnSearch);
            }
            redirect($redirectUrl);
        } catch (PDOException $e) {
            $errors[] = 'Failed to update order status.';
            error_log('Order status update error: ' . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to update order status.']);
                exit();
            }
        }
    }
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">View Order #<?php echo htmlspecialchars($orderId); ?></h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>
        
        <div style="background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem;">
            <h2 style="margin-bottom: 1.5rem; border-bottom: 2px solid #e0e0e0; padding-bottom: 0.5rem;">Order Information</h2>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                <div>
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">Order ID</div>
                        <div style="font-weight: 600; font-size: 1.1rem;">#<?php echo htmlspecialchars($orderId); ?></div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">Order Date</div>
                        <div style="font-weight: 500;"><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">Status</div>
                        <div id="order-status-display" style="font-weight: 500; color: #3498db;"><?php echo htmlspecialchars($order['status']); ?></div>
                    </div>
                    <?php if ($order['status'] === 'Refund Requested'): ?>
                        <div class="info-message" style="margin-top: 0.5rem;">A refund has been requested for this order. Please review it in <a href="/admin/report/refunds.php?order_id=<?php echo urlencode($orderId); ?>">Refund Requests</a>.</div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">Total Amount</div>
                        <div style="font-weight: 600; font-size: 1.2rem; color: #27ae60;"><?php echo formatPrice($order['total_amount']); ?></div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">Payment Method</div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($order['payment_method']); ?></div>
                    </div>
                </div>
            </div>
            
            <div style="border-top: 1px solid #e0e0e0; padding-top: 1.5rem; margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem; color: #333;">Member Information</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">Name</div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($order['member_name']); ?></div>
                    </div>
                    <div>
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">Email</div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($order['email']); ?></div>
                    </div>
                    <div>
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">Phone</div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($order['phone']); ?></div>
                    </div>
                </div>
            </div>
            
            <div style="border-top: 1px solid #e0e0e0; padding-top: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem; color: #333;">Shipping Address</h3>
                <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">Address</div>
                <div style="font-weight: 500; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
            </div>
            
            <h3 style="margin-top: 2rem;">Update Status</h3>
            <?php if ($order['status'] === 'Completed'): ?>
                <div class="info-message">This order is <strong>Completed</strong>. Status changes are disabled.</div>
            <?php else: ?>
                <form id="update-status-form" method="POST" action="/admin/order/view.php?id=<?php echo urlencode($orderId); ?><?php 
                    $formParams = [];
                    if ($returnPage) {
                        $formParams[] = 'page=' . $returnPage;
                    }
                    if ($returnSort) {
                        $formParams[] = 'sort=' . urlencode($returnSort);
                    }
                    if ($returnOrder) {
                        $formParams[] = 'order=' . urlencode($returnOrder);
                    }
                    if ($returnSearch) {
                        $formParams[] = 'search=' . urlencode($returnSearch);
                    }
                    if (!empty($formParams)) {
                        echo '&' . implode('&', $formParams);
                    }
                ?>" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label for="status">Order Status *</label>
                        <select id="status" name="status" required>
                            <option value="Pending" <?php echo $order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Processing" <?php echo $order['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="Shipped" <?php echo $order['status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="Completed" <?php echo $order['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo $order['status'] == 'Cancelled' ? 'selected' : ''; ?>>Refund</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
                <script>
                (function(){
                    const form = document.getElementById('update-status-form');
                    if (!form) return;
                    form.addEventListener('submit', function(e){
                        e.preventDefault();
                        const submitBtn = form.querySelector('button[type="submit"]');
                        if (submitBtn) submitBtn.disabled = true;
                        const fd = new FormData(form);
                        fd.append('ajax', '1');
                        fetch(form.action, {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin'
                        }).then(resp => resp.json()).then(data => {
                            if (submitBtn) submitBtn.disabled = false;
                            if (data && data.success) {
                                // Update top status display
                                const statusEl = document.getElementById('order-status-display');
                                if (statusEl) {
                                    statusEl.textContent = data.status;
                                }

                                // Show temporary success message
                                const msg = document.createElement('div');
                                msg.className = 'success-message';
                                msg.textContent = data.message || 'Order status updated';
                                form.parentNode.insertBefore(msg, form);
                                setTimeout(() => { if (msg && msg.parentNode) msg.parentNode.removeChild(msg); }, 3500);

                                // If status is Completed, hide the form and show info message
                                if (data.status === 'Completed') {
                                    const info = document.createElement('div');
                                    info.className = 'info-message';
                                    info.textContent = 'This order is Completed. Status changes are disabled.';
                                    form.parentNode.insertBefore(info, form);
                                    form.style.display = 'none';
                                }
                            } else {
                                alert((data && data.message) ? data.message : 'Update failed.');
                            }
                        }).catch(err => {
                            if (submitBtn) submitBtn.disabled = false;
                            alert('Update failed.');
                        });
                    });
                })();
                </script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const statusDisplay = document.getElementById('order-status-display');
                    const statusSelect = document.getElementById('status');
                    if (!statusDisplay || !statusSelect) return;

                    // Initialize select to match top status
                    const init = statusDisplay.textContent.trim();
                    try { statusSelect.value = init; } catch (e) {}

                    // When select changes (before saving), reflect it in the top display so both always match
                    statusSelect.addEventListener('change', function() {
                        statusDisplay.textContent = this.value;
                    });

                    // Observe top status for any external changes and update select accordingly
                    const mo = new MutationObserver(function(mutations) {
                        const newVal = statusDisplay.textContent.trim();
                        if (statusSelect.value !== newVal) {
                            // If option missing, add it
                            let found = false;
                            for (let i = 0; i < statusSelect.options.length; i++) {
                                if (statusSelect.options[i].value === newVal) { found = true; break; }
                            }
                            if (!found) {
                                const opt = document.createElement('option');
                                opt.value = newVal; opt.text = newVal; statusSelect.appendChild(opt);
                            }
                            statusSelect.value = newVal;
                        }
                    });
                    mo.observe(statusDisplay, { childList: true, characterData: true, subtree: true });
                });
                </script>
            <?php endif; ?>
        </div>
        
        <div style="background: white; padding: 2rem; border-radius: 8px;">
            <h2>Order Items</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr class="product-row" <?php if (!empty($item['image_path'])): ?>data-product-image="<?php echo htmlspecialchars($item['image_path']); ?>"<?php endif; ?>>
                            <td class="product-name-cell"><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo formatPrice($item['price']); ?></td>
                            <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 2rem;">
            <?php
            $backUrl = '/admin/order/list.php';
            $params = [];
            if ($returnPage) {
                $params['page'] = $returnPage;
            }
            if ($returnSort && ($returnSort !== 'order_id' || $returnOrder !== 'ASC')) {
                $params['sort'] = $returnSort;
                $params['order'] = $returnOrder;
            }
            if (!empty($returnSearch)) {
                $params['search'] = $returnSearch;
            }
            if (!empty($params)) {
                $backUrl .= '?' . http_build_query($params);
            }
            ?>
            <a href="<?php echo $backUrl; ?>" class="btn btn-secondary">Back to Orders</a>
        </div>
    </div>
</div>

<style>
.product-row {
    position: relative;
    cursor: pointer;
}

.product-row:hover {
    background-color: #f5f5f5;
}

.product-name-cell {
    position: relative;
}

.product-row .product-tooltip {
    display: none;
    position: absolute;
    left: 100%;
    top: 0;
    margin-left: 15px;
    z-index: 1000;
    background: white;
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    pointer-events: none;
    white-space: nowrap;
}

.product-row:hover .product-tooltip {
    display: block;
}

.product-row .product-tooltip img {
    width: 200px;
    height: 200px;
    object-fit: contain;
    display: block;
    border-radius: 4px;
}

/* Adjust tooltip position if near right edge */
.product-row .product-tooltip.tooltip-left {
    left: auto;
    right: 100%;
    margin-left: 0;
    margin-right: 15px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productRows = document.querySelectorAll('.product-row[data-product-image]');
    
    productRows.forEach(function(row) {
        const imagePath = row.getAttribute('data-product-image');
        if (imagePath) {
            const productNameCell = row.querySelector('.product-name-cell');
            const tooltip = document.createElement('div');
            tooltip.className = 'product-tooltip';
            const img = document.createElement('img');
            img.src = imagePath;
            img.alt = 'Product Image';
            img.onerror = function() {
                tooltip.style.display = 'none';
            };
            tooltip.appendChild(img);
            productNameCell.appendChild(tooltip);
            
            // Adjust tooltip position if it would go off-screen
            row.addEventListener('mouseenter', function() {
                setTimeout(function() {
                    const rect = tooltip.getBoundingClientRect();
                    const windowWidth = window.innerWidth;
                    if (rect.right > windowWidth - 20) {
                        tooltip.classList.add('tooltip-left');
                    } else {
                        tooltip.classList.remove('tooltip-left');
                    }
                }, 10);
            });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

