<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get form data
    $disc_id = trim($_POST['disc_id'] ?? '');
    $discount_value = trim($_POST['discount_value'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $status = trim($_POST['status'] ?? '');
    
    // Validation
    if (empty($disc_id)) {
        $errors[] = "Discount ID is required";
    }
    
    if (empty($discount_value)) {
        $errors[] = "Discount value is required";
    } elseif (!is_numeric($discount_value) || $discount_value < 0 || $discount_value > 100) {
        $errors[] = "Discount value must be a number between 0 and 100";
    }
    
    if (empty($start_date)) {
        $errors[] = "Start date is required";
    } elseif (!strtotime($start_date)) {
        $errors[] = "Invalid start date format";
    }
    
    if (empty($end_date)) {
        $errors[] = "End date is required";
    } elseif (!strtotime($end_date)) {
        $errors[] = "Invalid end date format";
    }
    
    // Validate date range
    if (!empty($start_date) && !empty($end_date) && strtotime($start_date) && strtotime($end_date)) {
        if (strtotime($end_date) <= strtotime($start_date)) {
            $errors[] = "End date must be after start date";
        }
    }
    
    if (empty($status)) {
        $errors[] = "Status is required";
    } elseif (!in_array($status, ['active', 'inactive'])) {
        $errors[] = "Invalid status value";
    }
    
    // Verify discount exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT disc_id FROM discount WHERE disc_id = ?");
            $stmt->execute([$disc_id]);
            if (!$stmt->fetch()) {
                $errors[] = "Discount not found";
            }
        } catch(PDOException $e) {
            $errors[] = "Error verifying discount: " . $e->getMessage();
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE discount SET discount_value = ?, start_date = ?, end_date = ?, status = ? WHERE disc_id = ?");
            $stmt->execute([$discount_value, $start_date, $end_date, $status, $disc_id]);
            
            $_SESSION['discount_message'] = 'Discount updated successfully!';
            $_SESSION['discount_success'] = true;
            header('Location: discount.php');
            exit();
            
        } catch(PDOException $e) {
            $_SESSION['discount_message'] = 'Error updating discount: ' . $e->getMessage();
            $_SESSION['discount_success'] = false;
            header('Location: discount.php');
            exit();
        }
    } else {
        $_SESSION['discount_message'] = implode('<br>', $errors);
        $_SESSION['discount_success'] = false;
        header('Location: discount.php');
        exit();
    }
} else {
    $_SESSION['discount_message'] = 'Invalid request method';
    $_SESSION['discount_success'] = false;
    header('Location: discount.php');
    exit();
}
?>

