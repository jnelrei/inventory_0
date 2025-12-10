<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get form data
    $item_name = trim($_POST['item_name'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '0');
    $total_cost = trim($_POST['total_cost'] ?? '0');
    $barcode = trim($_POST['barcode'] ?? '');
    $picture = null;
    
    // Validation
    if (empty($item_name)) {
        $errors[] = "Item name is required";
    }
    
    if (empty($category_id)) {
        $errors[] = "Category is required";
    } else {
        // Verify category exists
        try {
            $stmt = $pdo->prepare("SELECT category_id FROM category WHERE category_id = ?");
            $stmt->execute([$category_id]);
            if (!$stmt->fetch()) {
                $errors[] = "Invalid category selected";
            }
        } catch(PDOException $e) {
            $errors[] = "Error validating category: " . $e->getMessage();
        }
    }
    
    if (empty($quantity) || !is_numeric($quantity) || $quantity < 0) {
        $errors[] = "Valid quantity is required";
    }
    
    if (empty($total_cost) || !is_numeric($total_cost) || $total_cost < 0) {
        $errors[] = "Valid total cost is required";
    }
    
    // Handle file upload
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
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
        
        // Create upload directory if it doesn't exist (inside inventory folder)
        $upload_dir = 'images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid('inv_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $picture = $upload_dir . $unique_filename;
        } else {
            $errors[] = "Failed to upload image";
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            // Generate barcode if not provided
            if (empty($barcode)) {
                $barcode = time() . rand(1000, 9999);
            }
            
            $stmt = $pdo->prepare("INSERT INTO invtry (item_name, category_id, description, quantity, total_cost, picture, barcode, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $item_name,
                $category_id,
                $description ?: null,
                $quantity,
                $total_cost,
                $picture,
                $barcode ?: null
            ]);
            
            $_SESSION['inventory_message'] = 'Inventory item added successfully!';
            $_SESSION['inventory_success'] = true;
            header('Location: inventory.php');
            exit();
            
        } catch(PDOException $e) {
            // If database insert fails, delete uploaded image
            if ($picture && file_exists($picture)) {
                unlink($picture);
            }
            
            $_SESSION['inventory_message'] = 'Error adding inventory item: ' . $e->getMessage();
            $_SESSION['inventory_success'] = false;
            header('Location: inventory.php');
            exit();
        }
    } else {
        // If validation errors, delete uploaded image if any
        if ($picture && file_exists($picture)) {
            unlink($picture);
        }
        
        $_SESSION['inventory_message'] = implode('<br>', $errors);
        $_SESSION['inventory_success'] = false;
        header('Location: inventory.php');
        exit();
    }
} else {
    $_SESSION['inventory_message'] = 'Invalid request method';
    $_SESSION['inventory_success'] = false;
    header('Location: inventory.php');
    exit();
}
?>

