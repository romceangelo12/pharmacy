<?php
/**
 * Simple & Clean Registration Page - Pharmacy Inventory System
 */

require_once __DIR__ . '/config.php';

// Redirect to dashboard if already authenticated
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$errorMessage = '';
$successMessage = '';
$dbConnectionError = false;

// Check database connectivity
$pdo = getDBConnection();
if ($pdo === null) {
    $dbConnectionError = true;
}

// Preserve submitted form values on validation failure
$formData = [
    'full_name' => '',
    'username'  => '',
    'email'     => '',
    'role'      => 'pharmacist'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken       = $_POST['csrf_token'] ?? '';
    $fullName        = trim($_POST['full_name'] ?? '');
    $username        = trim($_POST['username'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $role            = trim($_POST['role'] ?? 'pharmacist');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $formData['full_name'] = $fullName;
    $formData['username']  = $username;
    $formData['email']     = $email;
    $formData['role']      = $role;

    // Allowed roles
    $allowedRoles = ['pharmacist', 'admin'];

    // Validations
    if (!verifyCSRFToken($csrfToken)) {
        $errorMessage = "Security token mismatch. Please try again.";
    } elseif ($pdo === null) {
        $errorMessage = "Database connection error. Please run the setup first.";
    } elseif (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        $errorMessage = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please provide a valid email address.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errorMessage = "Username must be 3-30 characters (letters, numbers, and underscores only).";
    } elseif (strlen($password) < 6) {
        $errorMessage = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirmPassword) {
        $errorMessage = "Passwords do not match. Please re-enter.";
    } elseif (!in_array($role, $allowedRoles, true)) {
        $errorMessage = "Invalid role selected.";
    } else {
        try {
            // Check if username or email is already taken
            $checkStmt = $pdo->prepare("
                SELECT id, username, email 
                FROM users 
                WHERE username = :username OR email = :email 
                LIMIT 1
            ");
            $checkStmt->execute([
                'username' => $username,
                'email'    => $email
            ]);
            $existingUser = $checkStmt->fetch();

            if ($existingUser) {
                if (strcasecmp($existingUser['username'], $username) === 0) {
                    $errorMessage = "Username '{$username}' is already taken. Please choose another.";
                } else {
                    $errorMessage = "An account with email '{$email}' already exists.";
                }
            } else {
                // Hash password securely
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Insert new user
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, full_name, role, status) 
                    VALUES (:username, :email, :password, :full_name, :role, 'active')
                ");
                $insertStmt->execute([
                    'username'  => $username,
                    'email'     => $email,
                    'password'  => $hashedPassword,
                    'full_name' => $fullName,
                    'role'      => $role
                ]);

                // Redirect to login page with success status
                header("Location: login.php?registered=1");
                exit;
            }
        } catch (PDOException $e) {
            $errorMessage = "Registration error: " . htmlspecialchars($e->getMessage());
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register &mdash; JVGH Pharmacy</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .simple-login-wrapper {
            max-width: 440px;
        }
        .form-select {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            color: var(--text-main);
            background-color: #ffffff;
            outline: none;
            transition: all 0.15s ease;
        }
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 480px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="simple-page">

    <div class="simple-login-wrapper">
        <div class="simple-card">

            <!-- Logo & Brand Header -->
            <div class="simple-header">
                <div class="simple-logo">
                    <img src="logo.jpg" alt="JVGH Pharmacy logo">
                </div>
                <h2>Create Account</h2>
                <p>Register as staff to access pharmacy inventory</p>
            </div>

            <!-- Database Setup Notice if not connected -->
            <?php if ($dbConnectionError): ?>
                <div class="alert alert-warning">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <div>
                        Database not found. <a href="install.php" style="color: #92400e; font-weight: 700; text-decoration: underline;">Click here to initialize</a>.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Feedback Alerts -->
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <span><?= htmlspecialchars($errorMessage) ?></span>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form id="registerForm" method="POST" action="register.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Full Name -->
                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-control" 
                        placeholder="e.g. Dr. Jane Doe" 
                        value="<?= htmlspecialchars($formData['full_name']) ?>" 
                        required 
                        autofocus
                    >
                </div>

                <!-- Username & Role -->
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control" 
                            placeholder="e.g. janedoe" 
                            value="<?= htmlspecialchars($formData['username']) ?>" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="role">Pharmacy Role</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="pharmacist" <?= $formData['role'] === 'pharmacist' ? 'selected' : '' ?>>Pharmacist</option>
                            <option value="admin" <?= $formData['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                        </select>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label class="form-label" for="email">Work Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="e.g. jane@pharmacy.local" 
                        value="<?= htmlspecialchars($formData['email']) ?>" 
                        required
                    >
                </div>

                <!-- Passwords -->
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control has-toggle" 
                                placeholder="Min. 6 chars" 
                                required
                            >
                            <button type="button" id="toggleRegPassword" class="password-toggle-btn" title="Show password" aria-label="Show password">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control" 
                            placeholder="Repeat password" 
                            required
                        >
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitRegBtn" class="btn btn-primary btn-block" style="margin-top: 8px;">
                    Create Account
                </button>
            </form>

            <!-- Back to Login Link -->
            <div style="margin-top: 20px; text-align: center; font-size: 0.88rem; color: #64748b;">
                Already have an account? 
                <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Sign In</a>
            </div>

        </div>

        <!-- Footer -->
        <div class="simple-footer">
            &copy; <?= date('Y') ?> JVGH Pharmacy &bull; <a href="install.php" style="color: #64748b; text-decoration: underline;">Setup Database</a>
        </div>
    </div>

    <script>
        // Password toggle for register
        const toggleBtn = document.getElementById('toggleRegPassword');
        const passField = document.getElementById('password');
        const confirmField = document.getElementById('confirm_password');

        if (toggleBtn && passField) {
            toggleBtn.addEventListener('click', () => {
                const isPass = passField.getAttribute('type') === 'password';
                passField.setAttribute('type', isPass ? 'text' : 'password');
                if (confirmField) {
                    confirmField.setAttribute('type', isPass ? 'text' : 'password');
                }
                toggleBtn.innerHTML = isPass ? `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                ` : `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                `;
            });
        }
    </script>
</body>
</html>

