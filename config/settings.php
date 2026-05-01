<?php
/**
 * Site Settings Helper
 * Call getSetting($key, $default) anywhere in the app.
 * Results are cached in a static variable so the DB is only hit once per request.
 */
function getSetting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        try {
            $pdo   = getDB();
            $rows  = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
            $cache = $rows;
        } catch (Exception $e) {
            $cache = [];
        }
    }
    $val = $cache[$key] ?? '';
    return ($val !== '' && $val !== null) ? $val : $default;
}

/**
 * Return a URL-ready path for a stored image setting.
 * Falls back to $default if no image has been uploaded.
 */
function getImageSetting(string $key, string $default = ''): string {
    $val = getSetting($key, '');
    if ($val === '') return $default;
    // Already a full URL or starts with /
    if (str_starts_with($val, 'http') || str_starts_with($val, '/')) return $val;
    return '/Holistic-Wellness-main/' . ltrim($val, '/');
}

/**
 * Return an inline style for a background image setting, with optional overlay.
 * Usage: echo bgImageStyle('img_hero');
 */
function bgImageStyle(string $key, string $fallbackColor = '', string $overlay = 'rgba(90,125,124,0.55)'): string {
    $url = getImageSetting($key);
    if ($url === '') {
        return $fallbackColor ? "style=\"background:{$fallbackColor}\"" : '';
    }
    $bg = "url('{$url}') center/cover no-repeat";
    if ($overlay) {
        $bg = "linear-gradient({$overlay},{$overlay}), {$bg}";
    }
    return "style=\"background:{$bg}\"";
}
