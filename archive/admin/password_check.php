<?php
/**
 * Direct Password Check - Compare Debug Tool vs Login Code
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

echo "<h1>Direct Password Verification Test</h1>";
echo "<pre>";

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password']; // No trim on password
    
    echo "=== INPUT ===\n";
    echo "Email: " . htmlspecialchars($email) . "\n";
    echo "Password (raw): '" . htmlspecialchars($password) . "'\n";
    echo "Password Length: " . strlen($password) . "\n";
    echo "Password Bytes: " . bin2hex($password) . "\n";
    echo "\n";
    
    // Method 1: Same as login.php
    echo "=== METHOD 1: Same as login.php ===\n";
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$email]);
    $admin1 = $stmt->fetch();
    
    if ($admin1) {
        echo "Admin found\n";
        echo "Password Hash: " . substr($admin1['password'], 0, 30) . "...\n";
        echo "Hash Length: " . strlen($admin1['password']) . "\n";
        echo "Hash Bytes (first 60): " . bin2hex(substr($admin1['password'], 0, 30)) . "\n";
        
        $result1 = password_verify($password, $admin1['password']);
        echo "password_verify() result: " . ($result1 ? 'TRUE ✓' : 'FALSE ✗') . "\n";
    } else {
        echo "Admin NOT found\n";
    }
    
    echo "\n";
    
    // Method 2: Direct query (no LOWER)
    echo "=== METHOD 2: Direct query (no LOWER) ===\n";
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $admin2 = $stmt->fetch();
    
    if ($admin2) {
        echo "Admin found\n";
        $result2 = password_verify($password, $admin2['password']);
        echo "password_verify() result: " . ($result2 ? 'TRUE ✓' : 'FALSE ✗') . "\n";
    } else {
        echo "Admin NOT found\n";
    }
    
    echo "\n";
    
    // Method 3: Check if password needs rehashing
    if ($admin1) {
        echo "=== PASSWORD INFO ===\n";
        $hash = $admin1['password'];
        
        if (preg_match('/^\$2[ayb]\$(\d+)\$/', $hash, $matches)) {
            echo "Hash Algorithm: bcrypt\n";
            echo "Cost Factor: " . $matches[1] . "\n";
            
            // Try to verify with different methods
            echo "\n=== VERIFICATION TESTS ===\n";
            
            // Test 1: Direct verify
            $test1 = password_verify($password, $hash);
            echo "Test 1 (direct): " . ($test1 ? 'PASS' : 'FAIL') . "\n";
            
            // Test 2: Trim password
            $test2 = password_verify(trim($password), $hash);
            echo "Test 2 (trimmed): " . ($test2 ? 'PASS' : 'FAIL') . "\n";
            
            // Test 3: Check if hash needs update
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                echo "⚠ Hash needs rehashing!\n";
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                echo "New hash would be: " . substr($newHash, 0, 30) . "...\n";
            } else {
                echo "✓ Hash is up to date\n";
            }
            
            // Test 4: Manual hash creation for comparison
            $manualHash = password_hash($password, PASSWORD_DEFAULT);
            echo "Manual hash of input: " . substr($manualHash, 0, 30) . "...\n";
            $test4 = password_verify($password, $manualHash);
            echo "Verify against manual hash: " . ($test4 ? 'PASS' : 'FAIL') . "\n";
            
            if (!$test1) {
                echo "\n=== TROUBLESHOOTING ===\n";
                echo "password_verify() is returning FALSE.\n";
                echo "Possible causes:\n";
                echo "1. Password in database was not hashed with password_hash()\n";
                echo "2. Password was modified after hashing\n";
                echo "3. Character encoding mismatch\n";
                echo "4. Extra whitespace or special characters\n";
                echo "\n";
                echo "To fix, you may need to:\n";
                echo "1. Re-hash the password in database\n";
                echo "2. Check the exact password value in database\n";
                echo "3. Verify the password was hashed correctly\n";
            }
        } else {
            echo "⚠ Password is NOT a valid bcrypt hash!\n";
            echo "Hash format: " . substr($hash, 0, 20) . "...\n";
            echo "This means the password was not hashed with password_hash()\n";
        }
    }
}

echo "</pre>";

?>

<h2>Test Password Verification</h2>
<form method="POST">
    <div style="margin-bottom: 10px;">
        <label>Email:</label><br>
        <input type="email" name="email" required style="width: 300px; padding: 5px;" 
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    </div>
    <div style="margin-bottom: 10px;">
        <label>Password:</label><br>
        <input type="password" name="password" required style="width: 300px; padding: 5px;">
    </div>
    <button type="submit" style="padding: 10px 20px;">Test Verification</button>
</form>

<p><a href="/admin/login.php">← Back to Admin Login</a></p>

