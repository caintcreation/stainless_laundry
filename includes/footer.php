<?php
declare(strict_types=1);
?>
    <footer class="site-footer">
        <div class="container footer-grid">
            <!-- Col 1: Brand Info -->
            <div class="footer-col">
                <h4><?= APP_NAME ?></h4>
                <p style="color: #D6E4F7; margin-bottom: 14px;">
                    Professional laundry, duvet cleaning, and garment care services tailored for students, families, and businesses around Ndagani and Chuka.
                </p>
                <div style="background-color: rgba(255, 211, 42, 0.15); border-left: 3px solid var(--yellow); padding: 8px 12px; border-radius: 4px;">
                    <strong style="color: var(--yellow); font-family: var(--font-heading); display: block; font-size: 0.95rem;">Fresh Clothes, Happy You!</strong>
                    <span style="font-size: 0.8rem; color: #FFFFFF;">Clean | Dry | Fold</span>
                </div>
            </div>

            <!-- Col 2: Quick Links -->
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?= APP_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= APP_URL ?>/services.php">Our Services</a></li>
                    <li><a href="<?= APP_URL ?>/pricing.php">Price List</a></li>
                    <li><a href="<?= APP_URL ?>/track-order.php">Track Order</a></li>
                    <li><a href="<?= APP_URL ?>/about.php">About Us</a></li>
                    <li><a href="<?= APP_URL ?>/contact.php">Contact Us</a></li>
                </ul>
            </div>

            <!-- Col 3: Customer Portal -->
            <div class="footer-col">
                <h4>Customer Portal</h4>
                <ul>
                    <?php if (current_user()): ?>
                        <li><a href="<?= APP_URL ?>/customer/dashboard.php">My Account</a></li>
                        <li><a href="<?= APP_URL ?>/customer/place-order.php">Book Laundry Pickup</a></li>
                        <li><a href="<?= APP_URL ?>/customer/orders.php">My Orders</a></li>
                        <li><a href="<?= APP_URL ?>/customer/profile.php">Account Profile</a></li>
                        <li><a href="<?= APP_URL ?>/logout.php">Log Out</a></li>
                    <?php else: ?>
                        <li><a href="<?= APP_URL ?>/login.php">Customer Login</a></li>
                        <li><a href="<?= APP_URL ?>/register.php">Create Account</a></li>
                        <li><a href="<?= APP_URL ?>/forgot-password.php">Forgot Password</a></li>
                        <li><a href="<?= APP_URL ?>/admin/login.php">Staff Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Col 4: Contact & Location -->
            <div class="footer-col">
                <h4>Find Us</h4>
                <div class="footer-contact-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--yellow); flex-shrink: 0;"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                    <div>
                        <strong>Location:</strong><br>
                        Around Ndagani, Chuka, Kenya
                    </div>
                </div>
                <div class="footer-contact-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--yellow); flex-shrink: 0;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <div>
                        <strong>Call / WhatsApp:</strong><br>
                        <a href="tel:<?= BUSINESS_PHONE ?>" style="color: #FFFFFF; font-weight: 700;"><?= BUSINESS_PHONE ?></a>
                    </div>
                </div>
                <div style="margin-top: 14px;">
                    <a class="btn btn-whatsapp btn-sm" href="https://wa.me/<?= BUSINESS_WHATSAPP ?>" target="_blank" rel="noopener noreferrer">
                        Chat on WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer Bottom Strip -->
        <div class="footer-bottom-strip">
            <div class="container footer-bottom-inner">
                <div>
                    &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
                    &nbsp;|&nbsp;
                    <a href="<?= APP_URL ?>/privacy-policy.php" style="color: #CBDDF5;">Privacy Policy</a>
                    &nbsp;|&nbsp;
                    <a href="<?= APP_URL ?>/terms.php" style="color: #CBDDF5;">Terms &amp; Conditions</a>
                </div>
                <div class="footer-slogans">
                    CLEANER CLOTHES &nbsp;|&nbsp; FRESHER DAYS &nbsp;|&nbsp; A HEALTHIER YOU
                </div>
            </div>
        </div>
    </footer>

    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
    <?php if (!empty($extraJs)): ?>
        <script src="<?= APP_URL ?>/<?= ltrim($extraJs, '/') ?>"></script>
    <?php endif; ?>
</body>
</html>