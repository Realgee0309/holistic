<?php
$pageTitle = 'Contact Us';
$metaDesc  = 'Get in touch with Holistic Wellness via WhatsApp, email, or the contact form. We respond within 24 hours.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
?>
<style>
.contact-layout { display: grid; grid-template-columns: 1fr 1.4fr; gap: 3rem; }
.contact-methods h2 { margin-bottom: 1.5rem; position: relative; padding-bottom: 0.8rem; }
.contact-methods h2::after { content:''; position:absolute; bottom:0; left:0; width:55px; height:3px; background:var(--accent); border-radius:2px; }
.contact-method { display:flex; gap:1.2rem; margin-bottom:2rem; background:white; padding:1.5rem; border-radius:var(--radius); box-shadow:var(--shadow-sm); transition:var(--transition); }
.contact-method:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); }
.method-icon { width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; }
.method-icon.email { background:#e8f4fd; }
.method-icon.whatsapp { background:#e8f8ef; }
.method-icon.hours { background:#fef9ec; }
.method-info h3 { margin-bottom:0.3rem; font-size:1rem; }
.method-info p { font-size:0.88rem; color:#666; margin-bottom:0.3rem; }
.method-info a.link { color:var(--primary); text-decoration:none; font-weight:600; font-size:0.9rem; }
.contact-form-card { background:white; padding:2.5rem; border-radius:var(--radius); box-shadow:var(--shadow-md); }
.contact-form-card h2 { margin-bottom:1.5rem; }
.response-banner { background:var(--secondary); padding:2rem; border-radius:var(--radius); text-align:center; margin-top:2rem; }
@media(max-width:768px){ .contact-layout { grid-template-columns:1fr; } }
</style>

<div class="page-header">
    <div class="container">
        <h1>Get in Touch</h1>
        <p>Have questions or ready to begin your journey? Reach out via any channel below.</p>
    </div>
</div>

<section>
    <div class="container">
        <?php renderFlash(); ?>
        <div class="contact-layout">
            <!-- Left: Contact Methods -->
            <div class="contact-methods">
                <h2>Contact Us</h2>
                <div class="contact-method">
                    <div class="method-icon email">✉️</div>
                    <div class="method-info">
                        <h3>Email</h3>
                        <p>General inquiries:</p>
                        <p><strong>contact@holisticwellness.com</strong></p>
                        <p>Existing clients:</p>
                        <p><strong>support@holisticwellness.com</strong></p>
                        <a href="mailto:contact@holisticwellness.com" class="link">Send Email →</a>
                    </div>
                </div>
                <div class="contact-method">
                    <div class="method-icon whatsapp">📱</div>
                    <div class="method-info">
                        <h3>WhatsApp</h3>
                        <p>Fastest response for booking &amp; inquiries:</p>
                        <p><strong>+254 797 582 384</strong></p>
                        <a href="https://wa.me/254797582384" class="btn btn-whatsapp btn-sm" style="margin-top:0.5rem;display:inline-flex;">
                            <i class="fab fa-whatsapp"></i> Chat Now
                        </a>
                    </div>
                </div>
                <div class="contact-method">
                    <div class="method-icon hours">🕐</div>
                    <div class="method-info">
                        <h3>Operating Hours</h3>
                        <p>Monday – Friday: <strong>9:00 AM – 7:00 PM</strong></p>
                        <p>Saturday: <strong>10:00 AM – 4:00 PM</strong></p>
                        <p>Sunday: <strong>Closed</strong></p>
                    </div>
                </div>
                <div class="response-banner">
                    <h3>⏱ Response Time</h3>
                    <p style="color:#555;margin-top:0.5rem;">We aim to respond to all inquiries <strong>within 24 hours</strong> on business days. For urgent matters, WhatsApp is fastest.</p>
                </div>
            </div>

            <!-- Right: Contact Form -->
            <div class="contact-form-card">
                <h2>Send Us a Message</h2>
                <form method="POST" action="actions/contact.php" id="contactForm" novalidate>
                    <div class="form-group">
                        <label for="name">Your Name <span style="color:red">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required placeholder="Jane Doe" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address <span style="color:red">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" class="form-control">
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Booking Information">Booking Information</option>
                            <option value="Services Question">Services Question</option>
                            <option value="Existing Client Support">Existing Client Support</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message">Your Message <span style="color:red">*</span></label>
                        <textarea id="message" name="message" class="form-control" required placeholder="How can we help you?"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        📨 Send Message
                    </button>
                    <p style="font-size:0.82rem;color:#aaa;text-align:center;margin-top:0.8rem;">🔒 Your message is confidential and secure.</p>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
