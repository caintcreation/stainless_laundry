<?php
declare(strict_types=1);

/**
 * Validates customer registration and profile input
 */
function validate_registration(array $input, ?int $ignoreUserId = null): array {
    $errors = [];
    $name = trim($input['full_name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $phone = sanitize_phone($input['phone'] ?? '');
    $location = trim($input['location'] ?? '');
    $password = $input['password'] ?? '';
    $confirm = $input['confirm_password'] ?? '';

    if (empty($name) || strlen($name) < 2) {
        $errors['full_name'] = 'Please enter your full name.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please provide a valid email address.';
    } else {
        $query = 'SELECT id FROM users WHERE email = ?';
        $params = [$email];
        if ($ignoreUserId !== null) {
            $query .= ' AND id != ?';
            $params[] = $ignoreUserId;
        }
        $stmt = db()->prepare($query);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email address already exists.';
        }
    }

    if (!is_valid_kenyan_phone($phone)) {
        $errors['phone'] = 'Please enter a valid Kenyan phone number (e.g., 0101476174 or 0712345678).';
    }

    if (empty($location)) {
        $errors['location'] = 'Please enter your estate or pickup location (e.g., Ndagani, Chuka).';
    }

    if ($ignoreUserId === null || !empty($password)) {
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        }
        if ($password !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
    }

    return $errors;
}

/**
 * Validates contact form submission
 */
function validate_contact(array $input): array {
    $errors = [];
    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $phone = trim($input['phone'] ?? '');
    $message = trim($input['message'] ?? '');

    if (empty($name)) {
        $errors['name'] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (!empty($phone) && !is_valid_kenyan_phone($phone)) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    if (empty($message) || strlen($message) < 5) {
        $errors['message'] = 'Please enter a message (at least 5 characters).';
    }

    return $errors;
}
