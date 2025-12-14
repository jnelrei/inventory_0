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
    
    // Verify item exists and get images before deletion
    try {
        $stmt = $pdo->prepare("SELECT item_id FROM invtry WHERE item_id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            echo json_encode([
                'success' => false,
                'message' => 'Item not found'
            ]);
            exit();
        }
        
        // Get all images for this item
        $stmt = $pdo->prepare("SELECT image FROM inventory_images WHERE item_id = ?");
        $stmt->execute([$item_id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete all images from inventory_images table
        $stmt = $pdo->prepare("DELETE FROM inventory_images WHERE item_id = ?");
        $stmt->execute([$item_id]);
        
        // Delete the item from invtry table
        $stmt = $pdo->prepare("DELETE FROM invtry WHERE item_id = ?");
        $stmt->execute([$item_id]);
        
        // Commit transaction
        $pdo->commit();
        
        // Delete associated image files if they exist
        foreach ($images as $image) {
            if (!empty($image['image']) && file_exists($image['image'])) {
                unlink($image['image']);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Inventory item deleted successfully!'
        ]);
        exit();
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
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

