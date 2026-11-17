
<?php

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$csrfToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['admin_logout_csrf'] ?? '';

if (
    !is_string($csrfToken) ||
    !is_string($sessionToken) ||
    !hash_equals($sessionToken, $csrfToken)
) {
    http_response_code(403);
    exit('Invalid logout request.');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: login.php');
exit;
