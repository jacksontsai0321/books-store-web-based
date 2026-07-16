<?php
$pageTitle = 'Login';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/_head.php';
require_once __DIR__ . '/includes/_header.php';

$errors = [];
$rememberCookie = $_COOKIE['remember_email'] ?? '';

// If already logged in, skip login page
if (isset($_SESSION['member_id'])) {
    redirect('/index.php');
}
// Auto-fill email from cookie
$prefillEmail = $rememberCookie;

// If remember_me cookie for auto-login
if (!isset($_SESSION['member_id']) && isset($_COOKIE['remember_member'])) {
    $token = $_COOKIE['remember_member'];
    $parts = explode('|', $token);
    if (count($parts) === 2) {
        [$rememberMemberId, $rememberSig] = $parts;
        try {
            $stmt = $pdo->prepare("SELECT * FROM member WHERE member_id = ?");
            $stmt->execute([$rememberMemberId]);
            $member = $stmt->fetch();
            if ($member) {
                $expectedSig = hash('sha256', $member['password'] . 'remember_secret');
                if (hash_equals($expectedSig, $rememberSig)) {
                    session_regenerate_id(true);
                    $_SESSION['member_id'] = $member['member_id'];
                    $_SESSION['member_name'] = $member['full_name'] ?? '';
                    $_SESSION['success'] = 'Welcome back, ' . ($_SESSION['member_name'] ?? 'Member') . '!';
                    redirect('/index.php');
                }
            }
        } catch (PDOException $e) {
            // ignore
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Don't sanitize password - keep it as-is
    $remember = isset($_POST['remember_me']);
    
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
            $stmt = $pdo->prepare("SELECT * FROM member WHERE LOWER(email) = LOWER(?)");
            $stmt->execute([$email]);
            $member = $stmt->fetch();
            
            if ($member) {
                // Always use password_verify for hashed passwords
                if (password_verify($password, $member['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['member_id'] = $member['member_id'];
                    $_SESSION['member_name'] = $member['full_name'] ?? '';
                    $_SESSION['success'] = 'Login successful!';
                    
                    // Remember me cookies
                    $cookieExpire = $remember ? time() + (86400 * 30) : time() - 3600;
                    if ($remember) {
                        setcookie('remember_email', $email, $cookieExpire, '/');
                        $sig = hash('sha256', $member['password'] . 'remember_secret');
                        setcookie('remember_member', $member['member_id'] . '|' . $sig, $cookieExpire, '/', '', false, true);
                    } else {
                        setcookie('remember_email', '', $cookieExpire, '/');
                        setcookie('remember_member', '', $cookieExpire, '/', '', false, true);
                    }
                    
                    redirect('/index.php');
                } else {
                    $errors[] = 'Invalid email or password.';
                }
            } else {
                $errors[] = 'Member account not found with email: ' . htmlspecialchars($email);
                $errors[] = 'Please register if you don\'t have an account.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
            $errors[] = 'Please check your database connection. <a href="/test_db.php">Test Database</a>';
        }
    }
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Member Login</h1>
        
        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>
        
        <form method="POST" action="/login.php" class="member-form">
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($prefillEmail); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password" data-target="#password">👁</button>
                </div>
            </div>
            
            <div class="remember-wrapper">
                <label class="remember-label">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Remember Me</span>
                </label>
            </div>
            
            <div class="login-actions">
                <button type="submit" class="login-btn">Login</button>

                <a href="password_reset_request.php" class="forget-password">Forgot Password?</a>
            </div>

            <div style="text-align: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                <p>Don't have an account? <a href="/register.php?type=member">Register here</a></p>
                <p style="margin-top: 0.5rem; color: #7f8c8d;">Are you an admin? <a href="/admin/login.php">Admin Login</a></p>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/_footer.php'; ?>

