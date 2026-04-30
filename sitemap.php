<?php
/**
 * Sitemap Generator — /sitemap.php
 * Outputs dynamic XML sitemap for search engines.
 * Submit URL: http://localhost/Holistic-Wellness-main/sitemap.php
 */
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex'); // The sitemap itself shouldn't be indexed

$baseUrl = 'http://localhost/Holistic-Wellness-main'; // Change to real domain in production

$pdo = getDB();

// Static pages
$staticPages = [
    ['loc' => '',             'priority' => '1.0',  'changefreq' => 'weekly'],
    ['loc' => '/about.php',   'priority' => '0.8',  'changefreq' => 'monthly'],
    ['loc' => '/services.php','priority' => '0.9',  'changefreq' => 'monthly'],
    ['loc' => '/book.php',    'priority' => '0.9',  'changefreq' => 'weekly'],
    ['loc' => '/blog.php',    'priority' => '0.8',  'changefreq' => 'weekly'],
    ['loc' => '/faq.php',     'priority' => '0.7',  'changefreq' => 'monthly'],
    ['loc' => '/contact.php', 'priority' => '0.7',  'changefreq' => 'monthly'],
    ['loc' => '/privacy.php', 'priority' => '0.3',  'changefreq' => 'yearly'],
    ['loc' => '/login.php',   'priority' => '0.4',  'changefreq' => 'yearly'],
    ['loc' => '/register.php','priority' => '0.4',  'changefreq' => 'yearly'],
];

// Dynamic blog posts
$posts = $pdo->query("SELECT slug, updated_at FROM blog_posts WHERE is_published=1 ORDER BY updated_at DESC")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages
foreach ($staticPages as $p) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($baseUrl . $p['loc'] ?: '/index.php') . '</loc>' . "\n";
    echo '    <changefreq>' . $p['changefreq'] . '</changefreq>' . "\n";
    echo '    <priority>' . $p['priority'] . '</priority>' . "\n";
    echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '  </url>' . "\n";
}

// Blog posts
foreach ($posts as $post) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($baseUrl . '/blog_post.php?slug=' . $post['slug']) . '</loc>' . "\n";
    echo '    <changefreq>monthly</changefreq>' . "\n";
    echo '    <priority>0.6</priority>' . "\n";
    echo '    <lastmod>' . date('Y-m-d', strtotime($post['updated_at'])) . '</lastmod>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>';
