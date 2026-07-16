<?php
$pageTitle = 'Reset Password';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/_head.php';
require_once __DIR__ . '/includes/_header.php';

$errors = [];

// Two-step flow:
// 1) Verification step (stage=verify) - check code sent to email and set session reset id
// 2) Reset step (stage=reset) - allow entering new password after verification

$stage = $_GET['stage'] ?? '';

// Ensure we have the email from request step in session
if (!isset($_SESSION['reset_email']) && $stage === 'verify') {
    // If no email in session, redirect to request page
    redirect('/password_reset_request.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($stage === 'verify') || isset($_POST['verify_code'])) {
        // Handle verification of code
        $resetCode = sanitize($_POST['reset_code'] ?? '');
        $email = sanitize($_SESSION['reset_email'] ?? ($_POST['email'] ?? ''));

        if (empty($resetCode)) {
            $errors[] = 'Reset code is required';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT pr.*, m.member_id FROM password_reset pr 
                                      JOIN member m ON LOWER(pr.email) = LOWER(m.email) 
                                      WHERE LOWER(pr.email) = LOWER(?) AND pr.token = ? AND pr.expiry > NOW() AND pr.role = 'member' 
                                      ORDER BY pr.expiry DESC, pr.reset_id DESC LIMIT 1");
                $stmt->execute([strtolower($email), $resetCode]);
                $resetRecord = $stmt->fetch();

                if ($resetRecord) {
                    // If user is logged in, ensure the reset request matches their own account
                    if (isset($_SESSION['member_id']) && $_SESSION['member_id'] != $resetRecord['member_id']) {
                        $errors[] = 'This reset code does not match your account.';
                    } else {
                        // Store reset id and member id in session to allow password reset
                        $_SESSION['password_reset_id'] = $resetRecord['reset_id'];
                        $_SESSION['password_reset_member_id'] = $resetRecord['member_id'];
                        $_SESSION['password_reset_email'] = $resetRecord['email'];
                        redirect('/password_reset.php?stage=reset');
                    }
                } else {
                    $errors[] = 'Invalid email or reset code. Code may have expired.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Verification failed. Please try again.';
            }
        }
    } elseif ($stage === 'reset' || isset($_POST['new_password'])) {
        // Handle final password reset
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }

        if (empty($errors)) {
            if (empty($_SESSION['password_reset_id']) || empty($_SESSION['password_reset_member_id'])) {
                $errors[] = 'Verification step missing. Please start again.';
            } else {
                try {
                    // Re-check that reset record still exists and is valid
                    $stmt = $pdo->prepare("SELECT * FROM password_reset WHERE reset_id = ? AND expiry > NOW() AND role = 'member' LIMIT 1");
                    $stmt->execute([$_SESSION['password_reset_id']]);
                    $resetRow = $stmt->fetch();

                    if (!$resetRow) {
                        $errors[] = 'Reset token no longer valid. Please request a new code.';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE member SET password = ? WHERE member_id = ?");
                        $stmt->execute([$hashedPassword, $_SESSION['password_reset_member_id']]);

                        // Delete used reset token
                        $stmt = $pdo->prepare("DELETE FROM password_reset WHERE reset_id = ?");
                        $stmt->execute([$_SESSION['password_reset_id']]);

                        // Clear session reset data
                        unset($_SESSION['password_reset_id'], $_SESSION['password_reset_member_id'], $_SESSION['password_reset_email'], $_SESSION['reset_email']);

                        $_SESSION['success'] = 'Password reset successful! You can now login.';
                        redirect('/login.php');
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Password reset failed. Please try again.';
                }
            }
        }
    }
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Reset Password</h1>

        <?php if (!empty($errors)): ?>
                <?php renderErrors($errors); ?>
        <?php endif; ?>

        <?php if ($stage === 'verify'): ?>
            <div class="info-message">
                <p>Please enter the reset code sent to: <?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?></p>
            </div>

            <form method="POST" action="/password_reset.php?stage=verify" class="member-form">
                <div class="form-group">
                    <label for="reset_code">Reset Code *</label>
                    <input type="text" id="reset_code" name="reset_code" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary member-btn-primary">Verify Code</button>
                    <a href="/password_reset_request.php" class="btn btn-secondary member-btn-secondary">Request New Code</a>
                </div>
            </form>

        <?php elseif ($stage === 'reset' && !empty($_SESSION['password_reset_id'])): ?>

            <div class="info-message">
                <p>Resetting password for: <?php echo htmlspecialchars($_SESSION['password_reset_email'] ?? ''); ?></p>
            </div>

            <form method="POST" action="/password_reset.php?stage=reset" class="member-form">
                <div class="form-group">
                    <label for="password">New Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary member-btn-primary">Set New Password</button>
                    <a href="/login.php" class="btn btn-secondary member-btn-secondary">Back to Login</a>
                </div>
            </form>

        <?php else: ?>

            <div class="info-message">
                <p>Please start by requesting a reset code for your account.</p>
            </div>
            <a href="/password_reset_request.php" class="btn btn-primary member-btn-primary">Request Reset Code</a>

        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/_footer.php'; ?>

