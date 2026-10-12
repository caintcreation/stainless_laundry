<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$unreadNotifs = $user ? unread_notifications_count($user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800;900&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
    <!-- Top Utility Information Bar -->
    <div class="top-bar">
        <div class="container top-bar-inner">
            <div class="top-bar-info">
                <span class="top-bar-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                    <span>Location: <strong>Around Ndagani, Chuka</strong></span>
                </span>
                <span class="top-bar-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span>Call / WhatsApp: <a href="tel:<?= BUSINESS_PHONE ?>"><?= BUSINESS_PHONE ?></a></span>
                </span>
            </div>
            <div class="top-bar-links">
                <?php if ($user && in_array($user['role'], ['admin', 'staff'], true)): ?>
                    <a href="<?= APP_URL ?>/admin/dashboard.php">Staff Dashboard (<?= e($user['full_name']) ?>)</a>
                <?php elseif ($user): ?>
                    <a href="<?= APP_URL ?>/customer/dashboard.php">Welcome, <?= e(explode(' ', $user['full_name'])[0]) ?></a>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/admin/login.php">Staff Portal</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="site-header">
        <div class="container nav-wrap">
            <a class="brand-link" href="<?= APP_URL ?>/index.php" title="<?= APP_NAME ?>">
                <img src="<?= APP_URL ?>/assets/images/logo.svg" alt="<?= APP_NAME ?>" class="brand-logo-img">
            </a>

            <button class="menu-toggle" type="button" aria-label="Toggle navigation menu" aria-expanded="false" data-menu-toggle>
                &#9776; Menu
            </button>

            <nav class="site-nav" data-menu>
                <a class="nav-link <?= $currentScript === 'index.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php">Home</a>
                <a class="nav-link <?= $currentScript === 'services.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/services.php">Services</a>
                <a class="nav-link <?= $currentScript === 'pricing.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/pricing.php">Pricing</a>
                <a class="nav-link <?= $currentScript === 'track-order.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/track-order.php">Track Order</a>
                <a class="nav-link <?= $currentScript === 'about.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/about.php">About</a>
                <a class="nav-link <?= $currentScript === 'contact.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/contact.php">Contact</a>

                <div class="nav-actions">
                    <?php if ($user): ?>
                        <?php if ($user['role'] === 'customer'): ?>
                            <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/dashboard.php">
                                My Account <?= $unreadNotifs > 0 ? "({$unreadNotifs})" : '' ?>
                            </a>
                            <a class="btn btn-accent btn-sm" href="<?= APP_URL ?>/customer/place-order.php">Book Pickup</a>
                        <?php else: ?>
                            <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/dashboard.php">Admin Panel</a>
                        <?php endif; ?>
                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/logout.php">Logout</a>
                    <?php else: ?>
                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/login.php">Login</a>
                        <a class="btn btn-accent btn-sm" href="<?= APP_URL ?>/register.php">Book Now</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Global Flash Notification Messages -->
    <div class="container mt-2">
        <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success" role="alert"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('error')): ?>
            <div class="alert alert-error" role="alert"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('info')): ?>
            <div class="alert alert-info" role="alert"><?= e($msg) ?></div>
        <?php endif; ?>
    </div>