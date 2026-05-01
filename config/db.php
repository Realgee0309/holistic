<?php
/**
 * Database Configuration — Holistic Wellness
 * Adjust DB_NAME, DB_USER, DB_PASS to match your XAMPP setup.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'holistic_wellness');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: empty password
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Show friendly message; log real error server-side
            error_log('DB Connection failed: ' . $e->getMessage());
            die('<div style="font-family:sans-serif;padding:2rem;color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;border-radius:8px;max-width:600px;margin:3rem auto;">
                    <h2>⚠ Database Connection Error</h2>
                    <p>Could not connect to the MySQL database. Please check that:</p>
                    <ul style="margin-top:1rem;line-height:2">
                        <li>XAMPP MySQL service is running</li>
                        <li>The database <strong>' . DB_NAME . '</strong> has been created</li>
                        <li>Credentials in <code>config/db.php</code> are correct</li>
                    </ul>
                 </div>');
        }
    }
    return $pdo;
}

/**
 * Flash message helpers (session-based).
 */
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $color = $flash['type'] === 'success' ? '#d4edda' : '#f8d7da';
    $border = $flash['type'] === 'success' ? '#c3e6cb' : '#f5c6cb';
    $text   = $flash['type'] === 'success' ? '#155724' : '#721c24';
    $icon   = $flash['type'] === 'success' ? '✅' : '⚠';
    echo "<div class='flash-message' style='background:{$color};border:1px solid {$border};color:{$text};padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;font-weight:500;'>
            {$icon} " . htmlspecialchars($flash['message']) . "
          </div>";
}
