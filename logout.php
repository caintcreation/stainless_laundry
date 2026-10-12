<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

logout_user();
flash('success', 'You have been logged out securely.');
redirect('login.php');