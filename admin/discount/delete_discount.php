<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $disc_id = trim($_POST['disc_id'] ?? '');
    
    // Validation
    if (empty($disc_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Discount ID is required'
        ]);
        exit();
    }
    
    // Verify discount exists
    try {
        $stmt = $pdo->prepare("SELECT disc_id FROM discount WHERE disc_id = ?");
        $stmt->execute([$disc_id]);
        if (!$stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Discount not found'
            ]);
            exit();
        }
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error verifying discount: ' . $e->getMessage()
        ]);
        exit();
    }
    
    // Delete discount
    try {
        $stmt = $pdo->prepare("DELETE FROM discount WHERE disc_id = ?");
        $stmt->execute([$disc_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Discount deleted successfully!'
        ]);
        exit();
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting discount: ' . $e->getMessage()
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

