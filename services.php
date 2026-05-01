<?php
$pageTitle = 'Our Services';
$metaDesc  = 'Explore our range of online therapy services including individual therapy, couples counseling, anxiety and depression support, and life coaching.';
require_once __DIR__ . '/includes/header.php';
?>
<style>
.services-hero { background: var(--secondary); padding: 5rem 0; text-align: center; }
.services-list { margin-top: 3rem; }
.service-detail {
    display: grid; grid-template-columns: 1fr 2fr; gap: 3rem;
    align-items: center; background: white;
    border-radius: var(--radius); padding: 2.5rem;
    box-shadow: var(--shadow-sm); margin-bottom: 2rem;
    transition: var(--transition);
}
.service-detail:hover { box-shadow: var(--shadow-md); }
.service-detail:nth-child(even) { direction: rtl; }
.service-detail:nth-child(even) > * { direction: ltr; }
.service-icon-box {
    background: var(--gradient); border-radius: 12px;
    height: 200px; display: flex; align-items: center;
    justify-content: center; font-size: 5rem;
}
.service-info h3 { font-size: 1.5rem; margin-bottom: 0.7rem; }
.service-info p { color: #666; margin-bottom: 1.2rem; line-height: 1.8; }
.service-price { display: inline-block; background: var(--secondary); color: var(--primary); font-weight: 700; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.9rem; margin-bottom: 1rem; }
.tag-list { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.2rem; }
.tag { background: #f0f4f0; color: var(--primary); font-size: 0.8rem; padding: 0.25rem 0.7rem; border-radius: 50px; font-weight: 500; }
.pricing-section { background: var(--gradient); }
.pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 2rem; margin-top: 2.5rem; }
.pricing-card { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); padding: 2.5rem; border-radius: var(--radius); text-align: center; color: white; transition: var(--transition); }
.pricing-card:hover { background: rgba(255,255,255,0.2); transform: translateY(-5px); }
.pricing-card.featured { background: white; color: var(--dark); }
.pricing-card.featured h3 { color: var(--primary); }
.price { font-size: 2.5rem; font-weight: 700; font-family: 'Playfair Display', serif; color: var(--accent); }
.price-period { font-size: 0.9rem; opacity: 0.8; }
.pricing-card ul { list-style: none; margin: 1.5rem 0; text-align: left; }
.pricing-card ul li { padding: 0.4rem 0; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 0.92rem; }
.pricing-card.featured ul li { border-color: #eee; }
.pricing-card ul li::before { content: '✓ '; color: var(--accent); font-weight: 700; }
@media (max-width: 768px) {
    .service-detail { grid-template-columns: 1fr; }
    .service-detail:nth-child(even) { direction: ltr; }
}
</style>

<div class="page-header">
    <div class="container">
        <h1>Our Services</h1>
        <p>Comprehensive mental health support tailored to your unique needs and goals.</p>
    </div>
</div>

<section>
    <div class="container">
        <h2 class="section-title">What We Offer</h2>
        <div class="services-list">

            <?php
            $services = [
                ['icon'=>'🧠','title'=>'Individual Therapy','price'=>'From KES 3,500/session',
                 'desc'=>'Personalized one-on-one sessions designed to help you explore your thoughts, emotions, and behaviors in a safe and confidential environment. We work collaboratively to develop strategies for overcoming life\'s challenges.',
                 'tags'=>['Anxiety','Depression','Trauma','Grief','Self-Esteem','Life Transitions']],
                ['icon'=>'💑','title'=>'Couples Therapy','price'=>'From KES 5,000/session',
                 'desc'=>'Specialized counseling to help partners strengthen their connection, improve communication, resolve conflicts, and rebuild trust. Whether you\'re navigating a rough patch or want to deepen your bond, we\'re here to help.',
                 'tags'=>['Communication','Conflict Resolution','Intimacy','Infidelity Recovery','Pre-Marital']],
                ['icon'=>'🌊','title'=>'Anxiety & Depression Support','price'=>'From KES 3,500/session',
                 'desc'=>'Evidence-based approaches including CBT and mindfulness to help manage anxiety, depression, and related conditions. We\'ll develop personalized coping strategies and build resilience for long-term wellbeing.',
                 'tags'=>['CBT','Mindfulness','Panic Attacks','OCD','PTSD','Social Anxiety']],
                ['icon'=>'🚀','title'=>'Life Coaching','price'=>'From KES 4,000/session',
                 'desc'=>'Goal-oriented sessions to help you achieve personal and professional growth. Whether you\'re navigating career transitions, seeking greater fulfillment, or developing leadership skills, we provide the clarity and accountability you need.',
                 'tags'=>['Goal Setting','Career Transition','Leadership','Work-Life Balance','Productivity']],
                ['icon'=>'🆓','title'=>'Initial Consultation','price'=>'FREE — 30 minutes',
                 'desc'=>'A complimentary 30-minute session to discuss your needs, answer your questions, and determine if we\'re the right fit for your journey. No commitment required — just an open conversation about your wellbeing.',
                 'tags'=>['No Commitment','Discovery Call','First Step','Free']],
            ];
            foreach ($services as $s): ?>
            <div class="service-detail">
                <div class="service-icon-box"><?= $s['icon'] ?></div>
                <div class="service-info">
                    <span class="service-price"><?= htmlspecialchars($s['price']) ?></span>
                    <h3><?= htmlspecialchars($s['title']) ?></h3>
                    <p><?= htmlspecialchars($s['desc']) ?></p>
                    <div class="tag-list">
                        <?php foreach ($s['tags'] as $tag): ?>
                        <span class="tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <a href="book.php" class="btn btn-sm btn-whatsapp"><i class="fab fa-whatsapp"></i> Book This Service</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Pricing Overview -->
<section class="pricing-section">
    <div class="container">
        <h2 class="section-title" style="color:white;">Session Packages</h2>
        <div class="pricing-grid">
            <div class="pricing-card">
                <h3>Single Session</h3>
                <div class="price">KES 3,500</div>
                <div class="price-period">per session (50 min)</div>
                <ul>
                    <li>Individual Therapy</li>
                    <li>Secure Video Session</li>
                    <li>Session Notes Provided</li>
                    <li>WhatsApp Support</li>
                </ul>
                <a href="book.php" class="btn btn-light btn-sm">Get Started</a>
            </div>
            <div class="pricing-card featured">
                <div style="background:var(--accent);color:white;font-size:0.8rem;font-weight:700;padding:0.3rem 1rem;border-radius:50px;display:inline-block;margin-bottom:1rem;">MOST POPULAR</div>
                <h3>Monthly Package</h3>
                <div class="price">KES 12,000</div>
                <div class="price-period">4 sessions / month</div>
                <ul>
                    <li>Save KES 2,000</li>
                    <li>Priority Scheduling</li>
                    <li>Between-Session Support</li>
                    <li>Progress Tracking</li>
                </ul>
                <a href="book.php" class="btn btn-whatsapp btn-sm"><i class="fab fa-whatsapp"></i> Book Package</a>
            </div>
            <div class="pricing-card">
                <h3>Couples Package</h3>
                <div class="price">KES 18,000</div>
                <div class="price-period">4 sessions / month</div>
                <ul>
                    <li>Couples Therapy Focus</li>
                    <li>60-Minute Sessions</li>
                    <li>Relationship Exercises</li>
                    <li>Partner Communication Tools</li>
                </ul>
                <a href="book.php" class="btn btn-light btn-sm">Book for Couples</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
