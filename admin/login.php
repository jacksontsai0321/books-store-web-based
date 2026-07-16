<?php
// Start output buffering to prevent header issues
ob_start();

$pageTitle = 'Admin Login';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    ob_end_clean();
    header('Location: /admin/dashboard.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Don't sanitize or trim password - keep it exactly as-is
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    if (empty($errors)) {
        try {
            // Try case-insensitive email match
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE LOWER(email) = LOWER(?)");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                // Get the stored hash
                $storedHash = $admin['password'];
                
                // Check if hash is valid bcrypt format
                $isValidHash = preg_match('/^\$2[ayb]\$\d{2}\$/', $storedHash);
                
                if (!$isValidHash) {
                    $errors[] = 'Password hash in database is invalid. Please contact administrator.';
                } else {
                    // Always use password_verify for hashed passwords
                    $passwordValid = password_verify($password, $storedHash);
                    
                    if ($passwordValid) {
                        // Set session variables
                        session_regenerate_id(true);
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['admin_name'] = $admin['full_name'] ?? $admin['username'] ?? 'Admin';
                        $_SESSION['admin_role'] = $admin['admin_id'] === SUPER_ADMIN_ID ? 'admin' : 'staff';
                        // Clear output buffer and redirect
                        ob_end_clean();
                        session_write_close();
                        header('Location: /admin/dashboard.php');
                        exit();
                    } else {
                        $errors[] = 'Invalid email or password.';
                    }
                }
            } else {
                $errors[] = 'Admin account not found with email: ' . htmlspecialchars($email);
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
            $errors[] = 'Please check your database connection. <a href="/test_db.php">Test Database</a>';
        }
    }
}

// End output buffering if we're displaying the form
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/logo.png" type="image/jpg">
    <title>Admin Login - Bookstore & Stationery</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #ecf0f1;">
    <div style="background: white; padding: 3rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 400px; width: 100%;">
        <h1 style="text-align: center; margin-bottom: 2rem; color: #2c3e50;">Admin Login</h1>
        
        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>
        
        <form method="POST" action="/admin/login.php">
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 1rem;">
            <a href="/index.php">Back to Home</a> | 
            <a href="/login.php">Member Login</a>
        </div>
    </div>
</body>
</html>

