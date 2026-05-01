<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/user_auth.php';
logoutUser();
setFlash('success', 'You have been logged out successfully.');
header('Location: index.php');
exit;
