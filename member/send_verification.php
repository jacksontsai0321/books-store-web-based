<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

try {
    $stmt = $pdo->prepare("SELECT email FROM member WHERE member_id = ?");
    $stmt->execute([$_SESSION['member_id']]);
    $member = $stmt->fetch();
    if (!$member) {
        $_SESSION['error'] = 'Member not found.';
        redirect('/member/profile.php');
    }
    $email = $member['email'];
    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $verifyId = 'V' . str_pad(rand(0, 999999), 9, '0', STR_PAD_LEFT);
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare("INSERT INTO verification_code (verify_id, email, code, expiry, is_used) VALUES (?, ?, ?, ?, 0)");
    $stmt->execute([$verifyId, $email, $code, $expiry]);

    $emailBody = "Your verification code is: {$code}\nThis code expires in 1 hour.";
    $sent = sendEmail($email, 'Bookstore Verification Code', $emailBody);
    if (!$sent) {
        $_SESSION['error'] = 'Could not send verification email. Please try again.';
    } else {
        $_SESSION['verification_email'] = $email;
        $_SESSION['success'] = 'Verification code sent. Please check your email.';
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to send verification code.';
}

redirect('/verify_code.php');

