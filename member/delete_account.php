<?php
$pageTitle = 'Delete Account';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (empty($password)) {
        $errors[] = 'Please enter your current password to confirm.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT member_id, password FROM member WHERE member_id = ? LIMIT 1");
            $stmt->execute([$_SESSION['member_id']]);
            $member = $stmt->fetch();
            if (!$member || !password_verify($password, $member['password'])) {
                $errors[] = 'Incorrect password. Account not deleted.';
            } else {
                // Anonymize member data but keep member_id so orders remain associated
                $anonymizedEmail = 'deleted+' . $member['member_id'] . '@example.invalid';
                $stmt = $pdo->prepare("UPDATE member SET full_name = ?, email = ?, phone = NULL, address = NULL, password = NULL, is_verified = 0 WHERE member_id = ?");
                $stmt->execute(['[Deleted User]', $anonymizedEmail, $member['member_id']]);

                // Optional: remove profile photo if exists
                try {
                    $photoPath = __DIR__ . '/../uploads/profile/' . $member['member_id'] . '.jpg';
                    if (file_exists($photoPath)) @unlink($photoPath);
                } catch (Exception $e) {
                    // ignore
                }

                // Log user out and redirect
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['success'] = 'Your account has been removed. Thank you for using our service.';
                redirect('/');
            }
        } catch (PDOException $e) {
            $errors[] = 'Failed to delete account. Please try again later.';
        }
    }
}

require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';
?>

<main>
    <div class="container">
        <h1 class="page-title">Delete Account</h1>

        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>

        <div class="member-form" style="max-width:600px; margin:0 auto;">
            <p>Deleting your account will remove your personal data from your profile. Your past orders will remain for record-keeping and will be associated with a deleted account.</p>
            <form method="POST" action="/member/delete_account.php">
                <div class="form-group">
                    <label for="password">Confirm with your current password *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-danger">Delete My Account</button>
                    <a href="/member/profile.php" class="btn btn-secondary member-btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>