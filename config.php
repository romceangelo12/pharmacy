<?php
/**
 * Database Configuration & App Helpers
 * Pharmacy Inventory Management System
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (Default XAMPP settings)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pharmacy_db');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'JVGH Pharmacy');
define('APP_VERSION', '1.0.0');

/**
 * Get PDO Database Connection
 * @return PDO|null
 */
function getDBConnection(): ?PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        // Ensure clerk user is purged and stock_type column exists
        try {
            $pdo->exec("DELETE FROM users WHERE username = 'clerk'");
            // Add stock_type column to medicines if not exists
            $checkCol = $pdo->query("SHOW COLUMNS FROM `medicines` LIKE 'stock_type'")->fetch();
            if (!$checkCol) {
                $pdo->exec("ALTER TABLE `medicines` ADD COLUMN `stock_type` ENUM('regular', 'donation', 'mhmap') NOT NULL DEFAULT 'regular' AFTER `category_id`");
            }
        } catch (Exception $ignored) {}
        return $pdo;
    } catch (PDOException $e) {
        // Connection failure (database may not exist yet or MySQL is stopped)
        return null;
    }
}

/**
 * Generate CSRF Token for form security
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if a user is authenticated
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['username']);
}

/**
 * Require login: redirect to login.php if unauthenticated
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Get current logged in user details
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'pharmacist',
    ];
}

