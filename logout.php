<?php
/**
 * Logout Handler
 * Destroys session and redirects to login page
 */

// Scope-aware logout: `scope=admin` clears only the admin session (ADMINSESSID)
// otherwise treat as user logout and clear the default session.
$scope = $_GET['scope'] ?? 'user';

if ($scope === 'admin') {
    // Clear only the admin session/cookie and mark DB record inactive
    if (isset($_COOKIE['ADMINSESSID'])) {
        session_name('ADMINSESSID');
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $adminSessionId = session_id();

        if (file_exists(__DIR__ . '/db_config.php')) {
            try {
                require_once __DIR__ . '/db_config.php';
                if (isset($pdo) && $adminSessionId) {
                    $upd = $pdo->prepare('UPDATE adminsessions SET is_active = 0 WHERE session_id = ?');
                    $upd->execute([$adminSessionId]);
                }
            } catch (Exception $e) {
                // ignore DB errors
            }
        }

        // Clear admin session and cookie
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie('ADMINSESSID', '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
} else {
    // Default: clear the normal (user) session only
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// Redirect to login page
header('Location: login.php');
exit;
?>

