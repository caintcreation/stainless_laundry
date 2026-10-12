<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pdo = db();

// Check if an admin already exists
$stmt = $pdo->query("SELECT id, email, full_name, role FROM users WHERE role = 'admin' LIMIT 1");
$admin = $stmt->fetch();

$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' || (PHP_SAPI === 'cli' && empty($admin))) {
    $email = strtolower(trim($_POST['email'] ?? 'admin@stainlesslaundry.co.ke'));
    $name = trim($_POST['full_name'] ?? 'Stainless Administrator');
    $phone = trim($_POST['phone'] ?? '0101476174');
    $password = $_POST['password'] ?? 'Admin@2026';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        if ($admin) {
            // Update existing admin password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET password_hash = ?, is_active = 1 WHERE id = ?');
            $update->execute([$hash, $admin['id']]);
            $message = "Admin password updated for {$admin['email']}.";
        } else {
            // Insert new admin
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (full_name, email, phone, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, 1)');
            $insert->execute([$name, $email, $phone, $hash, 'admin']);
            $newId = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO customer_profiles (user_id, location, address_details) VALUES (?, ?, ?)')->execute([$newId, 'Ndagani, Chuka', 'Main Laundry Office']);
            $message = "Admin created successfully! Email: {$email}";
        }
    }
}

if (PHP_SAPI === 'cli') {
    echo $message ?: ($error ?: ($admin ? "Admin already exists: {$admin['email']}\n" : "No admin found.\n"));
    exit;
}

$pageTitle = 'Admin Setup';
require __DIR__ . '/includes/header.php';
?>
<main class="form-page">
    <div class="form-card">
        <p class="eyebrow blue">SYSTEM INITIALIZATION</p>
        <h1>Administrator Setup</h1>
        <p class="muted">Initialize or update the administrative credentials for STAINLESS LAUNDRY SERVICES.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= e($message) ?></div>
            <p><a class="btn btn-primary" href="<?= APP_URL ?>/admin/login.php">Proceed to Admin Login</a></p>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($admin): ?>
                <div class="alert alert-info">
                    An administrator is already configured (<strong><?= e($admin['email']) ?></strong>). You can update the password below if needed.
                </div>
            <?php endif; ?>

            <form method="post" class="mt-4">
                <?= csrf_field() ?>
                <label>Admin Full Name
                    <input type="text" name="full_name" value="<?= e($admin['full_name'] ?? 'Stainless Administrator') ?>" required>
                </label>

                <label>Admin Email Address
                    <input type="email" name="email" value="<?= e($admin['email'] ?? 'admin@stainlesslaundry.co.ke') ?>" required>
                </label>

                <label>Phone Number
                    <input type="text" name="phone" value="0101476174" required>
                </label>

                <label>Password (min. 8 characters)
                    <input type="password" name="password" placeholder="Enter secure admin password" required minlength="8">
                </label>

                <button class="btn btn-primary btn-block" type="submit"><?= $admin ? 'Update Admin Password' : 'Create Admin Account' ?></button>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
