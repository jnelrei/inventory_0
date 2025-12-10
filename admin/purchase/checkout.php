<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get POST data
        $cart = json_decode($_POST['cart'] ?? '[]', true);
        $total = floatval($_POST['total'] ?? 0);
        $payment = floatval($_POST['payment'] ?? 0);
        $change = floatval($_POST['change'] ?? 0);
        
        // Validation
        if (empty($cart) || !is_array($cart)) {
            echo json_encode([
                'success' => false,
                'message' => 'Cart is empty'
            ]);
            exit();
        }
        
        if ($total <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid total amount'
            ]);
            exit();
        }
        
        if ($payment < $total) {
            echo json_encode([
                'success' => false,
                'message' => 'Payment amount is insufficient'
            ]);
            exit();
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Check stock availability and update inventory
        foreach ($cart as $item) {
            $item_id = intval($item['itemId']);
            $quantity = intval($item['quantity']);
            $item_name = htmlspecialchars($item['itemName'] ?? 'Unknown Item');
            
            // Validate quantity
            if ($quantity <= 0) {
                throw new Exception("Invalid quantity for item: {$item_name}");
            }
            
            // Get current stock with row lock to prevent race conditions
            $stmt = $pdo->prepare("SELECT quantity, item_name FROM invtry WHERE item_id = ? FOR UPDATE");
            $stmt->execute([$item_id]);
            $current_stock = $stmt->fetch();
            
            if (!$current_stock) {
                throw new Exception("Item not found: {$item_name} (ID: {$item_id})");
            }
            
            // Check if sufficient stock is available
            if ($current_stock['quantity'] < $quantity) {
                throw new Exception("Insufficient stock for item: {$item_name}. Available: {$current_stock['quantity']}, Requested: {$quantity}");
            }
            
            // Update inventory quantity (subtract purchased quantity)
            $stmt = $pdo->prepare("UPDATE invtry SET quantity = quantity - ? WHERE item_id = ? AND quantity >= ?");
            $stmt->execute([$quantity, $item_id, $quantity]);
            
            // Verify the update was successful
            $rows_affected = $stmt->rowCount();
            if ($rows_affected === 0) {
                throw new Exception("Failed to update inventory for item: {$item_name}. Stock may have changed.");
            }
        }
        
        // Create sales transaction record
        // Extract only item names from cart
        $item_names = [];
        foreach ($cart as $item) {
            $item_name = htmlspecialchars($item['itemName'] ?? 'Unknown Item');
            $quantity = intval($item['quantity'] ?? 1);
            // Format: "Item Name x Quantity" or just "Item Name" if quantity is 1
            if ($quantity > 1) {
                $item_names[] = $item_name . ' x' . $quantity;
            } else {
                $item_names[] = $item_name;
            }
        }
        $items_string = implode(', ', $item_names);
        
        $stmt = $pdo->prepare("
            INSERT INTO sales (total_amount, payment_amount, change_amount, items, created_at, created_by) 
            VALUES (?, ?, ?, ?, NOW(), ?)
        ");
        
        $user_id = $_SESSION['user_id'] ?? null;
        
        $stmt->execute([$total, $payment, $change, $items_string, $user_id]);
        $transaction_id = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Checkout successful',
            'transaction_id' => $transaction_id,
            'total' => $total,
            'change' => $change
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Checkout failed: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>

