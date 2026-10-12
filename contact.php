<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = 'Contact Us';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_once __DIR__ . '/includes/validation.php';
    $errors = validate_contact($_POST);

    if (empty($errors)) {
        try {
            $stmt = db()->prepare('INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['name'] ?? ''),
                strtolower(trim($_POST['email'] ?? '')),
                trim($_POST['phone'] ?? ''),
                trim($_POST['message'] ?? '')
            ]);
            $success = true;
            flash('success', 'Thank you! Your message has been received. We will respond shortly.');
            redirect('contact.php');
        } catch (Throwable) {
            $errors['general'] = 'An unexpected database error occurred. Please contact us via WhatsApp.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="section section-bg-light" style="padding: 50px 0;">
        <div class="container text-center">
            <span class="section-tag">GET IN TOUCH</span>
            <h1 class="section-title">We Are Here to Help</h1>
            <p class="section-subtext" style="max-width: 650px; margin: 0 auto;">
                Have questions about our services or need urgent laundry assistance? Reach out to Stainless Laundry Services.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px; align-items: start;">
                <!-- Contact Information -->
                <div class="panel">
                    <h2 class="mb-3" style="font-size: 1.4rem;">Office &amp; Pickup Desk</h2>

                    <div class="footer-contact-item" style="color: var(--ink);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--blue); flex-shrink: 0;"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                        <div>
                            <strong>Physical Location:</strong><br>
                            Around Ndagani, Chuka, Kenya<br>
                            <small class="text-muted">Convenient pickup coverage across all student hostels and residences.</small>
                        </div>
                    </div>

                    <div class="footer-contact-item" style="color: var(--ink); margin-top: 18px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--blue); flex-shrink: 0;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <div>
                            <strong>Phone / WhatsApp:</strong><br>
                            <a href="tel:<?= BUSINESS_PHONE ?>" style="color: var(--navy); font-weight: 700; font-size: 1.1rem;"><?= BUSINESS_PHONE ?></a><br>
                            <small class="text-muted">Direct calls, SMS, and WhatsApp messaging.</small>
                        </div>
                    </div>

                    <div class="footer-contact-item" style="color: var(--ink); margin-top: 18px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--blue); flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <div>
                            <strong>Business Hours:</strong><br>
                            Monday &ndash; Saturday: 7:00 AM &ndash; 7:30 PM<br>
                            Sunday: 9:00 AM &ndash; 5:00 PM
                        </div>
                    </div>

                    <div class="mt-4">
                        <a class="btn btn-whatsapp btn-block" href="https://wa.me/<?= BUSINESS_WHATSAPP ?>" target="_blank" rel="noopener noreferrer">
                            Open Chat on WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="panel">
                    <h2 class="mb-3" style="font-size: 1.4rem;">Send a Message</h2>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-error"><?= e($errors['general']) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= APP_URL ?>/contact.php">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="name">Your Full Name *</label>
                            <input type="text" id="name" name="name" required value="<?= e($_POST['name'] ?? '') ?>" placeholder="e.g. John Mwangi">
                            <?php if (!empty($errors['name'])): ?><div class="form-error-msg"><?= e($errors['name']) ?></div><?php endif; ?>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="e.g. john@example.com">
                                <?php if (!empty($errors['email'])): ?><div class="form-error-msg"><?= e($errors['email']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="0101476174">
                                <?php if (!empty($errors['phone'])): ?><div class="form-error-msg"><?= e($errors['phone']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" required placeholder="Let us know how we can help you..."><?= e($_POST['message'] ?? '') ?></textarea>
                            <?php if (!empty($errors['message'])): ?><div class="form-error-msg"><?= e($errors['message']) ?></div><?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Send Inquiries</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>