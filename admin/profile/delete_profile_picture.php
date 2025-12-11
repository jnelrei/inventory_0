<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get current picture path
        $stmt = $pdo->prepare("SELECT picture FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
            exit();
        }
        
        $picturePath = $user['picture'];
        
        // Delete the file if it exists
        if ($picturePath && file_exists($picturePath)) {
            unlink($picturePath);
        }
        
        // Update database to remove picture reference
        $stmt = $pdo->prepare("UPDATE users SET picture = NULL WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile picture deleted successfully!'
        ]);
        exit();
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting picture: ' . $e->getMessage()
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




