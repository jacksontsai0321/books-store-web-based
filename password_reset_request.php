<?php
$pageTitle = 'Password Reset Request';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/_head.php';
require_once __DIR__ . '/includes/_header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email) || !validateEmail($email)) {
        $errors[] = 'Valid email is required';
    } else {
        // If user is logged in, ensure the email matches the profile email
        if (isset($_SESSION['member_id'])) {
            try {
                $stmt = $pdo->prepare("SELECT email FROM member WHERE member_id = ? LIMIT 1");
                $stmt->execute([$_SESSION['member_id']]);
                $profileEmail = $stmt->fetchColumn();
                if ($profileEmail && strtolower($profileEmail) !== strtolower($email)) {
                    $errors[] = 'The email provided does not match your account email.';
                }
            } catch (PDOException $e) {
                // ignore and continue with standard checks
            }
        }
        try {
            $stmt = $pdo->prepare("SELECT member_id FROM member WHERE LOWER(email) = LOWER(?)");
            $stmt->execute([$email]);
            $member = $stmt->fetch();
            
            if ($member) {
                // Generate reset OTP (6 digits)
                $resetCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $resetId = 'R' . str_pad(rand(0, 999999), 9, '0', STR_PAD_LEFT);
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Remove old reset codes for this email/role
                $stmt = $pdo->prepare("DELETE FROM password_reset WHERE LOWER(email) = LOWER(?) AND role = 'member'");
                $stmt->execute([$email]);
                
                // Store in password_reset table (use token column for OTP)
                $stmt = $pdo->prepare("INSERT INTO password_reset (reset_id, email, token, expiry, role) VALUES (?, ?, ?, ?, 'member')");
                $stmt->execute([$resetId, $email, $resetCode, $expiry]);
                
                // Send email with reset code
                $emailBody = "Your password reset code is: {$resetCode}\nThis code expires in 1 hour.";
                $sent = sendEmail($email, 'Bookstore Password Reset Code', $emailBody);
                if (!$sent) {
                    $_SESSION['error'] = 'Could not send reset email. Please try again.';
                }
                
                $_SESSION['reset_email'] = $email;
                // Redirect to verification step so user must confirm code before resetting
                redirect('/password_reset.php?stage=verify');
            } else {
                // Don't reveal if email exists
                // Still redirect to verification step to avoid revealing account existence
                $_SESSION['reset_email'] = $email;
                redirect('/password_reset.php?stage=verify');
            }
        } catch (PDOException $e) {
            $errors[] = 'Request failed. Please try again.';
        }
    }
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Password Reset Request</h1>
        
        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <p>If the email exists, a reset code has been sent. Please check your email.</p>
            </div>
        <?php else: ?>
            <form method="POST" action="/password_reset_request.php" class="member-form">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary member-btn-primary">Send Reset Code</button>
                    <a href="/login.php" class="btn btn-secondary member-btn-secondary">Back to Login</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/_footer.php'; ?>

