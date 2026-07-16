<?php
// Allow member registration directly; keep admin separate via admin/login.php

$pageTitle = 'Member Registration';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/_head.php';
require_once __DIR__ . '/includes/_header.php';

$errors = [];
$member = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member['member_name'] = sanitize($_POST['member_name'] ?? '');
    $member['email'] = sanitize($_POST['email'] ?? '');
    $member['phone'] = sanitize($_POST['phone'] ?? '');
    $member['address'] = sanitize($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($member['member_name']) || !validateText($member['member_name'], 2)) {
        $errors[] = 'Full name must be at least 2 characters';
    }
    
    if (empty($member['email']) || !validateEmail($member['email'])) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($member['phone'])) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($member['address'])) {
        $errors[] = 'Address is required';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT member_id FROM member WHERE email = ?");
            $stmt->execute([$member['email']]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $verificationCode = rand(100000, 999999);
                
                // Generate sequential member ID (M0001, M0002, etc.)
                $memberId = generateSequentialId($pdo, 'M', 'member', 'member_id');
                
                // Insert into member table
                $stmt = $pdo->prepare("INSERT INTO member (member_id, full_name, email, phone, address, password, is_verified) 
                                      VALUES (?, ?, ?, ?, ?, ?, 0)");
                $stmt->execute([
                    $memberId,
                    $member['member_name'],
                    $member['email'],
                    $member['phone'],
                    $member['address'],
                    $hashedPassword
                ]);
                
                // Insert verification code into verification_code table
                $verifyId = 'V' . str_pad(rand(0, 999999), 9, '0', STR_PAD_LEFT);
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $stmt = $pdo->prepare("INSERT INTO verification_code (verify_id, email, code, expiry, is_used) 
                                      VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([$verifyId, $member['email'], $verificationCode, $expiry]);
                
                // Send verification email
                $emailBody = "Your verification code is: {$verificationCode}\nThis code expires in 1 hour.";
                $mailSent = sendEmail($member['email'], 'Bookstore Verification Code', $emailBody);
                if (!$mailSent) {
                    $_SESSION['error'] = 'We could not send a verification email. Please check your mail settings or contact support.';
                }
                
                // Auto-login after registration
                $_SESSION['member_id'] = $memberId;
                $_SESSION['member_name'] = $member['member_name'];
                $_SESSION['member_email'] = $member['email'];
                $_SESSION['verification_email'] = $member['email'];
                $_SESSION['success'] = 'Registration successful! You are now logged in. Please verify your email from your account page.';
                redirect('/member/profile.php');
            }
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Member Registration</h1>
        
        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>
        
        <form method="POST" action="/register.php" class="member-form">
            <div class="form-group">
                <label for="member_name">Full Name *</label>
                <input type="text" id="member_name" name="member_name" 
                       value="<?php echo htmlspecialchars($member['member_name'] ?? ''); ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="phone">Phone *</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="address">Address *</label>
                <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($member['address'] ?? ''); ?></textarea>
                <small style="color:#666;">Enter your full address.</small>
            </div> 
            
            <div class="form-group">
                <label for="password">Password *</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password" data-target="#password">👁</button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <div style="position: relative;">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <button type="button" class="toggle-password" data-target="#confirm_password">👁</button>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary member-btn-primary">Register</button>
                <a href="/login.php" class="btn btn-secondary member-btn-secondary">Back to Login</a>
            </div>
        </form>
    </div>


</main>

<?php require_once __DIR__ . '/includes/_footer.php'; ?>

