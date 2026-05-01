<?php
$pageTitle = 'About Us';
$metaDesc  = 'Learn about Dr. Jerald and the Holistic Wellness mission — compassionate, evidence-based online therapy.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/includes/header.php';
$therapistPhoto = getImageSetting('img_about_photo');
$therapistName  = getSetting('therapist_name', 'Dr. Jerald');
$therapistTitle = getSetting('therapist_title', 'Licensed Clinical Psychologist &amp; Founder');
$aboutBanner    = getImageSetting('img_about_banner');
?>
<style>
.therapist-profile { display: flex; flex-wrap: wrap; gap: 3rem; align-items: center; }
.profile-image { flex: 1; min-width: 280px; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-md); background: var(--gradient); display: flex; align-items: center; justify-content: center; height: 340px; font-size: 7rem; }
.profile-content { flex: 2; min-width: 280px; }
.profile-content h2 { margin-bottom: 0.5rem; }
.profile-content .role { color: var(--accent); font-weight: 600; margin-bottom: 1.2rem; font-size: 1rem; }
.profile-content p { margin-bottom: 1rem; color: #555; }
.qual-list { list-style: none; margin-top: 0.5rem; }
.qual-list li { padding: 0.4rem 0 0.4rem 1.6rem; position: relative; color: #555; font-size: 0.95rem; }
.qual-list li::before { content: '✓'; position: absolute; left: 0; color: var(--accent); font-weight: 700; }
.mission-vision { background: var(--gradient); }
.mission-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-top: 2.5rem; }
.mission-card { background: rgba(255,255,255,0.12); padding: 2rem; border-radius: var(--radius); border: 1px solid rgba(255,255,255,0.2); }
.mission-card h3 { color: var(--accent); margin-bottom: 1rem; }
.mission-card p { color: rgba(255,255,255,0.9); line-height: 1.8; }
.approach-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.8rem; margin-top: 2.5rem; }
.approach-card { background: white; padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); text-align: center; transition: var(--transition); }
.approach-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
.approach-icon { font-size: 2.5rem; margin-bottom: 1rem; }
.online-section { background: var(--secondary); }
.online-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.8rem; margin-top: 2.5rem; }
.online-card { background: white; padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); }
.cta-section { text-align: center; }
.cta-section h2 { color: var(--primary); margin-bottom: 1rem; }
</style>

<div class="page-header" <?= $aboutBanner ? 'style="background:linear-gradient(rgba(30,42,53,0.55),rgba(30,42,53,0.55)),url(\'' . htmlspecialchars($aboutBanner) . '\') center/cover no-repeat"' : '' ?> >
    <div class="container">
        <h1>About Holistic Wellness</h1>
        <p>Learn about our approach, mission, and the people behind our practice.</p>
    </div>
</div>

<!-- Therapist Profile -->
<section>
    <div class="container">
        <h2 class="section-title">Meet Your Therapist</h2>
        <div class="therapist-profile">
            <div class="profile-image" style="<?= $therapistPhoto ? 'padding:0;font-size:0' : '' ?>">
                <?php if ($therapistPhoto): ?>
                <img src="<?= htmlspecialchars($therapistPhoto) ?>" alt="<?= htmlspecialchars($therapistName) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                <?php else: ?>🧑‍⚕️<?php endif; ?>
            </div>
            <div class="profile-content">
                <h2><?= htmlspecialchars($therapistName) ?></h2>
                <p class="role"><?= htmlspecialchars($therapistTitle) ?></p>
                <p>With years of experience in mental health counseling, I founded Holistic Wellness with a vision to make therapy accessible, comfortable, and tailored to each individual's unique journey.</p>
                <p>My approach combines evidence-based techniques with a warm, empathetic presence. I believe that therapy should be a collaborative process where we work together to help you achieve your goals and find balance in your life.</p>
                <h4 style="color:var(--primary);margin-bottom:0.6rem;">Qualifications</h4>
                <ul class="qual-list">
                    <li>Ph.D. in Clinical Psychology, Stanford University</li>
                    <li>Licensed Clinical Psychologist</li>
                    <li>Certified in Cognitive Behavioral Therapy (CBT)</li>
                    <li>Trained in EMDR for Trauma Processing</li>
                    <li>Mindfulness-Based Stress Reduction (MBSR) Certified</li>
                    <li>Member of the American Psychological Association</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section class="mission-vision">
    <div class="container">
        <h2 class="section-title" style="color:white;">Our Mission &amp; Vision</h2>
        <div class="mission-grid">
            <div class="mission-card">
                <h3>Our Mission</h3>
                <p>To provide compassionate, accessible, and effective mental health support that empowers individuals to overcome obstacles and thrive in their daily lives.</p>
                <p>We're committed to breaking down barriers to mental healthcare through innovative online delivery while maintaining the highest standards of professional care.</p>
            </div>
            <div class="mission-card">
                <h3>Our Vision</h3>
                <p>A world where quality mental health support is accessible to all, where seeking help is normalized, and where every person has the tools they need for emotional wellbeing.</p>
                <p>We envision a society that prioritizes mental health as essential to overall wellness and human potential.</p>
            </div>
        </div>
    </div>
</section>

<!-- What Makes Us Different -->
<section>
    <div class="container">
        <h2 class="section-title">The Holistic Wellness Difference</h2>
        <div class="approach-grid">
            <div class="approach-card">
                <div class="approach-icon">🎯</div>
                <h3>Personalized Approach</h3>
                <p>We recognize that no two individuals are the same. Your therapy journey is tailored specifically to your needs, goals, and personal circumstances.</p>
            </div>
            <div class="approach-card">
                <div class="approach-icon">🌱</div>
                <h3>Whole-Person Focus</h3>
                <p>We address not just symptoms but the whole person — emotional, physical, social, and spiritual aspects of wellbeing for truly comprehensive care.</p>
            </div>
            <div class="approach-card">
                <div class="approach-icon">📊</div>
                <h3>Evidence-Based Methods</h3>
                <p>Our therapeutic approaches are grounded in research and proven effectiveness, ensuring you receive the highest quality of care.</p>
            </div>
            <div class="approach-card">
                <div class="approach-icon">🤝</div>
                <h3>Collaborative Partnership</h3>
                <p>Therapy is a two-way process. We work together as a team, setting shared goals and celebrating your progress every step of the way.</p>
            </div>
        </div>
    </div>
</section>

<!-- Online Practice -->
<section class="online-section">
    <div class="container">
        <h2 class="section-title">Our Online Practice</h2>
        <div class="online-grid">
            <div class="online-card">
                <h3>♿ Accessibility</h3>
                <p>Our fully online practice eliminates geographic barriers, transportation issues, and scheduling conflicts, making therapy accessible to more people.</p>
            </div>
            <div class="online-card">
                <h3>🔒 Privacy &amp; Confidentiality</h3>
                <p>We use secure, HIPAA-compliant technology for all sessions. Your privacy is protected with the same level of confidentiality as in-person therapy.</p>
            </div>
            <div class="online-card">
                <h3>🏡 Comfortable Environment</h3>
                <p>Sessions take place in the comfort of your chosen space, creating a sense of safety that can enhance the therapeutic process.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2>Ready to Start Your Journey?</h2>
        <p style="color:#666;margin-bottom:2rem;">Book your first session today and take the first step toward positive change.</p>
        <a href="book.php" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i> Book a Session</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
