<?php
http_response_code(404);
$pageTitle = '404 — Page Not Found';
$metaDesc  = 'The page you are looking for could not be found on Holistic Wellness.';
require_once __DIR__ . '/includes/header.php';
?>
<style>
.error-page{min-height:75vh;display:flex;align-items:center;justify-content:center;padding:4rem 1rem;background:linear-gradient(135deg,#f7f4f1,#e8eeee)}
.error-card{background:white;border-radius:20px;box-shadow:0 20px 60px rgba(90,125,124,.12);padding:4rem 3rem;max-width:560px;width:100%;text-align:center}
.error-code{font-family:'Playfair Display',serif;font-size:6rem;font-weight:700;color:var(--secondary);line-height:1;margin-bottom:.5rem;display:block}
.error-icon{font-size:3.5rem;margin-bottom:1.5rem;display:block}
.error-card h1{font-size:1.7rem;color:var(--dark);margin-bottom:.8rem}
.error-card p{color:#666;font-size:1rem;line-height:1.7;margin-bottom:2rem}
.error-links{display:flex;flex-wrap:wrap;gap:.75rem;justify-content:center;margin-bottom:2rem}
.error-links a{display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.3rem;border-radius:50px;font-size:.9rem;font-weight:600;text-decoration:none;transition:all .3s}
.error-links .primary{background:var(--primary);color:white;box-shadow:0 4px 14px rgba(90,125,124,.35)}
.error-links .primary:hover{background:var(--primary-d);transform:translateY(-2px)}
.error-links .secondary{background:white;color:var(--primary);border:1.5px solid var(--primary)}
.error-links .secondary:hover{background:var(--primary);color:white}
.error-links .whatsapp{background:#25D366;color:white}
.error-links .whatsapp:hover{background:#1ebd5b}
.search-box{display:flex;gap:.5rem;max-width:360px;margin:0 auto}
.search-box input{flex:1;padding:.7rem 1rem;border:1.5px solid #dde1e5;border-radius:8px;font-size:.9rem;font-family:inherit;outline:none;transition:.3s}
.search-box input:focus{border-color:var(--primary)}
.search-box button{padding:.7rem 1rem;background:var(--primary);color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600;transition:.3s}
.search-box button:hover{background:var(--primary-d)}
.quick-links{margin-top:2rem;text-align:left;background:#f9fafb;border-radius:10px;padding:1.2rem 1.5rem}
.quick-links h4{font-size:.82rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.7px;margin-bottom:.75rem}
.quick-links ul{list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:.4rem}
.quick-links a{font-size:.85rem;color:#555;text-decoration:none;display:flex;align-items:center;gap:.4rem;padding:.3rem 0}
.quick-links a:hover{color:var(--primary)}
.quick-links i{color:var(--accent);width:14px;text-align:center;font-size:.8rem}
</style>

<div class="error-page">
  <div class="error-card">
    <span class="error-icon">🌿</span>
    <span class="error-code">404</span>
    <h1>Page Not Found</h1>
    <p>The page you're looking for seems to have wandered off. Let's help you find your way back to wellness.</p>

    <div class="error-links">
      <a href="index.php" class="primary"><i class="fas fa-home"></i> Go Home</a>
      <a href="book.php"  class="secondary"><i class="fas fa-calendar"></i> Book Session</a>
      <a href="https://wa.me/254797582384" class="whatsapp" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp Us</a>
    </div>

    <div class="quick-links">
      <h4>Popular Pages</h4>
      <ul>
        <li><a href="about.php"><i class="fas fa-user-doctor"></i> About Us</a></li>
        <li><a href="services.php"><i class="fas fa-heart"></i> Our Services</a></li>
        <li><a href="faq.php"><i class="fas fa-circle-question"></i> FAQ</a></li>
        <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
        <li><a href="blog.php"><i class="fas fa-newspaper"></i> Resources</a></li>
        <li><a href="login.php"><i class="fas fa-lock"></i> Client Login</a></li>
      </ul>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
