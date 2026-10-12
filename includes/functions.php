<?php
declare(strict_types=1);

/**
 * Escape HTML output safely
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Safe redirect within the application
 */
function redirect(string $path): never {
    $url = APP_URL . '/' . ltrim($path, '/');
    header("Location: {$url}");
    exit;
}

/**
 * Flash message getter and setter
 */
function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

/**
 * Format currency in Kenyan Shillings
 */
function money(float|string|int $amount): string {
    return CURRENCY . ' ' . number_format((float)$amount, 0);
}

/**
 * Generate unique order reference: SLS-YYMMDD-XXXX
 */
function order_reference(): string {
    return 'SLS-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

/**
 * Retrieve system setting from database
 */
function setting(string $key, string $default = ''): string {
    try {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? (string)$val : $default;
    } catch (Throwable) {
        return $default;
    }
}

/**
 * Update system setting in database
 */
function update_setting(string $key, string $value): void {
    $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?');
    $stmt->execute([$key, $value, $value]);
}

/**
 * Format datetime for Africa/Nairobi
 */
function format_datetime(?string $value): string {
    if (!$value) return 'Not set';
    return date('d M Y, h:i A', strtotime($value));
}

/**
 * Format date
 */
function format_date(?string $value): string {
    if (!$value) return 'Not set';
    return date('d M Y', strtotime($value));
}

/**
 * Official laundry workflow statuses
 */
function get_order_statuses(): array {
    return [
        'Order Placed',
        'Pickup Scheduled',
        'Pickup Assigned',
        'Laundry Collected',
        'Received at Laundry Facility',
        'Washing in Progress',
        'Drying in Progress',
        'Folding and Quality Check',
        'Ready for Delivery',
        'Out for Delivery',
        'Delivered',
        'Cancelled'
    ];
}

/**
 * Return CSS status badge class
 */
function status_badge(string $status): string {
    $sanitized = e($status);
    $cls = match ($status) {
        'Order Placed' => 'badge-new',
        'Pickup Scheduled', 'Pickup Assigned' => 'badge-scheduled',
        'Laundry Collected', 'Received at Laundry Facility' => 'badge-facility',
        'Washing in Progress', 'Drying in Progress' => 'badge-progress',
        'Folding and Quality Check', 'Ready for Delivery' => 'badge-ready',
        'Out for Delivery' => 'badge-transit',
        'Delivered' => 'badge-delivered',
        'Cancelled' => 'badge-cancelled',
        default => 'badge-default'
    };
    return "<span class=\"status-pill {$cls}\">{$sanitized}</span>";
}

/**
 * Return CSS payment badge
 */
function payment_badge(string $status): string {
    $sanitized = e($status);
    $cls = match ($status) {
        'Paid' => 'badge-paid',
        'Partially Paid' => 'badge-partial',
        'Refunded' => 'badge-refunded',
        default => 'badge-unpaid'
    };
    return "<span class=\"status-pill {$cls}\">{$sanitized}</span>";
}

/**
 * Audit log recording
 */
function audit_log(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
    try {
        $stmt = db()->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $action, $entityType, $entityId, $details]);
    } catch (Throwable) {
        // Silently preserve stability if audit logging fails
    }
}

/**
 * Create customer notification
 */
function notify_user(int $userId, ?int $orderId, string $title, string $message): void {
    try {
        $stmt = db()->prepare('INSERT INTO notifications (user_id, order_id, title, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $orderId, $title, $message]);
    } catch (Throwable) {
    }
}

/**
 * Count unread notifications
 */
function unread_notifications_count(int $userId): int {
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
}

/**
 * Clean & validate Kenyan phone number
 * Format: 07XXXXXXXX or 01XXXXXXXX -> standard 10 digits starting with 0
 */
function sanitize_phone(string $phone): string {
    $clean = preg_replace('/[^\d+]/', '', trim($phone));
    if (str_starts_with($clean, '+254')) {
        $clean = '0' . substr($clean, 4);
    } elseif (str_starts_with($clean, '254')) {
        $clean = '0' . substr($clean, 3);
    }
    return $clean;
}

function is_valid_kenyan_phone(string $phone): bool {
    $clean = sanitize_phone($phone);
    return (bool)preg_match('/^(07|01)\d{8}$/', $clean);
}