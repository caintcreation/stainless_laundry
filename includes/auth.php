<?php
declare(strict_types=1);

/**
 * Fetch the currently logged-in user profile, if any
 */
function current_user(): ?array {
    static $currentUser = null;
    static $checked = false;

    if ($checked) {
        return $currentUser;
    }

    $checked = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    try {
        $stmt = db()->prepare('
            SELECT u.*, cp.location, cp.address_details 
            FROM users u 
            LEFT JOIN customer_profiles cp ON cp.user_id = u.id 
            WHERE u.id = ? AND u.is_active = 1
        ');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            $currentUser = $user;
            return $user;
        }
    } catch (Throwable) {
        return null;
    }

    // Invalid session user ID
    unset($_SESSION['user_id']);
    return null;
}

/**
 * Require customer or admin login
 */
function require_login(): void {
    if (!current_user()) {
        flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

/**
 * Require specific roles (e.g. ['admin', 'staff'] or ['customer'])
 */
function require_role(array $roles): void {
    require_login();
    $user = current_user();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        if ($user['role'] === 'customer') {
            flash('error', 'Access denied. Staff privileges required.');
            redirect('customer/dashboard.php');
        } else {
            flash('error', 'Access denied.');
            redirect('admin/dashboard.php');
        }
    }
}

/**
 * Set session for authenticated user
 */
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
}

/**
 * Terminate session securely
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool)$params['secure'],
            (bool)$params['httponly']
        );
    }
    session_destroy();
}