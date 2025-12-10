<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $category_id = trim($_GET['category_id'] ?? '');
    
    // Validation
    if (empty($category_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Category ID is required'
        ]);
        exit();
    }
    
    // Fetch category data
    try {
        $stmt = $pdo->prepare("SELECT category_id, category_name, created_at FROM category WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            echo json_encode([
                'success' => true,
                'data' => $category
            ]);
            exit();
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Category not found'
            ]);
            exit();
        }
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching category: ' . $e->getMessage()
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

