<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $existing_picture = trim($_POST['existing_picture'] ?? '');
    $delete_picture = isset($_POST['delete_picture']) && $_POST['delete_picture'] === '1';
    $picture = $existing_picture; // Default to existing picture
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } else {
        // Check if username is already taken by another user
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt->execute([$username, $userId]);
            if ($stmt->fetch()) {
                $errors[] = "Username already taken";
            }
        } catch(PDOException $e) {
            $errors[] = "Error validating username: " . $e->getMessage();
        }
    }
    
    // Handle picture deletion
    if ($delete_picture && $existing_picture) {
        // Delete the file if it exists
        if (file_exists($existing_picture)) {
            unlink($existing_picture);
        }
        $picture = null;
    }
    
    // Handle file upload if new file is provided
    if (!$delete_picture && isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['picture'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $file['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.";
        }
        
        // Validate file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_size) {
            $errors[] = "Image size exceeds 5MB limit";
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = 'images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid('profile_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Delete old picture if it exists and is different
            if ($existing_picture && file_exists($existing_picture) && $existing_picture !== $upload_path) {
                unlink($existing_picture);
            }
            $picture = $upload_path;
        } else {
            $errors[] = "Failed to upload image";
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            // Update user information
            if ($picture !== null) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, picture = ? WHERE user_id = ?");
                $stmt->execute([$name, $username, $picture, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, picture = NULL WHERE user_id = ?");
                $stmt->execute([$name, $username, $userId]);
            }
            
            // Update session data
            $_SESSION['name'] = $name;
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully!'
            ]);
            exit();
            
        } catch(PDOException $e) {
            // If database update fails, delete uploaded image if it was new
            if ($picture !== $existing_picture && $picture && file_exists($picture)) {
                unlink($picture);
            }
            
            echo json_encode([
                'success' => false,
                'message' => 'Error updating profile: ' . $e->getMessage()
            ]);
            exit();
        }
    } else {
        // If validation errors, delete uploaded image if it was new
        if (isset($file) && isset($upload_path) && file_exists($upload_path)) {
            unlink($upload_path);
        }
        
        echo json_encode([
            'success' => false,
            'message' => implode('<br>', $errors)
        ]);
        exit();
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}
?>




