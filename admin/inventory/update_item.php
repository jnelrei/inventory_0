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
    $existing_images_json = trim($_POST['existing_images'] ?? '[]');
    $existing_images = [];
    if (!empty($existing_images_json)) {
        $existing_images = json_decode($existing_images_json, true) ?: [];
    }
    $uploaded_images = [];
    
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
    
    // Handle multiple file uploads if new files are provided
    if (isset($_FILES['picture']) && is_array($_FILES['picture']['name'])) {
        $files = $_FILES['picture'];
        $file_count = count($files['name']);
        
        // Create upload directory if it doesn't exist
        $upload_dir = 'images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        
        for ($i = 0; $i < $file_count; $i++) {
            // Skip if no file was uploaded for this index
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue; // Skip empty file inputs
                } else {
                    $errors[] = "Error uploading file: " . $files['name'][$i];
                    continue;
                }
            }
            
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            // Validate file type
            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = "Invalid image type for " . $file['name'] . ". Only JPEG, PNG, GIF, and WebP are allowed.";
                continue;
            }
            
            // Validate file size (max 5MB)
            if ($file['size'] > $max_size) {
                $errors[] = "Image size exceeds 5MB limit for " . $file['name'];
                continue;
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('inv_', true) . '.' . $file_extension;
            $upload_path = $upload_dir . $unique_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $uploaded_images[] = $upload_dir . $unique_filename;
            } else {
                $errors[] = "Failed to upload image: " . $file['name'];
            }
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
            // Start transaction
            $pdo->beginTransaction();
            
            // Generate barcode if not provided
            if (empty($barcode)) {
                $barcode = time() . rand(1000, 9999);
            }
            
            // Update invtry table
            $stmt = $pdo->prepare("UPDATE invtry SET item_name = ?, category_id = ?, description = ?, quantity = ?, total_cost = ?, barcode = ? WHERE item_id = ?");
            $stmt->execute([
                $item_name,
                $category_id,
                $description ?: null,
                $quantity,
                $total_cost,
                $barcode ?: null,
                $item_id
            ]);
            
            // Get current images from database
            $stmt = $pdo->prepare("SELECT image_id, image FROM inventory_images WHERE item_id = ?");
            $stmt->execute([$item_id]);
            $db_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Find images to delete (images in DB but not in existing_images array)
            $existing_image_paths = array_column($existing_images, 'image');
            $images_to_delete = [];
            foreach ($db_images as $db_img) {
                if (!in_array($db_img['image'], $existing_image_paths)) {
                    $images_to_delete[] = $db_img;
                }
            }
            
            // Delete removed images from database and filesystem
            foreach ($images_to_delete as $img_to_delete) {
                $stmt = $pdo->prepare("DELETE FROM inventory_images WHERE image_id = ?");
                $stmt->execute([$img_to_delete['image_id']]);
                
                // Delete file if it exists
                if (file_exists($img_to_delete['image'])) {
                    unlink($img_to_delete['image']);
                }
            }
            
            // Insert new uploaded images
            $first_image_id = null;
            if (!empty($uploaded_images)) {
                foreach ($uploaded_images as $image_path) {
                    $stmt = $pdo->prepare("INSERT INTO inventory_images (image, item_id, create_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$image_path, $item_id]);
                    
                    // Store first image_id for invtry table
                    if ($first_image_id === null) {
                        $first_image_id = $pdo->lastInsertId();
                    }
                }
            }
            
            // Update image_id in invtry table
            // If we have existing images, use the first one's image_id
            // Otherwise, use the first newly uploaded image's image_id
            $new_image_id = null;
            if (!empty($existing_images)) {
                // Get the first existing image's image_id from database
                $stmt = $pdo->prepare("SELECT image_id FROM inventory_images WHERE item_id = ? AND image = ? LIMIT 1");
                $stmt->execute([$item_id, $existing_images[0]['image']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $new_image_id = $result['image_id'];
                }
            }
            
            // If no existing images but we have new ones, use first new image_id
            if ($new_image_id === null && $first_image_id !== null) {
                $new_image_id = $first_image_id;
            }
            
            // Update invtry table with image_id
            if ($new_image_id !== null) {
                $stmt = $pdo->prepare("UPDATE invtry SET image_id = ? WHERE item_id = ?");
                $stmt->execute([$new_image_id, $item_id]);
            } else {
                // If no images left, set image_id to NULL
                $stmt = $pdo->prepare("UPDATE invtry SET image_id = NULL WHERE item_id = ?");
                $stmt->execute([$item_id]);
            }
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['inventory_message'] = 'Inventory item updated successfully!';
            $_SESSION['inventory_success'] = true;
            header('Location: inventory.php');
            exit();
            
        } catch(PDOException $e) {
            // Rollback transaction
            $pdo->rollBack();
            
            // If database update fails, delete uploaded images
            foreach ($uploaded_images as $image_path) {
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            $_SESSION['inventory_message'] = 'Error updating inventory item: ' . $e->getMessage();
            $_SESSION['inventory_success'] = false;
            header('Location: inventory.php');
            exit();
        }
    } else {
        // If validation errors, delete uploaded images if any
        foreach ($uploaded_images as $image_path) {
            if (file_exists($image_path)) {
                unlink($image_path);
            }
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

