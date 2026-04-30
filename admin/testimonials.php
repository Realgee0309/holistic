<?php
/**
 * Admin — Testimonials Manager
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/security.php';

$pdo = getDB();

// ── Add / Edit ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_testimonial'])) {
    csrfVerify();
    $id      = cleanInt($_POST['id'] ?? 0);
    $name    = cleanStr($_POST['client_name'] ?? '', 100);
    $service = cleanStr($_POST['service']     ?? '', 100);
    $rating  = max(1, min(5, cleanInt($_POST['rating'] ?? 5)));
    $body    = cleanText($_POST['body']       ?? '', 1000);
    $pub     = !empty($_POST['is_published']) ? 1 : 0;
    $order   = cleanInt($_POST['sort_order']  ?? 0);

    if (!$name || !$body) {
        setFlash('error', 'Name and testimonial text are required.');
        header('Location: testimonials.php');
        exit;
    }

    if ($id) {
        $pdo->prepare("UPDATE testimonials SET client_name=:n, service=:s, rating=:r, body=:b, is_published=:p, sort_order=:o WHERE id=:id")
            ->execute([':n'=>$name,':s'=>$service,':r'=>$rating,':b'=>$body,':p'=>$pub,':o'=>$order,':id'=>$id]);
        setFlash('success', 'Testimonial updated successfully.');
    } else {
        $pdo->prepare("INSERT INTO testimonials (client_name, service, rating, body, is_published, sort_order) VALUES (:n,:s,:r,:b,:p,:o)")
            ->execute([':n'=>$name,':s'=>$service,':r'=>$rating,':b'=>$body,':p'=>$pub,':o'=>$order]);
        setFlash('success', 'Testimonial added successfully.');
    }
    header('Location: testimonials.php');
    exit;
}

// ── Toggle publish ────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id  = cleanInt($_GET['toggle']);
    $cur = $pdo->prepare("SELECT is_published FROM testimonials WHERE id=:id");
    $cur->execute([':id'=>$id]);
    $cur = $cur->fetchColumn();
    $pdo->prepare("UPDATE testimonials SET is_published=:p WHERE id=:id")->execute([':p'=>$cur?0:1,':id'=>$id]);
    header('Location: testimonials.php?msg=toggled');
    exit;
}

// ── Delete ────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = cleanInt($_GET['delete']);
    if ($id) $pdo->prepare("DELETE FROM testimonials WHERE id=:id")->execute([':id'=>$id]);
    header('Location: testimonials.php?msg=deleted');
    exit;
}

// ── Load edit target ─────────────────────────────────────────
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id=:id");
    $stmt->execute([':id' => cleanInt($_GET['edit'])]);
    $editing = $stmt->fetch();
}

$testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY sort_order ASC, created_at DESC")->fetchAll();

adminHead('Testimonials', 'Manage client testimonials shown on the website');
?>
<style>
.test-layout{display:grid;grid-template-columns:1fr 380px;gap:1.5rem;align-items:start}
.stars-preview{color:#f59e0b;font-size:1rem;letter-spacing:2px}
.pub-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.22rem .65rem;border-radius:50px;font-size:.72rem;font-weight:600}
.pub-badge.published{background:#d1fae5;color:#065f46}
.pub-badge.draft{background:#f3f4f6;color:#6b7280}
.form-group label{font-size:.82rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem}
.form-control{width:100%;padding:.7rem .9rem;border:1.5px solid #e5e7eb;border-radius:9px;font-size:.88rem;font-family:inherit;color:#1f2937;background:#fafafa;transition:all .3s}
.form-control:focus{outline:none;border-color:var(--primary);background:white;box-shadow:0 0 0 3px rgba(90,125,124,.1)}
textarea.form-control{min-height:100px;resize:vertical}
.rating-select{display:flex;gap:.5rem;margin-top:.3rem}
.rating-select label{display:flex;align-items:center;gap:.3rem;font-size:.85rem;cursor:pointer;padding:.35rem .6rem;border:1.5px solid #e5e7eb;border-radius:8px;transition:all .2s}
.rating-select input[type=radio]:checked + .star-label-inner{color:#f59e0b}
.rating-select label:has(input:checked){border-color:#f59e0b;background:#fef9ec}
.test-card-body{padding:.9rem 1.2rem 1rem;border-bottom:1px solid #f3f4f6}
.test-card-body:last-child{border-bottom:none}
.test-quote{font-size:.85rem;color:#374151;line-height:1.7;font-style:italic;margin:.4rem 0 .7rem;background:#f9fafb;padding:.8rem 1rem;border-radius:8px;border-left:3px solid var(--accent)}
@media(max-width:900px){.test-layout{grid-template-columns:1fr}}
</style>

<?php if (isset($_GET['msg'])): ?>
<div class="flash <?= $_GET['msg']==='deleted'?'error':'success' ?>">
  <i class="fas fa-<?= $_GET['msg']==='deleted'?'trash':'check-circle' ?>"></i>
  <?= ['deleted'=>'Testimonial deleted.','toggled'=>'Visibility updated.'][$_GET['msg']] ?? 'Done.' ?>
</div>
<?php endif; ?>
<?php renderFlash(); // from flash session ?>

<div class="test-layout">

  <!-- Testimonials List -->
  <div class="panel">
    <div class="panel-head">
      <div class="panel-head-left"><i class="fas fa-star panel-icon"></i><h3>Testimonials</h3><span style="font-size:.78rem;color:var(--text-muted);margin-left:.4rem"><?= count($testimonials) ?> total</span></div>
    </div>

    <?php if (empty($testimonials)): ?>
    <div class="empty-state"><i class="fas fa-star"></i><p>No testimonials yet. Add one using the form.</p></div>
    <?php else: ?>
    <?php foreach ($testimonials as $t): ?>
    <div class="test-card-body">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap">
        <div>
          <div style="font-size:.9rem;font-weight:700;color:var(--dark)"><?= htmlspecialchars($t['client_name']) ?></div>
          <?php if ($t['service']): ?><div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($t['service']) ?></div><?php endif; ?>
          <div class="stars-preview"><?= str_repeat('★',$t['rating']) ?><span style="color:#e5e7eb"><?= str_repeat('★',5-$t['rating']) ?></span></div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.4rem">
          <span class="pub-badge <?= $t['is_published']?'published':'draft' ?>">
            <?= $t['is_published']?'✓ Published':'○ Draft' ?>
          </span>
          <span style="font-size:.7rem;color:var(--text-muted)">Order: <?= $t['sort_order'] ?></span>
        </div>
      </div>
      <div class="test-quote">"<?= htmlspecialchars($t['body']) ?>"</div>
      <div style="display:flex;gap:.4rem">
        <a href="testimonials.php?edit=<?= $t['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit"><i class="fas fa-pencil"></i></a>
        <a href="testimonials.php?toggle=<?= $t['id'] ?>" class="btn btn-sm <?= $t['is_published']?'btn-danger':'btn-success' ?>" style="font-size:.75rem">
          <?= $t['is_published']?'Unpublish':'Publish' ?>
        </a>
        <a href="testimonials.php?delete=<?= $t['id'] ?>"
           onclick="return confirm('Delete this testimonial?')"
           class="btn btn-danger btn-sm btn-icon" title="Delete"><i class="fas fa-trash-can"></i></a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Add / Edit Form -->
  <div>
    <div class="panel">
      <div class="panel-head">
        <div class="panel-head-left">
          <i class="fas fa-<?= $editing?'pencil':'plus' ?> panel-icon"></i>
          <h3><?= $editing ? 'Edit Testimonial' : 'Add Testimonial' ?></h3>
        </div>
        <?php if ($editing): ?>
        <a href="testimonials.php" class="btn btn-ghost btn-sm">Cancel</a>
        <?php endif; ?>
      </div>
      <div style="padding:1.4rem">
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="save_testimonial" value="1">
          <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

          <div class="form-group" style="margin-bottom:1rem">
            <label>Client Name <span style="color:#ef4444">*</span></label>
            <input type="text" name="client_name" class="form-control" required maxlength="100"
                   placeholder="e.g. Sarah M." value="<?= htmlspecialchars($editing['client_name']??'') ?>">
          </div>

          <div class="form-group" style="margin-bottom:1rem">
            <label>Service (optional)</label>
            <select name="service" class="form-control">
              <option value="">— Select service —</option>
              <?php foreach(['Individual Therapy','Couples Therapy','Anxiety & Depression','Life Coaching','Initial Consultation'] as $svc): ?>
              <option value="<?= htmlspecialchars($svc) ?>" <?= ($editing['service']??'')===$svc?'selected':'' ?>><?= htmlspecialchars($svc) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:1rem">
            <label>Rating</label>
            <div class="rating-select">
              <?php for($i=1;$i<=5;$i++): ?>
              <label>
                <input type="radio" name="rating" value="<?= $i ?>" <?= ($editing['rating']??5)===$i?'checked':($i===5&&!$editing?'checked':'') ?> style="position:absolute;opacity:0;width:0">
                <span class="star-label-inner" style="font-size:1.1rem;color:<?= ($editing['rating']??5)>=$i?'#f59e0b':'#d1d5db' ?>"><?= str_repeat('★',$i) ?></span>
              </label>
              <?php endfor; ?>
            </div>
          </div>

          <div class="form-group" style="margin-bottom:1rem">
            <label>Testimonial Text <span style="color:#ef4444">*</span></label>
            <textarea name="body" class="form-control" required maxlength="1000"
                      placeholder="Write or paste the client's testimonial here…"><?= htmlspecialchars($editing['body']??'') ?></textarea>
            <div style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem">Max 1000 characters</div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
            <div class="form-group" style="margin-bottom:0">
              <label>Sort Order</label>
              <input type="number" name="sort_order" class="form-control" min="0" max="999"
                     value="<?= htmlspecialchars($editing['sort_order']??'0') ?>" placeholder="0">
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label>Visibility</label>
              <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.7rem .9rem;border:1.5px solid #e5e7eb;border-radius:9px;font-size:.88rem">
                <input type="checkbox" name="is_published" value="1" <?= ($editing['is_published']??1)?'checked':'' ?> style="accent-color:var(--primary)">
                Publish on site
              </label>
            </div>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;display:flex">
            <i class="fas fa-<?= $editing?'check':'plus' ?>"></i>
            <?= $editing ? 'Update Testimonial' : 'Add Testimonial' ?>
          </button>
        </form>
      </div>
    </div>

    <div class="panel" style="margin-top:1rem">
      <div class="panel-head"><div class="panel-head-left"><i class="fas fa-circle-info panel-icon"></i><h3>Tips</h3></div></div>
      <div style="padding:1rem 1.4rem;font-size:.82rem;color:var(--text-muted);line-height:1.8">
        <p>• Use <strong>Sort Order</strong> (0 = first) to control the display sequence.</p>
        <p>• Unpublished testimonials are hidden from the site but saved here.</p>
        <p>• Keep names like "Maria K." (first name + initial) to respect privacy.</p>
        <p>• Published testimonials appear on the homepage and services page.</p>
      </div>
    </div>
  </div>
</div>

<?php adminFoot(); ?>
