<?php
$pageTitle = 'Resources & Blog';
$metaDesc  = 'Mental health articles, tips, and resources from the Holistic Wellness team.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/header.php';
sendSecurityHeaders();

$pdo = getDB();

$category = trim($_GET['cat'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 9;
$offset   = ($page - 1) * $perPage;

$where  = "WHERE is_published = 1";
$params = [];
if ($category) {
    $where .= " AND category = :cat";
    $params[':cat'] = $category;
}

$total = $pdo->prepare("SELECT COUNT(*) FROM blog_posts $where");
$total->execute($params);
$total = (int)$total->fetchColumn();
$pages = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM blog_posts $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$posts = $stmt->fetchAll();

// All categories for filter
$cats = $pdo->query("SELECT DISTINCT category FROM blog_posts WHERE is_published=1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<style>
.blog-hero{background:var(--secondary);padding:5rem 0;text-align:center}
.blog-hero h1{margin-bottom:.8rem}
.blog-hero p{max-width:580px;margin:0 auto;color:#666;font-size:1.05rem}
.cat-filters{display:flex;gap:.5rem;flex-wrap:wrap;justify-content:center;margin-bottom:3rem}
.cat-btn{padding:.45rem 1.1rem;border-radius:50px;font-size:.83rem;font-weight:600;text-decoration:none;border:1.5px solid #dde1e5;color:#666;background:white;transition:all .3s}
.cat-btn:hover,.cat-btn.active{background:var(--primary);color:white;border-color:var(--primary)}
.posts-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:2rem}
.post-card{background:white;border-radius:var(--radius);box-shadow:var(--shadow-sm);overflow:hidden;display:flex;flex-direction:column;transition:var(--transition)}
.post-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-md)}
.post-thumb{height:180px;background:linear-gradient(135deg,var(--secondary),rgba(90,125,124,.4));display:flex;align-items:center;justify-content:center;font-size:3.5rem;flex-shrink:0;overflow:hidden}
.post-thumb img{width:100%;height:100%;object-fit:cover}
.post-body{padding:1.5rem;flex:1;display:flex;flex-direction:column}
.post-cat{display:inline-block;background:rgba(90,125,124,.1);color:var(--primary);font-size:.72rem;font-weight:700;padding:.2rem .65rem;border-radius:50px;margin-bottom:.75rem;text-transform:uppercase;letter-spacing:.5px}
.post-body h2{font-size:1.05rem;color:var(--dark);margin-bottom:.6rem;line-height:1.45}
.post-body p{font-size:.88rem;color:#666;line-height:1.7;flex:1;margin-bottom:1rem}
.post-footer{display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:.9rem;border-top:1px solid #f3f4f6}
.post-date{font-size:.76rem;color:#aaa}
.read-more{font-size:.83rem;font-weight:600;color:var(--primary);text-decoration:none}
.read-more:hover{color:var(--primary-d)}
.pagination{display:flex;gap:.4rem;justify-content:center;margin-top:3rem}
.page-btn{padding:.45rem .9rem;border-radius:8px;font-size:.85rem;font-weight:500;text-decoration:none;border:1px solid #dde1e5;color:#666;background:white;transition:var(--transition)}
.page-btn:hover{background:#f9fafb}
.page-btn.active{background:var(--primary);color:white;border-color:var(--primary)}
.empty-posts{text-align:center;padding:4rem 2rem;color:#aaa}
.empty-posts i{font-size:3rem;opacity:.25;display:block;margin-bottom:1rem}
.featured-post{grid-column:1/-1;display:grid;grid-template-columns:1.5fr 1fr;gap:0;border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow-md);background:white;transition:var(--transition)}
.featured-post:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
.featured-thumb{background:linear-gradient(135deg,var(--secondary),rgba(90,125,124,.5));display:flex;align-items:center;justify-content:center;font-size:5rem;min-height:260px}
.featured-content{padding:2.5rem;display:flex;flex-direction:column;justify-content:center}
.featured-content .post-cat{margin-bottom:1rem}
.featured-content h2{font-size:1.4rem;margin-bottom:.9rem;line-height:1.4}
.featured-content p{font-size:.95rem;color:#555;line-height:1.8;margin-bottom:1.5rem}
@media(max-width:768px){.featured-post{grid-template-columns:1fr}.featured-thumb{min-height:160px}}
</style>

<div class="blog-hero">
    <div class="container">
        <h1>Resources &amp; Insights</h1>
        <p>Evidence-based articles, self-help tips, and mental health guidance from our clinical team.</p>
    </div>
</div>

<section>
    <div class="container">
        <!-- Category filters -->
        <?php if (!empty($cats)): ?>
        <div class="cat-filters">
            <a href="blog.php" class="cat-btn <?= !$category ? 'active' : '' ?>">All Topics</a>
            <?php foreach ($cats as $c): ?>
            <a href="blog.php?cat=<?= urlencode($c) ?>" class="cat-btn <?= $category===$c ? 'active' : '' ?>"><?= htmlspecialchars($c) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
        <div class="empty-posts">
            <i class="fas fa-newspaper"></i>
            <p>No articles found<?= $category ? ' in "'.htmlspecialchars($category).'"' : '' ?>. Check back soon!</p>
        </div>
        <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($posts as $i => $post):
                $emoji = match($post['category']) {
                    'Anxiety'          => '😮‍💨',
                    'Depression'       => '🌧️',
                    'Relationships'    => '💑',
                    'Self-Care'        => '🌿',
                    'Mindfulness'      => '🧘',
                    'Trauma'           => '🦋',
                    'Life Transitions' => '🚀',
                    default            => '📖'
                };
                // First post on page 1 is featured
                $isFeatured = ($i === 0 && $page === 1 && !$category);
            ?>

            <?php if ($isFeatured): ?>
            <a href="blog_post.php?slug=<?= urlencode($post['slug']) ?>" class="featured-post" style="text-decoration:none;color:inherit">
                <div class="featured-thumb"><?= $post['cover_image'] ? '<img src="'.htmlspecialchars($post['cover_image']).'" alt="">' : $emoji ?></div>
                <div class="featured-content">
                    <span class="post-cat">✨ Featured · <?= htmlspecialchars($post['category']) ?></span>
                    <h2><?= htmlspecialchars($post['title']) ?></h2>
                    <p><?= htmlspecialchars($post['excerpt'] ?: mb_substr(strip_tags($post['body']), 0, 160).'…') ?></p>
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <span style="font-size:.8rem;color:#aaa"><?= date('d M Y', strtotime($post['created_at'])) ?></span>
                        <span class="btn btn-sm btn-primary" style="text-decoration:none">Read Article →</span>
                    </div>
                </div>
            </a>

            <?php else: ?>
            <div class="post-card">
                <div class="post-thumb"><?= $post['cover_image'] ? '<img src="'.htmlspecialchars($post['cover_image']).'" alt="">' : $emoji ?></div>
                <div class="post-body">
                    <span class="post-cat"><?= htmlspecialchars($post['category']) ?></span>
                    <h2><?= htmlspecialchars($post['title']) ?></h2>
                    <p><?= htmlspecialchars($post['excerpt'] ?: mb_substr(strip_tags($post['body']), 0, 130).'…') ?></p>
                    <div class="post-footer">
                        <span class="post-date"><?= date('d M Y', strtotime($post['created_at'])) ?></span>
                        <a href="blog_post.php?slug=<?= urlencode($post['slug']) ?>" class="read-more">Read More →</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?><?= $category?'&cat='.urlencode($category):'' ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
            <?php for ($p = 1; $p <= $pages; $p++): ?><a href="?page=<?= $p ?><?= $category?'&cat='.urlencode($category):'' ?>" class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a><?php endfor; ?>
            <?php if ($page < $pages): ?><a href="?page=<?= $page+1 ?><?= $category?'&cat='.urlencode($category):'' ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <div style="margin-top:4rem;background:var(--secondary);border-radius:var(--radius);padding:2.5rem;text-align:center">
            <h3>Ready to take the next step?</h3>
            <p style="color:#555;margin:.8rem 0 1.5rem">Reading is a great start — talking to a professional is even better.</p>
            <a href="book.php" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i> Book a Free Consultation</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
