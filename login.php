<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

// If already logged in, redirect
if ($user = current_user()) {
    if (in_array($user['role'], ['admin', 'staff'], true)) {
        redirect('admin/dashboard.php');
    } else {
        redirect('customer/dashboard.php');
    }
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        try {
            $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
            $stmt->execute([$email]);
            $candidate = $stmt->fetch();

            if ($candidate && password_verify($password, $candidate['password_hash'])) {
                login_user($candidate);
                audit_log((int)$candidate['id'], 'User login', 'users', (int)$candidate['id'], 'Successful login');
                flash('success', 'Welcome back, ' . $candidate['full_name'] . '!');

                if (in_array($candidate['role'], ['admin', 'staff'], true)) {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('customer/dashboard.php');
                }
            } else {
                // Generic error to avoid email enumeration
                $error = 'Invalid email address or password.';
            }
        } catch (Throwable $e) {
            $error = 'A system error occurred. Please try again later.';
        }
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>

<main class="form-page">
    <div class="form-card">
        <span class="section-tag">WELCOME BACK</span>
        <h1 class="mb-2">Log In to Your Account</h1>
        <p class="text-muted mb-4">
            Manage your laundry orders, schedule pickups, and track your clothes in real time.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= APP_URL ?>/login.php">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>" placeholder="name@domain.com">
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="password" style="margin: 0;">Password</label>
                    <a href="<?= APP_URL ?>/forgot-password.php" style="font-size: 0.82rem; font-weight: 600;">Forgot Password?</a>
                </div>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">Log In</button>
        </form>

        <div class="text-center mt-4" style="border-top: 1px solid var(--border-light); padding-top: 20px; font-size: 0.92rem;">
            Do not have an account? <a href="<?= APP_URL ?>/register.php" class="fw-bold">Register &amp; Book Now</a>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>