<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get form data
    $category_id = trim($_POST['category_id'] ?? '');
    $category_name = trim($_POST['category_name'] ?? '');
    
    // Validation
    if (empty($category_id)) {
        $errors[] = "Category ID is required";
    }
    
    if (empty($category_name)) {
        $errors[] = "Category name is required";
    } else {
        // Check if category name already exists (excluding current category)
        try {
            $stmt = $pdo->prepare("SELECT category_id FROM category WHERE category_name = ? AND category_id != ?");
            $stmt->execute([$category_name, $category_id]);
            if ($stmt->fetch()) {
                $errors[] = "Category name already exists";
            }
        } catch(PDOException $e) {
            $errors[] = "Error validating category: " . $e->getMessage();
        }
    }
    
    // Verify category exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT category_id FROM category WHERE category_id = ?");
            $stmt->execute([$category_id]);
            if (!$stmt->fetch()) {
                $errors[] = "Category not found";
            }
        } catch(PDOException $e) {
            $errors[] = "Error verifying category: " . $e->getMessage();
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE category SET category_name = ? WHERE category_id = ?");
            $stmt->execute([$category_name, $category_id]);
            
            $_SESSION['category_message'] = 'Category updated successfully!';
            $_SESSION['category_success'] = true;
            header('Location: category.php');
            exit();
            
        } catch(PDOException $e) {
            $_SESSION['category_message'] = 'Error updating category: ' . $e->getMessage();
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

