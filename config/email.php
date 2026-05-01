<?php
/**
 * Email Configuration and Sending
 */

// Email settings
define('SMTP_HOST', 'smtp.gmail.com'); // Change to your SMTP host
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Change to your email
define('SMTP_PASSWORD', 'your-app-password'); // Use app password for Gmail
define('FROM_EMAIL', 'noreply@holisticwellness.com');
define('FROM_NAME', 'Holistic Wellness');

/**
 * Send an email using PHP's built-in mail function (basic)
 * For production, consider using PHPMailer or similar
 */
function sendEmail(string $to, string $subject, string $message, string $type = 'general'): bool {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . FROM_EMAIL,
        'X-Mailer: PHP/' . phpversion()
    ];

    $htmlMessage = getEmailTemplate($subject, $message, $type);

    // Log the email attempt
    logEmail($to, $subject, $type);

    // For development, just log and return true
    // In production, use proper SMTP
    error_log("Email to $to: $subject");

    return true; // Assume success for demo
}

/**
 * Get HTML email template
 */
function getEmailTemplate(string $subject, string $content, string $type): string {
    $template = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($subject) . '</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #5a7d7c 0%, #3b5a57 100%); color: white; padding: 30px 20px; text-align: center; }
            .content { padding: 30px 20px; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .btn { display: inline-block; background: #5a7d7c; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            .highlight { background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #5a7d7c; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🌿 Holistic Wellness</h1>
                <p>Professional Online Therapy</p>
            </div>
            <div class="content">
                ' . $content . '
            </div>
            <div class="footer">
                <p>This email was sent to you because you have an account with Holistic Wellness.</p>
                <p>If you have any questions, please contact us at contact@holisticwellness.com</p>
                <p>&copy; ' . date('Y') . ' Holistic Wellness. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';

    return $template;
}

/**
 * Send booking confirmation email
 */
function sendBookingConfirmationEmail(array $booking): void {
    $subject = 'Booking Confirmation - Holistic Wellness';

    $content = '
        <h2>✅ Your Booking is Confirmed!</h2>
        <div class="highlight">
            <h3>Booking Details:</h3>
            <p><strong>Service:</strong> ' . htmlspecialchars($booking['service']) . '</p>
            <p><strong>Date:</strong> ' . date('l, F j, Y', strtotime($booking['preferred_date'])) . '</p>
            <p><strong>Time:</strong> ' . htmlspecialchars($booking['preferred_time']) . '</p>
            <p><strong>Booking ID:</strong> #' . $booking['id'] . '</p>
        </div>
        <p>Thank you for choosing Holistic Wellness. Your session has been scheduled and you will receive a confirmation call or WhatsApp message within 24 hours with the secure video link.</p>
        <p>If you need to reschedule or have any questions, please contact us:</p>
        <ul>
            <li>📞 WhatsApp: +254 797 582 384</li>
            <li>📧 Email: contact@holisticwellness.com</li>
        </ul>
        <p>We look forward to supporting your mental wellness journey.</p>
        <p>Warm regards,<br>Dr. Jerald<br>Holistic Wellness Team</p>
    ';

    sendEmail($booking['email'], $subject, $content, 'booking_confirmation');
}

/**
 * Send payment confirmation email
 */
function sendPaymentConfirmationEmail(array $booking, array $payment): void {
    $subject = 'Payment Confirmed - Holistic Wellness';

    $content = '
        <h2>💳 Payment Received Successfully!</h2>
        <div class="highlight">
            <h3>Payment Details:</h3>
            <p><strong>Amount:</strong> KES ' . number_format($payment['amount'], 0) . '</p>
            <p><strong>Method:</strong> ' . ucfirst($payment['method']) . '</p>
            <p><strong>Service:</strong> ' . htmlspecialchars($booking['service']) . '</p>
            <p><strong>Transaction ID:</strong> ' . htmlspecialchars($payment['transaction_id'] ?? 'N/A') . '</p>
        </div>
        <p>Your payment has been processed successfully. Your session is now fully confirmed.</p>
        <p>You will receive the video call link 24 hours before your session.</p>
        <p>If you have any questions about your payment or booking, please don\'t hesitate to contact us.</p>
        <p>Best regards,<br>Holistic Wellness Team</p>
    ';

    sendEmail($booking['email'], $subject, $content, 'payment_confirmation');
}

/**
 * Send session reminder email
 */
function sendSessionReminderEmail(array $booking): void {
    $subject = 'Session Reminder - Tomorrow at Holistic Wellness';

    $content = '
        <h2>🔔 Session Reminder</h2>
        <div class="highlight">
            <h3>Your session is tomorrow:</h3>
            <p><strong>Service:</strong> ' . htmlspecialchars($booking['service']) . '</p>
            <p><strong>Date & Time:</strong> ' . date('l, F j, Y', strtotime($booking['preferred_date'])) . ' at ' . htmlspecialchars($booking['preferred_time']) . '</p>
        </div>
        <p>This is a friendly reminder about your upcoming therapy session. Please ensure you have:</p>
        <ul>
            <li>✅ A stable internet connection</li>
            <li>✅ A quiet, private space</li>
            <li>✅ Your preferred video calling app ready</li>
        </ul>
        <p>You will receive the video call link 2 hours before your session.</p>
        <p>If you need to reschedule, please contact us at least 24 hours in advance.</p>
        <p>We look forward to seeing you tomorrow!</p>
        <p>Warm regards,<br>Dr. Jerald</p>
    ';

    sendEmail($booking['email'], $subject, $content, 'reminder');
}

/**
 * Log email sending
 */
function logEmail(string $recipient, string $subject, string $type): void {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO email_logs (recipient, subject, type) VALUES (?, ?, ?)");
        $stmt->execute([$recipient, $subject, $type]);
    } catch (Exception $e) {
        error_log('Email logging failed: ' . $e->getMessage());
    }
}

/**
 * Send automated reminders (to be called by cron job)
 */
function sendAutomatedReminders(): void {
    $pdo = getDB();

    // Get bookings for tomorrow
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $stmt = $pdo->prepare("
        SELECT b.* FROM bookings b
        LEFT JOIN email_logs el ON el.recipient = b.email AND el.type = 'reminder' AND DATE(el.sent_at) = CURDATE()
        WHERE b.preferred_date = ? AND b.status = 'confirmed' AND el.id IS NULL
    ");
    $stmt->execute([$tomorrow]);

    $remindersSent = 0;
    while ($booking = $stmt->fetch(PDO::FETCH_ASSOC)) {
        sendSessionReminderEmail($booking);
        $remindersSent++;
    }

    if ($remindersSent > 0) {
        error_log("Sent $remindersSent session reminders");
    }
}