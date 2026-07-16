<?php
/**
 * Check Your Table Structures
 * This will show the structure of all your tables
 * for testing and debugging purposes.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/db.php';

echo "<h1>Your Database Table Structures</h1>";
echo "<p>Please share this information so I can update the code to match your schema.</p>";

$tables = ['admin', 'member', 'order_detail', 'order_header', 'order_payment', 'password_reset', 'product', 'product_category', 'product_photo', 'shopping_cart', 'verification_code','review'];

foreach ($tables as $table) {
    try {
        echo "<h2>Table: $table</h2>";
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show sample data count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "<p><strong>Records:</strong> $count</p>";
        echo "<hr>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error reading table $table: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Next Step</h2>";
echo "<p>Please copy and share the table structures above, or run this SQL query and share the results:</p>";
echo "<pre>";
echo "SHOW CREATE TABLE admin;\n";
echo "SHOW CREATE TABLE member;\n";
echo "SHOW CREATE TABLE order_detail;\n";
echo "SHOW CREATE TABLE order_header;\n";
echo "SHOW CREATE TABLE order_payment;\n";
echo "SHOW CREATE TABLE password_reset;\n";
echo "SHOW CREATE TABLE product;\n";
echo "SHOW CREATE TABLE product_category;\n";
echo "SHOW CREATE TABLE product_photo;\n";
echo "SHOW CREATE TABLE shopping_cart;\n";
echo "SHOW CREATE TABLE verification_code;\n";
echo "</pre>";
?>

