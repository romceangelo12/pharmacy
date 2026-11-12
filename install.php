<?php
/**
 * One-Click Database Setup & Diagnostics Tool
 * Pharmacy Inventory System
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'pharmacy_db';
$sqlFile = __DIR__ . '/database.sql';

$status = null;
$message = '';
$details = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    try {
        // Step 1: Connect to MySQL server without selecting DB
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $details[] = "Connected to MySQL server at <code>$host</code> as <code>$user</code>.";

        // Step 2: Read SQL file
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found at: $sqlFile");
        }
        $sqlContent = file_get_contents($sqlFile);

        // Step 3: Execute SQL queries
        // Execute line by line / statement by statement or as multi-query
        $pdo->exec($sqlContent);
        $details[] = "Database <code>$dbName</code> created and schema imported successfully.";
        $details[] = "Demo users created: <strong>admin</strong> (admin123), <strong>pharmacist</strong> (pharma123).";
        $details[] = "Initial categories and sample medicine records populated.";

        $status = 'success';
        $message = 'Database and tables successfully initialized!';
    } catch (PDOException $e) {
        $status = 'error';
        $message = 'Database error: ' . htmlspecialchars($e->getMessage());
        $details[] = "Ensure MySQL is running in your XAMPP Control Panel.";
    } catch (Exception $e) {
        $status = 'error';
        $message = 'Setup error: ' . htmlspecialchars($e->getMessage());
    }
} else {
    // Check current status
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
        ]);
        if ($pdo) {
            $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
            if ($stmt && $stmt->fetch()) {
                $status = 'already_installed';
                $message = "Database '$dbName' is already installed and detected.";
            } else {
                $status = 'ready';
                $message = "MySQL is running. Ready to create and configure '$dbName'.";
            }
        }
    } catch (Exception $e) {
        $status = 'not_running';
        $message = "Could not connect to MySQL: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - PharmaCare Inventory</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .install-box {
            max-width: 650px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
        }
        .step-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .step-list li {
            padding: 10px 14px;
            margin-bottom: 8px;
            background: #f8fafc;
            border-left: 4px solid #0d9488;
            border-radius: 6px;
            font-size: 0.92rem;
            color: #334155;
        }
        .code-pill {
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85rem;
        }
    </style>
</head>
<body class="install-body">
    <div class="install-box">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div style="background: #0d9488; color: white; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2v20M2 12h20"/>
                </svg>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.4rem; color: #0f172a;">PharmaCare Database Setup</h2>
                <p style="margin: 0; font-size: 0.88rem; color: #64748b;">Automated 1-Click Installation for XAMPP</p>
            </div>
        </div>

        <?php if ($status === 'success'): ?>
            <div class="alert alert-success">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span><strong>Setup Complete!</strong> <?= $message ?></span>
            </div>
            <ul class="step-list">
                <?php foreach ($details as $d): ?>
                    <li><?= $d ?></li>
                <?php endforeach; ?>
            </ul>
            <div style="margin-top: 24px; display: flex; gap: 12px;">
                <a href="login.php" class="btn btn-primary" style="text-decoration: none; text-align: center; flex: 1;">Proceed to Login</a>
            </div>
        <?php elseif ($status === 'already_installed'): ?>
            <div class="alert alert-info">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span><?= $message ?> You can log in directly or reinstall if you wish to reset demo data.</span>
            </div>
            <div style="margin-top: 20px; display: flex; gap: 12px;">
                <a href="login.php" class="btn btn-primary" style="text-decoration: none; text-align: center; flex: 1;">Go to Login Page</a>
                <form method="POST" style="margin: 0; flex: 1;">
                    <input type="hidden" name="action" value="install">
                    <button type="submit" class="btn btn-outline" style="width: 100%;" onclick="return confirm('Re-installing will overwrite tables and demo data. Continue?');">Reset / Reinstall Database</button>
                </form>
            </div>
        <?php else: ?>
            <?php if (!empty($message)): ?>
                <div class="alert <?= ($status === 'error' || $status === 'not_running') ? 'alert-danger' : 'alert-info' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span><?= $message ?></span>
                </div>
            <?php endif; ?>

            <p style="color: #475569; font-size: 0.95rem; line-height: 1.5;">
                This wizard will create the <code>pharmacy_db</code> database and seed the default accounts:
            </p>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.9rem;">
                <thead>
                    <tr style="background: #f1f5f9; text-align: left;">
                        <th style="padding: 8px 12px; border: 1px solid #e2e8f0;">Role</th>
                        <th style="padding: 8px 12px; border: 1px solid #e2e8f0;">Username</th>
                        <th style="padding: 8px 12px; border: 1px solid #e2e8f0;">Password</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #e2e8f0;">Administrator</td>
                        <td style="padding: 8px 12px; border: 1px solid #e2e8f0;"><code>admin</code></td>
                        <td style="padding: 8px 12px; border: 1px solid #e2e8f0;"><code>admin123</code></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #e2e8f0;">Pharmacist</td>
                        <td style="padding: 8px 12px; border: 1px solid #e2e8f0;"><code>pharmacist</code></td>
                        <td style="padding: 8px 12px; border: 1px solid #e2e8f0;"><code>pharma123</code></td>
                    </tr>
                </tbody>
            </table>

            <form method="POST">
                <input type="hidden" name="action" value="install">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
                    Initialize Database Now
                </button>
            </form>
            <div style="margin-top: 14px; text-align: center;">
                <a href="login.php" style="color: #64748b; font-size: 0.88rem; text-decoration: none;">Skip to Login page &rarr;</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

