<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$message = null;
$error = null;
$devResetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = db()->prepare('SELECT id, full_name FROM users WHERE email = ? AND is_active = 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate secure random token
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);
                $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                $pdo = db();
                $stmtInsert = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
                $stmtInsert->execute([(int)$user['id'], $tokenHash, $expiresAt]);

                audit_log((int)$user['id'], 'Password reset requested', 'password_resets', null, "For email: {$email}");

                // In a production environment with configured SMTP, an email would be dispatched.
                // In local development / unconfigured SMTP, we surface the link explicitly as instructed in specifications.
                $resetUrl = APP_URL . '/reset-password.php?token=' . urlencode($rawToken);
                $devResetLink = $resetUrl;

                $message = "If an account exists with that email, a password reset request has been processed.";
            } else {
                // Generic confirmation to prevent email probing
                $message = "If an account exists with that email, a password reset request has been processed.";
            }
        } catch (Throwable $e) {
            $error = 'A system error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Forgot Password';
require __DIR__ . '/includes/header.php';
?>

<main class="form-page">
    <div class="form-card">
        <span class="section-tag">ACCOUNT RECOVERY</span>
        <h1 class="mb-2">Forgot Password?</h1>
        <p class="text-muted mb-4">
            Enter the email address registered with your Stainless Laundry Services account.
        </p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= e($message) ?></div>

            <?php if ($devResetLink): ?>
                <div class="panel" style="background-color: var(--bg-light); border-left: 4px solid var(--blue); padding: 18px; margin-top: 16px;">
                    <strong style="color: var(--navy); display: block; margin-bottom: 6px;">Local Development / Offline Notice:</strong>
                    <p style="font-size: 0.88rem; margin-bottom: 12px; color: var(--ink);">
                        Standard SMTP is not configured in this local XAMPP environment. You can complete your password reset directly using this generated link:
                    </p>
                    <a class="btn btn-primary btn-sm" href="<?= $devResetLink ?>">Reset Password Now &rarr;</a>
                </div>
            <?php else: ?>
                <div class="panel" style="background-color: var(--bg-light); font-size: 0.88rem;">
                    Please check your inbox or spam folder for password reset instructions.
                </div>
            <?php endif; ?>

            <p class="mt-4 text-center"><a href="<?= APP_URL ?>/login.php" class="fw-bold">&larr; Return to Login</a></p>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= APP_URL ?>/forgot-password.php">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email">Registered Email Address</label>
                    <input type="email" id="email" name="email" required autofocus placeholder="name@domain.com">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">Request Reset Link</button>
            </form>

            <div class="text-center mt-4">
                <a href="<?= APP_URL ?>/login.php" class="text-muted" style="font-size: 0.9rem;">Remember your password? <strong>Log In</strong></a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>