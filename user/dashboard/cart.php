<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once('../../production/includes/db.php');

$userId = $_SESSION['user_id'];

// Fetch active cart and items
$cartItems = [];
$total = 0;
try {
    $stmt = $pdo->prepare("
        SELECT ci.id as cart_item_id,
               ci.item_id,
               ci.quantity,
               ci.price,
               ci.subtotal,
               i.item_name,
               i.picture,
               i.quantity as stock_quantity
        FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.cart_id
        JOIN invtry i ON ci.item_id = i.item_id
        WHERE c.user_id = ? AND c.status = 'active'
        ORDER BY ci.id DESC
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cartItems as $row) {
        $total += ($row['price'] * $row['quantity']);
    }
} catch (PDOException $e) {
    $cartItems = [];
    $total = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f7f9fc;
            color: #2f4050;
            padding: 30px;
        }
        .page {
            max-width: 1500px;
            min-height: 90vh;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            padding: 24px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .page-title {
            font-size: 22px;
            font-weight: 700;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-secondary {
            background: #0d6efd;
            color: #fff;
            width: 42px;
            height: 42px;
            padding: 0;
            justify-content: center;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(13,110,253,0.25);
        }
        .btn-secondary:hover { background: #0b5ed7; }
        .btn-primary { background: #1ABB9C; color: #fff; }
        .btn-primary:hover { background: #117a65; }
        .table-wrapper {
            margin-top: 8px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            max-height: 60vh;
            overflow-y: auto;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        .table th {
            font-size: 14px;
            color: #6c757d;
            font-weight: 600;
        }
        .product-info { display: flex; align-items: center; gap: 12px; }
        .product-img {
            width: 60px; height: 60px; border-radius: 6px;
            object-fit: cover; background: #f1f3f5;
        }
        .product-name { font-weight: 600; font-size: 14px; color: #2f4050; }
        .price { font-weight: 700; color: #1ABB9C; }
        .qty-control {
            display: inline-flex;
            align-items: center;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            overflow: hidden;
        }
        .qty-btn {
            width: 28px; height: 32px; border: none; background: #f8f9fa; cursor: pointer;
            color: #2f4050; font-weight: 700;
        }
        .qty-btn:hover { background: #1ABB9C; color: #fff; }
        .qty-input {
            width: 48px; height: 32px; border: none; text-align: center; font-weight: 600;
        }
        .remove-btn {
            color: #dc3545; cursor: pointer; border: none; background: transparent; font-size: 16px;
        }
        .summary {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .summary-card {
            min-width: 260px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid #e9ecef;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #6c757d;
        }
        .summary-row.total {
            font-size: 18px;
            font-weight: 700;
            color: #2f4050;
            margin-top: 8px;
        }
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty i { font-size: 48px; color: #adb5bd; margin-bottom: 12px; display: block; }
    </style>
</head>
<body>
    <div class="page">
        <div class="page-header">
            <div class="page-title">Shopping Cart</div>
            <div class="actions">
                <button class="btn btn-primary" id="checkoutBtn"><i class="fas fa-check"></i> Checkout</button>
                <a class="btn btn-secondary" href="usr_dashboard.php" aria-label="Continue Shopping"><i class="fa fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="empty">
                <i class="fas fa-shopping-cart"></i>
                Your cart is empty.
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table" id="cartTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th style="width:160px;">Quantity</th>
                            <th>Subtotal</th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <?php foreach ($cartItems as $item): ?>
                            <tr data-cart-item-id="<?php echo htmlspecialchars($item['cart_item_id']); ?>">
                                <td>
                                    <div class="product-info">
                                        <img class="product-img" src="<?php echo '../../admin/inventory/' . htmlspecialchars($item['picture']); ?>"
                                             onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2760%27 height=%2760%27%3E%3Crect fill=%27%23f1f3f5%27 width=%2760%27 height=%2760%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2710%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';"
                                             alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                                        <div class="product-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                    </div>
                                </td>
                                <td class="price">$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <div class="qty-control">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['cart_item_id']; ?>, -1)">-</button>
                                        <input class="qty-input" type="number" min="1" max="<?php echo (int)$item['stock_quantity']; ?>"
                                               value="<?php echo (int)$item['quantity']; ?>"
                                               onchange="inputQty(<?php echo $item['cart_item_id']; ?>, this.value)">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['cart_item_id']; ?>, 1)">+</button>
                                    </div>
                                </td>
                                <td class="price subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td><button class="remove-btn" onclick="removeItem(<?php echo $item['cart_item_id']; ?>)"><i class="fas fa-trash"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="summary">
                <div class="summary-card">
                    <div class="summary-row"><span>Subtotal</span><span id="subtotal">$<?php echo number_format($total, 2); ?></span></div>
                    <div class="summary-row total"><span>Total</span><span id="total">$<?php echo number_format($total, 2); ?></span></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const CART_API_URL = '../../production/includes/cart_api.php';

        async function callCartAPI(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            for (const key in data) formData.append(key, data[key]);
            const res = await fetch(CART_API_URL, { method: 'POST', body: formData });
            return res.json();
        }

        async function loadCart() {
            const res = await fetch(`${CART_API_URL}?action=get_cart_items`);
            const data = await res.json();
            if (!data.success) return;

            const tbody = document.getElementById('cartBody');
            const subtotalEl = document.getElementById('subtotal');
            const totalEl = document.getElementById('total');
            const checkoutBtn = document.getElementById('checkoutBtn');

            if (!tbody) return;

            if (!data.items || data.items.length === 0) {
                document.querySelector('.page').innerHTML = `
                    <div class="page-header">
                        <div class="page-title">Shopping Cart</div>
                        <div class="actions">
                            <a class="btn btn-secondary" href="usr_dashboard.php"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                        </div>
                    </div>
                    <div class="empty">
                        <i class="fas fa-shopping-cart"></i>
                        Your cart is empty.
                    </div>
                `;
                return;
            }

            let html = '';
            let total = 0;
            data.items.forEach(item => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                html += `
                    <tr data-cart-item-id="${item.cart_item_id}">
                        <td>
                            <div class="product-info">
                                <img class="product-img" src="../../admin/inventory/${item.picture || ''}"
                                     onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2760%27 height=%2760%27%3E%3Crect fill=%27%23f1f3f5%27 width=%2760%27 height=%2760%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2710%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';"
                                     alt="${item.item_name}">
                                <div class="product-name">${item.item_name}</div>
                            </div>
                        </td>
                        <td class="price">$${Number(item.price).toFixed(2)}</td>
                        <td>
                            <div class="qty-control">
                                <button class="qty-btn" onclick="changeQty(${item.cart_item_id}, -1)">-</button>
                                <input class="qty-input" type="number" min="1" max="${item.stock_quantity}"
                                       value="${item.quantity}"
                                       onchange="inputQty(${item.cart_item_id}, this.value)">
                                <button class="qty-btn" onclick="changeQty(${item.cart_item_id}, 1)">+</button>
                            </div>
                        </td>
                        <td class="price subtotal">$${subtotal.toFixed(2)}</td>
                        <td><button class="remove-btn" onclick="removeItem(${item.cart_item_id})"><i class="fas fa-trash"></i></button></td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
            if (subtotalEl) subtotalEl.textContent = '$' + total.toFixed(2);
            if (totalEl) totalEl.textContent = '$' + total.toFixed(2);
            if (checkoutBtn) checkoutBtn.disabled = total <= 0;
        }

        async function changeQty(cartItemId, delta) {
            const row = document.querySelector(`tr[data-cart-item-id="${cartItemId}"]`);
            if (!row) return;
            const input = row.querySelector('.qty-input');
            let newQty = parseInt(input.value || '1', 10) + delta;
            if (newQty < 1) newQty = 1;
            input.value = newQty;
            await updateQty(cartItemId, newQty);
        }

        async function inputQty(cartItemId, value) {
            let qty = parseInt(value || '1', 10);
            if (qty < 1) qty = 1;
            await updateQty(cartItemId, qty);
        }

        async function updateQty(cartItemId, qty) {
            const res = await callCartAPI('update_quantity', { cart_item_id: cartItemId, quantity: qty });
            if (!res.success) {
                alert(res.message || 'Failed to update quantity');
                await loadCart();
                return;
            }
            await loadCart();
        }

        async function removeItem(cartItemId) {
            if (!confirm('Remove this item?')) return;
            const res = await callCartAPI('remove_from_cart', { cart_item_id: cartItemId });
            if (!res.success) {
                alert(res.message || 'Failed to remove item');
                return;
            }
            await loadCart();
        }

        document.getElementById('checkoutBtn')?.addEventListener('click', () => {
            if (confirm('Proceed to checkout?')) {
                alert('Checkout flow not implemented yet.');
            }
        });

        // Initial refresh with live data
        loadCart();
    </script>
</body>
</html>

