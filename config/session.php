<?php
/**
 * Session Configuration
 * Starts session if not already started
 */

if (session_status() === PHP_SESSION_NONE) {
    // Ensure PHP uses the correct timezone for date() and expiry calculations
    // Change this to your timezone if needed (e.g., 'Asia/Kuala_Lumpur')
    date_default_timezone_set('Asia/Kuala_Lumpur');
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0, // expire when browser closes
        'path' => $cookieParams['path'] ?? '/',
        'domain' => $cookieParams['domain'] ?? '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
?>

