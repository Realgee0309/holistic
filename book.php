<?php
$pageTitle = 'Book a Session';
$metaDesc  = 'Book your online therapy session with Holistic Wellness. Fill in the form and we will confirm via WhatsApp within 24 hours.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
// Set min date to today
$minDate = date('Y-m-d');
?>
<style>
.booking-layout { display: grid; grid-template-columns: 1fr 1.3fr; gap: 3rem; align-items: start; }
.booking-info h2 { margin-bottom: 1.5rem; position: relative; padding-bottom: 0.8rem; }
.booking-info h2::after { content:''; position:absolute; bottom:0; left:0; width:55px; height:3px; background:var(--accent); border-radius:2px; }
.booking-info p { color:#666; margin-bottom:1.2rem; line-height:1.8; }
.step { display:flex; align-items:flex-start; gap:1rem; margin-bottom:1.5rem; }
.step-number { background:var(--accent); color:white; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; flex-shrink:0; font-size:0.95rem; }
.step-content h3 { margin-bottom:0.3rem; font-size:1rem; }
.step-content p { font-size:0.9rem; color:#666; margin:0; }
.booking-form-card { background:white; padding:2.5rem; border-radius:var(--radius); box-shadow:var(--shadow-md); }
.booking-form-card h3 { margin-bottom:1.5rem; color:var(--primary); font-size:1.3rem; }
.form-note { font-size:0.82rem; color:#888; margin-top:0.3rem; }
.availability-section { background:var(--secondary); }
.hours-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1.5rem; margin-top:2rem; }
.hours-card { background:white; padding:1.5rem; border-radius:var(--radius); box-shadow:var(--shadow-sm); }
.hours-card h3 { margin-bottom:1rem; font-size:1rem; }
.hours-list { list-style:none; }
.hours-list li { display:flex; justify-content:space-between; padding:0.35rem 0; font-size:0.9rem; border-bottom:1px solid #f0f0f0; }
.hours-list li:last-child { border:none; }
@media(max-width:768px) { .booking-layout { grid-template-columns:1fr; } }
</style>

<div class="page-header">
    <div class="container">
        <h1>Book Your Session</h1>
        <p>Take the first step toward positive change today.</p>
    </div>
</div>

<section>
    <div class="container">
        <?php renderFlash(); ?>
        <div class="booking-layout">
            <!-- Info Side -->
            <div class="booking-info">
                <h2>How Our Booking Process Works</h2>
                <p>We've designed a simple, seamless booking process that gets you connected with the support you need without unnecessary complications.</p>
                <div style="margin:2rem 0;">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Fill Out the Form</h3>
                            <p>Complete the booking request form with your details and preferred session time.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Submit &amp; Save</h3>
                            <p>Your request is saved to our system and you'll also have the option to send via WhatsApp for the fastest confirmation.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Confirmation &amp; Details</h3>
                            <p>We'll respond promptly to confirm your session and provide the secure video link for your appointment.</p>
                        </div>
                    </div>
                </div>
                <p>Your privacy and comfort are our priorities. All sessions are conducted through secure, encrypted video platforms accessible from any device.</p>
                <div style="margin-top:1.5rem;padding:1.2rem;background:var(--secondary);border-radius:var(--radius);">
                    <strong>📞 WhatsApp:</strong> +254 797 582 384<br>
                    <strong>📧 Email:</strong> contact@holisticwellness.com
                </div>
            </div>

            <!-- Form Side -->
            <div class="booking-form-card">
                <h3>📅 Request Your Session</h3>
                <form method="POST" action="actions/book.php" id="bookingForm" novalidate>
                    <div class="form-group">
                        <label for="name">Full Name <span style="color:red">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. Peter Kamau Wafula " value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address <span style="color:red">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="service">Service Type <span style="color:red">*</span></label>
                        <select id="service" name="service" class="form-control" required>
                            <option value="">Select a service...</option>
                            <option value="Individual Therapy">Individual Therapy</option>
                            <option value="Couples Therapy">Couples Therapy</option>
                            <option value="Anxiety &amp; Depression">Anxiety &amp; Depression Support</option>
                            <option value="Life Coaching">Life Coaching</option>
                            <option value="Initial Consultation">Initial Consultation (Free)</option>
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label for="date">Preferred Date <span style="color:red">*</span></label>
                            <input type="date" id="date" name="date" class="form-control" required min="<?= $minDate ?>">
                        </div>
                        <div class="form-group">
                            <label for="time">Preferred Time <span style="color:red">*</span></label>
                            <select id="time" name="time" class="form-control" required>
                                <option value="">Select time...</option>
                                <option value="Morning (9am-12pm)">Morning (9am–12pm)</option>
                                <option value="Afternoon (1pm-5pm)">Afternoon (1pm–5pm)</option>
                                <option value="Evening (6pm-9pm)">Evening (6pm–9pm)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="message">Additional Information <span style="color:#aaa;font-weight:400">(optional)</span></label>
                        <textarea id="message" name="message" class="form-control" placeholder="Share any specific concerns, questions, or anything you'd like us to know before your session..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    <!-- Submit to DB -->
                    <button type="submit" class="btn btn-primary btn-full" style="margin-bottom:0.8rem;">
                        📨 Submit Booking Request
                    </button>
                    <!-- WhatsApp shortcut (JS only, doesn't save to DB) -->
                    <button type="button" id="whatsappBtn" class="btn btn-whatsapp btn-full">
                        <i class="fab fa-whatsapp"></i> Also Send via WhatsApp
                    </button>
                    <p class="form-note" style="text-align:center;margin-top:0.8rem;">🔒 Your information is secure and confidential.</p>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Availability -->
<section class="availability-section">
    <div class="container" style="text-align:center;">
        <h2 class="section-title">Our Availability</h2>
        <p style="color:#555;">Flexible scheduling to fit your lifestyle, with weekday and weekend options.</p>
        <div class="hours-grid">
            <div class="hours-card">
                <h3>🗓 Weekdays</h3>
                <ul class="hours-list">
                    <li><span>Monday</span><span>9am–9pm</span></li>
                    <li><span>Tuesday</span><span>9am–9pm</span></li>
                    <li><span>Wednesday</span><span>9am–9pm</span></li>
                    <li><span>Thursday</span><span>9am–9pm</span></li>
                    <li><span>Friday</span><span>9am–6pm</span></li>
                </ul>
            </div>
            <div class="hours-card">
                <h3>🌅 Weekends</h3>
                <ul class="hours-list">
                    <li><span>Saturday</span><span>10am–4pm</span></li>
                    <li><span>Sunday</span><span>Closed</span></li>
                </ul>
            </div>
            <div class="hours-card">
                <h3>🌍 Time Zones</h3>
                <p style="font-size:0.92rem;color:#666;margin-top:0.5rem;">All times are East Africa Time (EAT, UTC+3). We accommodate other time zones upon request — just mention it in your booking.</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Accordion -->
<section>
    <div class="container" style="max-width:800px;">
        <h2 class="section-title">Booking FAQs</h2>
        <?php
        $faqs = [
            ['q'=>'How long does each session last?','a'=>'Standard therapy sessions are 50 minutes. Initial consultations are 30 minutes and are offered free of charge to help determine if we\'re the right fit.'],
            ['q'=>'What if I need to reschedule?','a'=>'You can reschedule with at least 24 hours\' notice at no fee. Last-minute cancellations (under 24 hours) may incur a 50% cancellation fee.'],
            ['q'=>'How do I pay for sessions?','a'=>'Payment details are provided after confirmation. We accept M-Pesa, bank transfers, major credit cards, and PayPal. Payment is due 24 hours before your session.'],
            ['q'=>'What platform do you use for video sessions?','a'=>'We primarily use Zoom. The secure link is sent after booking confirmation. Google Meet is available upon request.'],
        ];
        foreach ($faqs as $faq): ?>
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
</section>

<script>
document.getElementById('whatsappBtn').addEventListener('click', function() {
    const name    = document.getElementById('name').value || '';
    const email   = document.getElementById('email').value || '';
    const service = document.getElementById('service').value || '';
    const date    = document.getElementById('date').value || '';
    const time    = document.getElementById('time').value || '';
    const message = document.getElementById('message').value || '';
    if (!name || !service || !date || !time) {
        alert('Please fill in at least Name, Service, Date, and Time before sending via WhatsApp.');
        return;
    }
    const text = `*New Booking Request*\nName: ${name}\nEmail: ${email}\nService: ${service}\nDate: ${date}\nTime: ${time}${message ? '\nNote: ' + message : ''}`;
    window.open('https://wa.me/254797582384?text=' + encodeURIComponent(text), '_blank');
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
