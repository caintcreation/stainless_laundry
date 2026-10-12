<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = 'Pricing & Rates';

// Fetch options from database
$options = [];
try {
    $stmt = db()->query('SELECT so.*, s.name as service_name FROM service_options so JOIN services s ON s.id = so.service_id WHERE so.is_active = 1 ORDER BY so.service_id, so.id');
    while ($row = $stmt->fetch()) {
        $options[$row['service_id']][] = $row;
    }
} catch (Throwable) {
}

require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="section section-bg-light" style="padding: 50px 0;">
        <div class="container text-center">
            <span class="section-tag">OFFICIAL PRICE LIST</span>
            <h1 class="section-title">Transparent &amp; Pocket-Friendly Pricing</h1>
            <p class="section-subtext" style="max-width: 680px; margin: 0 auto;">
                No hidden costs. Designed specifically to suit students, residents, and families in Ndagani and Chuka.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="pricing-grid">
                <!-- Basket Pricing Table -->
                <div class="poster-price-card">
                    <div class="poster-price-header">
                        <svg viewBox="0 0 24 24"><path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12z"/></svg>
                        <div>
                            <h3>LAUNDRY BASKET</h3>
                            <small style="color: #D6E4F7;">Washing, Drying &amp; Folding</small>
                        </div>
                    </div>
                    <div class="poster-price-body">
                        <?php if (!empty($options[1])): ?>
                            <?php foreach ($options[1] as $item): ?>
                                <div class="price-row-item">
                                    <div>
                                        <span class="price-item-label"><?= e($item['option_name']) ?></span>
                                        <small class="text-muted" style="display: block; font-size: 0.8rem;">
                                            <?= match($item['option_name']) {
                                                'Small' => 'Light load / 1-2 days wear',
                                                'Medium' => 'Standard weekly basket',
                                                'Large Narrow' => 'Tall basket / bulky garments',
                                                'Large Wide' => 'Heavy family or fortnightly basket',
                                                default => 'Full wash, dry & fold'
                                            } ?>
                                        </small>
                                    </div>
                                    <span class="price-item-pill">KES <?= number_format((float)$item['unit_price'], 0) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="price-row-item"><span class="price-item-label">Small</span><span class="price-item-pill">KES 250</span></div>
                            <div class="price-row-item"><span class="price-item-label">Medium</span><span class="price-item-pill">KES 300</span></div>
                            <div class="price-row-item"><span class="price-item-label">Large Narrow</span><span class="price-item-pill">KES 350</span></div>
                            <div class="price-row-item"><span class="price-item-label">Large Wide</span><span class="price-item-pill">KES 400</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="poster-price-footer">
                        <a class="btn btn-primary btn-block" href="<?= APP_URL ?>/customer/place-order.php">Book Basket Laundry</a>
                    </div>
                </div>

                <!-- Duvet Pricing Table -->
                <div class="poster-price-card">
                    <div class="poster-price-header">
                        <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/></svg>
                        <div>
                            <h3>DUVET CLEANING</h3>
                            <small style="color: #D6E4F7;">Deep sanitizing wash &amp; high-temp dry</small>
                        </div>
                    </div>
                    <div class="poster-price-body">
                        <?php if (!empty($options[2])): ?>
                            <?php foreach ($options[2] as $item): ?>
                                <div class="price-row-item">
                                    <div>
                                        <span class="price-item-label"><?= e($item['option_name']) ?> Feet</span>
                                        <small class="text-muted" style="display: block; font-size: 0.8rem;">
                                            <?= match($item['option_name']) {
                                                '3 x 4' => 'Single bed / child duvet',
                                                '4 x 6' => '4x6 student mattress / 3/4 bed',
                                                '5 x 6' => 'Queen size bed / heavy duvet',
                                                '6 x 6' => 'King size master bed quilt',
                                                default => 'Thorough anti-bacterial clean'
                                            } ?>
                                        </small>
                                    </div>
                                    <span class="price-item-pill">KES <?= number_format((float)$item['unit_price'], 0) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="price-row-item"><span class="price-item-label">3 &times; 4</span><span class="price-item-pill">KES 250</span></div>
                            <div class="price-row-item"><span class="price-item-label">4 &times; 6</span><span class="price-item-pill">KES 300</span></div>
                            <div class="price-row-item"><span class="price-item-label">5 &times; 6</span><span class="price-item-pill">KES 350</span></div>
                            <div class="price-row-item"><span class="price-item-label">6 &times; 6</span><span class="price-item-pill">KES 400</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="poster-price-footer">
                        <a class="btn btn-primary btn-block" href="<?= APP_URL ?>/customer/place-order.php">Book Duvet Cleaning</a>
                    </div>
                </div>
            </div>

            <!-- Notes & Terms -->
            <div class="panel mt-4" style="background-color: var(--bg-light);">
                <h3 class="mb-2">Important Pricing Information</h3>
                <ul style="padding-left: 20px; line-height: 1.8; color: var(--ink);">
                    <li><strong>Free Pickup &amp; Delivery:</strong> Complimentary doorstep collection and drop-off is included for standard orders around Ndagani and Chuka University vicinities.</li>
                    <li><strong>Price Confirmation:</strong> The final amount should be confirmed by our team upon physical collection for any oversized items or unusual fabric requests.</li>
                    <li><strong>Price Snapshots:</strong> Once your order is submitted, the unit price at the time of booking is locked into your order record and will not change.</li>
                    <li><strong>Payment Methods:</strong> We accept M-Pesa, Cash on delivery/pickup, and direct bank transfers.</li>
                </ul>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>