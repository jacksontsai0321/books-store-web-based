<?php
$pageTitle = 'Sales Reports';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

// Get date range filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today

// Validate date range
$startTs = strtotime($startDate);
$endTs = strtotime($endDate);
if ($startTs === false || $endTs === false || $startTs > $endTs) {
    $error = 'Invalid date range: Start date cannot be after End date.';
    $overallStats = ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0, 'unique_customers' => 0];
    $revenueByStatus = [];
    $dailySales = [];
    $topProducts = [];
    $topCustomers = [];
    $paymentStats = [];
} else {
    try {
        // Overall Statistics
        $stmt = $pdo->prepare("SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value,
            COUNT(DISTINCT member_id) as unique_customers
            FROM order_header 
            WHERE order_date BETWEEN ? AND ?");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $overallStats = $stmt->fetch();
        
        
        // Daily Sales Trend
        $stmt = $pdo->prepare("SELECT 
            DATE(order_date) as sale_date,
            COUNT(*) as order_count,
            SUM(total_amount) as daily_revenue
            FROM order_header 
            WHERE order_date BETWEEN ? AND ?
            GROUP BY DATE(order_date)
            ORDER BY sale_date DESC
            LIMIT 5");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $dailySales = $stmt->fetchAll();
        
        // Top Products
        $stmt = $pdo->prepare("SELECT 
            p.name as product_name,
            SUM(od.quantity) as total_quantity,
            SUM(od.price * od.quantity) as total_revenue
            FROM order_detail od
            JOIN product p ON od.product_id = p.product_id
            JOIN order_header oh ON od.order_id = oh.order_id
            WHERE oh.order_date BETWEEN ? AND ?
            GROUP BY od.product_id, p.name
            ORDER BY total_quantity DESC
            LIMIT 5");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $topProducts = $stmt->fetchAll();
        
        // Top Customers
        $stmt = $pdo->prepare("SELECT 
            m.full_name,
            m.email,
            COUNT(oh.order_id) as order_count,
            SUM(oh.total_amount) as total_spent
            FROM order_header oh
            JOIN member m ON oh.member_id = m.member_id
            WHERE oh.order_date BETWEEN ? AND ?
            GROUP BY oh.member_id, m.full_name, m.email
            ORDER BY total_spent DESC
            LIMIT 5");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $topCustomers = $stmt->fetchAll();
        
        // Revenue by Status (limit to 5)
        $stmt = $pdo->prepare("SELECT 
            status,
            COUNT(*) as order_count,
            SUM(total_amount) as revenue
            FROM order_header 
            WHERE order_date BETWEEN ? AND ?
            GROUP BY status
            ORDER BY revenue DESC
            LIMIT 5");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $revenueByStatus = $stmt->fetchAll();
        
        // Payment Statistics (limit to 5)
        $stmt = $pdo->prepare("SELECT 
            payment_status,
            COUNT(*) as payment_count,
            SUM(amount) as total_amount
            FROM order_payment
            WHERE payment_date BETWEEN ? AND ?
            GROUP BY payment_status
            ORDER BY total_amount DESC
            LIMIT 5");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $paymentStats = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        $overallStats = ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0, 'unique_customers' => 0];
        $revenueByStatus = [];
        $dailySales = [];
        $topProducts = [];
        $topCustomers = [];
        $paymentStats = [];
        $error = 'Unable to load report data.';
    }
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Sales Reports</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Date Range Filter -->
        <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <form method="GET" action="/admin/report/index.php" style="display: flex; gap: 1rem; align-items: end;">
                <div class="form-group" style="flex: 1;">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="/admin/report/index.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
        
        <!-- Overall Statistics -->
        <div class="dashboard-stats" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="stat-value"><?php echo number_format($overallStats['total_orders'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="stat-value"><?php echo formatPrice($overallStats['total_revenue'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Average Order Value</h3>
                <div class="stat-value"><?php echo formatPrice($overallStats['avg_order_value'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Unique Customers</h3>
                <div class="stat-value"><?php echo number_format($overallStats['unique_customers'] ?? 0); ?></div>
            </div>
        </div>
        
        <!-- Revenue by Status -->
        <div style="background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem;">
            <h2>Top 5 Revenue by Order Status</h2>
            <?php if (empty($revenueByStatus)): ?>
                <p>No data available for the selected period.</p>
            <?php else: ?>
                <div style="display:flex; gap:2rem; align-items:flex-start;">
                    <table class="admin-table" style="flex:1;">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Order Count</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenueByStatus as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['status']); ?></td>
                                    <td><?php echo number_format($stat['order_count']); ?></td>
                                    <td><?php echo formatPrice($stat['revenue'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="width: 350px;">
                        <canvas id="revenueStatusChart" width="350" height="250"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($revenueByStatus)): ?>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                (function(){
                    const ctx = document.getElementById('revenueStatusChart').getContext('2d');
                    const data = <?php echo json_encode(array_map(function($r){ return ['label' => $r['status'], 'value' => (float)($r['revenue'] ?? 0)]; }, $revenueByStatus)); ?>;
                    const labels = data.map(d => d.label);
                    const values = data.map(d => d.value);
                    const colors = ["#3498db", "#e74c3c", "#2ecc71", "#f1c40f", "#9b59b6"];
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: values,
                                backgroundColor: colors.slice(0, labels.length)
                            }]
                        },
                        options: {
                            plugins: { legend: { position: 'bottom' } }
                        }
                    });
                })();
            </script>
        <?php endif; ?>
        
        <!-- Top Products -->
        <div style="background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem;">
            <h2>Top 5 Selling Products</h2>
            <?php if (empty($topProducts)): ?>
                <p>No product sales data available for the selected period.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo number_format($product['total_quantity']); ?></td>
                                <td><?php echo formatPrice($product['total_revenue'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Top Customers -->
        <div style="background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem;">
            <h2>Top 5 Customers</h2>
            <?php if (empty($topCustomers)): ?>
                <p>No customer data available for the selected period.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topCustomers as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo number_format($customer['order_count']); ?></td>
                                <td><?php echo formatPrice($customer['total_spent'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

