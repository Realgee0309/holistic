<?php
/**
 * CSRF Protection + Input Hardening Helpers
 * Include via config/security.php — required by every form page.
 */

// ── CSRF ────────────────────────────────────────────────────

/**
 * Generate a CSRF token tied to the current session.
 * Stores token in $_SESSION for lightweight validation.
 */
function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_expires']) || time() > $_SESSION['csrf_expires']) {
        $_SESSION['csrf_token']   = bin2hex(random_bytes(32));
        $_SESSION['csrf_expires'] = time() + 7200; // 2 hours
    }
    return $_SESSION['csrf_token'];
}

/**
 * Emit a hidden CSRF input field — drop inside every <form>.
 */
function csrfField(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES) . '">';
}

/**
 * Verify CSRF token from POST data. Kills request on failure.
 */
function csrfVerify(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $submitted = trim($_POST['_csrf'] ?? '');
    $stored    = $_SESSION['csrf_token'] ?? '';
    $expires   = $_SESSION['csrf_expires'] ?? 0;

    if (!$submitted || !$stored || !hash_equals($stored, $submitted) || time() > $expires) {
        http_response_code(403);
        // Try to redirect back nicely
        $ref = $_SERVER['HTTP_REFERER'] ?? null;
        if (function_exists('setFlash')) {
            setFlash('error', 'Security check failed. Please try again.');
        }
        if ($ref) {
            header('Location: ' . $ref);
        } else {
            die('<h2 style="font-family:sans-serif;color:#721c24;padding:2rem">Security check failed. Please go back and try again.</h2>');
        }
        exit;
    }
    // Rotate token after successful verification
    unset($_SESSION['csrf_token'], $_SESSION['csrf_expires']);
}

// ── Input Sanitisation Helpers ──────────────────────────────

/**
 * Sanitise a plain text input — strips tags, encodes entities.
 */
function cleanStr(string $val, int $maxLen = 255): string {
    return htmlspecialchars(mb_substr(trim(strip_tags($val)), 0, $maxLen), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Validate and return a clean email or empty string.
 */
function cleanEmail(string $val): string {
    $val = mb_strtolower(trim($val));
    return filter_var($val, FILTER_VALIDATE_EMAIL) ? $val : '';
}

/**
 * Sanitise textarea content (strips tags, preserves newlines).
 */
function cleanText(string $val, int $maxLen = 5000): string {
    $val = mb_substr(trim($val), 0, $maxLen);
    return htmlspecialchars(strip_tags($val), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Return a safe positive integer or 0.
 */
function cleanInt(mixed $val): int {
    return max(0, (int) $val);
}

/**
 * Validate a date string (Y-m-d). Returns '' on failure.
 */
function cleanDate(string $val): string {
    $d = DateTime::createFromFormat('Y-m-d', $val);
    return ($d && $d->format('Y-m-d') === $val) ? $val : '';
}

// ── Rate Limiting (file-based, XAMPP-friendly) ───────────────

/**
 * Simple rate limiter using session counters.
 * $key   — unique action key (e.g. 'login', 'contact')
 * $limit — max attempts
 * $window — seconds
 */
function rateLimitCheck(string $key, int $limit = 5, int $window = 300): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $now    = time();
    $sKey   = 'rl_' . $key;
    $tKey   = 'rl_' . $key . '_t';

    if (empty($_SESSION[$tKey]) || ($now - $_SESSION[$tKey]) > $window) {
        $_SESSION[$sKey] = 0;
        $_SESSION[$tKey] = $now;
    }

    $_SESSION[$sKey]++;
    return $_SESSION[$sKey] <= $limit;
}

/**
 * Reset rate limit for a key (on success).
 */
function rateLimitReset(string $key): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    unset($_SESSION['rl_' . $key], $_SESSION['rl_' . $key . '_t']);
}

// ── Security Headers ─────────────────────────────────────────

/**
 * Send recommended security headers. Call once in header.php.
 */
function sendSecurityHeaders(): void {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Light CSP — adjust if you load external resources
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://fonts.gstatic.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: blob:; connect-src 'self';");
}
