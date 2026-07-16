<?php
/**
 * PayPal configuration
 *
 * Place your PayPal credentials here.
 * - PAYPAL_ENV: 'sandbox' or 'live'
 * - PAYPAL_CLIENT_ID: Your PayPal client id (the one shown to frontend too)
 * - PAYPAL_SECRET: Your PayPal secret (used server-side to get access token)
 *
 * IMPORTANT: Do NOT commit real secrets to public repositories. Use environment
 * variables or deployment-specific configuration for production.
 */

// Set to 'sandbox' for testing or 'live' for production
if (!defined('PAYPAL_ENV')) define('PAYPAL_ENV', 'sandbox');

// Replace these placeholders with your credentials
if (!defined('PAYPAL_CLIENT_ID')) define('PAYPAL_CLIENT_ID', 'AfpPpJJdHQoJtMS9aKE3KCCmeW3EgebZZni7-gTmMSdMoCkiLFzbZL-FGQh7vb0rPfC4kGYOm-s5Amxp');
if (!defined('PAYPAL_SECRET')) define('PAYPAL_SECRET', 'EHNi65oyjUe_Sgl0GT25qVGWZsYUSHP7DEMrK35uZgvb1B1yqimlle2lC8UC07fv0QONXM6ge95CRA3V');

// Default currency for PayPal operations (e.g., 'USD', 'MYR'). Change to 'MYR' to use Ringgit.
if (!defined('PAYPAL_CURRENCY')) define('PAYPAL_CURRENCY', 'MYR');

function paypal_currency() {
    return defined('PAYPAL_CURRENCY') ? PAYPAL_CURRENCY : 'USD';
} 

function paypal_api_base() {
    return PAYPAL_ENV === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
}

/**
 * Get a PayPal OAuth2 access token using client credentials.
 * Returns access token string on success, or throws Exception on failure.
 */
function paypal_get_access_token() {
    $url = paypal_api_base() . '/v1/oauth2/token';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en_US'
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('PayPal token request failed: ' . $err);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($resp, true);
    if ($httpCode !== 200 || empty($data['access_token'])) {
        throw new Exception('PayPal token fetch error: ' . $resp);
    }

    return $data['access_token'];
}

?>