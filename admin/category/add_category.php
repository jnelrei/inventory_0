<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get form data
    $category_name = trim($_POST['category_name'] ?? '');
    
    // Validation
    if (empty($category_name)) {
        $errors[] = "Category name is required";
    } else {
        // Check if category name already exists
        try {
            $stmt = $pdo->prepare("SELECT category_id FROM category WHERE category_name = ?");
            $stmt->execute([$category_name]);
            if ($stmt->fetch()) {
                $errors[] = "Category name already exists";
            }
        } catch(PDOException $e) {
            $errors[] = "Error validating category: " . $e->getMessage();
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO category (category_name, created_at) VALUES (?, NOW())");
            $stmt->execute([$category_name]);
            
            $_SESSION['category_message'] = 'Category added successfully!';
            $_SESSION['category_success'] = true;
            header('Location: category.php');
            exit();
            
        } catch(PDOException $e) {
            $_SESSION['category_message'] = 'Error adding category: ' . $e->getMessage();
            $_SESSION['category_success'] = false;
            header('Location: category.php');
            exit();
        }
    } else {
        $_SESSION['category_message'] = implode('<br>', $errors);
        $_SESSION['category_success'] = false;
        header('Location: category.php');
        exit();
    }
} else {
    $_SESSION['category_message'] = 'Invalid request method';
    $_SESSION['category_success'] = false;
    header('Location: category.php');
    exit();
}
?>

