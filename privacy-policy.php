<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = 'Privacy Policy';
require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="section section-bg-light" style="padding: 50px 0;">
        <div class="container text-center">
            <span class="section-tag">LEGAL &amp; PRIVACY</span>
            <h1 class="section-title">Privacy Policy</h1>
            <p class="section-subtext" style="max-width: 650px; margin: 0 auto;">
                How Stainless Laundry Services protects and handles your personal information.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width: 860px;">
            <div class="panel">
                <h2 class="mb-3">1. Information We Collect</h2>
                <p>
                    When you register an account or place an order with Stainless Laundry Services, we collect your name, email address, Kenyan phone number, hostel or estate location in Ndagani/Chuka, and specific pickup or delivery notes.
                </p>

                <h2 class="mb-3 mt-4">2. How We Use Your Information</h2>
                <p>
                    Your data is solely used to:
                </p>
                <ul style="padding-left: 20px; line-height: 1.8; margin-bottom: 1rem;">
                    <li>Coordinate doorstep laundry pickups and return deliveries.</li>
                    <li>Update you on the real-time washing, drying, and delivery status of your laundry.</li>
                    <li>Verify payment confirmations (M-Pesa, Cash, Bank).</li>
                    <li>Provide customer support through WhatsApp and phone calls.</li>
                </ul>

                <h2 class="mb-3 mt-4">3. Data Confidentiality &amp; Public Tracking</h2>
                <p>
                    Our public tracking tool requires both your Order Reference and your phone number to access status milestones. We never reveal your personal living address, detailed financial statements, or phone numbers publicly on the web.
                </p>

                <h2 class="mb-3 mt-4">4. Password Security</h2>
                <p>
                    All passwords are encrypted using state-of-the-art secure one-way hashing algorithms. We never store or transmit your plaintext password.
                </p>

                <h2 class="mb-3 mt-4">5. Contact Concerning Privacy</h2>
                <p>
                    For inquiries or to update your personal information, reach out to us directly at <strong>0101476174</strong> or visit our office around Ndagani, Chuka.
                </p>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>