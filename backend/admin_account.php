<?php
// Used by the terminal script and the protected Admin Panel.
function create_admin_account($conn, $name, $email, $password) {
    $name = trim($name);
    $email = strtolower(trim($email));

    if ($name === '' || strlen($name) > 255) {
        throw new InvalidArgumentException('Enter a name (maximum 255 bytes).');
    }
    if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Enter a valid email address.');
    }
    if (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
        throw new InvalidArgumentException('Use a password between 8 and 72 bytes.');
    }

    $exists = $conn->execute_query('SELECT id FROM users WHERE email = ?', [$email])->fetch_assoc();
    if ($exists) {
        throw new InvalidArgumentException('This email is already registered. Use another email, or promote the existing account in phpMyAdmin.');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    // Admin setup does not require a CV. The empty path means no CV was uploaded.
    try {
        $conn->execute_query(
            "INSERT INTO users (full_name, role, department, email, password, cv_path) VALUES (?, 'admin', 'Administration', ?, ?, '')",
            [$name, $email, $hash]
        );
    } catch (mysqli_sql_exception $error) {
        if ($error->getCode() === 1062) {
            throw new InvalidArgumentException('This email is already registered.');
        }
        throw $error;
    }
}
