<?php
/**
 * Simple & Clean Login Page - Pharmacy Inventory System
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

// Handle messages from redirection
if (isset($_GET['registered'])) {
    $successMessage = "Account created successfully! Please sign in with your credentials.";
} elseif (isset($_GET['logged_out'])) {
    $successMessage = "You have logged out successfully.";
} elseif (isset($_GET['session_expired'])) {
    $errorMessage = "Your session expired. Please sign in again.";
}

// Check database connectivity
$pdo = getDBConnection();
if ($pdo === null) {
    $dbConnectionError = true;
}

// Process login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    $rememberMe = isset($_POST['remember_me']);

    // CSRF verification
    if (!verifyCSRFToken($csrfToken)) {
        $errorMessage = "Security token mismatch. Please try again.";
    } elseif ($pdo === null) {
        $errorMessage = "Database connection error. Please run the setup first.";
    } elseif (empty($usernameOrEmail) || empty($password)) {
        $errorMessage = "Please enter your username and password.";
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT id, username, email, password, full_name, role, status 
                FROM users 
                WHERE username = :username OR email = :email 
                LIMIT 1
            ");
            $stmt->execute([
                'username' => $usernameOrEmail,
                'email'    => $usernameOrEmail
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $errorMessage = "This account is inactive. Please contact your manager.";
                } else {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in_time'] = time();

                    // Update last login timestamp
                    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                    $updateStmt->execute(['id' => $user['id']]);

                    if ($rememberMe) {
                        setcookie('pharmacy_remember', $user['username'], time() + (86400 * 30), "/");
                    }

                    header("Location: dashboard.php");
                    exit;
                }
            } else {
                $errorMessage = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $errorMessage = "System error: " . htmlspecialchars($e->getMessage());
        }
    }
}

$csrfToken = generateCSRFToken();
$prefillUser = htmlspecialchars($_POST['username'] ?? ($_COOKIE['pharmacy_remember'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; JVGH Pharmacy</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="simple-page">

    <div class="simple-login-wrapper">
        <div class="simple-card">

            <!-- Logo & Brand Header -->
            <div class="simple-header">
                <div class="simple-logo">
                    <img src="logo.jpg" alt="JVGH Pharmacy logo">
                </div>
                <h2 style="margin-bottom: 0;">JVGH Pharmacy</h2>
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

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span><?= htmlspecialchars($successMessage) ?></span>
                </div>
            <?php endif; ?>

            <!-- Simple Login Form -->
            <form id="loginForm" method="POST" action="login.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Username Field -->
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Enter username" 
                        value="<?= $prefillUser ?>" 
                        required 
                        autocomplete="username"
                        autofocus
                    >
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                        <a href="javascript:void(0)" onclick="alert('Please contact your administrator to reset your password.');" class="forgot-link" style="font-size: 0.8rem;">Forgot?</a>
                    </div>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control has-toggle" 
                            placeholder="Enter password" 
                            required 
                            autocomplete="current-password"
                        >
                        <button type="button" id="togglePassword" class="password-toggle-btn" title="Show password" aria-label="Show password">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="form-row" style="margin-bottom: 18px;">
                    <label class="remember-label">
                        <input type="checkbox" name="remember_me" class="remember-checkbox" <?= isset($_COOKIE['pharmacy_remember']) ? 'checked' : '' ?>>
                        <span>Remember me</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn" class="btn btn-primary btn-block">
                    Sign In
                </button>
            </form>

            <!-- Register Link -->
            <div style="margin-top: 20px; text-align: center; font-size: 0.88rem; color: #64748b;">
                Don't have an account? 
                <a href="register.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Register here</a>
            </div>

        </div>

        <!-- Footer -->
        <div class="simple-footer">
            &copy; <?= date('Y') ?> JVGH Pharmacy &bull; <a href="install.php" style="color: #64748b; text-decoration: underline;">Setup Database</a>
        </div>
    </div>

    <script src="assets/js/login.js"></script>
</body>
</html>
