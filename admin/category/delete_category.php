<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = trim($_POST['category_id'] ?? '');
    
    // Validation
    if (empty($category_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Category ID is required'
        ]);
        exit();
    }
    
    // Check if category is being used in inventory
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invtry WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $result = $stmt->fetch();
        
        if ($result && $result['count'] > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete category. It is being used by ' . $result['count'] . ' inventory item(s).'
            ]);
            exit();
        }
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error checking category usage: ' . $e->getMessage()
        ]);
        exit();
    }
    
    // Verify category exists
    try {
        $stmt = $pdo->prepare("SELECT category_id FROM category WHERE category_id = ?");
        $stmt->execute([$category_id]);
        if (!$stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Category not found'
            ]);
            exit();
        }
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error verifying category: ' . $e->getMessage()
        ]);
        exit();
    }
    
    // Delete category
    try {
        $stmt = $pdo->prepare("DELETE FROM category WHERE category_id = ?");
        $stmt->execute([$category_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully!'
        ]);
        exit();
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting category: ' . $e->getMessage()
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

