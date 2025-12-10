<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get form data
    $discount_value = trim($_POST['discount_value'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $status = trim($_POST['status'] ?? '');
    
    // Validation
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
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO discount (discount_value, start_date, end_date, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$discount_value, $start_date, $end_date, $status]);
            
            $_SESSION['discount_message'] = 'Discount added successfully!';
            $_SESSION['discount_success'] = true;
            header('Location: discount.php');
            exit();
            
        } catch(PDOException $e) {
            $_SESSION['discount_message'] = 'Error adding discount: ' . $e->getMessage();
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

