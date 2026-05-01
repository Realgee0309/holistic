<?php
$pageTitle = 'Home';
$metaDesc  = 'Holistic Wellness — Professional online therapy for individuals, couples, and families. Book your session today.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/includes/header.php';
$heroImg  = getImageSetting('img_hero');
$heroHead = getSetting('hero_headline', 'Your Journey to Wellbeing Begins Here');
$heroSub  = getSetting('hero_subtext', 'Professional online therapy that fits your schedule, from the comfort of your own space.');
?>

<style>
/* ── Hero ── */
.hero {
    background: var(--secondary);
    background-image: linear-gradient(135deg, rgba(191,205,192,0.9) 0%, rgba(90,125,124,0.15) 100%);
    padding: 9rem 0 7rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%235a7d7c' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.hero-content {
    max-width: 760px; margin: 0 auto; padding: 3rem;
    background: rgba(255,255,255,0.88);
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(90,125,124,0.15);
    backdrop-filter: blur(10px);
    position: relative;
}
.hero-badge {
    display: inline-block;
    background: var(--primary); color: white;
    font-size: 0.82rem; font-weight: 600;
    padding: 0.3rem 1rem; border-radius: 50px;
    margin-bottom: 1.2rem; letter-spacing: 1px;
    text-transform: uppercase;
}
.hero h1 { font-size: 2.8rem; margin-bottom: 1rem; }
.hero h1 span { color: var(--accent); }
.hero p { font-size: 1.15rem; color: #555; margin-bottom: 2rem; max-width: 560px; margin-left: auto; margin-right: auto; }
.hero-cta { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }

/* ── Stats Bar ── */
.stats-bar { background: var(--gradient); padding: 2.5rem 0; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; text-align: center; }
.stat-item { color: white; }
.stat-number { font-size: 2.2rem; font-weight: 700; font-family: 'Playfair Display', serif; color: var(--accent); }
.stat-label { font-size: 0.88rem; opacity: 0.85; margin-top: 0.2rem; }

/* ── Services ── */
.services-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2.5rem; }
.service-card {
    background: white; border-radius: var(--radius);
    overflow: hidden; box-shadow: var(--shadow-sm);
    transition: var(--transition);
    display: flex; flex-direction: column;
}
.service-card:hover { transform: translateY(-7px); box-shadow: var(--shadow-lg); }
.service-thumb {
    height: 160px; display: flex; align-items: center;
    justify-content: center; font-size: 3.5rem;
    background: linear-gradient(135deg, var(--secondary), var(--primary));
    flex-shrink: 0;
}
.service-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.service-card .card-content {
    padding: 1.5rem; flex: 1;
    display: flex; flex-direction: column;
}
.service-card .card-content h3 { margin-bottom: 0.7rem; }
.service-card .card-content p { font-size: 0.93rem; color: #666; margin-bottom: 1.2rem; flex: 1; }
.service-card .card-content .btn { align-self: flex-start; }

/* ── Why Us ── */
.why-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.8rem; margin-top: 2.5rem; }
.why-card {
    background: white; padding: 2rem; border-radius: var(--radius);
    box-shadow: var(--shadow-sm); text-align: center;
    transition: var(--transition); display: flex;
    flex-direction: column; align-items: center;
}
.why-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
.why-icon {
    width: 60px; height: 60px; border-radius: 16px;
    background: linear-gradient(135deg, var(--secondary), rgba(90,125,124,0.15));
    display: flex; align-items: center; justify-content: center;
    font-size: 1.7rem; margin-bottom: 1.1rem; flex-shrink: 0;
}
.why-card h3 { margin-bottom: 0.6rem; font-size: 1.05rem; }
.why-card p { font-size: 0.9rem; color: #666; }

/* ── Testimonials ── */
.testimonials { background: var(--secondary); }
.testimonial-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 2rem; margin-top: 2.5rem; }
.testimonial-card { background: white; padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); position: relative; }
.testimonial-card::before { content: '"'; font-family: 'Playfair Display', serif; font-size: 4rem; color: var(--accent); opacity: 0.3; position: absolute; top: 0.5rem; left: 1.2rem; line-height: 1; }
.testimonial-text { font-style: italic; color: #555; margin-bottom: 1.2rem; padding-top: 1rem; line-height: 1.8; }
.testimonial-author { font-weight: 700; color: var(--primary); }
.stars { color: var(--accent); font-size: 0.85rem; margin-bottom: 0.5rem; }

/* ── CTA ── */
.cta { background: var(--gradient); text-align: center; }
.cta-content { max-width: 680px; margin: 0 auto; color: white; }
.cta h2 { color: white; margin-bottom: 1rem; }
.cta p { opacity: 0.9; font-size: 1.1rem; margin-bottom: 2rem; }

/* ── About intro ── */
.about-intro-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 3rem; align-items: center; margin-top: 2rem;
}
.about-intro-img {
    border-radius: 14px; overflow: hidden;
    box-shadow: var(--shadow-md);
    background: var(--gradient);
    height: 320px; display: flex; align-items: center;
    justify-content: center; font-size: 6rem;
}
.about-intro-img img { width: 100%; height: 100%; object-fit: cover; }
@media(max-width:768px) {
    .about-intro-grid { grid-template-columns: 1fr; }
    .about-intro-img { height: 220px; }
    .hero h1 { font-size: 2rem; }
    .hero-content { padding: 2rem 1.5rem; }
}
</style>

<!-- Hero -->
<?php
$heroStyle = '';
if ($heroImg) {
    $heroStyle = 'style="background: linear-gradient(rgba(30,42,53,0.55),rgba(30,42,53,0.55)), url(\'' . htmlspecialchars($heroImg, ENT_QUOTES) . '\') center/cover no-repeat;"';
}
// Safely highlight the word Wellbeing without double-escaping
$heroHeadSafe = htmlspecialchars($heroHead);
$heroHeadSafe = preg_replace('/\bWellbeing\b/', '<span>Wellbeing</span>', $heroHeadSafe);
?>
<section class="hero" <?= $heroStyle ?>>
    <div class="container">
        <div class="hero-content">
            <span class="hero-badge">🌿 Online Therapy</span>
            <h1><?= $heroHeadSafe ?></h1>
            <p><?= htmlspecialchars($heroSub) ?></p>
            <div class="hero-cta">
                <a href="book.php" class="btn btn-whatsapp">
                    <i class="fab fa-whatsapp"></i> Book via WhatsApp
                </a>
                <a href="services.php" class="btn btn-primary">Explore Services</a>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">500+</div>
                <div class="stat-label">Clients Helped</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">5+</div>
                <div class="stat-label">Years Experience</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">98%</div>
                <div class="stat-label">Satisfaction Rate</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">24h</div>
                <div class="stat-label">Response Time</div>
            </div>
        </div>
    </div>
</section>

<!-- About Intro -->
<?php $therapistPhoto = getImageSetting('img_about_photo'); ?>
<section>
    <div class="container">
        <h2 class="section-title">Welcome to Holistic Wellness</h2>
        <div class="about-intro-grid">
            <div class="about-intro-img">
                <?php if ($therapistPhoto): ?>
                <img src="<?= htmlspecialchars($therapistPhoto) ?>" alt="Therapist">
                <?php else: ?>
                🧑‍⚕️
                <?php endif; ?>
            </div>
            <div>
                <p style="font-size:1.08rem;color:#555;margin-bottom:1.2rem;line-height:1.8;">At Holistic Wellness, we believe in a comprehensive approach to mental wellbeing that addresses the mind, body, and spirit. Our fully online therapy practice makes professional counseling accessible to everyone, regardless of location or schedule constraints.</p>
                <p style="font-size:1.08rem;color:#555;margin-bottom:2rem;line-height:1.8;">With secure video sessions and personalized care plans, we're committed to helping you navigate life's challenges and discover your path to healing and growth.</p>
                <a href="about.php" class="btn btn-primary">Learn More About Us</a>
            </div>
        </div>
    </div>
</section>

<!-- Services -->
<section style="background:#f8f9fa;">
    <div class="container">
        <h2 class="section-title">Our Services</h2>
        <div class="services-grid">
            <?php
            $services = [
                ['icon'=>'🧠', 'title'=>'Individual Therapy',   'desc'=>'Personalized one-on-one sessions to address your unique challenges and goals in a safe, confidential environment.'],
                ['icon'=>'💑', 'title'=>'Couples Therapy',      'desc'=>'Strengthen your relationship with specialized counseling focused on communication, intimacy, and conflict resolution.'],
                ['icon'=>'🌊', 'title'=>'Anxiety & Depression', 'desc'=>'Evidence-based approaches to manage symptoms and develop coping strategies for anxiety, depression, and related conditions.'],
            ];
            foreach ($services as $s): ?>
            <div class="service-card">
                <div class="service-thumb">
                    <?= $s['icon'] ?>
                </div>
                <div class="card-content">
                    <h3><?= htmlspecialchars($s['title']) ?></h3>
                    <p><?= htmlspecialchars($s['desc']) ?></p>
                    <a href="services.php" class="btn btn-sm">Learn More</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section>
    <div class="container">
        <h2 class="section-title">Why Choose Holistic Wellness?</h2>
        <div class="why-grid">
            <?php
            $reasons = [
                ['icon'=>'🔒', 'title'=>'Fully Confidential',   'desc'=>'HIPAA-compliant encrypted sessions protect your privacy completely.'],
                ['icon'=>'📅', 'title'=>'Flexible Scheduling',  'desc'=>'Morning, afternoon, and evening slots to fit your busy lifestyle.'],
                ['icon'=>'💻', 'title'=>'100% Online',          'desc'=>'No commute needed — attend sessions from anywhere with internet.'],
                ['icon'=>'🎓', 'title'=>'Expert Therapists',    'desc'=>'Licensed and credentialed professionals with specialized training.'],
            ];
            foreach ($reasons as $r): ?>
            <div class="why-card">
                <div class="why-icon"><?= $r['icon'] ?></div>
                <h3><?= htmlspecialchars($r['title']) ?></h3>
                <p><?= htmlspecialchars($r['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="testimonials">
    <div class="container">
        <h2 class="section-title">Client Experiences</h2>
        <div class="testimonial-grid">
            <?php
            $testimonials = [
                ['text'=>'Working with Holistic Wellness transformed my approach to anxiety. The online sessions were convenient and just as effective as in-person therapy.', 'author'=>'Maria K.', 'stars'=>5],
                ['text'=>'The couples therapy helped us rebuild our communication from the ground up. We\'re so grateful for the guidance during a difficult time.', 'author'=>'James & Sarah', 'stars'=>5],
                ['text'=>'I was skeptical about online therapy at first, but the experience has been seamless. The flexibility to schedule sessions around my busy work life has been invaluable.', 'author'=>'Thomas R.', 'stars'=>5],
            ];
            foreach ($testimonials as $t): ?>
            <div class="testimonial-card">
                <div class="stars"><?= str_repeat('★', $t['stars']) ?></div>
                <p class="testimonial-text">"<?= htmlspecialchars($t['text']) ?>"</p>
                <p class="testimonial-author">— <?= htmlspecialchars($t['author']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Begin Your Healing Journey?</h2>
            <p>Take the first step toward positive change. Book a session through WhatsApp — we respond within 24 hours.</p>
            <a href="book.php" class="btn btn-light">Book Your Session Today</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
