<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get form data
    $item_id = trim($_POST['item_id'] ?? '');
    $item_name = trim($_POST['item_name'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '0');
    $total_cost = trim($_POST['total_cost'] ?? '0');
    $barcode = trim($_POST['barcode'] ?? '');
    $existing_picture = trim($_POST['existing_picture'] ?? '');
    $picture = $existing_picture; // Default to existing picture
    
    // Validation
    if (empty($item_id)) {
        $errors[] = "Item ID is required";
    }
    
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
    
    // Handle file upload if new file is provided
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
        
        // Create upload directory if it doesn't exist
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
            // Delete old picture if it exists and is different
            if ($existing_picture && file_exists($existing_picture) && $existing_picture !== $upload_path) {
                unlink($existing_picture);
            }
            $picture = $upload_dir . $unique_filename;
        } else {
            $errors[] = "Failed to upload image";
        }
    }
    
    // Verify item exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT item_id FROM invtry WHERE item_id = ?");
            $stmt->execute([$item_id]);
            if (!$stmt->fetch()) {
                $errors[] = "Item not found";
            }
        } catch(PDOException $e) {
            $errors[] = "Error verifying item: " . $e->getMessage();
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            // Generate barcode if not provided
            if (empty($barcode)) {
                $barcode = time() . rand(1000, 9999);
            }
            
            $stmt = $pdo->prepare("UPDATE invtry SET item_name = ?, category_id = ?, description = ?, quantity = ?, total_cost = ?, picture = ?, barcode = ? WHERE item_id = ?");
            $stmt->execute([
                $item_name,
                $category_id,
                $description ?: null,
                $quantity,
                $total_cost,
                $picture,
                $barcode ?: null,
                $item_id
            ]);
            
            $_SESSION['inventory_message'] = 'Inventory item updated successfully!';
            $_SESSION['inventory_success'] = true;
            header('Location: inventory.php');
            exit();
            
        } catch(PDOException $e) {
            // If database update fails, delete uploaded image if it was new
            if ($picture !== $existing_picture && $picture && file_exists($picture)) {
                unlink($picture);
            }
            
            $_SESSION['inventory_message'] = 'Error updating inventory item: ' . $e->getMessage();
            $_SESSION['inventory_success'] = false;
            header('Location: inventory.php');
            exit();
        }
    } else {
        // If validation errors, delete uploaded image if it was new
        if (isset($file) && isset($upload_path) && file_exists($upload_path)) {
            unlink($upload_path);
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

