<?php
$pageTitle = 'FAQ';
$metaDesc  = 'Frequently asked questions about online therapy at Holistic Wellness — sessions, privacy, payments, and more.';
require_once __DIR__ . '/includes/header.php';
?>
<style>
.faq-intro { max-width: 720px; margin: 0 auto 3rem; text-align: center; color: #666; font-size: 1.05rem; }
.faq-category { margin-bottom: 3rem; }
.faq-category-title { font-size: 1.15rem; font-weight: 700; color: var(--primary); margin-bottom: 1.2rem; padding-left: 0.8rem; border-left: 4px solid var(--accent); }
</style>

<div class="page-header">
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <p>Everything you need to know about online therapy with Holistic Wellness.</p>
    </div>
</div>

<section>
    <div class="container" style="max-width: 850px;">
        <p class="faq-intro">Can't find the answer you're looking for? <a href="contact.php" style="color:var(--primary);font-weight:600;">Contact us directly</a> — we're happy to help.</p>

        <?php
        $categories = [
            'Getting Started' => [
                ['q'=>'What is online therapy?','a'=>'Online therapy (also called teletherapy or e-therapy) is a form of mental health counseling conducted via video call, phone, or secure messaging. It offers the same evidence-based treatments as in-person therapy, with the added convenience of attending sessions from your own space.'],
                ['q'=>'How do I know if online therapy is right for me?','a'=>'Online therapy is effective for a wide range of concerns including anxiety, depression, relationship issues, stress, and personal growth. If you\'re unsure, our free 30-minute initial consultation can help you decide if it\'s a good fit.'],
                ['q'=>'How do I book my first session?','a'=>'Simply fill out the booking form on our Book page, or send us a WhatsApp message at +254 797 582 384. We\'ll confirm your appointment within 24 hours.'],
            ],
            'Sessions & Format' => [
                ['q'=>'How long does each session last?','a'=>'Standard individual sessions are 50 minutes. Couples sessions are 60 minutes. Initial consultations are 30 minutes and completely free.'],
                ['q'=>'What platform do you use for video sessions?','a'=>'We primarily use Zoom for secure, encrypted video sessions. We can also accommodate Google Meet upon request. You\'ll receive a link after booking confirmation.'],
                ['q'=>'Do I need to download any special software?','a'=>'You\'ll need Zoom installed on your device (free download at zoom.us). A smartphone, tablet, or computer with a working camera and microphone is sufficient.'],
                ['q'=>'What if I have a poor internet connection?','a'=>'We can switch to phone audio if your video connection is unstable. We recommend a stable Wi-Fi connection for the best experience, but we\'re flexible.'],
            ],
            'Privacy & Confidentiality' => [
                ['q'=>'Is online therapy confidential?','a'=>'Yes. All sessions are protected by the same confidentiality laws as in-person therapy. We use HIPAA-compliant, encrypted platforms and your information is never shared without your consent, except where legally required (e.g., imminent safety risks).'],
                ['q'=>'What happens to my personal data?','a'=>'Your data is stored securely and used solely for providing your therapy services. We never sell or share personal information. Please review our Privacy Policy for full details.'],
            ],
            'Payments & Cancellations' => [
                ['q'=>'How much does therapy cost?','a'=>'Individual sessions start from KES 3,500 per session. Couples sessions from KES 5,000. We offer monthly packages at discounted rates. An initial 30-minute consultation is free.'],
                ['q'=>'What payment methods do you accept?','a'=>'We accept M-Pesa, bank transfers, major credit cards (Visa, Mastercard), and PayPal. Payment is due 24 hours before your scheduled session.'],
                ['q'=>'What is your cancellation policy?','a'=>'You may reschedule or cancel with at least 24 hours\' notice at no charge. Cancellations with less than 24 hours\' notice may incur a 50% fee to compensate for the reserved time.'],
                ['q'=>'Do you offer sliding scale fees?','a'=>'We believe mental health support should be accessible. Please contact us to discuss your situation — we do our best to accommodate where possible.'],
            ],
        ];

        foreach ($categories as $cat => $faqs): ?>
        <div class="faq-category">
            <div class="faq-category-title"><?= htmlspecialchars($cat) ?></div>
            <?php foreach ($faqs as $faq): ?>
            <div class="accordion">
                <div class="accordion-header">
                    <?= htmlspecialchars($faq['q']) ?>
                    <span class="toggle-icon">+</span>
                </div>
                <div class="accordion-content">
                    <p><?= htmlspecialchars($faq['a']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <div style="background:var(--secondary);border-radius:var(--radius);padding:2rem;text-align:center;margin-top:2rem;">
            <h3 style="margin-bottom:0.8rem;">Still have questions?</h3>
            <p style="color:#555;margin-bottom:1.5rem;">We're here to help. Reach out via WhatsApp for the fastest response.</p>
            <a href="https://wa.me/254797582384" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
            &nbsp;
            <a href="contact.php" class="btn btn-primary">Contact Us</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
