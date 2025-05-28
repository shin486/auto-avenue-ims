<?php
session_start();
require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Store user role in session
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                case 'manager':
                    header("Location: dashboard.php");
                    break;
                case 'staff':
                    header("Location: dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        }
    }
    $error = "Invalid username or password";
}
?>

