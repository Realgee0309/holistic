<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/security.php';
sendSecurityHeaders();

$pdo  = getDB();
$slug = trim($_GET['slug'] ?? '');

if (!$slug) { header('Location: blog.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug=:s AND is_published=1 LIMIT 1");
$stmt->execute([':s' => $slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $pageTitle = '404 — Post Not Found';
    $metaDesc  = 'This article could not be found.';
    require_once __DIR__ . '/includes/header.php';
    echo '<section style="text-align:center;padding:6rem 1rem"><h2>Article Not Found</h2><p style="color:#666;margin:1rem 0 2rem">That article doesn\'t exist or has been removed.</p><a href="blog.php" class="btn btn-primary">← Back to Resources</a></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Increment views
$pdo->prepare("UPDATE blog_posts SET views=views+1 WHERE id=:id")->execute([':id' => $post['id']]);

// Related posts (same category, excluding current)
$related = $pdo->prepare("SELECT * FROM blog_posts WHERE category=:cat AND slug!=:s AND is_published=1 ORDER BY RAND() LIMIT 3");
$related->execute([':cat' => $post['category'], ':s' => $slug]);
$related = $related->fetchAll();

$pageTitle = $post['title'];
$metaDesc  = $post['excerpt'] ?: mb_substr(strip_tags($post['body']), 0, 160);
require_once __DIR__ . '/includes/header.php';
?>
<style>
.post-page{max-width:820px;margin:0 auto;padding:3rem 1.5rem}
.post-header{margin-bottom:2.5rem}
.post-cat-badge{display:inline-block;background:rgba(90,125,124,.1);color:var(--primary);font-size:.75rem;font-weight:700;padding:.25rem .8rem;border-radius:50px;text-transform:uppercase;letter-spacing:.6px;margin-bottom:1rem;text-decoration:none}
.post-cat-badge:hover{background:var(--primary);color:white}
.post-header h1{font-size:2.1rem;color:var(--dark);line-height:1.3;margin-bottom:1rem}
.post-meta-bar{display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;padding:1rem 0;border-top:1px solid #f3f4f6;border-bottom:1px solid #f3f4f6;font-size:.82rem;color:#888;margin-bottom:2rem}
.meta-item{display:flex;align-items:center;gap:.4rem}
.post-excerpt{font-size:1.1rem;color:#555;line-height:1.85;margin-bottom:2rem;padding-left:1.2rem;border-left:3px solid var(--accent);font-style:italic}
.post-content h2,.post-content h3{color:var(--primary);margin:2rem 0 .8rem}
.post-content h2{font-size:1.45rem}
.post-content h3{font-size:1.15rem}
.post-content p{color:#444;line-height:1.85;margin-bottom:1.2rem;font-size:1.01rem}
.post-content ul,.post-content ol{color:#444;padding-left:1.5rem;margin-bottom:1.2rem}
.post-content li{margin-bottom:.5rem;line-height:1.7;font-size:1.01rem}
.post-content strong{color:var(--dark)}
.post-content a{color:var(--primary);text-decoration:underline}
.share-bar{background:var(--secondary);border-radius:var(--radius);padding:1.5rem;margin-top:3rem;text-align:center}
.share-bar h3{font-size:1rem;margin-bottom:1rem}
.share-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1.2rem;border-radius:50px;font-size:.85rem;font-weight:600;text-decoration:none;margin:.25rem;transition:all .3s}
.share-wa{background:#25D366;color:white}
.share-wa:hover{background:#1ebd5b}
.share-copy{background:var(--primary);color:white}
.share-copy:hover{background:var(--primary-d)}
.cta-box{background:linear-gradient(135deg,var(--primary),var(--primary-d));color:white;border-radius:var(--radius);padding:2.5rem;text-align:center;margin-top:3rem}
.cta-box h3{color:white;margin-bottom:.8rem;font-size:1.3rem}
.cta-box p{opacity:.9;margin-bottom:1.5rem}
.related-section{margin-top:4rem}
.related-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.5rem;margin-top:1.5rem}
.related-card{background:white;border-radius:var(--radius);box-shadow:var(--shadow-sm);overflow:hidden;text-decoration:none;color:inherit;display:flex;flex-direction:column;transition:var(--transition)}
.related-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md)}
.related-thumb{height:120px;background:linear-gradient(135deg,var(--secondary),rgba(90,125,124,.4));display:flex;align-items:center;justify-content:center;font-size:2.5rem}
.related-body{padding:1rem}
.related-body h4{font-size:.9rem;color:var(--dark);line-height:1.4;margin-bottom:.4rem}
.related-body span{font-size:.76rem;color:#aaa}
.breadcrumb{display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:#888;margin-bottom:1.5rem;flex-wrap:wrap}
.breadcrumb a{color:var(--primary);text-decoration:none}
.breadcrumb a:hover{text-decoration:underline}
.breadcrumb-sep{color:#ccc}
</style>

<section style="background:#f7f4f1;padding:2.5rem 0 0">
<div class="container">
<div class="post-page" style="padding-top:0">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span class="breadcrumb-sep">/</span>
        <a href="blog.php">Resources</a>
        <span class="breadcrumb-sep">/</span>
        <a href="blog.php?cat=<?= urlencode($post['category']) ?>"><?= htmlspecialchars($post['category']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <span style="color:#444"><?= htmlspecialchars(mb_substr($post['title'], 0, 50)) ?>…</span>
    </div>

    <!-- Post Header -->
    <div class="post-header">
        <a href="blog.php?cat=<?= urlencode($post['category']) ?>" class="post-cat-badge"><?= htmlspecialchars($post['category']) ?></a>
        <h1><?= htmlspecialchars($post['title']) ?></h1>
        <div class="post-meta-bar">
            <div class="meta-item"><i class="fas fa-user-md"></i> Dr. Jerald, LCP</div>
            <div class="meta-item"><i class="fas fa-calendar"></i> <?= date('d F Y', strtotime($post['created_at'])) ?></div>
            <div class="meta-item"><i class="fas fa-eye"></i> <?= number_format($post['views']) ?> reads</div>
            <div class="meta-item"><i class="fas fa-tag"></i> <?= htmlspecialchars($post['category']) ?></div>
        </div>
        <?php if ($post['excerpt']): ?>
        <div class="post-excerpt"><?= htmlspecialchars($post['excerpt']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Body -->
    <div class="post-content">
        <?= $post['body'] /* Admin-supplied HTML — trusted source */ ?>
    </div>

    <!-- Share Bar -->
    <div class="share-bar">
        <h3>💬 Found this helpful? Share it.</h3>
        <a href="https://wa.me/?text=<?= urlencode($post['title'].' — https://holisticwellness.com/blog_post.php?slug='.$post['slug']) ?>"
           class="share-btn share-wa" target="_blank" rel="noopener">
            <i class="fab fa-whatsapp"></i> Share on WhatsApp
        </a>
        <button onclick="navigator.clipboard.writeText(window.location.href).then(()=>{this.textContent='✓ Copied!';setTimeout(()=>{this.innerHTML='<i class=\'fas fa-link\'></i> Copy Link'},2000)})"
                class="share-btn share-copy" style="border:none;cursor:pointer;font-family:inherit">
            <i class="fas fa-link"></i> Copy Link
        </button>
    </div>

    <!-- CTA -->
    <div class="cta-box">
        <h3>Take the Next Step</h3>
        <p>Reading about mental health is a great start. Speaking with a professional is even better.</p>
        <a href="book.php" class="btn btn-light"><i class="fab fa-whatsapp" style="color:#25D366"></i> Book a Free Consultation</a>
    </div>

    <!-- Related Posts -->
    <?php if (!empty($related)): ?>
    <div class="related-section">
        <h2 style="font-size:1.3rem;color:var(--primary)">More in <?= htmlspecialchars($post['category']) ?></h2>
        <div class="related-grid">
            <?php foreach ($related as $r):
                $emoji = match($r['category']) {
                    'Anxiety'=>'😮‍💨','Depression'=>'🌧️','Relationships'=>'💑','Self-Care'=>'🌿','Mindfulness'=>'🧘','Trauma'=>'🦋','Life Transitions'=>'🚀',default=>'📖'
                };
            ?>
            <a href="blog_post.php?slug=<?= urlencode($r['slug']) ?>" class="related-card">
                <div class="related-thumb"><?= $emoji ?></div>
                <div class="related-body">
                    <h4><?= htmlspecialchars($r['title']) ?></h4>
                    <span><?= date('d M Y', strtotime($r['created_at'])) ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
