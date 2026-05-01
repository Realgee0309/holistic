<?php
$pageTitle = 'Privacy Policy';
$metaDesc  = 'Read the Holistic Wellness Privacy Policy — how we collect, use, and protect your personal information.';
require_once __DIR__ . '/includes/header.php';
?>
<style>
.privacy-content { max-width: 860px; margin: 0 auto; }
.privacy-content h2 { color: var(--primary); margin: 2.5rem 0 1rem; font-size: 1.3rem; }
.privacy-content h3 { color: var(--primary-d); margin: 1.5rem 0 0.5rem; font-size: 1.05rem; }
.privacy-content p, .privacy-content li { color: #555; line-height: 1.9; margin-bottom: 0.8rem; }
.privacy-content ul { padding-left: 1.5rem; margin-bottom: 1rem; }
.privacy-content ul li { margin-bottom: 0.4rem; }
.last-updated { background: var(--secondary); padding: 0.8rem 1.2rem; border-radius: 8px; font-size: 0.88rem; color: #666; margin-bottom: 2rem; }
.highlight-box { background: #fff8f0; border-left: 4px solid var(--accent); padding: 1.2rem 1.5rem; border-radius: 0 8px 8px 0; margin: 1.5rem 0; }
</style>

<div class="page-header">
    <div class="container">
        <h1>Privacy Policy</h1>
        <p>Your privacy is fundamental to the trust we build with you.</p>
    </div>
</div>

<section>
    <div class="container">
        <div class="privacy-content">
            <div class="last-updated">📅 Last Updated: <?= date('F j, Y') ?></div>

            <div class="highlight-box">
                <strong>Summary:</strong> We collect only what's necessary to provide therapy services, never sell your data, and protect everything with industry-standard security. Your health information is treated with the utmost confidentiality.
            </div>

            <h2>1. Introduction</h2>
            <p>Holistic Wellness ("we," "us," or "our") is committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or use our therapy services.</p>

            <h2>2. Information We Collect</h2>
            <h3>Information You Provide Directly</h3>
            <ul>
                <li>Contact information (name, email address, phone number)</li>
                <li>Booking details (preferred date, time, service type)</li>
                <li>Messages and communications sent via contact forms or WhatsApp</li>
                <li>Payment information (processed securely through third-party providers)</li>
            </ul>
            <h3>Information Collected Automatically</h3>
            <ul>
                <li>IP address and browser type (for security and analytics)</li>
                <li>Pages visited and time spent on site</li>
                <li>Referring website (how you found us)</li>
            </ul>

            <h2>3. How We Use Your Information</h2>
            <ul>
                <li>To provide and manage therapy services</li>
                <li>To confirm bookings and send appointment reminders</li>
                <li>To respond to inquiries and provide support</li>
                <li>To improve our website and services</li>
                <li>To comply with legal obligations</li>
            </ul>

            <h2>4. Confidentiality of Health Information</h2>
            <p>All information shared during therapy sessions is strictly confidential. We will not disclose your health information to third parties without your written consent, except in legally mandated situations including:</p>
            <ul>
                <li>Immediate risk of harm to yourself or others</li>
                <li>Suspected child or elder abuse (as required by law)</li>
                <li>Court order or legal subpoena</li>
            </ul>

            <h2>5. Data Security</h2>
            <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. Video sessions use end-to-end encrypted platforms compliant with applicable privacy standards.</p>

            <h2>6. Third-Party Services</h2>
            <p>We may use third-party services including:</p>
            <ul>
                <li><strong>Zoom</strong> — for secure video sessions</li>
                <li><strong>WhatsApp</strong> — for booking and communication</li>
                <li><strong>Payment processors</strong> — for secure transactions</li>
            </ul>
            <p>These services have their own privacy policies. We encourage you to review them.</p>

            <h2>7. Your Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access the personal information we hold about you</li>
                <li>Request correction of inaccurate data</li>
                <li>Request deletion of your data (subject to legal retention requirements)</li>
                <li>Withdraw consent for non-essential processing</li>
            </ul>

            <h2>8. Contact Us</h2>
            <p>For any privacy-related questions or to exercise your rights, please contact us:</p>
            <ul>
                <li><strong>Email:</strong> privacy@holisticwellness.com</li>
                <li><strong>WhatsApp:</strong> +254 797 582 384</li>
            </ul>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
