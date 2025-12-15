<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $item_id = trim($_GET['item_id'] ?? '');
    
    // Validation
    if (empty($item_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Item ID is required'
        ]);
        exit();
    }
    
    // Fetch item data
    try {
<<<<<<< HEAD
        $stmt = $pdo->prepare("SELECT item_id, item_name, category_id, description, quantity, total_cost, picture, barcode, created_at FROM invtry WHERE item_id = ?");
=======
        $stmt = $pdo->prepare("SELECT item_id, item_name, category_id, description, quantity, total_cost, image_id, barcode, created_at FROM invtry WHERE item_id = ?");
>>>>>>> bffd17eb2ccfbbfa430d2dfe62f4af6da5ab7e21
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
<<<<<<< HEAD
=======
            // Fetch all images for this item
            $stmt = $pdo->prepare("SELECT image_id, image, create_at FROM inventory_images WHERE item_id = ? ORDER BY create_at ASC");
            $stmt->execute([$item_id]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add images array to item data
            $item['images'] = $images;
            
>>>>>>> bffd17eb2ccfbbfa430d2dfe62f4af6da5ab7e21
            echo json_encode([
                'success' => true,
                'data' => $item
            ]);
            exit();
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Item not found'
            ]);
            exit();
        }
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching item: ' . $e->getMessage()
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

