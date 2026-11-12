<?php
/**
 * REST API - Authentication (Login, Register, Session check, Logout)
 * Pharmacy Inventory System
 */

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please ensure MySQL is started.'
    ]);
    exit;
}

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

switch ($action) {
    case 'login':
        $usernameOrEmail = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($usernameOrEmail) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
            exit;
        }

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
                    echo json_encode(['success' => false, 'message' => 'Account is inactive.']);
                    exit;
                }

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Update last login
                $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $update->execute(['id' => $user['id']]);

                echo json_encode([
                    'success' => true,
                    'user' => [
                        'id'        => $user['id'],
                        'username'  => $user['username'],
                        'email'     => $user['email'],
                        'full_name' => $user['full_name'],
                        'role'      => $user['role']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'register':
        $fullName = trim($data['full_name'] ?? '');
        $username = trim($data['username'] ?? '');
        $email    = trim($data['email'] ?? '');
        $role     = trim($data['role'] ?? 'pharmacist');
        $password = $data['password'] ?? '';

        $allowedRoles = ['pharmacist', 'admin'];

        if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address format.']);
            exit;
        }

        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            echo json_encode(['success' => false, 'message' => 'Username must be 3-30 letters, numbers, or underscores.']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }

        if (!in_array($role, $allowedRoles, true)) {
            $role = 'pharmacist';
        }

        try {
            // Check existing
            $check = $pdo->prepare("SELECT id, username, email FROM users WHERE username = :u OR email = :e LIMIT 1");
            $check->execute(['u' => $username, 'e' => $email]);
            $existing = $check->fetch();

            if ($existing) {
                if (strcasecmp($existing['username'], $username) === 0) {
                    echo json_encode(['success' => false, 'message' => "Username '{$username}' is already taken."]);
                } else {
                    echo json_encode(['success' => false, 'message' => "An account with email '{$email}' already exists."]);
                }
                exit;
            }

            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare("
                INSERT INTO users (username, email, password, full_name, role, status) 
                VALUES (:username, :email, :password, :full_name, :role, 'active')
            ");
            $insert->execute([
                'username'  => $username,
                'email'     => $email,
                'password'  => $hashed,
                'full_name' => $fullName,
                'role'      => $role
            ]);

            echo json_encode(['success' => true, 'message' => 'Registration successful!']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'me':
        if (isLoggedIn()) {
            echo json_encode([
                'authenticated' => true,
                'user' => getCurrentUser()
            ]);
        } else {
            echo json_encode(['authenticated' => false]);
        }
        break;

    case 'logout':
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action parameter.']);
        break;
}

