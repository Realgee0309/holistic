<?php
/**
 * Admin Settings — Images & Site Content
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/settings.php';

$pdo = getDB();

// ── Handle Save ──────────────────────────────────────────────
$saveMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = __DIR__ . '/../assets/uploads/';
    $webDir    = 'assets/uploads/';

    // 1. Text / textarea / email / tel / url fields
    $textKeys = ['site_name','site_tagline','contact_phone','contact_email',
                 'therapist_name','therapist_title','hero_headline','hero_subtext','footer_tagline'];
    foreach ($textKeys as $k) {
        if (isset($_POST[$k])) {
            $val = htmlspecialchars(trim($_POST[$k]), ENT_QUOTES);
            $pdo->prepare("UPDATE site_settings SET setting_value=:v WHERE setting_key=:k")
                ->execute([':v'=>$val, ':k'=>$k]);
        }
    }

    // 2. Image uploads
    $imageKeys = ['img_hero','img_about_photo','img_about_banner',
                  'img_services_banner','img_book_banner','img_contact_banner','img_og'];
    $errors = [];
    foreach ($imageKeys as $k) {
        if (!isset($_FILES[$k]) || $_FILES[$k]['error'] === UPLOAD_ERR_NO_FILE) continue;
        if ($_FILES[$k]['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload error for $k: code " . $_FILES[$k]['error'];
            continue;
        }
        $allowed = ['image/jpeg','image/png','image/webp','image/gif','image/svg+xml'];
        $mime    = mime_content_type($_FILES[$k]['tmp_name']);
        if (!in_array($mime, $allowed)) {
            $errors[] = ucfirst(str_replace('img_','',$k)) . ': only JPG, PNG, WebP, GIF or SVG allowed.';
            continue;
        }
        $maxSize = 5 * 1024 * 1024; // 5 MB
        if ($_FILES[$k]['size'] > $maxSize) {
            $errors[] = ucfirst(str_replace('img_','',$k)) . ': file too large (max 5 MB).';
            continue;
        }
        $ext      = pathinfo($_FILES[$k]['name'], PATHINFO_EXTENSION);
        $filename = $k . '_' . time() . '.' . strtolower($ext);
        $dest     = $uploadDir . $filename;

        // Delete old file
        $old = getSetting($k);
        if ($old && file_exists(__DIR__ . '/../' . $old)) @unlink(__DIR__ . '/../' . $old);

        if (move_uploaded_file($_FILES[$k]['tmp_name'], $dest)) {
            $pdo->prepare("UPDATE site_settings SET setting_value=:v WHERE setting_key=:k")
                ->execute([':v' => $webDir . $filename, ':k' => $k]);
        } else {
            $errors[] = 'Failed to save ' . $filename . '. Check folder permissions.';
        }
    }

    // 3. Handle "Remove image" buttons
    foreach ($imageKeys as $k) {
        if (!empty($_POST['remove_' . $k])) {
            $old = getSetting($k);
            if ($old && file_exists(__DIR__ . '/../' . $old)) @unlink(__DIR__ . '/../' . $old);
            $pdo->prepare("UPDATE site_settings SET setting_value='' WHERE setting_key=:k")
                ->execute([':k' => $k]);
        }
    }

    header('Location: settings.php?saved=1' . (!empty($errors) ? '&errors=' . urlencode(implode('|', $errors)) : ''));
    exit;
}

// ── Reload fresh settings ────────────────────────────────────
$settings = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
function sv(string $key, array $s, string $d=''): string {
    return htmlspecialchars($s[$key] ?? $d);
}
function imgUrl(string $key, array $s): string {
    $v = $s[$key] ?? '';
    if (!$v) return '';
    return '/Holistic-Wellness-main/' . ltrim($v, '/');
}

adminHead('Settings', 'Manage site images & content');
?>
<style>
.settings-tabs { display:flex; gap:0.4rem; margin-bottom:1.8rem; flex-wrap:wrap; }
.stab { padding:0.6rem 1.2rem; border-radius:8px; font-size:0.85rem; font-weight:600; cursor:pointer; border:1.5px solid var(--border); color:var(--text-muted); background:var(--white); text-decoration:none; transition:var(--transition); }
.stab:hover { border-color:var(--primary); color:var(--primary); }
.stab.active { background:var(--primary); color:white; border-color:var(--primary); }
.tab-section { display:none; }
.tab-section.active { display:block; }

/* Image grid */
.img-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1.5rem; }
.img-card { background:white; border:1.5px solid var(--border); border-radius:14px; overflow:hidden; transition:var(--transition); }
.img-card:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
.img-preview {
    height:180px; background:#f3f4f6;
    display:flex; align-items:center; justify-content:center;
    overflow:hidden; position:relative;
}
.img-preview img { width:100%; height:100%; object-fit:cover; display:block; }
.img-placeholder { color:#d1d5db; text-align:center; }
.img-placeholder i { font-size:2.5rem; display:block; margin-bottom:0.5rem; }
.img-placeholder span { font-size:0.8rem; display:block; }
.img-card-body { padding:1rem 1.1rem; }
.img-card-body label { font-size:0.82rem; font-weight:700; color:#1f2937; display:block; margin-bottom:0.2rem; }
.img-card-body .desc { font-size:0.75rem; color:#9ca3af; margin-bottom:0.7rem; }
.file-input-wrap { position:relative; }
.file-input-wrap input[type="file"] { width:100%; padding:0.5rem; border:1.5px dashed #d1d5db; border-radius:8px; font-size:0.8rem; cursor:pointer; background:#fafafa; }
.file-input-wrap input[type="file"]:hover { border-color:var(--primary); background:#f0f9f8; }
.remove-btn { margin-top:0.5rem; width:100%; padding:0.4rem; background:#fee2e2; color:#991b1b; border:1px solid #fecaca; border-radius:7px; font-size:0.76rem; font-weight:600; cursor:pointer; font-family:inherit; }
.remove-btn:hover { background:#fecaca; }
.img-badge { position:absolute; top:0.5rem; right:0.5rem; background:rgba(0,0,0,0.55); color:white; font-size:0.7rem; padding:0.2rem 0.5rem; border-radius:50px; backdrop-filter:blur(4px); }

/* General settings */
.settings-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:1.2rem; }
.setting-field { }
.setting-field label { font-size:0.82rem; font-weight:700; color:#374151; display:block; margin-bottom:0.4rem; }
.setting-field .desc { font-size:0.75rem; color:#9ca3af; margin-bottom:0.5rem; }
.setting-input { width:100%; padding:0.7rem 0.9rem; border:1.5px solid #e5e7eb; border-radius:9px; font-size:0.88rem; font-family:inherit; color:#1f2937; transition:all 0.3s; background:#fafafa; }
.setting-input:focus { outline:none; border-color:var(--primary); background:white; box-shadow:0 0 0 3px rgba(90,125,124,0.1); }
textarea.setting-input { min-height:80px; resize:vertical; }
.section-divider { font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--primary); margin:1.5rem 0 1rem; padding-bottom:0.5rem; border-bottom:2px solid var(--secondary); }
.save-bar { position:sticky; bottom:0; background:white; border-top:1px solid #e5e7eb; padding:1rem 1.5rem; display:flex; justify-content:flex-end; gap:0.8rem; z-index:10; box-shadow:0 -4px 20px rgba(0,0,0,0.06); margin:0 -2rem; margin-top:1.5rem; padding-left:2rem; padding-right:2rem; }
</style>

<!-- Flash messages -->
<?php if (isset($_GET['saved'])): ?>
<div class="flash success"><i class="fas fa-check-circle"></i> Settings saved successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['errors'])): ?>
<?php foreach (explode('|', urldecode($_GET['errors'])) as $e): ?>
<div class="flash error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Tab Switcher -->
<div class="settings-tabs">
    <a href="#" class="stab active" data-tab="images"><i class="fas fa-images"></i> Images</a>
    <a href="#" class="stab" data-tab="general"><i class="fas fa-sliders"></i> General Content</a>
    <a href="#" class="stab" data-tab="contact"><i class="fas fa-address-book"></i> Contact Info</a>
</div>

<form method="POST" enctype="multipart/form-data">

<!-- ══ TAB: Images ══════════════════════════════════════════ -->
<div class="tab-section active" id="tab-images">
    <div class="panel">
        <div class="panel-head">
            <div class="panel-head-left"><i class="fas fa-images panel-icon"></i><h3>Website Images</h3></div>
            <span style="font-size:0.78rem;color:var(--text-muted)">JPG, PNG, WebP, GIF — max 5 MB each</span>
        </div>
        <div style="padding:1.5rem">
        <div class="img-grid">
        <?php
        $imgFields = [
            ['key'=>'img_hero',         'label'=>'Homepage Hero Background', 'desc'=>'Large full-width banner on the homepage. Best size: 1920×1080px'],
            ['key'=>'img_about_photo',  'label'=>'Therapist Photo',          'desc'=>'Your profile photo on the About page. Best size: 600×700px (portrait)'],
            ['key'=>'img_about_banner', 'label'=>'About Page Banner',        'desc'=>'Header background on the About page. Best size: 1920×400px'],
            ['key'=>'img_services_banner','label'=>'Services Page Banner',   'desc'=>'Header background on Services. Best size: 1920×400px'],
            ['key'=>'img_book_banner',  'label'=>'Book Page Banner',         'desc'=>'Header background on the Book page. Best size: 1920×400px'],
            ['key'=>'img_contact_banner','label'=>'Contact Page Banner',     'desc'=>'Header background on Contact. Best size: 1920×400px'],
            ['key'=>'img_og',           'label'=>'Social Share Image',       'desc'=>'Shown when someone shares your site on social. Best: 1200×630px'],
        ];
        foreach ($imgFields as $f):
            $currentUrl = imgUrl($f['key'], $settings);
        ?>
        <div class="img-card">
            <div class="img-preview">
                <?php if ($currentUrl): ?>
                <img src="<?= htmlspecialchars($currentUrl) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($f['label']) ?>">
                <span class="img-badge"><i class="fas fa-check" style="margin-right:3px"></i> Uploaded</span>
                <?php else: ?>
                <div class="img-placeholder">
                    <i class="fas fa-image"></i>
                    <span>No image uploaded</span>
                </div>
                <?php endif; ?>
            </div>
            <div class="img-card-body">
                <label><?= htmlspecialchars($f['label']) ?></label>
                <div class="desc"><?= htmlspecialchars($f['desc']) ?></div>
                <div class="file-input-wrap">
                    <input type="file" name="<?= $f['key'] ?>" accept="image/*"
                           onchange="previewImage(this, '<?= $f['key'] ?>')">
                </div>
                <?php if ($currentUrl): ?>
                <button type="submit" name="remove_<?= $f['key'] ?>" value="1" class="remove-btn"
                        onclick="return confirm('Remove this image?')">
                    <i class="fas fa-trash-can"></i> Remove Image
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        </div>
    </div>
</div>

<!-- ══ TAB: General ══════════════════════════════════════════ -->
<div class="tab-section" id="tab-general">
    <div class="panel">
        <div class="panel-head">
            <div class="panel-head-left"><i class="fas fa-pen panel-icon"></i><h3>Content & Branding</h3></div>
        </div>
        <div style="padding:1.5rem">
            <div class="section-divider">Practice Identity</div>
            <div class="settings-grid">
                <div class="setting-field">
                    <label>Practice Name</label>
                    <div class="desc">Displayed in browser tab and navigation</div>
                    <input type="text" name="site_name" class="setting-input" value="<?= sv('site_name',$settings,'Holistic Wellness') ?>">
                </div>
                <div class="setting-field">
                    <label>Tagline</label>
                    <div class="desc">Short phrase below the site name</div>
                    <input type="text" name="site_tagline" class="setting-input" value="<?= sv('site_tagline',$settings) ?>">
                </div>
                <div class="setting-field">
                    <label>Therapist Name</label>
                    <div class="desc">Shown on the About page and footer</div>
                    <input type="text" name="therapist_name" class="setting-input" value="<?= sv('therapist_name',$settings,'Dr. Jerald') ?>">
                </div>
                <div class="setting-field">
                    <label>Therapist Title / Role</label>
                    <div class="desc">Displayed below your name</div>
                    <input type="text" name="therapist_title" class="setting-input" value="<?= sv('therapist_title',$settings) ?>">
                </div>
            </div>

            <div class="section-divider">Homepage Hero</div>
            <div class="settings-grid">
                <div class="setting-field" style="grid-column:1/-1">
                    <label>Hero Headline</label>
                    <div class="desc">The big heading on the homepage</div>
                    <input type="text" name="hero_headline" class="setting-input" value="<?= sv('hero_headline',$settings) ?>">
                </div>
                <div class="setting-field" style="grid-column:1/-1">
                    <label>Hero Subtext</label>
                    <div class="desc">Paragraph below the headline</div>
                    <textarea name="hero_subtext" class="setting-input"><?= sv('hero_subtext',$settings) ?></textarea>
                </div>
            </div>

            <div class="section-divider">Footer</div>
            <div class="settings-grid">
                <div class="setting-field" style="grid-column:1/-1">
                    <label>Footer Tagline</label>
                    <div class="desc">Short description in the footer</div>
                    <textarea name="footer_tagline" class="setting-input"><?= sv('footer_tagline',$settings) ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ TAB: Contact ══════════════════════════════════════════ -->
<div class="tab-section" id="tab-contact">
    <div class="panel">
        <div class="panel-head">
            <div class="panel-head-left"><i class="fas fa-address-book panel-icon"></i><h3>Contact Information</h3></div>
        </div>
        <div style="padding:1.5rem">
            <div class="section-divider">Contact Details</div>
            <div class="settings-grid">
                <div class="setting-field">
                    <label>WhatsApp / Phone Number</label>
                    <div class="desc">Used for booking links (include country code, no spaces)</div>
                    <input type="tel" name="contact_phone" class="setting-input" value="<?= sv('contact_phone',$settings) ?>">
                </div>
                <div class="setting-field">
                    <label>Primary Email</label>
                    <div class="desc">Shown in the footer and contact page</div>
                    <input type="email" name="contact_email" class="setting-input" value="<?= sv('contact_email',$settings) ?>">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Save Bar -->
<div class="save-bar">
    <a href="index.php" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-floppy-disk"></i> Save All Settings
    </button>
</div>

</form>

<script>
// Tab switching
document.querySelectorAll('.stab').forEach(function(tab) {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.stab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
});

// Live image preview before upload
function previewImage(input, key) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const card = input.closest('.img-card');
        const preview = card.querySelector('.img-preview');
        preview.innerHTML = '<img src="' + e.target.result + '" alt="preview" style="width:100%;height:100%;object-fit:cover">' +
            '<span class="img-badge"><i class="fas fa-upload" style="margin-right:3px"></i>Ready to save</span>';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>

<?php adminFoot(); ?>
