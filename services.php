<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = 'Our Laundry Services';
require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="section section-bg-light" style="padding: 50px 0;">
        <div class="container">
            <span class="section-tag">SERVICES CATALOGUE</span>
            <h1 class="section-title">Care For Every Load &amp; Fabric</h1>
            <p class="section-subtext" style="max-width: 650px;">
                We provide hygienic, convenient, and affordable laundry services tailored for Ndagani, Chuka University students, families, and local businesses.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div style="display: grid; gap: 32px;">
                <!-- Service 1: Laundry Basket -->
                <div class="panel" style="display: grid; grid-template-columns: 80px 1fr auto; gap: 24px; align-items: center;">
                    <div class="service-icon-box" style="width: 70px; height: 70px; font-size: 2rem;">&#129530;</div>
                    <div>
                        <h2>Laundry Basket Washing</h2>
                        <p class="text-muted">
                            Bring your filled laundry basket or let us pick it up from your hostel or doorstep. We meticulously sort lights and darks, wash using premium detergents and skin-safe softeners, and tumble-dry thoroughly.
                        </p>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <span class="status-pill badge-new">Small: KES 250</span>
                            <span class="status-pill badge-new">Medium: KES 300</span>
                            <span class="status-pill badge-new">Large Narrow: KES 350</span>
                            <span class="status-pill badge-new">Large Wide: KES 400</span>
                        </div>
                    </div>
                    <div>
                        <a class="btn btn-primary" href="<?= APP_URL ?>/customer/place-order.php">Book Basket Wash</a>
                    </div>
                </div>

                <!-- Service 2: Duvet Cleaning -->
                <div class="panel" style="display: grid; grid-template-columns: 80px 1fr auto; gap: 24px; align-items: center;">
                    <div class="service-icon-box" style="width: 70px; height: 70px; font-size: 2rem;">&#128716;</div>
                    <div>
                        <h2>Duvet &amp; Heavy Blankets Cleaning</h2>
                        <p class="text-muted">
                            Heavy duvets require specialized heavy-capacity washing machines and heat-controlled drying to prevent mold, odors, and fiber clumping. We return your duvets fresh, fluffy, and completely dry.
                        </p>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <span class="status-pill badge-new">3 &times; 4: KES 250</span>
                            <span class="status-pill badge-new">4 &times; 6: KES 300</span>
                            <span class="status-pill badge-new">5 &times; 6: KES 350</span>
                            <span class="status-pill badge-new">6 &times; 6: KES 400</span>
                        </div>
                    </div>
                    <div>
                        <a class="btn btn-primary" href="<?= APP_URL ?>/customer/place-order.php">Book Duvet Clean</a>
                    </div>
                </div>

                <!-- Service 3: Wash, Dry & Fold -->
                <div class="panel" style="display: grid; grid-template-columns: 80px 1fr auto; gap: 24px; align-items: center;">
                    <div class="service-icon-box" style="width: 70px; height: 70px; font-size: 2rem;">&#10024;</div>
                    <div>
                        <h2>Washing, Drying &amp; Neat Folding</h2>
                        <p class="text-muted">
                            The complete full-cycle package. Every item goes through quality wash, heat drying, and is hand-folded neatly so you can unpack directly into your drawers or luggage.
                        </p>
                        <p style="font-size: 0.9rem; color: var(--navy); font-weight: 600; margin: 0;">
                            Standard turnaround: Within 24 hours. Same-day emergency service available upon request.
                        </p>
                    </div>
                    <div>
                        <a class="btn btn-primary" href="<?= APP_URL ?>/customer/place-order.php">Schedule Full Cycle</a>
                    </div>
                </div>

                <!-- Service 4: Ironing & Pressing -->
                <div class="panel" style="display: grid; grid-template-columns: 80px 1fr auto; gap: 24px; align-items: center;">
                    <div class="service-icon-box" style="width: 70px; height: 70px; font-size: 2rem;">&#129519;</div>
                    <div>
                        <h2>Ironing &amp; Steam Pressing</h2>
                        <p class="text-muted">
                            Ensure crisp collars, sharp pleats, and wrinkle-free outfits for presentations, interviews, church, and ceremonies. We handle delicate fabrics with temperature-safe steam irons.
                        </p>
                    </div>
                    <div>
                        <a class="btn btn-primary" href="<?= APP_URL ?>/customer/place-order.php">Request Pressing</a>
                    </div>
                </div>

                <!-- Service 5: Pickup & Delivery -->
                <div class="panel" style="display: grid; grid-template-columns: 80px 1fr auto; gap: 24px; align-items: center;">
                    <div class="service-icon-box" style="width: 70px; height: 70px; font-size: 2rem;">&#128666;</div>
                    <div>
                        <h2>Pickup &amp; Delivery Around Ndagani &amp; Chuka</h2>
                        <p class="text-muted">
                            Save time and heavy lifting. Our delivery team collects laundry directly from your residence or hostel and returns it neatly packed at your designated time.
                        </p>
                    </div>
                    <div>
                        <a class="btn btn-accent" href="<?= APP_URL ?>/customer/place-order.php">Book Free Pickup</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>