<?php
declare(strict_types=1);

date_default_timezone_set('Africa/Nairobi');

const APP_NAME = 'STAINLESS LAUNDRY SERVICES';
const BUSINESS_SLOGAN = 'Fresh Clothes, Happy You!';
const SECONDARY_SLOGAN = 'Clean | Dry | Fold';
const BUSINESS_PHONE = '0101476174';
const BUSINESS_WHATSAPP = '254101476174';
const BUSINESS_LOCATION = 'Around Ndagani, Chuka, Kenya';
const CURRENCY = 'KES';

if (!defined('APP_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = preg_replace('#/(customer|admin|includes|config|assets|tests).*$#i', '', $scriptDir);
    $basePath = rtrim($basePath, '/');
    // An InfinityFree installation normally runs at the domain root.
    // Keep the detected subdirectory for local installations, but do not
    // assume the local XAMPP folder exists on the hosted server.
    define('APP_URL', $basePath);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';