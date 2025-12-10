<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = trim($_POST['item_id'] ?? '');
    
    // Validation
    if (empty($item_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Item ID is required'
        ]);
        exit();
    }
    
    // Verify item exists and get picture path before deletion
    try {
        $stmt = $pdo->prepare("SELECT picture FROM invtry WHERE item_id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            echo json_encode([
                'success' => false,
                'message' => 'Item not found'
            ]);
            exit();
        }
        
        // Delete the item from database
        $stmt = $pdo->prepare("DELETE FROM invtry WHERE item_id = ?");
        $stmt->execute([$item_id]);
        
        // Delete associated picture file if it exists
        if (!empty($item['picture']) && file_exists($item['picture'])) {
            unlink($item['picture']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Inventory item deleted successfully!'
        ]);
        exit();
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting item: ' . $e->getMessage()
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

