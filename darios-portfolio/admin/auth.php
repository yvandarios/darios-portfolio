
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Check whether an administrator is logged in.
 */
function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

/**
 * Protect admin pages.
 */
function require_admin(): void
{
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}
