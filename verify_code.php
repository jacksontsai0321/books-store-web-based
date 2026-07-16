<?php
$pageTitle = 'Verify Email';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/_head.php';
require_once __DIR__ . '/includes/_header.php';

if (!isset($_SESSION['verification_email'])) {
    redirect('/register.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize($_POST['code'] ?? '');
    $email = strtolower(trim($_SESSION['verification_email']));
    
    if (empty($code)) {
        $errors[] = 'Verification code is required';
    } else {
        try {
            // Look up verification record by email and code (case-insensitive email)
            $stmt = $pdo->prepare("SELECT * FROM verification_code WHERE LOWER(email) = ? AND code = ? AND expiry > NOW() AND is_used = 0 ORDER BY expiry DESC, verify_id DESC LIMIT 1");
            $stmt->execute([$email, $code]);
            $verifyRecord = $stmt->fetch();

            if ($verifyRecord) {
                // Try to mark member as verified using email. Support both `member` and `members` table names.
                $updated = 0;
                try {
                    $stmt = $pdo->prepare("UPDATE member SET is_verified = 1 WHERE LOWER(email) = ? LIMIT 1");
                    $stmt->execute([$email]);
                    $updated = $stmt->rowCount();
                } catch (PDOException $inner) {
                    // ignore and try alternative table name
                }

                if ($updated === 0) {
                    try {
                        $stmt = $pdo->prepare("UPDATE members SET is_verified = 1 WHERE LOWER(email) = ? LIMIT 1");
                        $stmt->execute([$email]);
                        $updated = $stmt->rowCount();
                    } catch (PDOException $inner) {
                        // ignore - we'll still mark code used and return success only if member updated
                    }
                }

                // Mark verification code as used
                $stmt = $pdo->prepare("UPDATE verification_code SET is_used = 1 WHERE verify_id = ?");
                $stmt->execute([$verifyRecord['verify_id']]);

                if ($updated > 0) {
                    unset($_SESSION['verification_email']);
                    $_SESSION['success'] = 'Email verified successfully!';
                    redirect('/login.php');
                } else {
                    // Member row not updated — likely table/column mismatch. Log details for debugging.
                    error_log("[verify_code] verification succeeded for verification_code.verify_id={$verifyRecord['verify_id']} but no member row was updated for email={$email}");
                    $errors[] = 'Verification record found but failed to update member. Contact support.';
                }
            } else {
                // Log the failed attempt for debugging (do not expose details to user)
                error_log("[verify_code] Invalid/expired code attempt for email={$email}, code={$code}");
                $errors[] = 'Invalid or expired verification code';
            }
        } catch (PDOException $e) {
            error_log('[verify_code] PDOException: ' . $e->getMessage());
            $errors[] = 'Verification failed. Please try again.';
        }
    }
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Verify Email</h1>
        
        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>
        
        <div class="info-message">
            <p>Please enter the verification code sent to: <?php echo htmlspecialchars($_SESSION['verification_email']); ?></p>
        </div>
        
        <form method="POST" action="/verify_code.php" class="member-form">
            <div class="form-group">
                <label for="code">Verification Code *</label>
                <input type="text" id="code" name="code" maxlength="6" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary member-btn-primary">Verify</button>
                <a href="/register.php" class="btn btn-secondary member-btn-secondary">Back to Register</a>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/_footer.php'; ?>

