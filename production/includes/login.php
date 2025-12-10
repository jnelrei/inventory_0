<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no validation errors, check credentials
    if (empty($errors)) {
        try {
            // Get user from database
            $stmt = $pdo->prepare("SELECT user_id, name, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // Verify password
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['show_intro_animation'] = true;
                
                if ($remember) {
                    // Set cookie for "remember me" functionality (30 days)
                    setcookie('remember_user', $username, time() + (86400 * 30), '/');
                }
                
                // Set redirect URL based on role
                if ($user['role'] === 'superadmin') {
                    $_SESSION['redirect_url'] = '../../superadmin/dashboard/sa_dashboard.php';
                } elseif ($user['role'] === 'admin') {
                    $_SESSION['redirect_url'] = '../../admin/dashboard/adm_dashboard.php';
                } elseif ($user['role'] === 'user') {
                    $_SESSION['redirect_url'] = '../../user/dashboard/usr_dashboard.php';
                }
                
                // Redirect to intermediate page with message
                header('Location: redirect.php');
                exit();
            } else {
                $errors[] = "Invalid username or password";
            }
            
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    // If there are errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        header('Location: ../../index.php');
        exit();
    }
} else {
    // If not POST request, redirect to index
    header('Location: ../index.php');
    exit();
}
?>


