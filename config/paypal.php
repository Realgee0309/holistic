<?php
/**
 * PayPal Payment Processing
 */

require_once __DIR__ . '/../config/db.php';

function getPayPalApiBase(): string {
    return PAYPAL_ENVIRONMENT === 'production'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';
}

function getPayPalAccessToken(): string {
    $credentials = base64_encode(PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
    $ch = curl_init(getPayPalApiBase() . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $credentials,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return $data['access_token'] ?? '';
}

function createPayPalPayment(float $amount, string $description, int $bookingId): array {
    $token = getPayPalAccessToken();
    if (!$token) {
        return ['success' => false, 'message' => 'Unable to authenticate with PayPal'];
    }

    $body = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => 'BOOKING-' . $bookingId,
            'custom_id' => (string)$bookingId,
            'description' => $description,
            'amount' => [
                'currency_code' => 'KES',
                'value' => number_format($amount, 2, '.', '')
            ]
        ]],
        'application_context' => [
            'return_url' => PAYPAL_RETURN_URL,
            'cancel_url' => PAYPAL_CANCEL_URL,
            'brand_name' => 'Holistic Wellness',
            'landing_page' => 'LOGIN',
            'user_action' => 'PAY_NOW'
        ]
    ];

    $ch = curl_init(getPayPalApiBase() . '/v2/checkout/orders');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);

    if (isset($data['id']) && isset($data['links'])) {
        $approvalLink = '';
        foreach ($data['links'] as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                $approvalLink = $link['href'];
                break;
            }
        }
        return [
            'success' => true,
            'order_id' => $data['id'],
            'approval_url' => $approvalLink ?: PAYPAL_RETURN_URL . '?order_id=' . urlencode($data['id']) . '&booking_id=' . intval($bookingId)
        ];
    }

    return [
        'success' => false,
        'message' => $data['message'] ?? 'Failed to create PayPal order'
    ];
}

function verifyPayPalPayment(string $orderId): array {
    $token = getPayPalAccessToken();
    if (!$token) {
        return ['success' => false, 'message' => 'Unable to authenticate with PayPal'];
    }

    $ch = curl_init(getPayPalApiBase() . '/v2/checkout/orders/' . urlencode($orderId));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);

    if (empty($data['status'])) {
        return ['success' => false, 'message' => 'Unable to verify PayPal order'];
    }

    if ($data['status'] === 'APPROVED') {
        $ch = curl_init(getPayPalApiBase() . '/v2/checkout/orders/' . urlencode($orderId) . '/capture');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $captureResponse = curl_exec($ch);
        curl_close($ch);
        $captureData = json_decode($captureResponse, true);

        $captureId = $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
        if ($captureId) {
            return ['success' => true, 'status' => 'completed', 'transaction_id' => $captureId];
        }
    }

    if ($data['status'] === 'COMPLETED') {
        $captureId = $data['purchase_units'][0]['payments']['captures'][0]['id'] ?? ($data['id'] ?? '');
        return ['success' => true, 'status' => 'completed', 'transaction_id' => $captureId];
    }

    return ['success' => false, 'status' => $data['status'], 'message' => 'Order not completed'];
}

function getPayPalHeader(string $name): ?string {
    $headers = getallheaders();
    $nameLower = strtolower($name);
    foreach ($headers as $key => $value) {
        if (strtolower($key) === $nameLower) {
            return $value;
        }
    }
    return null;
}

function verifyPayPalWebhook(array $headers, string $body): bool {
    $token = getPayPalAccessToken();
    if (!$token) {
        return false;
    }

    $payload = [
        'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'] ?? $headers['Paypal-Transmission-Id'] ?? '',
        'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? $headers['Paypal-Transmission-Time'] ?? '',
        'cert_url' => $headers['PAYPAL-CERT-URL'] ?? $headers['Paypal-Cert-Url'] ?? '',
        'auth_algo' => $headers['PAYPAL-AUTH-ALGO'] ?? $headers['Paypal-Auth-Algo'] ?? '',
        'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? $headers['Paypal-Transmission-Sig'] ?? '',
        'webhook_id' => PAYPAL_WEBHOOK_ID,
        'webhook_event' => json_decode($body, true)
    ];

    $ch = curl_init(getPayPalApiBase() . '/v1/notifications/verify-webhook-signature');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);

    return isset($data['verification_status']) && $data['verification_status'] === 'SUCCESS';
}
?>
