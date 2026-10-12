<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = 'About Us';
require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="section section-bg-light" style="padding: 50px 0;">
        <div class="container text-center">
            <span class="section-tag">ABOUT OUR BUSINESS</span>
            <h1 class="section-title">Dedicated to Fresh Clothes &amp; Happy Clients</h1>
            <p class="section-subtext" style="max-width: 680px; margin: 0 auto;">
                STAINLESS LAUNDRY SERVICES is a trusted local laundry business based in Ndagani, Chuka, Kenya.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 48px; align-items: center;">
                <div>
                    <h2 class="mb-3">Our Story &amp; Service Commitment</h2>
                    <p>
                        Located in the heart of Ndagani near Chuka University, Stainless Laundry Services was established to remove the burden and exhaustion of laundry for students, busy lecturers, healthcare workers, and families in the Chuka area.
                    </p>
                    <p>
                        We know that unpredictable weather, tight academic schedules, and limited drying spaces make washing heavy duvets and everyday clothes difficult. Our mission is encapsulated in our primary slogan: <strong>"Fresh Clothes, Happy You!"</strong>
                    </p>
                    <p>
                        We operate high-grade washing machines, rapid tumble dryers, and sanitized folding stations. We treat every garment with respect, using quality skin-tested detergents and softeners that leave your fabrics clean, soft, and smelling invigorated.
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 24px;">
                        <div class="panel" style="margin: 0; padding: 20px;">
                            <strong style="color: var(--navy); display: block; font-size: 1.1rem; margin-bottom: 6px;">Hygiene Standards</strong>
                            <p class="text-muted" style="font-size: 0.9rem; margin: 0;">We wash orders separately. Your clothes are never mixed with another customer's laundry.</p>
                        </div>
                        <div class="panel" style="margin: 0; padding: 20px;">
                            <strong style="color: var(--navy); display: block; font-size: 1.1rem; margin-bottom: 6px;">Reliable Pickup</strong>
                            <p class="text-muted" style="font-size: 0.9rem; margin: 0;">On-time scheduled collection and return directly to your hostel or residence around Ndagani.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <img src="<?= APP_URL ?>/assets/images/poster.jpg" alt="Stainless Laundry Services Poster" class="hero-card-img">
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>