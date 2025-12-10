<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../production/includes/db.php");

// Fetch inventory items with available stock
try {
    $stmt = $pdo->query("SELECT i.item_id, i.item_name, i.category_id, c.category_name, i.description, i.quantity, i.total_cost, i.picture, i.barcode, i.created_at FROM invtry i LEFT JOIN category c ON i.category_id = c.category_id WHERE i.quantity > 0 ORDER BY i.item_name ASC");
    $inventory_items = $stmt->fetchAll();
} catch(PDOException $e) {
    $inventory_items = [];
    $error_message = "Error loading inventory: " . $e->getMessage();
}

// Fetch active discounts
try {
    $current_date = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT disc_id, discount_value, start_date, end_date, status FROM discount WHERE status = 'active' AND start_date <= ? AND end_date >= ? ORDER BY discount_value DESC");
    $stmt->execute([$current_date, $current_date]);
    $discounts = $stmt->fetchAll();
} catch(PDOException $e) {
    $discounts = [];
    $error_message = "Error loading discounts: " . $e->getMessage();
}

include("../admin_components/header.php");
include("../admin_components/navigation.php");
include("../admin_components/sidebar.php");
include("../admin_components/top_navigation.php");
?>

<div class="col-md-12 col-sm-12">
    <div class="x_panel" style="box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px;">
        <div class="x_content" style="padding: 10px;">
            <div class="row" style="display: flex; align-items: stretch;">
                <!-- Product Selection Area -->
                <div class="col-md-8" style="display: flex;">
                    <div class="x_panel" style="min-height: 650px; height: 100%; box-shadow: 0 2px 4px rgba(0,0,0,0.08); border-radius: 8px; border: none; display: flex; flex-direction: column;">
                        <div class="x_title" style="border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px;">
                            <h3 style="color: #2A3F54; font-weight: 600; margin: 0;">
                                <i class="fa fa-shopping-bag" style="color: #26B99A; margin-right: 8px;"></i>Products
                            </h3>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content" style="flex: 1; display: flex; flex-direction: column;">
                            <!-- Search Bar -->
                            <div class="form-group" style="position: relative; margin-bottom: 25px; flex-shrink: 0;">
                                <i class="fa fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; z-index: 10;"></i>
                                <input type="text" id="productSearch" class="form-control" placeholder="Search products by name or category..." 
                                       style="font-size: 16px; padding: 12px 12px 12px 40px; border-radius: 25px; border: 2px solid #e0e0e0; transition: all 0.3s;">
                            </div>
                            
                            <!-- Product Grid -->
                            <div id="productGrid" class="row" style="flex: 1; overflow-y: auto; padding: 5px; margin: 0; min-height: 0;">
                                <?php if (empty($inventory_items)): ?>
                                    <div class="col-12 text-center" style="padding: 60px 20px;">
                                        <i class="fa fa-box-open" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
                                        <p style="margin-top: 15px; color: #999; font-size: 16px;">No products available</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($inventory_items as $item): 
                                        $stock_level = $item['quantity'];
                                        $stock_class = $stock_level > 10 ? 'stock-high' : ($stock_level > 5 ? 'stock-medium' : 'stock-low');
                                        
                                        // Calculate image path for cart
                                        $picture_path = isset($item['picture']) && !empty($item['picture']) ? trim($item['picture']) : '';
                                        $img_path_for_cart = '';
                                        if (!empty($picture_path)) {
                                            if (strpos($picture_path, 'http://') === 0 || strpos($picture_path, 'https://') === 0) {
                                                $img_path_for_cart = htmlspecialchars($picture_path);
                                            } else {
                                                if (strpos($picture_path, 'images/') === 0) {
                                                    $img_path_for_cart = '../inventory/' . htmlspecialchars($picture_path);
                                                } else if (strpos($picture_path, 'inventory/images/') === 0) {
                                                    $img_path_for_cart = '../' . htmlspecialchars($picture_path);
                                                } else if (strpos($picture_path, '../') === 0) {
                                                    $img_path_for_cart = htmlspecialchars($picture_path);
                                                } else {
                                                    $img_path_for_cart = '../inventory/images/' . htmlspecialchars(basename($picture_path));
                                                }
                                            }
                                        }
                                    ?>
                                        <div class="col-md-3 col-sm-4 col-xs-6 product-item" 
                                             data-name="<?php echo strtolower(htmlspecialchars($item['item_name'])); ?>"
                                             data-category="<?php echo strtolower(htmlspecialchars($item['category_name'] ?? '')); ?>"
                                             data-item-id="<?php echo $item['item_id']; ?>"
                                             data-price="<?php echo $item['total_cost']; ?>"
                                             data-stock="<?php echo $item['quantity']; ?>"
                                             data-barcode="<?php echo htmlspecialchars($item['barcode'] ?? ''); ?>"
                                             style="margin-bottom: 20px; padding: 0 10px;">
                                            <div class="product-card" 
                                                 onclick="addToCart(<?php echo $item['item_id']; ?>, '<?php echo addslashes($item['item_name']); ?>', <?php echo $item['total_cost']; ?>, <?php echo $item['quantity']; ?>, '<?php echo addslashes($img_path_for_cart); ?>')">
                                                <div class="product-image-container">
                                                    <?php 
                                                    if (!empty($img_path_for_cart)): 
                                                    ?>
                                                        <img src="<?php echo $img_path_for_cart; ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" 
                                                             class="product-image"
                                                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27150%27 height=%27150%27%3E%3Crect fill=%27%23f0f0f0%27 width=%27150%27 height=%27150%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';">
                                                    <?php else: ?>
                                                        <img src="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27150%27 height=%27150%27%3E%3Crect fill=%27%23f0f0f0%27 width=%27150%27 height=%27150%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E" 
                                                             alt="No Image" class="product-image">
                                                    <?php endif; ?>
                                                    <div class="product-overlay">
                                                        <i class="fa fa-cart-plus"></i>
                                                        <span>Add to Cart</span>
                                                    </div>
                                                </div>
                                                <div class="product-info">
                                                    <h5 class="product-name"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                                    <p class="product-category"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></p>
                                                    <div class="product-price">₱ <?php echo number_format($item['total_cost'], 2); ?></div>
                                                    <div class="product-stock <?php echo $stock_class; ?>">
                                                        <i class="fa fa-cubes"></i> Stock: <?php echo $item['quantity']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cart and Payment Area -->
                <div class="col-md-4" style="display: flex;">
                    <div class="x_panel cart-panel" style="min-height: 650px; height: 100%; box-shadow: 0 2px 4px rgba(0,0,0,0.08); border-radius: 8px; border: none; position: sticky; top: 20px; display: flex; flex-direction: column;">
                        <div class="x_title" style="border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="color: #2A3F54; font-weight: 600; margin: 0;">
                                <i class="fa fa-shopping-cart" style="color: #26B99A; margin-right: 8px;"></i>Cart
                                <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
                            </h3>
                            <button type="button" class="btn btn-sm btn-success" id="barcodeScannerBtn" onclick="toggleBarcodeScanner()" style="margin-top: 0; padding: 6px 12px; border-radius: 6px;">
                                <i class="fa fa-barcode"></i> Scan
                            </button>
                        </div>
                        <div class="x_content" style="flex: 1; display: flex; flex-direction: column;">
                            <!-- Cart Items -->
                            <div id="cartItems" class="cart-items-container" style="flex: 1; min-height: 0;">
                                <div class="empty-cart">
                                    <i class="fa fa-shopping-cart" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                                    <p style="color: #999; font-size: 14px;">Cart is empty</p>
                                    <p style="color: #bbb; font-size: 12px; margin-top: 5px;">Click on products to add them</p>
                                </div>
                            </div>
                            
                            <!-- Cart Summary -->
                            <div class="cart-summary" style="flex-shrink: 0;">
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <span id="subtotal" class="amount">₱ 0.00</span>
                                </div>
                                <div class="summary-row">
                                    <span>Total Items:</span>
                                    <span id="totalItems" class="amount">0</span>
                                </div>
                                <div class="summary-row total-row">
                                    <span><strong>Total:</strong></span>
                                    <span id="grandTotal" class="amount total-amount">₱ 0.00</span>
                                </div>
                                
                                <!-- Payment Section -->
                                <div class="payment-section">
                                    <div class="form-group">
                                        <label class="payment-label">
                                            <i class="fa fa-money"></i> Payment Amount
                                        </label>
                                        <input type="number" id="paymentAmount" class="form-control payment-input" 
                                               placeholder="0.00" step="0.01" min="0">
                                    </div>
                                    <div class="form-group">
                                        <label class="payment-label">
                                            <i class="fa fa-exchange"></i> Change
                                        </label>
                                        <input type="text" id="changeAmount" class="form-control change-input" readonly>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-clear" onclick="clearCart()">
                                        <i class="fa fa-trash"></i> Clear Cart
                                    </button>
                                    <button type="button" class="btn btn-checkout" onclick="processCheckout()" id="checkoutBtn" disabled>
                                        <i class="fa fa-cash-register"></i> Checkout
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Barcode Scanner Modal -->
<div class="modal fade" id="barcodeScannerModal" tabindex="-1" role="dialog" aria-labelledby="barcodeScannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 600px;">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #26B99A 0%, #1e9d82 100%); border-radius: 12px 12px 0 0; padding: 20px 25px; border: none;">
                <h5 class="modal-title" id="barcodeScannerModalLabel" style="color: white; font-weight: 600; font-size: 18px;">
                    <i class="fa fa-barcode" style="margin-right: 10px;"></i>Barcode Scanner
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="stopBarcodeScanner()" style="color: white; opacity: 0.9; font-size: 28px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <div id="barcode-scanner-container" style="width: 100%; height: 400px; background: #000; border-radius: 8px; overflow: hidden; position: relative; margin-bottom: 20px;">
                    <div id="interactive" style="width: 100%; height: 100%;"></div>
                    <div id="scanner-overlay" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; text-align: center; pointer-events: none;">
                        <i class="fa fa-barcode" style="font-size: 48px; opacity: 0.3; margin-bottom: 10px;"></i>
                        <p style="font-size: 14px; opacity: 0.7;">Position barcode within the frame</p>
                    </div>
                </div>
                <div id="scanner-status" style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 6px; margin-bottom: 15px;">
                    <p style="margin: 0; color: #666; font-size: 14px;">Ready to scan...</p>
                </div>
                <div style="text-align: center;">
                    <button type="button" class="btn btn-secondary" onclick="stopBarcodeScanner()" style="padding: 10px 25px; border-radius: 6px; margin-right: 10px;">
                        <i class="fa fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
  
<!-- /page content -->
<?php include("../admin_components/footer.php")?>
<?php include("../../production/includes/fd.php")?>

<!-- QuaggaJS Barcode Scanner Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>

<script>
let cart = [];

// Product Search
$('#productSearch').on('keyup', function() {
    const searchTerm = $(this).val().toLowerCase();
    let visibleCount = 0;
    
    $('.product-item').each(function() {
        const name = $(this).data('name');
        const category = $(this).data('category');
        if (name.includes(searchTerm) || category.includes(searchTerm)) {
            $(this).show();
            visibleCount++;
        } else {
            $(this).hide();
        }
    });
    
    // Update search border color
    if (searchTerm.length > 0) {
        $(this).css('border-color', '#26B99A');
    } else {
        $(this).css('border-color', '#e0e0e0');
    }
});

// Add to Cart
function addToCart(itemId, itemName, price, stock, imagePath = '') {
    const existingItem = cart.find(item => item.itemId === itemId);
    
    if (existingItem) {
        if (existingItem.quantity >= stock) {
            Swal.fire({
                icon: 'warning',
                iconColor: '#ffc107',
                title: '<div style="font-size: 22px; font-weight: 600; color: #2A3F54;"><i class="fa fa-exclamation-triangle" style="color: #ffc107; margin-right: 8px;"></i>Stock Limit</div>',
                html: '<div style="color: #856404; font-size: 15px;">Cannot add more items. Stock limit reached.</div>',
                timer: 2500,
                showConfirmButton: false,
                customClass: {
                    popup: 'warning-modal'
                }
            });
            return;
        }
        existingItem.quantity++;
    } else {
        cart.push({
            itemId: itemId,
            itemName: itemName,
            price: parseFloat(price),
            quantity: 1,
            stock: parseInt(stock),
            image: imagePath
        });
    }
    
    updateCartDisplay();
}

// Remove from Cart
function removeFromCart(itemId) {
    cart = cart.filter(item => item.itemId !== itemId);
    updateCartDisplay();
}

// Update Quantity
function updateQuantity(itemId, change) {
    const item = cart.find(i => i.itemId === itemId);
    if (item) {
        const newQuantity = item.quantity + change;
        if (newQuantity <= 0) {
            removeFromCart(itemId);
        } else if (newQuantity > item.stock) {
            Swal.fire({
                icon: 'warning',
                iconColor: '#ffc107',
                title: '<div style="font-size: 22px; font-weight: 600; color: #2A3F54;"><i class="fa fa-exclamation-triangle" style="color: #ffc107; margin-right: 8px;"></i>Stock Limit</div>',
                html: '<div style="color: #856404; font-size: 15px;">Cannot exceed available stock.</div>',
                timer: 2500,
                showConfirmButton: false,
                customClass: {
                    popup: 'warning-modal'
                }
            });
        } else {
            item.quantity = newQuantity;
            updateCartDisplay();
        }
    }
}

// Clear Cart
function clearCart() {
    if (cart.length === 0) return;
    
    Swal.fire({
        title: 'Clear Cart?',
        text: 'Are you sure you want to clear all items?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, clear it!'
    }).then((result) => {
        if (result.isConfirmed) {
            cart = [];
            updateCartDisplay();
            $('#paymentAmount').val('');
            $('#changeAmount').val('');
        }
    });
}

// Update Cart Display
function updateCartDisplay() {
    const cartItemsDiv = $('#cartItems');
    
    if (cart.length === 0) {
        cartItemsDiv.html(`
            <div class="empty-cart">
                <i class="fa fa-shopping-cart" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                <p style="color: #999; font-size: 14px;">Cart is empty</p>
                <p style="color: #bbb; font-size: 12px; margin-top: 5px;">Click on products to add them</p>
            </div>
        `);
        $('#checkoutBtn').prop('disabled', true);
        $('#subtotal').text('₱ 0.00');
        $('#grandTotal').text('₱ 0.00');
        $('#totalItems').text('0');
        $('#cartBadge').hide();
        return;
    }
    
    let html = '';
    let subtotal = 0;
    let totalItems = 0;
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        totalItems += item.quantity;
        
        html += `
            <div class="cart-item">
                <div class="cart-item-header">
                    <strong class="cart-item-name">${item.itemName}</strong>
                    <span class="cart-item-price">₱ ${itemTotal.toFixed(2)}</span>
                </div>
                <div class="cart-item-details">
                    <span class="cart-item-unit-price">₱ ${item.price.toFixed(2)} each</span>
                </div>
                <div class="cart-item-controls">
                    <button type="button" class="btn-qty" onclick="updateQuantity(${item.itemId}, -1)">
                        <i class="fa fa-minus"></i>
                    </button>
                    <span class="qty-display">${item.quantity}</span>
                    <button type="button" class="btn-qty" onclick="updateQuantity(${item.itemId}, 1)">
                        <i class="fa fa-plus"></i>
                    </button>
                    <button type="button" class="btn-remove" onclick="removeFromCart(${item.itemId})" title="Remove">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    cartItemsDiv.html(html);
    $('#subtotal').text('₱ ' + subtotal.toFixed(2));
    
    // Calculate and display total with VAT
    const vatRate = 0.12; // 12% VAT
    const vat = subtotal * vatRate;
    const totalWithVat = subtotal + vat;
    $('#grandTotal').text('₱ ' + totalWithVat.toFixed(2));
    $('#totalItems').text(totalItems);
    $('#checkoutBtn').prop('disabled', false);
    $('#cartBadge').text(totalItems).show();
    
    // Update change when payment amount changes
    calculateChange();
}

// Calculate Change
function calculateChange() {
    const subtotal = parseFloat($('#subtotal').text().replace('₱ ', '').replace(',', '')) || 0;
    const vatRate = 0.12; // 12% VAT
    const vat = subtotal * vatRate;
    const totalWithVat = subtotal + vat;
    const payment = parseFloat($('#paymentAmount').val()) || 0;
    const change = payment - totalWithVat;
    
    if (payment === 0) {
        $('#changeAmount').val('');
        $('#changeAmount').css({
            'color': '#666',
            'background': '#f8f9fa',
            'border-color': '#e0e0e0'
        });
    } else if (change >= 0) {
        $('#changeAmount').val('₱ ' + change.toFixed(2));
        $('#changeAmount').css({
            'color': '#26B99A',
            'background': '#d4edda',
            'border-color': '#26B99A'
        });
    } else {
        $('#changeAmount').val('₱ ' + Math.abs(change).toFixed(2) + ' (Insufficient)');
        $('#changeAmount').css({
            'color': '#dc3545',
            'background': '#f8d7da',
            'border-color': '#dc3545'
        });
    }
}

// Payment Amount Change
$('#paymentAmount').on('input', function() {
    calculateChange();
});

// Process Checkout
function processCheckout() {
    if (cart.length === 0) {
        Swal.fire({
            icon: 'warning',
            iconColor: '#ffc107',
            title: '<div style="font-size: 22px; font-weight: 600; color: #2A3F54;"><i class="fa fa-shopping-cart" style="color: #ffc107; margin-right: 8px;"></i>Empty Cart</div>',
            html: '<div style="color: #856404; font-size: 15px;">Please add items to cart before checkout.</div>',
            confirmButtonColor: '#26B99A',
            customClass: {
                popup: 'warning-modal'
            }
        });
        return;
    }
    
    const subtotal = parseFloat($('#subtotal').text().replace('₱ ', '').replace(',', '')) || 0;
    const vatRate = 0.12; // 12% VAT
    const vat = subtotal * vatRate;
    const totalWithVat = subtotal + vat;
    const payment = parseFloat($('#paymentAmount').val()) || 0;
    const change = payment - totalWithVat;
    
    if (payment <= 0) {
        Swal.fire({
            icon: 'warning',
            iconColor: '#ffc107',
            title: '<div style="font-size: 22px; font-weight: 600; color: #2A3F54;"><i class="fa fa-money" style="color: #ffc107; margin-right: 8px;"></i>Payment Required</div>',
            html: '<div style="color: #856404; font-size: 15px;">Please enter payment amount.</div>',
            confirmButtonColor: '#26B99A',
            customClass: {
                popup: 'warning-modal'
            }
        });
        return;
    }
    
    if (change < 0) {
        Swal.fire({
            icon: 'error',
            iconColor: '#dc3545',
            title: '<div style="font-size: 22px; font-weight: 600; color: #2A3F54;"><i class="fa fa-exclamation-circle" style="color: #dc3545; margin-right: 8px;"></i>Insufficient Payment</div>',
            html: '<div style="color: #721c24; font-size: 15px;">Payment amount is less than total amount.</div>',
            confirmButtonColor: '#26B99A',
            customClass: {
                popup: 'error-modal'
            }
        });
        return;
    }
    
    // Build cart items list for display with images
    let itemsList = '';
    let totalItemsCount = 0;
    cart.forEach((item, index) => {
        totalItemsCount += item.quantity;
        const itemImage = item.image || 'data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2780%27 height=%2780%27%3E%3Crect fill=%27%23f0f0f0%27 width=%2780%27 height=%2780%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2712%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';
        itemsList += `
            <div style="display: flex; align-items: center; padding: 10px 0; ${index < cart.length - 1 ? 'border-bottom: 1px solid #e9ecef;' : ''}">
                <div style="width: 60px; height: 60px; border-radius: 8px; overflow: hidden; margin-right: 12px; background: #f8f9fa; flex-shrink: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.08);">
                    <img src="${itemImage}" alt="${item.itemName}" style="width: 100%; height: 100%; object-fit: cover;" 
                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2760%27 height=%2760%27%3E%3Crect fill=%27%23f0f0f0%27 width=%2760%27 height=%2760%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2712%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';">
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #2A3F54; margin-bottom: 4px; font-size: 14px; letter-spacing: 0.2px;">${item.itemName}</div>
                    <div style="font-size: 12px; color: #6c757d; font-weight: 500;">Quantity: ${item.quantity}</div>
                </div>
            </div>
        `;
    });
    
    // Build discount dropdown options
    let discountOptions = '<option value="" selected>No Discount</option>';
    <?php if (!empty($discounts)): ?>
        <?php foreach ($discounts as $disc): ?>
            discountOptions += '<option value="<?php echo $disc['disc_id']; ?>" data-value="<?php echo $disc['discount_value']; ?>"><?php echo htmlspecialchars($disc['discount_value']); ?>% Off</option>';
        <?php endforeach; ?>
    <?php endif; ?>
    
    // Initialize discount variables (will be updated when discount is selected)
    let selectedDiscountId = '';
    let selectedDiscountValue = 0;
    let discountAmount = 0;
    let subtotalAfterDiscount = subtotal;
    let finalVat = vat;
    let finalTotal = totalWithVat;
    
    // Confirm checkout
    Swal.fire({
        title: '<div style="font-size: 28px; font-weight: 700; color: #2A3F54; margin-bottom: 0; text-align: center; letter-spacing: -0.5px;">Confirm Checkout?</div>',
        html: `
            <div style="display: flex; gap: 20px; padding: 5px 0; margin-top: 5px;">
                <!-- Left Column: Order Summary -->
                <div style="flex: 1; background: #ffffff; border-radius: 12px; padding: 18px; border: 1px solid #e9ecef; display: flex; flex-direction: column; box-shadow: 0 2px 8px rgba(0,0,0,0.04); max-height: calc(100vh - 280px);">
                    <div style="font-size: 11px; color: #adb5bd; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 15px; font-weight: 700; flex-shrink: 0;">ORDER SUMMARY</div>
                    <div style="overflow-y: auto; overflow-x: hidden; padding-right: 8px; flex: 1; min-height: 0;" class="order-summary-scroll">
                        ${itemsList}
                    </div>
                </div>
                
                <!-- Right Column: Payment Details -->
                <div style="flex: 1; background: #ffffff; border-radius: 12px; padding: 18px; border: 1px solid #e9ecef; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
                    <div style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e9ecef;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <div style="display: flex; align-items: center;">
                                <i class="fa fa-shopping-cart" style="color: #6c757d; margin-right: 10px; font-size: 16px;"></i>
                                <span style="font-weight: 600; color: #495057; font-size: 13px;">Subtotal:</span>
                            </div>
                        </div>
                        <div style="font-size: 18px; font-weight: 600; color: #495057; text-align: right; letter-spacing: -0.3px;">₱ ${subtotal.toFixed(2)}</div>
                    </div>
                    
                    <!-- Discount Dropdown -->
                    <div style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e9ecef;">
                        <div style="margin-bottom: 10px;">
                            <label style="display: flex; align-items: center; margin-bottom: 8px; font-weight: 600; color: #495057; font-size: 13px;">
                                <i class="fa fa-tag" style="color: #26B99A; margin-right: 10px; font-size: 16px;"></i>
                                Discount:
                            </label>
                            <select id="checkoutDiscountSelect" class="form-control" style="padding: 2px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: all 0.3s; background: white;">
                                ${discountOptions}
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e9ecef;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <div style="display: flex; align-items: center;">
                                <i class="fa fa-percent" style="color: #6c757d; margin-right: 10px; font-size: 16px;"></i>
                                <span style="font-weight: 600; color: #495057; font-size: 13px;">VAT (12%):</span>
                            </div>
                        </div>
                        <div id="vatAmountDisplay" style="font-size: 18px; font-weight: 600; color: #495057; text-align: right; letter-spacing: -0.3px;">₱ ${vat.toFixed(2)}</div>
                    </div>
                    
                    <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <div style="display: flex; align-items: center;">
                            <i class="fa fa-shopping-cart" style="color: #26B99A; margin-right: 10px; font-size: 18px;"></i>
                                <span style="font-weight: 700; color: #2A3F54; font-size: 15px;">Total Amount:</span>
                        </div>
                        </div>
                        <div id="totalAmountDisplay" style="font-size: 28px; font-weight: 700; color: #000000; text-align: right; letter-spacing: -0.5px;">₱ ${totalWithVat.toFixed(2)}</div>
                    </div>
                    
                    <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <div style="display: flex; align-items: center;">
                                <i class="fa fa-money-bill-wave" style="color: #26B99A; margin-right: 10px; font-size: 18px;"></i>
                                <span style="font-weight: 600; color: #495057; font-size: 14px;">Payment:</span>
                        </div>
                        </div>
                        <div style="font-size: 24px; font-weight: 700; color: #2A3F54; text-align: right; letter-spacing: -0.5px;">₱ ${payment.toFixed(2)}</div>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <div style="display: flex; align-items: center;">
                                <i class="fa fa-exchange-alt" style="color: #26B99A; margin-right: 10px; font-size: 18px;"></i>
                                <span style="font-weight: 600; color: #495057; font-size: 14px;">Change:</span>
                        </div>
                        </div>
                        <div id="changeAmountDisplay" style="font-size: 24px; font-weight: 700; color: #007bff; text-align: right; letter-spacing: -0.5px;">₱ ${change.toFixed(2)}</div>
                    </div>
                </div>
            </div>
        `,
        icon: null,
        showCancelButton: true,
        confirmButtonColor: '#26B99A',
        cancelButtonColor: '#495057',
        confirmButtonText: '<i class="fa fa-check" style="margin-right: 8px;"></i> Yes, checkout!',
        cancelButtonText: '<i class="fa fa-times" style="margin-right: 8px;"></i> Cancel',
        buttonsStyling: true,
        customClass: {
            popup: 'checkout-modal',
            confirmButton: 'swal-confirm-btn',
            cancelButton: 'swal-cancel-btn'
        },
        width: '65%',
        maxWidth: '600px',
        padding: '1.5rem 2rem',
        heightAuto: true,
        allowOutsideClick: false,
        didOpen: () => {
            // Initialize discount values
            selectedDiscountId = '';
            selectedDiscountValue = 0;
            discountAmount = 0;
            subtotalAfterDiscount = subtotal;
            finalVat = vat;
            finalTotal = totalWithVat;
            
            // Handle discount selection
            const discountSelect = document.getElementById('checkoutDiscountSelect');
            if (discountSelect) {
                // Set "No Discount" as default (first option)
                discountSelect.value = '';
                discountSelect.selectedIndex = 0;
                
                discountSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    selectedDiscountId = this.value;
                    selectedDiscountValue = selectedOption ? parseFloat(selectedOption.getAttribute('data-value') || 0) : 0;
                    
                    // Calculate discount amount
                    if (selectedDiscountValue > 0) {
                        discountAmount = (subtotal * selectedDiscountValue) / 100;
                        subtotalAfterDiscount = subtotal - discountAmount;
                    } else {
                        discountAmount = 0;
                        subtotalAfterDiscount = subtotal;
                    }
                    
                    // Recalculate VAT on discounted subtotal
                    finalVat = subtotalAfterDiscount * vatRate;
                    finalTotal = subtotalAfterDiscount + finalVat;
                    
                    // Update display
                    const vatAmountDisplay = document.getElementById('vatAmountDisplay');
                    const totalAmountDisplay = document.getElementById('totalAmountDisplay');
                    const changeAmountDisplay = document.getElementById('changeAmountDisplay');
                    
                    vatAmountDisplay.textContent = '₱ ' + finalVat.toFixed(2);
                    totalAmountDisplay.textContent = '₱ ' + finalTotal.toFixed(2);
                    
                    // Update change calculation using the payment value from outer scope
                    const change = payment - finalTotal;
                    if (changeAmountDisplay) {
                        changeAmountDisplay.textContent = '₱ ' + change.toFixed(2);
                        if (change < 0) {
                            changeAmountDisplay.style.color = '#dc3545';
                        } else {
                            changeAmountDisplay.style.color = '#007bff';
                        }
                    }
                });
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading animation
            Swal.fire({
                title: 'Processing...',
                html: '<div style="text-align: center;"><div class="loading-spinner" style="width: 60px; height: 60px; border: 5px solid #f3f3f3; border-top: 5px solid #26B99A; border-radius: 50%; margin: 20px auto;"></div><p style="margin-top: 20px; color: #666; font-size: 16px; font-weight: 500;">Please wait while we process your checkout...</p></div>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                customClass: {
                    popup: 'loading-modal'
                },
                width: '400px'
            });
            
            // Get final values (with discount if applied)
            // Use the values that were updated by the discount handler
            const finalSubtotal = subtotalAfterDiscount;
            const finalChange = payment - finalTotal;
            
            // Send checkout data to server
            $.ajax({
                url: 'checkout.php',
                type: 'POST',
                data: {
                    cart: JSON.stringify(cart),
                    subtotal: finalSubtotal,
                    vat: finalVat,
                    total: finalTotal,
                    payment: payment,
                    change: finalChange,
                    discount_id: selectedDiscountId || '',
                    discount_value: selectedDiscountValue || 0,
                    discount_amount: discountAmount || 0
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Close loading modal and show success
                        Swal.close();
                        
                        // Build success modal HTML with discount if applied
                        let successHtml = `
                            <div style="padding: 5px 0; margin-top: 10px;">
                                <div style="background: #ffffff; border-radius: 12px; padding: 18px; border: 1px solid #e9ecef; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                                    <div style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e9ecef;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                            <div style="display: flex; align-items: center;">
                                                <i class="fa fa-shopping-cart" style="color: #6c757d; margin-right: 10px; font-size: 16px;"></i>
                                                <span style="font-weight: 600; color: #495057; font-size: 13px;">Subtotal:</span>
                                            </div>
                                        </div>
                                        <div style="font-size: 18px; font-weight: 600; color: #495057; text-align: right; letter-spacing: -0.3px;">₱ ${finalSubtotal.toFixed(2)}</div>
                                    </div>`;
                        
                        if (discountAmount > 0) {
                            successHtml += `
                                    <div style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e9ecef;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                            <div style="display: flex; align-items: center;">
                                                <i class="fa fa-tag" style="color: #26B99A; margin-right: 10px; font-size: 16px;"></i>
                                                <span style="font-weight: 600; color: #26B99A; font-size: 13px;">Discount (${selectedDiscountValue}%):</span>
                                            </div>
                                        </div>
                                        <div style="font-size: 18px; font-weight: 600; color: #26B99A; text-align: right; letter-spacing: -0.3px;">-₱ ${discountAmount.toFixed(2)}</div>
                                    </div>`;
                        }
                        
                        successHtml += `
                                    <div style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e9ecef;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                            <div style="display: flex; align-items: center;">
                                                <i class="fa fa-percent" style="color: #6c757d; margin-right: 10px; font-size: 16px;"></i>
                                                <span style="font-weight: 600; color: #495057; font-size: 13px;">VAT (12%):</span>
                                            </div>
                                        </div>
                                        <div style="font-size: 18px; font-weight: 600; color: #495057; text-align: right; letter-spacing: -0.3px;">₱ ${finalVat.toFixed(2)}</div>
                                    </div>
                                    
                                    <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                            <div style="display: flex; align-items: center;">
                                                <i class="fa fa-shopping-cart" style="color: #26B99A; margin-right: 10px; font-size: 18px;"></i>
                                                <span style="font-weight: 700; color: #2A3F54; font-size: 15px;">Total Amount:</span>
                                            </div>
                                        </div>
                                        <div style="font-size: 28px; font-weight: 700; color: #000000; text-align: right; letter-spacing: -0.5px;">₱ ${finalTotal.toFixed(2)}</div>
                                    </div>
                                    
                                    <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                            <div style="display: flex; align-items: center;">
                                                <i class="fa fa-money-bill-wave" style="color: #26B99A; margin-right: 10px; font-size: 18px;"></i>
                                                <span style="font-weight: 600; color: #495057; font-size: 14px;">Payment:</span>
                                            </div>
                                        </div>
                                        <div style="font-size: 24px; font-weight: 700; color: #2A3F54; text-align: right; letter-spacing: -0.5px;">₱ ${payment.toFixed(2)}</div>
                                    </div>
                                    
                                    <div style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-radius: 10px; padding: 12px; margin-top: 8px; border: 1px solid #a5d6a7;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                            <div style="display: flex; align-items: center;">
                                                <i class="fa fa-exchange-alt" style="color: #26B99A; margin-right: 10px; font-size: 18px;"></i>
                                                <span style="font-weight: 700; color: #26B99A; font-size: 14px;">Change:</span>
                                            </div>
                                        </div>
                                        <div style="font-size: 28px; font-weight: 700; color: #007bff; text-align: right; letter-spacing: -0.5px;">₱ ${finalChange.toFixed(2)}</div>
                                    </div>
                                </div>
                            </div>`;
                        
                        Swal.fire({
                            icon: null,
                            title: '<div style="font-size: 28px; font-weight: 700; color: #2A3F54; margin-bottom: 0; text-align: center; letter-spacing: -0.5px;"><i class="fa fa-check-circle" style="color: #26B99A; margin-right: 10px; font-size: 32px;"></i>Checkout Successful!</div>',
                            html: successHtml,
                            confirmButtonText: '<i class="fa fa-check" style="margin-right: 8px;"></i> OK',
                            confirmButtonColor: '#26B99A',
                            customClass: {
                                popup: 'success-modal',
                                confirmButton: 'swal-confirm-btn',
                                htmlContainer: 'success-modal-content'
                            },
                            width: '65%',
                            maxWidth: '500px',
                            padding: '1.5rem 2rem',
                            heightAuto: true,
                            allowOutsideClick: false,
                            scrollbarPadding: false
                        }).then(() => {
                            // Clear cart and reset
                            cart = [];
                            updateCartDisplay();
                            $('#paymentAmount').val('');
                            $('#changeAmount').val('');
                            
                            // Reload page to refresh inventory quantities
                            location.reload();
                        });
                    } else {
                        // Close loading modal and show error
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            iconColor: '#dc3545',
                            title: '<div style="font-size: 22px; font-weight: 600; color: #2A3F54;"><i class="fa fa-times-circle" style="color: #dc3545; margin-right: 8px;"></i>Checkout Failed</div>',
                            html: `<div style="color: #721c24; font-size: 15px;">${response.message || 'An error occurred during checkout.'}</div>`,
                            confirmButtonColor: '#26B99A',
                            customClass: {
                                popup: 'error-modal'
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // Close loading modal and show error
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        iconColor: '#dc3545',
                        title: '<div style="font-size: 22px; font-weight: 600; color: #2A3F54;"><i class="fa fa-exclamation-circle" style="color: #dc3545; margin-right: 8px;"></i>Error</div>',
                        html: `<div style="color: #721c24; font-size: 15px;">An error occurred: ${error}</div>`,
                        confirmButtonColor: '#26B99A',
                        customClass: {
                            popup: 'error-modal'
                        }
                    });
                }
            });
        }
    });
}

// Barcode Scanner Functions
let scannerActive = false;

function toggleBarcodeScanner() {
    if (scannerActive) {
        stopBarcodeScanner();
    } else {
        startBarcodeScanner();
    }
}

function startBarcodeScanner() {
    $('#barcodeScannerModal').modal('show');
    
    // Wait for modal to be fully shown (use one-time event)
    $('#barcodeScannerModal').one('shown.bs.modal', function() {
        if (!scannerActive) {
            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#interactive'),
                    constraints: {
                        width: 640,
                        height: 480,
                        facingMode: "environment" // Use back camera on mobile
                    }
                },
                locator: {
                    patchSize: "medium",
                    halfSample: true
                },
                numOfWorkers: 2,
                decoder: {
                    readers: [
                        "code_128_reader",
                        "ean_reader",
                        "ean_8_reader",
                        "code_39_reader",
                        "code_39_vin_reader",
                        "codabar_reader",
                        "upc_reader",
                        "upc_e_reader",
                        "i2of5_reader"
                    ]
                },
                locate: true
            }, function(err) {
                if (err) {
                    console.error('Quagga initialization error:', err);
                    $('#scanner-status').html('<p style="margin: 0; color: #dc3545; font-size: 14px;"><i class="fa fa-exclamation-triangle"></i> Camera access denied or not available. Please allow camera access.</p>');
                    return;
                }
                Quagga.start();
                scannerActive = true;
                $('#scanner-status').html('<p style="margin: 0; color: #26B99A; font-size: 14px;"><i class="fa fa-check-circle"></i> Scanner active - Point camera at barcode</p>');
            });

            Quagga.onDetected(function(result) {
                const code = result.codeResult.code;
                handleBarcodeScanned(code);
            });
        }
    });
}

function stopBarcodeScanner() {
    if (scannerActive) {
        Quagga.stop();
        scannerActive = false;
    }
    $('#barcodeScannerModal').modal('hide');
}

// Handle when modal is closed
$('#barcodeScannerModal').on('hidden.bs.modal', function() {
    stopBarcodeScanner();
});

function handleBarcodeScanned(barcode) {
    // Stop scanner temporarily
    Quagga.stop();
    scannerActive = false;
    
    // Normalize scanned value (some scanners add spaces/newlines)
    const scannedRaw = (barcode || '').toString();
    const scanned = scannedRaw.trim();
    
    $('#scanner-status').html('<p style="margin: 0; color: #26B99A; font-size: 14px;"><i class="fa fa-spinner fa-spin"></i> Processing barcode: ' + scanned + '</p>');
    
    // Try to find product by barcode, item_id, or name
    let productFound = false;
    
    // First, try to find by barcode field (exact match)
    $('.product-item').each(function() {
        const productBarcodeRaw = $(this).data('barcode');
        if (!productBarcodeRaw) {
            return;
        }

        const productBarcode = productBarcodeRaw.toString().trim();

        // Allow exact match or when one value contains the other (for scanners that add prefixes)
        const exactMatch = productBarcode === scanned;
        const containsMatch = productBarcode.includes(scanned) || scanned.includes(productBarcode);

        if (exactMatch || containsMatch) {
            const itemId = $(this).data('item-id');
            const itemName = $(this).find('.product-name').text();
            const price = parseFloat($(this).data('price'));
            const stock = parseInt($(this).data('stock'));
            const imagePath = $(this).find('.product-image').attr('src') || '';
            
            addToCart(itemId, itemName, price, stock, imagePath);
            productFound = true;
            return false; // Break loop
        }
    });
    
    // If not found by barcode, try to find by item_id if barcode is numeric
    if (!productFound && !isNaN(scanned)) {
        const itemId = parseInt(scanned, 10);
        $('.product-item').each(function() {
            const productItemId = $(this).data('item-id');
            if (productItemId == itemId) {
                const itemName = $(this).find('.product-name').text();
                const price = parseFloat($(this).data('price'));
                const stock = parseInt($(this).data('stock'));
                const imagePath = $(this).find('.product-image').attr('src') || '';
                
                addToCart(itemId, itemName, price, stock, imagePath);
                productFound = true;
                return false; // Break loop
            }
        });
    }
    
    // If not found by ID, try to find by name (partial match)
    if (!productFound) {
        const barcodeLower = barcode.toLowerCase();
        $('.product-item').each(function() {
            const productName = $(this).data('name');
            if (productName && productName.includes(barcodeLower)) {
                const itemId = $(this).data('item-id');
                const itemName = $(this).find('.product-name').text();
                const price = parseFloat($(this).data('price'));
                const stock = parseInt($(this).data('stock'));
                const imagePath = $(this).find('.product-image').attr('src') || '';
                
                addToCart(itemId, itemName, price, stock, imagePath);
                productFound = true;
                return false; // Break loop
            }
        });
    }
    
    if (productFound) {
        $('#scanner-status').html('<p style="margin: 0; color: #26B99A; font-size: 14px;"><i class="fa fa-check-circle"></i> Product added to cart!</p>');
        
        // Show success notification
        Swal.fire({
            icon: 'success',
            title: 'Product Added!',
            text: 'Item has been added to cart',
            timer: 1500,
            showConfirmButton: false,
            customClass: {
                popup: 'success-modal'
            }
        });
        
        // Restart scanner after a short delay
        setTimeout(function() {
            if ($('#barcodeScannerModal').hasClass('show') && !scannerActive) {
                Quagga.start();
                scannerActive = true;
                $('#scanner-status').html('<p style="margin: 0; color: #26B99A; font-size: 14px;"><i class="fa fa-check-circle"></i> Scanner active - Point camera at barcode</p>');
            }
        }, 2000);
    } else {
        $('#scanner-status').html('<p style="margin: 0; color: #dc3545; font-size: 14px;"><i class="fa fa-exclamation-circle"></i> Product not found for barcode: ' + barcode + '</p>');
        
        // Show error notification
        Swal.fire({
            icon: 'warning',
            title: 'Product Not Found',
            text: 'No product found for barcode: ' + barcode,
            timer: 2000,
            showConfirmButton: false,
            customClass: {
                popup: 'warning-modal'
            }
        });
        
        // Restart scanner after a short delay
        setTimeout(function() {
            if ($('#barcodeScannerModal').hasClass('show') && !scannerActive) {
                Quagga.start();
                scannerActive = true;
                $('#scanner-status').html('<p style="margin: 0; color: #26B99A; font-size: 14px;"><i class="fa fa-check-circle"></i> Scanner active - Point camera at barcode</p>');
            }
        }, 2000);
    }
}

// Initialize
$(document).ready(function() {
    updateCartDisplay();
    
    // Focus payment input when cart has items
    $(document).on('click', '#checkoutBtn:not(:disabled)', function() {
        if ($('#paymentAmount').val() === '') {
            $('#paymentAmount').focus();
        }
    });
    
    // Auto-focus payment when items added
    $(document).on('DOMSubtreeModified', '#cartItems', function() {
        if (cart.length > 0 && $('#paymentAmount').val() === '') {
            setTimeout(() => $('#paymentAmount').focus(), 300);
        }
    });
});
</script>

<style>
/* Main Styles */
.section-title-sidebar {
    font-weight: 400;
    letter-spacing: 1.2px;
    color: #2A3F54;
    font-size: 20px;
    margin: 0;
    padding: 8px 0;
    position: relative;
    display: inline-block;
}

/* Product Cards */
.product-card {
    border: 2px solid #e8e8e8;
    border-radius: 12px;
    padding: 0;
    background: #fff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
    overflow: hidden;
    cursor: pointer;
    position: relative;
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 24px rgba(38, 185, 154, 0.2);
    border-color: #26B99A;
}

.product-image-container {
    position: relative;
    width: 100%;
    height: 160px;
    overflow: hidden;
    background: #f8f9fa;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.product-card:hover .product-image {
    transform: scale(1.1);
}

.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(38, 185, 154, 0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    color: white;
    font-weight: 600;
}

.product-overlay i {
    font-size: 32px;
    margin-bottom: 8px;
}

.product-card:hover .product-overlay {
    opacity: 1;
}

.product-info {
    padding: 15px;
}

.product-name {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 5px 0;
    color: #2A3F54;
    text-align: center;
    line-height: 1.3;
}

.product-category {
    font-size: 12px;
    color: #999;
    margin: 0 0 10px 0;
    text-align: center;
}

.product-price {
    font-size: 20px;
    font-weight: bold;
    color: #26B99A;
    text-align: center;
    margin: 10px 0;
}

.product-stock {
    font-size: 11px;
    text-align: center;
    padding: 4px 8px;
    border-radius: 12px;
    display: inline-block;
    width: 100%;
    margin-top: 8px;
}

.stock-high {
    background: #d4edda;
    color: #155724;
}

.stock-medium {
    background: #fff3cd;
    color: #856404;
}

.stock-low {
    background: #f8d7da;
    color: #721c24;
}

/* Cart Styles */
.cart-panel {
    background: #fff;
}

.cart-badge {
    background: #26B99A;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    margin-left: 8px;
    vertical-align: middle;
}

.cart-items-container {
    max-height: 380px;
    overflow-y: auto;
    margin-bottom: 20px;
    padding-right: 5px;
}

.empty-cart {
    text-align: center;
    padding: 60px 20px;
}

.cart-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    border: 1px solid #e9ecef;
    transition: all 0.2s;
}

.cart-item:hover {
    background: #f0f0f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.cart-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.cart-item-name {
    font-size: 14px;
    font-weight: 600;
    color: #2A3F54;
    flex: 1;
}

.cart-item-price {
    font-size: 16px;
    font-weight: bold;
    color: #26B99A;
}

.cart-item-details {
    margin-bottom: 10px;
}

.cart-item-unit-price {
    font-size: 12px;
    color: #666;
}

.cart-item-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-qty {
    width: 32px;
    height: 32px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    color: #666;
}

.btn-qty:hover {
    background: #26B99A;
    border-color: #26B99A;
    color: white;
}

.qty-display {
    min-width: 40px;
    text-align: center;
    font-weight: 600;
    color: #2A3F54;
    font-size: 14px;
}

.btn-remove {
    width: 32px;
    height: 32px;
    border: 1px solid #dc3545;
    background: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    color: #dc3545;
    margin-left: auto;
}

.btn-remove:hover {
    background: #dc3545;
    color: white;
}

.cart-summary {
    border-top: 2px solid #e0e0e0;
    padding-top: 20px;
    margin-top: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 14px;
    color: #666;
}

.summary-row .amount {
    font-weight: 600;
    color: #2A3F54;
}

.total-row {
    border-top: 1px solid #e0e0e0;
    padding-top: 12px;
    margin-top: 12px;
    font-size: 18px;
}

.total-amount {
    font-size: 22px;
    color: #26B99A !important;
    font-weight: bold !important;
}

.payment-section {
    border-top: 1px solid #e0e0e0;
    padding-top: 20px;
    margin-top: 20px;
}

.payment-label {
    font-weight: 600;
    color: #2A3F54;
    margin-bottom: 8px;
    display: block;
    font-size: 14px;
}

.payment-label i {
    color: #26B99A;
    margin-right: 6px;
}

.payment-input {
    font-size: 20px;
    font-weight: bold;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    transition: all 0.3s;
}

.payment-input:focus {
    border-color: #26B99A;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, 0.1);
    outline: none;
}

.change-input {
    font-size: 20px;
    font-weight: bold;
    padding: 12px 15px;
    background: #f8f9fa;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
}

.action-buttons {
    margin-top: 25px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.btn-clear {
    background: #fff;
    border: 2px solid #dc3545;
    color: #dc3545;
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
    cursor: pointer;
}

.btn-clear:hover {
    background: #dc3545;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.btn-checkout {
    background: linear-gradient(135deg, #26B99A 0%, #1e9d82 100%);
    border: none;
    color: white;
    padding: 15px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(38, 185, 154, 0.3);
}

.btn-checkout:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(38, 185, 154, 0.4);
}

.btn-checkout:disabled {
    background: #ccc;
    cursor: not-allowed;
    box-shadow: none;
    opacity: 0.6;
}

/* Scrollbar Styles */
#cartItems::-webkit-scrollbar,
#productGrid::-webkit-scrollbar {
    width: 8px;
}

#cartItems::-webkit-scrollbar-track,
#productGrid::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

#cartItems::-webkit-scrollbar-thumb,
#productGrid::-webkit-scrollbar-thumb {
    background: #26B99A;
    border-radius: 10px;
}

#cartItems::-webkit-scrollbar-thumb:hover,
#productGrid::-webkit-scrollbar-thumb:hover {
    background: #1e9d82;
}

/* Modal Styles */
.checkout-modal .swal2-popup,
.success-modal .swal2-popup {
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    overflow: visible !important;
    border: 1px solid rgba(0,0,0,0.05);
    max-height: calc(100vh - 40px) !important;
}

.checkout-modal .swal2-title {
    padding: 0 0 10px 0 !important;
    margin-bottom: 0 !important;
}

.checkout-modal .swal2-html-container {
    margin: 0 !important;
    padding: 0 !important;
}

.success-modal .swal2-html-container,
.success-modal-content {
    overflow: visible !important;
    max-height: none !important;
}

.swal-confirm-btn {
    background: linear-gradient(135deg, #26B99A 0%, #1e9d82 100%) !important;
    border: none !important;
    border-radius: 10px !important;
    padding: 14px 36px !important;
    font-weight: 700 !important;
    font-size: 16px !important;
    letter-spacing: 0.3px !important;
    box-shadow: 0 4px 15px rgba(38, 185, 154, 0.35) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    text-transform: none !important;
}

.swal-confirm-btn:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 25px rgba(38, 185, 154, 0.45) !important;
    background: linear-gradient(135deg, #2dc4a8 0%, #22b08f 100%) !important;
}

.swal-cancel-btn {
    background: #495057 !important;
    border: none !important;
    border-radius: 10px !important;
    padding: 14px 36px !important;
    font-weight: 700 !important;
    font-size: 16px !important;
    letter-spacing: 0.3px !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    text-transform: none !important;
    box-shadow: 0 2px 8px rgba(73, 80, 87, 0.2) !important;
}

.swal-cancel-btn:hover {
    background: #343a40 !important;
    transform: translateY(-3px) !important;
    box-shadow: 0 4px 15px rgba(73, 80, 87, 0.3) !important;
}

.checkout-modal .swal2-icon {
    border: none !important;
    margin: 0 auto 20px !important;
}

.checkout-modal .swal2-icon.swal2-question {
    border-color: #26B99A !important;
    color: #26B99A !important;
}

.success-modal .swal2-icon.swal2-success {
    border-color: #26B99A !important;
    color: #26B99A !important;
}

.success-modal .swal2-icon.swal2-success [class^=swal2-success-line] {
    background-color: #26B99A !important;
}

.success-modal .swal2-icon.swal2-success .swal2-success-ring {
    border-color: rgba(38, 185, 154, 0.3) !important;
}

.warning-modal .swal2-popup,
.error-modal .swal2-popup {
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
}

.warning-modal .swal2-icon {
    border-color: #ffc107 !important;
    color: #ffc107 !important;
}

.error-modal .swal2-icon {
    border-color: #dc3545 !important;
    color: #dc3545 !important;
}

/* Order Summary Scrollable Area */
.order-summary-scroll {
    scrollbar-width: thin;
    scrollbar-color: #26B99A #f8f9fa;
}

.order-summary-scroll::-webkit-scrollbar {
    width: 6px;
}

.order-summary-scroll::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 10px;
}

.order-summary-scroll::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #26B99A 0%, #1e9d82 100%);
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.2);
}

.order-summary-scroll::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #2dc4a8 0%, #22b08f 100%);
}

/* Loading Animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-spinner {
    animation: spin 1s linear infinite;
}

.loading-modal .swal2-popup {
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
}

/* Barcode Scanner Styles */
#barcodeScannerBtn {
    transition: all 0.3s;
}

#barcodeScannerBtn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(38, 185, 154, 0.3);
}

#barcode-scanner-container {
    position: relative;
}

#interactive {
    width: 100%;
    height: 100%;
}

#scanner-overlay {
    z-index: 10;
}

/* QuaggaJS Drawing Canvas Styles */
.drawingBuffer {
    position: absolute;
    left: 0;
    top: 0;
}

/* Discount Dropdown Styles */
#checkoutDiscountSelect {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
    background: white;
    cursor: pointer;
}

#checkoutDiscountSelect:focus {
    border-color: #26B99A;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, 0.1);
    outline: none;
}

#checkoutDiscountSelect:hover {
    border-color: #26B99A;
}

#checkoutDiscountSelect option {
    color: #2A3F54;
    font-style: normal;
}

/* Responsive */
@media (max-width: 768px) {
    .product-card {
        margin-bottom: 15px;
    }
    
    .cart-panel {
        position: relative !important;
        top: 0 !important;
        margin-top: 20px;
    }
    
    .checkout-modal .swal2-popup,
    .success-modal .swal2-popup {
        width: 95% !important;
        padding: 1.5rem !important;
    }
    
    .checkout-modal .swal2-html-container > div {
        flex-direction: column !important;
    }
    
    .checkout-modal .swal2-html-container > div > div {
        margin-bottom: 15px !important;
    }
    
    #barcode-scanner-container {
        height: 300px !important;
    }
}
</style>
