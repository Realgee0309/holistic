<?php
/**
 * Admin — Blog / Resources Manager
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/security.php';

$pdo = getDB();

// ── Handle save (add or edit) ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post'])) {
    csrfVerify();

    $id      = cleanInt($_POST['id'] ?? 0);
    $title   = cleanStr($_POST['title']    ?? '', 200);
    $excerpt = cleanStr($_POST['excerpt']  ?? '', 500);
    $body    = trim($_POST['body']         ?? ''); // HTML body — not escaped (admin input)
    $cat     = cleanStr($_POST['category'] ?? 'General', 80);
    $pub     = !empty($_POST['is_published']) ? 1 : 0;

    // Slug generation
    $slug = isset($_POST['slug']) && trim($_POST['slug'])
        ? preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', strtolower(trim($_POST['slug']))))
        : preg_replace('/[^a-z0-9-]+/', '-', strtolower(trim($title)));
    $slug = trim($slug, '-');

    if (!$title || !$body || !$slug) {
        setFlash('error', 'Title, slug, and body are required.');
        header('Location: blog.php' . ($id ? '?edit='.$id : ''));
        exit;
    }

    // Ensure unique slug
    $slugCheck = $pdo->prepare("SELECT id FROM blog_posts WHERE slug=:s AND id!=:id");
    $slugCheck->execute([':s'=>$slug, ':id'=>$id]);
    if ($slugCheck->fetch()) {
        $slug .= '-' . time();
    }

    if ($id) {
        $pdo->prepare("UPDATE blog_posts SET title=:t, slug=:s, excerpt=:e, body=:b, category=:c, is_published=:p WHERE id=:id")
            ->execute([':t'=>$title,':s'=>$slug,':e'=>$excerpt,':b'=>$body,':c'=>$cat,':p'=>$pub,':id'=>$id]);
        setFlash('success', 'Post updated.');
    } else {
        $pdo->prepare("INSERT INTO blog_posts (title, slug, excerpt, body, category, is_published) VALUES (:t,:s,:e,:b,:c,:p)")
            ->execute([':t'=>$title,':s'=>$slug,':e'=>$excerpt,':b'=>$body,':c'=>$cat,':p'=>$pub]);
        setFlash('success', 'Post created.');
    }
    header('Location: blog.php');
    exit;
}

// ── Toggle publish ────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id  = cleanInt($_GET['toggle']);
    $cur = $pdo->prepare("SELECT is_published FROM blog_posts WHERE id=:id");
    $cur->execute([':id'=>$id]);
    $cur = $cur->fetchColumn();
    $pdo->prepare("UPDATE blog_posts SET is_published=:p WHERE id=:id")->execute([':p'=>$cur?0:1,':id'=>$id]);
    header('Location: blog.php?msg=toggled');
    exit;
}

// ── Delete ────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = cleanInt($_GET['delete']);
    if ($id) $pdo->prepare("DELETE FROM blog_posts WHERE id=:id")->execute([':id'=>$id]);
    header('Location: blog.php?msg=deleted');
    exit;
}

// ── Load edit target ─────────────────────────────────────────
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id=:id");
    $stmt->execute([':id' => cleanInt($_GET['edit'])]);
    $editing = $stmt->fetch();
}

$posts = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC")->fetchAll();
$categories = ['Anxiety','Depression','Relationships','Self-Care','Mindfulness','Trauma','Life Transitions','General'];

adminHead('Blog Manager', 'Create and manage resource articles');
?>
<style>
.blog-layout{display:grid;grid-template-columns:1fr 420px;gap:1.5rem;align-items:start}
.form-group{margin-bottom:1rem}
.form-group label{font-size:.82rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem}
.form-control{width:100%;padding:.7rem .9rem;border:1.5px solid #e5e7eb;border-radius:9px;font-size:.88rem;font-family:inherit;color:#1f2937;background:#fafafa;transition:all .3s}
.form-control:focus{outline:none;border-color:var(--primary);background:white;box-shadow:0 0 0 3px rgba(90,125,124,.1)}
textarea.form-control{min-height:300px;resize:vertical;font-family:monospace;font-size:.82rem}
.post-row{display:flex;gap:1rem;padding:1rem 1.2rem;border-bottom:1px solid #f3f4f6;align-items:flex-start}
.post-row:last-child{border-bottom:none}
.post-meta{flex:1;min-width:0}
.post-title{font-size:.9rem;font-weight:600;color:var(--dark);margin-bottom:.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.post-info{font-size:.75rem;color:var(--text-muted)}
.cat-badge{display:inline-block;background:rgba(90,125,124,.1);color:var(--primary);font-size:.7rem;font-weight:600;padding:.15rem .5rem;border-radius:50px;margin-right:.4rem}
.editor-help{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:.75rem 1rem;font-size:.75rem;color:#6b7280;margin-bottom:.8rem}
.editor-help code{background:#e5e7eb;padding:.1rem .3rem;border-radius:3px;font-size:.82em}
@media(max-width:900px){.blog-layout{grid-template-columns:1fr}}
</style>

<?php if (isset($_GET['msg'])): ?>
<div class="flash <?= $_GET['msg']==='deleted'?'error':'success' ?>">
  <i class="fas fa-<?= $_GET['msg']==='deleted'?'trash':'check-circle' ?>"></i>
  <?= ['deleted'=>'Post deleted.','toggled'=>'Post visibility updated.'][$_GET['msg']] ?? 'Done.' ?>
</div>
<?php endif; ?>
<?php renderFlash(); ?>

<div class="blog-layout">

  <!-- Posts List -->
  <div class="panel">
    <div class="panel-head">
      <div class="panel-head-left"><i class="fas fa-newspaper panel-icon"></i><h3>Posts</h3><span style="font-size:.78rem;color:var(--text-muted);margin-left:.4rem"><?= count($posts) ?> total</span></div>
      <a href="blog.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Post</a>
    </div>

    <?php if (empty($posts)): ?>
    <div class="empty-state"><i class="fas fa-newspaper"></i><p>No posts yet. Create your first resource article.</p></div>
    <?php else: ?>
    <?php foreach ($posts as $p): ?>
    <div class="post-row">
      <div class="post-meta">
        <div class="post-title"><?= htmlspecialchars($p['title']) ?></div>
        <div class="post-info">
          <span class="cat-badge"><?= htmlspecialchars($p['category']) ?></span>
          <?= date('d M Y', strtotime($p['created_at'])) ?> ·
          <span style="color:<?= $p['views']>0?'var(--primary)':'#aaa' ?>"><?= $p['views'] ?> views</span> ·
          <a href="../blog_post.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" style="color:var(--primary);font-size:.72rem">Preview ↗</a>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:.35rem;align-items:flex-end;flex-shrink:0">
        <span class="badge <?= $p['is_published']?'badge-confirmed':'badge-read' ?>"><?= $p['is_published']?'Published':'Draft' ?></span>
        <div style="display:flex;gap:.3rem">
          <a href="blog.php?edit=<?= $p['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit"><i class="fas fa-pencil"></i></a>
          <a href="blog.php?toggle=<?= $p['id'] ?>" class="btn btn-sm <?= $p['is_published']?'btn-danger':'btn-success' ?>" style="font-size:.72rem">
            <?= $p['is_published']?'Unpublish':'Publish' ?>
          </a>
          <a href="blog.php?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this post permanently?')"
             class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash-can"></i></a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Editor Form -->
  <div>
    <div class="panel">
      <div class="panel-head">
        <div class="panel-head-left">
          <i class="fas fa-<?= $editing?'pencil':'file-pen' ?> panel-icon"></i>
          <h3><?= $editing ? 'Edit Post' : 'New Post' ?></h3>
        </div>
        <?php if ($editing): ?><a href="blog.php" class="btn btn-ghost btn-sm">Cancel</a><?php endif; ?>
      </div>
      <div style="padding:1.2rem 1.4rem">
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="save_post" value="1">
          <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

          <div class="form-group">
            <label>Title <span style="color:#ef4444">*</span></label>
            <input type="text" name="title" class="form-control" required maxlength="200" id="titleInput"
                   placeholder="Article title…" value="<?= htmlspecialchars($editing['title']??'') ?>">
          </div>

          <div class="form-group">
            <label>URL Slug <span style="color:#ef4444">*</span></label>
            <input type="text" name="slug" class="form-control" id="slugInput" maxlength="160"
                   placeholder="auto-generated-from-title"
                   value="<?= htmlspecialchars($editing['slug']??'') ?>"
                   style="font-family:monospace;font-size:.82rem">
            <div style="font-size:.72rem;color:var(--text-muted);margin-top:.2rem">blog.php?slug=<strong id="slugPreview"><?= htmlspecialchars($editing['slug']??'…') ?></strong></div>
          </div>

          <div class="form-group">
            <label>Category</label>
            <select name="category" class="form-control">
              <?php foreach ($categories as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= ($editing['category']??'')===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Excerpt (shown on listing page)</label>
            <textarea name="excerpt" class="form-control" style="min-height:70px;font-family:inherit;font-size:.88rem"
                      maxlength="500" placeholder="Short description of the article…"><?= htmlspecialchars($editing['excerpt']??'') ?></textarea>
          </div>

          <div class="form-group">
            <label>Body (HTML) <span style="color:#ef4444">*</span></label>
            <div class="editor-help">
              Use basic HTML: <code>&lt;p&gt;</code> <code>&lt;h3&gt;</code> <code>&lt;ul&gt;&lt;li&gt;</code> <code>&lt;strong&gt;</code> <code>&lt;em&gt;</code>
            </div>
            <textarea name="body" class="form-control" required><?= htmlspecialchars($editing['body']??'') ?></textarea>
          </div>

          <div class="form-group">
            <label>
              <input type="checkbox" name="is_published" value="1" <?= ($editing['is_published']??0)?'checked':'' ?> style="accent-color:var(--primary)">
              &nbsp; Publish immediately (visible on blog page)
            </label>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;display:flex">
            <i class="fas fa-<?= $editing?'check':'plus' ?>"></i>
            <?= $editing?'Update Post':'Create Post' ?>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Auto-generate slug from title
const titleInput = document.getElementById('titleInput');
const slugInput  = document.getElementById('slugInput');
const slugPrev   = document.getElementById('slugPreview');
function makeSlug(s){ return s.toLowerCase().trim().replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').substring(0,100); }
titleInput && titleInput.addEventListener('input', function(){
  if (!slugInput.dataset.manual) {
    const sl = makeSlug(this.value);
    slugInput.value = sl;
    slugPrev.textContent = sl || '…';
  }
});
slugInput && slugInput.addEventListener('input', function(){
  this.dataset.manual = '1';
  slugPrev.textContent = this.value || '…';
});
</script>

<?php adminFoot(); ?>
