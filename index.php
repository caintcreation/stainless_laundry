<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = 'Fresh Clothes, Happy You!';
$contactSuccess = false;
$contactErrors = [];

// Handle Contact Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    verify_csrf();
    require_once __DIR__ . '/includes/validation.php';
    $contactErrors = validate_contact($_POST);

    if (empty($contactErrors)) {
        try {
            $stmt = db()->prepare('INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['name'] ?? ''),
                strtolower(trim($_POST['email'] ?? '')),
                trim($_POST['phone'] ?? ''),
                trim($_POST['message'] ?? '')
            ]);
            $contactSuccess = true;
            flash('success', 'Thank you! Your message has been sent to Stainless Laundry Services.');
        } catch (Throwable) {
            $contactErrors['general'] = 'Unable to send message right now. Please call or WhatsApp us directly.';
        }
    }
}

// Fetch live database prices for the poster tables
$pricingData = [];
try {
    $stmt = db()->query('SELECT so.*, s.name as service_name FROM service_options so JOIN services s ON s.id = so.service_id WHERE so.is_active = 1 ORDER BY so.service_id, so.id');
    while ($row = $stmt->fetch()) {
        $pricingData[$row['service_id']][] = $row;
    }
} catch (Throwable) {
    // Graceful fallback if query fails
}

require __DIR__ . '/includes/header.php';
?>

<main>
    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="container hero-grid">
            <div>
                <span class="hero-tag">CLEAN | DRY | FOLD</span>
                <h1 class="hero-headline">
                    Fresh Clothes,<br>
                    <span class="highlight">Happy You!</span>
                </h1>
                <p class="hero-description">
                    Reliable laundry services around Ndagani, Chuka. We wash, dry, fold, and return your clothes with care. Book a pickup and let us take care of your laundry.
                </p>
                <div class="hero-buttons">
                    <a class="btn btn-primary btn-lg" href="<?= APP_URL ?>/customer/place-order.php">Book Laundry Pickup</a>
                    <a class="btn btn-secondary btn-lg" href="<?= APP_URL ?>/pricing.php">View Our Prices</a>
                    <a class="btn btn-whatsapp btn-lg" href="https://wa.me/<?= BUSINESS_WHATSAPP ?>" target="_blank" rel="noopener noreferrer">
                        WhatsApp Us
                    </a>
                </div>
                <div class="hero-location-badge">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--blue);"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                    <span>Serving Ndagani &amp; Chuka University vicinity &bull; Call <strong><?= BUSINESS_PHONE ?></strong></span>
                </div>
            </div>
            <div class="hero-image-wrap">
                <img src="<?= APP_URL ?>/assets/images/poster.jpg" alt="Stainless Laundry Services Official Poster" class="hero-card-img">
                <div class="hero-float-badge">
                    <strong>Your Laundry, Our Priority!</strong>
                    <span>Free pickup &amp; delivery around Ndagani</span>
                </div>
            </div>
        </div>
    </section>

    <!-- POSTER 4 FEATURE PILLS STRIP -->
    <section class="feature-strip">
        <div class="container feature-grid">
            <div class="feature-pill">
                <div class="feature-pill-header">
                    <span class="feature-pill-icon">&#10022;</span>
                    <h3>CLEANING</h3>
                </div>
                <p>Removes tough stains &amp; thoroughly freshens up fabrics.</p>
            </div>
            <div class="feature-pill">
                <div class="feature-pill-header">
                    <span class="feature-pill-icon">&#9728;</span>
                    <h3>DRYING</h3>
                </div>
                <p>Quick and proper temperature drying with fresh finish.</p>
            </div>
            <div class="feature-pill">
                <div class="feature-pill-header">
                    <span class="feature-pill-icon">&#9638;</span>
                    <h3>FOLDING</h3>
                </div>
                <p>Neat, wrinkle-free organization ready to place in your wardrobe.</p>
            </div>
            <div class="feature-pill">
                <div class="feature-pill-header">
                    <span class="feature-pill-icon">&#128666;</span>
                    <h3>FREE PICK UP &amp; DELIVERY</h3>
                </div>
                <p>Convenient doorstep pickup and drop-off around Ndagani, Chuka.</p>
            </div>
        </div>
    </section>

    <!-- POSTER PRICING SECTION -->
    <section class="section section-bg-light" id="pricing-preview">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">EXPERT LAUNDRY SERVICES</span>
                <h2 class="section-title">Transparent Poster Pricing</h2>
                <p class="section-subtext">
                    All prices are fixed and affordable with no hidden charges. Check out our standard basket and duvet cleaning rates.
                </p>
            </div>

            <div class="pricing-grid">
                <!-- Basket Pricing Card -->
                <div class="poster-price-card">
                    <div class="poster-price-header">
                        <svg viewBox="0 0 24 24"><path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12z"/></svg>
                        <h3>LAUNDRY BASKET</h3>
                    </div>
                    <div class="poster-price-body">
                        <?php if (!empty($pricingData[1])): ?>
                            <?php foreach ($pricingData[1] as $item): ?>
                                <div class="price-row-item">
                                    <span class="price-item-label"><?= e($item['option_name']) ?></span>
                                    <span class="price-item-pill"><?= number_format((float)$item['unit_price'], 0) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="price-row-item"><span class="price-item-label">Small</span><span class="price-item-pill">250</span></div>
                            <div class="price-row-item"><span class="price-item-label">Medium</span><span class="price-item-pill">300</span></div>
                            <div class="price-row-item"><span class="price-item-label">Large Narrow</span><span class="price-item-pill">350</span></div>
                            <div class="price-row-item"><span class="price-item-label">Large Wide</span><span class="price-item-pill">400</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="poster-price-footer">
                        <a class="btn btn-primary btn-block" href="<?= APP_URL ?>/customer/place-order.php">Book Basket Wash</a>
                    </div>
                </div>

                <!-- Duvet Pricing Card -->
                <div class="poster-price-card">
                    <div class="poster-price-header">
                        <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/></svg>
                        <h3>DUVET</h3>
                    </div>
                    <div class="poster-price-body">
                        <?php if (!empty($pricingData[2])): ?>
                            <?php foreach ($pricingData[2] as $item): ?>
                                <div class="price-row-item">
                                    <span class="price-item-label"><?= e($item['option_name']) ?></span>
                                    <span class="price-item-pill"><?= number_format((float)$item['unit_price'], 0) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="price-row-item"><span class="price-item-label">3 &times; 4</span><span class="price-item-pill">250</span></div>
                            <div class="price-row-item"><span class="price-item-label">4 &times; 6</span><span class="price-item-pill">300</span></div>
                            <div class="price-row-item"><span class="price-item-label">5 &times; 6</span><span class="price-item-pill">350</span></div>
                            <div class="price-row-item"><span class="price-item-label">6 &times; 6</span><span class="price-item-pill">400</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="poster-price-footer">
                        <a class="btn btn-primary btn-block" href="<?= APP_URL ?>/customer/place-order.php">Book Duvet Clean</a>
                    </div>
                </div>
            </div>

            <p class="poster-price-note">
                * Note: Final amount should be confirmed by the business when oversized or special treatment is required. Free pickup applies around Ndagani and Chuka.
            </p>
        </div>
    </section>

    <!-- PRIMARY SERVICES SECTION -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">COMPREHENSIVE CARE</span>
                <h2 class="section-title">Our Laundry Services</h2>
                <p class="section-subtext">
                    Whether you are an active student, busy professional, or family, we handle your garments with the highest hygiene standards.
                </p>
            </div>

            <div class="services-card-grid">
                <div class="service-box">
                    <div class="service-box-header">
                        <div class="service-icon-box">&#129530;</div>
                        <h3>Laundry Basket Washing</h3>
                    </div>
                    <p>Everyday clothes washed with quality detergent and fabric softeners. We sort colors and delicates carefully.</p>
                    <div class="service-box-foot">
                        <span class="service-price-hint">From KES 250</span>
                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/place-order.php">Book Service</a>
                    </div>
                </div>

                <div class="service-box">
                    <div class="service-box-header">
                        <div class="service-icon-box">&#128716;</div>
                        <h3>Duvet Cleaning</h3>
                    </div>
                    <p>Deep clean for duvets, blankets, heavy quilts, and bedspreads. Thorough drying ensures zero moisture or odor.</p>
                    <div class="service-box-foot">
                        <span class="service-price-hint">From KES 250</span>
                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/place-order.php">Book Service</a>
                    </div>
                </div>

                <div class="service-box">
                    <div class="service-box-header">
                        <div class="service-icon-box">&#10024;</div>
                        <h3>Washing, Drying &amp; Folding</h3>
                    </div>
                    <p>Complete end-to-end service. Clothes come back completely dried, crisply folded, and wrapped ready to wear.</p>
                    <div class="service-box-foot">
                        <span class="service-price-hint">Full Service</span>
                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/place-order.php">Book Service</a>
                    </div>
                </div>

                <div class="service-box">
                    <div class="service-box-header">
                        <div class="service-icon-box">&#129519;</div>
                        <h3>Ironing &amp; Pressing</h3>
                    </div>
                    <p>Professional steam pressing for shirts, trousers, suits, and dresses. Crisp finish for classes and events.</p>
                    <div class="service-box-foot">
                        <span class="service-price-hint">On Request</span>
                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/place-order.php">Book Service</a>
                    </div>
                </div>

                <div class="service-box">
                    <div class="service-box-header">
                        <div class="service-icon-box">&#128666;</div>
                        <h3>Pickup &amp; Delivery</h3>
                    </div>
                    <p>Scheduled pickup right from your hostel, apartment, or estate in Ndagani and fast delivery back upon completion.</p>
                    <div class="service-box-foot">
                        <span class="service-price-hint">Included Nearby</span>
                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/place-order.php">Book Service</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS (TIMELINE) -->
    <section class="section section-bg-light">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">SIMPLE PROCESS</span>
                <h2 class="section-title">How It Works</h2>
                <p class="section-subtext">
                    Experience effortless laundry in four simple steps without ever interrupting your daily routine.
                </p>
            </div>

            <div class="timeline-four">
                <div class="timeline-step">
                    <span class="timeline-step-badge">1</span>
                    <h3>Book a Pickup</h3>
                    <p>Create an account and specify your laundry basket or duvet sizes, preferred pickup time, and location in Ndagani or Chuka.</p>
                </div>
                <div class="timeline-step">
                    <span class="timeline-step-badge">2</span>
                    <h3>We Collect</h3>
                    <p>Our dedicated staff arrives on schedule at your doorstep to safely collect your laundry load and register the order.</p>
                </div>
                <div class="timeline-step">
                    <span class="timeline-step-badge">3</span>
                    <h3>Professional Care</h3>
                    <p>Your garments are sorted, washed with premium detergents, properly tumble-dried, and neatly folded with thorough quality checks.</p>
                </div>
                <div class="timeline-step">
                    <span class="timeline-step-badge">4</span>
                    <h3>Fresh Delivery</h3>
                    <p>We deliver your crisp, fresh laundry straight back to your hostel or doorstep ready for you to enjoy.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- LAUNDRY TRACKING PREVIEW -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">REAL-TIME ORDER TRACKING</span>
                <h2 class="section-title">Follow Every Stage of Your Laundry</h2>
                <p class="section-subtext">
                    No more guessing when your clothes will be ready. From collection to final delivery, you can track live updates recorded by our team.
                </p>
            </div>

            <div class="tracking-stepper-box">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-light); padding-bottom: 14px;">
                    <div>
                        <strong style="color: var(--navy); font-size: 1.1rem;">Sample Live Order Workflow</strong>
                        <p class="text-muted" style="font-size: 0.85rem; margin: 0;">12 distinct milestone checkpoints recorded by Stainless Laundry staff</p>
                    </div>
                    <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/track-order.php">Track Real Order</a>
                </div>

                <ul class="tracking-stepper-list">
                    <li class="tracking-stepper-item completed"><span class="step-dot">&#10003;</span> 1. Order Placed</li>
                    <li class="tracking-stepper-item completed"><span class="step-dot">&#10003;</span> 2. Pickup Scheduled</li>
                    <li class="tracking-stepper-item completed"><span class="step-dot">&#10003;</span> 3. Laundry Collected</li>
                    <li class="tracking-stepper-item completed"><span class="step-dot">&#10003;</span> 4. At Laundry Facility</li>
                    <li class="tracking-stepper-item current"><span class="step-dot">&#9654;</span> 5. Washing in Progress</li>
                    <li class="tracking-stepper-item"><span class="step-dot">6</span> 6. Drying in Progress</li>
                    <li class="tracking-stepper-item"><span class="step-dot">7</span> 7. Folding &amp; Check</li>
                    <li class="tracking-stepper-item"><span class="step-dot">8</span> 8. Ready for Delivery</li>
                    <li class="tracking-stepper-item"><span class="step-dot">9</span> 9. Out for Delivery</li>
                    <li class="tracking-stepper-item"><span class="step-dot">10</span> 10. Delivered</li>
                </ul>

                <p style="font-size: 0.88rem; color: var(--muted); margin-top: 24px; text-align: center;">
                    Logged-in customers receive real-time dashboard updates and in-app notifications with staff timestamps and notes.
                </p>
            </div>
        </div>
    </section>

    <!-- CONTACT & INQUIRIES SECTION -->
    <section class="section section-bg-light" id="contact">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">GET IN TOUCH</span>
                <h2 class="section-title">Contact Stainless Laundry Services</h2>
                <p class="section-subtext">
                    Have questions about our prices, special fabrics, or recurring hostel pickups? Reach out to us directly.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px; align-items: start;">
                <div class="panel">
                    <h3 class="mb-3">Business Information</h3>
                    <div class="footer-contact-item" style="color: var(--ink);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--blue);"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                        <div>
                            <strong>Physical Location:</strong><br>
                            Around Ndagani, Chuka, Kenya (Near Chuka University)
                        </div>
                    </div>

                    <div class="footer-contact-item" style="color: var(--ink); margin-top: 14px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--blue);"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <div>
                            <strong>Direct Hotline &amp; WhatsApp:</strong><br>
                            <a href="tel:<?= BUSINESS_PHONE ?>" style="font-weight: 700; color: var(--navy);"><?= BUSINESS_PHONE ?></a>
                        </div>
                    </div>

                    <div class="footer-contact-item" style="color: var(--ink); margin-top: 14px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--blue);"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <div>
                            <strong>Working Hours:</strong><br>
                            Monday &ndash; Saturday: 7:00 AM &ndash; 7:30 PM<br>
                            Sunday: 9:00 AM &ndash; 5:00 PM
                        </div>
                    </div>

                    <div class="mt-4">
                        <a class="btn btn-whatsapp btn-block" href="https://wa.me/<?= BUSINESS_WHATSAPP ?>" target="_blank" rel="noopener noreferrer">
                            Chat on WhatsApp (<?= BUSINESS_PHONE ?>)
                        </a>
                    </div>
                </div>

                <div class="panel">
                    <h3 class="mb-3">Send Us a Direct Message</h3>
                    <?php if ($contactSuccess): ?>
                        <div class="alert alert-success">Your message has been delivered to our team! We will contact you shortly.</div>
                    <?php endif; ?>
                    <?php if (!empty($contactErrors['general'])): ?>
                        <div class="alert alert-error"><?= e($contactErrors['general']) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= APP_URL ?>/index.php#contact">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="contact_name">Full Name *</label>
                            <input type="text" id="contact_name" name="name" required value="<?= e($_POST['name'] ?? '') ?>" placeholder="Your name">
                            <?php if (!empty($contactErrors['name'])): ?><div class="form-error-msg"><?= e($contactErrors['name']) ?></div><?php endif; ?>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="contact_email">Email Address *</label>
                                <input type="email" id="contact_email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="name@domain.com">
                                <?php if (!empty($contactErrors['email'])): ?><div class="form-error-msg"><?= e($contactErrors['email']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="contact_phone">Phone Number</label>
                                <input type="text" id="contact_phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="0101476174">
                                <?php if (!empty($contactErrors['phone'])): ?><div class="form-error-msg"><?= e($contactErrors['phone']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contact_message">Message *</label>
                            <textarea id="contact_message" name="message" required placeholder="How can we assist you?"><?= e($_POST['message'] ?? '') ?></textarea>
                            <?php if (!empty($contactErrors['message'])): ?><div class="form-error-msg"><?= e($contactErrors['message']) ?></div><?php endif; ?>
                        </div>

                        <button type="submit" name="contact_submit" class="btn btn-primary btn-block">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
