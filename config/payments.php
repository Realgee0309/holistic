<?php
/**
 * Payment Configuration and Processing
 */

// M-Pesa Configuration (Sandbox)
define('MPESA_CONSUMER_KEY', 'your_consumer_key_here');
define('MPESA_CONSUMER_SECRET', 'your_consumer_secret_here');
define('MPESA_SHORTCODE', '174379'); // Sandbox shortcode
define('MPESA_PASSKEY', 'your_passkey_here');
define('MPESA_ENVIRONMENT', 'sandbox'); // Change to 'production' for live

// Stripe Configuration
define('STRIPE_PUBLISHABLE_KEY', 'your_stripe_publishable_key_here');
define('STRIPE_SECRET_KEY', 'your_stripe_secret_key_here');
define('STRIPE_WEBHOOK_SECRET', 'your_stripe_webhook_secret_here');

// PayPal Configuration
define('PAYPAL_CLIENT_ID', 'your_paypal_client_id_here');
define('PAYPAL_CLIENT_SECRET', 'your_paypal_client_secret_here');
define('PAYPAL_ENVIRONMENT', 'sandbox'); // Change to 'production' for live
define('PAYPAL_RETURN_URL', 'https://yourdomain.com/payments/paypal_return.php');
define('PAYPAL_CANCEL_URL', 'https://yourdomain.com/calendar.php?payment_cancelled=1');
define('PAYPAL_WEBHOOK_ID', 'your_paypal_webhook_id_here');

define('PAYPAL_WEBHOOK_URL', 'https://yourdomain.com/payments/paypal_webhook.php');
define('STRIPE_WEBHOOK_URL', 'https://yourdomain.com/payments/stripe_webhook.php');

// Bank Transfer Details
define('BANK_DETAILS', [
    'bank_name' => 'KCB Bank Kenya',
    'account_name' => 'Holistic Wellness Ltd',
    'account_number' => '1234567890',
    'branch' => 'Westlands Branch',
    'swift_code' => 'KCBLKENX'
]);

// Service Pricing (in KES)
define('PRICING', [
    'Individual Therapy' => 3500,
    'Couples Therapy' => 5000,
    'Anxiety & Depression' => 3500,
    'Life Coaching' => 4000,
    'Initial Consultation' => 0,
    'Monthly Package' => 12000,
    'Couples Package' => 18000
]);

/**
 * Get service price
 */
function getServicePrice(string $service): int {
    return PRICING[$service] ?? 0;
}

/**
 * Initialize M-Pesa STK Push
 */
function initiateMpesaPayment(int $bookingId, string $phone, float $amount): array {
    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

    $payload = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => MPESA_SHORTCODE,
        'PhoneNumber' => $phone,
        'CallBackURL' => 'https://yourdomain.com/payments/mpesa_callback.php',
        'AccountReference' => 'HOLISTIC-' . $bookingId,
        'TransactionDesc' => 'Therapy Session Payment'
    ];

    $ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . getMpesaAccessToken(),
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

/**
 * Get M-Pesa Access Token
 */
function getMpesaAccessToken(): string {
    $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);

    $ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $credentials
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? '';
}

/**
 * Process Stripe Payment
 */
function processStripePayment(float $amount, string $token, string $description = ''): array {
    require_once 'vendor/autoload.php'; // If using Composer

    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    try {
        $charge = \Stripe\Charge::create([
            'amount' => $amount * 100, // Convert to cents
            'currency' => 'kes',
            'source' => $token,
            'description' => $description
        ]);

        return [
            'success' => true,
            'transaction_id' => $charge->id,
            'status' => $charge->status
        ];
    } catch (\Stripe\Exception\CardException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Record payment in database
 */
function recordPayment(int $bookingId, float $amount, string $method, string $status = 'pending',
                      string $transactionId = null, string $reference = null): int {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        INSERT INTO payments (booking_id, amount, method, status, transaction_id, reference)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([$bookingId, $amount, $method, $status, $transactionId, $reference]);
    return $pdo->lastInsertId();
}

/**
 * Get bank transfer details
 */
function getBankDetails(): array {
    return BANK_DETAILS;
}
