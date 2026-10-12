<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

// If already logged in as staff/admin, go to dashboard
if ($user = current_user()) {
    if (in_array($user['role'], ['admin', 'staff'], true)) {
        redirect('admin/dashboard.php');
    } else {
        flash('error', 'You must log in with an administrative staff account.');
    }
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter staff credentials.';
    } else {
        try {
            $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'staff') AND is_active = 1");
            $stmt->execute([$email]);
            $candidate = $stmt->fetch();

            if ($candidate && password_verify($password, $candidate['password_hash'])) {
                login_user($candidate);
                audit_log((int)$candidate['id'], 'Staff login', 'users', (int)$candidate['id'], 'Admin/Staff logged into management console');
                flash('success', 'Logged in to Stainless Laundry Administration.');
                redirect('admin/dashboard.php');
            } else {
                $error = 'Invalid administrative credentials.';
            }
        } catch (Throwable) {
            $error = 'A system error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Staff Management Login';
require __DIR__ . '/../includes/header.php';
?>

<main class="form-page">
    <div class="form-card">
        <span class="section-tag" style="color: var(--yellow); background-color: var(--navy); padding: 4px 10px; border-radius: 4px; display: inline-block;">STAFF PORTAL</span>
        <h1 class="mb-2 mt-2">Staff &amp; Admin Login</h1>
        <p class="text-muted mb-4">
            Authorized administrative access for Stainless Laundry Services operations, tracking updates, and financial logs.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= APP_URL ?>/admin/login.php">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email">Staff Email Address</label>
                <input type="email" id="email" name="email" required autofocus placeholder="admin@stainlesslaundry.co.ke" value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter administrative password">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg mt-3">Access Admin Panel</button>
        </form>

        <div class="text-center mt-4" style="font-size: 0.88rem; color: var(--muted);">
            Customer looking for orders? <a href="<?= APP_URL ?>/login.php" class="fw-bold">Customer Login Here</a>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>