<?php
/**
 * Client User Auth Helpers
 * Include this in any page that needs user session awareness.
 * Does NOT redirect — use requireLogin() for protected pages.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'           => $_SESSION['user_id'],
        'name'         => $_SESSION['user_name'],
        'email'        => $_SESSION['user_email'],
        'is_anonymous' => $_SESSION['user_anonymous'] ?? 0,
    ];
}

/** Redirect to login if not authenticated. */
function requireLogin(string $redirect = ''): void {
    if (!isLoggedIn()) {
        $back = $redirect ?: $_SERVER['REQUEST_URI'];
        header('Location: /Holistic-Wellness-main/login.php?redirect=' . urlencode($back));
        exit;
    }
}

/** Display name respecting anonymity (admin sees real name, client sees their own). */
function displayName(array $user, bool $isAdmin = false): string {
    if ($user['is_anonymous'] && !$isAdmin) return 'Anonymous User';
    return htmlspecialchars($user['name']);
}

/** Set user session after login. */
function loginUser(array $user): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['user_id']        = $user['id'];
    $_SESSION['user_name']      = $user['name'];
    $_SESSION['user_email']     = $user['email'];
    $_SESSION['user_anonymous'] = $user['is_anonymous'];
    session_regenerate_id(true);
}

/** Destroy user session. */
function logoutUser(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION = [];
    session_destroy();
}
