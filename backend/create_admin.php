<?php
// Run from the project terminal: php backend/create_admin.php
// This script must never create accounts through a public browser URL.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this file from the project terminal.');
}

require __DIR__ . '/db.php';
require __DIR__ . '/admin_account.php';

echo "Create a new admin account\n";
echo "Full name: ";
$name = trim(fgets(STDIN) ?: '');
echo "Email: ";
$email = trim(fgets(STDIN) ?: '');
echo "Password (8-72 bytes; visible while typing): ";
$password = rtrim(fgets(STDIN) ?: '', "\r\n");
echo "Confirm password: ";
$confirm = rtrim(fgets(STDIN) ?: '', "\r\n");

try {
    if ($password !== $confirm) {
        throw new InvalidArgumentException('Passwords do not match.');
    }
    create_admin_account($conn, $name, $email, $password);
    echo "Admin account created. Open signIn.php and sign in with this email and password.\n";
} catch (InvalidArgumentException $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
} catch (Throwable $error) {
    error_log($error->getMessage());
    fwrite(STDERR, "Could not create the account. Check the database setup.\n");
    exit(1);
}
