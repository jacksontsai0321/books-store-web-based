<?php
/**
 * Database Configuration
 * PDO connection for MySQL
 * 
 * XAMPP Default Settings:
 * Host: localhost
 * Username: root
 * Password: (empty)
 * Port: 3306 (default)
 */

$host = 'localhost';
$dbname = 'bookstore_db';
$username = 'root';
$password = ''; // XAMPP default is empty password
$port = 3306; // MySQL default port

try {
    // Try connecting with port specified (some XAMPP setups need this)
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // More helpful error message
    $errorMsg = "Database connection failed!\n\n";
    $errorMsg .= "Error: " . $e->getMessage() . "\n\n";
    $errorMsg .= "Please check:\n";
    $errorMsg .= "1. XAMPP MySQL service is running\n";
    $errorMsg .= "2. Database 'bookstore_db' exists (import schema.sql)\n";
    $errorMsg .= "3. Database credentials in config/db.php are correct\n";
    $errorMsg .= "4. Visit test_db.php to diagnose connection issues\n";
    
    // In web context, show HTML error
    if (php_sapi_name() !== 'cli') {
        die("<html><body><h1>Database Connection Error</h1><pre>" . htmlspecialchars($errorMsg) . "</pre><p><a href='test_db.php'>Run Database Test</a></p></body></html>");
    } else {
        die($errorMsg);
    }
}
?>

