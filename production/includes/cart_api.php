<?php
session_start();
require_once('db.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_or_create_cart':
            // Get active cart or create a new one
            $stmt = $pdo->prepare("SELECT cart_id FROM carts WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $cart = $stmt->fetch();
            
            if (!$cart) {
                // Create new cart
                $stmt = $pdo->prepare("INSERT INTO carts (user_id, status) VALUES (?, 'active')");
                $stmt->execute([$user_id]);
                $cart_id = $pdo->lastInsertId();
            } else {
                $cart_id = $cart['cart_id'];
            }
            
            echo json_encode(['success' => true, 'cart_id' => $cart_id]);
            break;
            
        case 'add_to_cart':
            $item_id = $_POST['item_id'] ?? null;
            $quantity = intval($_POST['quantity'] ?? 1);
            $price = floatval($_POST['price'] ?? 0);
            
            if (!$item_id || $quantity < 1 || $price <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit();
            }
            
            // Check item availability
            $stmt = $pdo->prepare("SELECT quantity FROM invtry WHERE item_id = ?");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch();
            
            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Item not found']);
                exit();
            }
            
            // Get or create cart
            $stmt = $pdo->prepare("SELECT cart_id FROM carts WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $cart = $stmt->fetch();
            
            if (!$cart) {
                $stmt = $pdo->prepare("INSERT INTO carts (user_id, status) VALUES (?, 'active')");
                $stmt->execute([$user_id]);
                $cart_id = $pdo->lastInsertId();
            } else {
                $cart_id = $cart['cart_id'];
            }
            
            // Check if item already exists in cart
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND item_id = ?");
            $stmt->execute([$cart_id, $item_id]);
            $existing_item = $stmt->fetch();
            
            // Check total quantity in cart + new quantity doesn't exceed stock
            $current_cart_quantity = $existing_item ? $existing_item['quantity'] : 0;
            $new_total_quantity = $current_cart_quantity + $quantity;
            
            if ($new_total_quantity > $item['quantity']) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Cannot add {$quantity} items. Only " . ($item['quantity'] - $current_cart_quantity) . " available in stock!"
                ]);
                exit();
            }
            
            if ($existing_item) {
                // Update existing cart item
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + ?, price = ? WHERE id = ?");
                $stmt->execute([$quantity, $price, $existing_item['id']]);
            } else {
                // Insert new cart item
                $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$cart_id, $item_id, $quantity, $price]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Item added to cart']);
            break;
            
        case 'update_quantity':
            $cart_item_id = $_POST['cart_item_id'] ?? null;
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if (!$cart_item_id || $quantity < 1) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit();
            }
            
            // Get cart item and verify it belongs to user
            $stmt = $pdo->prepare("
                SELECT ci.id, ci.item_id, ci.quantity, i.quantity as stock_quantity 
                FROM cart_items ci
                JOIN carts c ON ci.cart_id = c.cart_id
                JOIN invtry i ON ci.item_id = i.item_id
                WHERE ci.id = ? AND c.user_id = ? AND c.status = 'active'
            ");
            $stmt->execute([$cart_item_id, $user_id]);
            $cart_item = $stmt->fetch();
            
            if (!$cart_item) {
                echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                exit();
            }
            
            if ($quantity > $cart_item['stock_quantity']) {
                echo json_encode(['success' => false, 'message' => 'Quantity exceeds available stock']);
                exit();
            }
            
            // Update quantity
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $stmt->execute([$quantity, $cart_item_id]);
            
            echo json_encode(['success' => true, 'message' => 'Quantity updated']);
            break;
            
        case 'remove_from_cart':
            $cart_item_id = $_POST['cart_item_id'] ?? null;
            
            if (!$cart_item_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit();
            }
            
            // Verify cart item belongs to user
            $stmt = $pdo->prepare("
                SELECT ci.id FROM cart_items ci
                JOIN carts c ON ci.cart_id = c.cart_id
                WHERE ci.id = ? AND c.user_id = ? AND c.status = 'active'
            ");
            $stmt->execute([$cart_item_id, $user_id]);
            
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                exit();
            }
            
            // Delete cart item
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
            $stmt->execute([$cart_item_id]);
            
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            break;
            
        case 'get_cart_items':
            // Get active cart
            $stmt = $pdo->prepare("SELECT cart_id FROM carts WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $cart = $stmt->fetch();
            
            if (!$cart) {
                echo json_encode(['success' => true, 'items' => [], 'total_items' => 0]);
                exit();
            }
            
            // Get cart items with product details
            $stmt = $pdo->prepare("
                SELECT 
                    ci.id as cart_item_id,
                    ci.item_id,
                    ci.quantity,
                    ci.price,
                    ci.subtotal,
                    i.item_name,
                    i.picture,
                    i.quantity as stock_quantity
                FROM cart_items ci
                JOIN invtry i ON ci.item_id = i.item_id
                WHERE ci.cart_id = ?
                ORDER BY ci.id DESC
            ");
            $stmt->execute([$cart['cart_id']]);
            $items = $stmt->fetchAll();
            
            $total_items = array_sum(array_column($items, 'quantity'));
            
            echo json_encode([
                'success' => true, 
                'items' => $items, 
                'total_items' => $total_items,
                'cart_id' => $cart['cart_id']
            ]);
            break;
            
        case 'get_cart_count':
            // Get total items in active cart
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(ci.quantity), 0) as total_items
                FROM cart_items ci
                JOIN carts c ON ci.cart_id = c.cart_id
                WHERE c.user_id = ? AND c.status = 'active'
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            echo json_encode(['success' => true, 'total_items' => intval($result['total_items'])]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>


