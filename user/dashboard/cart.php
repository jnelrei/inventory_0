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
$totalItems = 0;
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
        $totalItems += $row['quantity'];
    }
} catch (PDOException $e) {
    $cartItems = [];
    $total = 0;
    $totalItems = 0;
}

// Calculate tax (8% example)
$taxRate = 0.08;
$tax = $total * $taxRate;
$shipping = $total > 50 ? 0 : 5.99; // Free shipping over $50
$finalTotal = $total + $tax + $shipping;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Ecommerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/typeit@8.7.1/dist/index.umd.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #f9fafb;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 8px;
            --transition: all 0.2s ease-in-out;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', 'Inter', sans-serif;
            background: #f8fafc;
            color: var(--text);
            line-height: 1.6;
            padding: 0;
            min-height: 100vh;
            position: relative;
            width: 100%;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .container {
            max-width: 1600px;
            margin-left: auto;
            margin-right: auto;
            padding: 30px;
            padding-right: 500px;
            background: #f8fafc;
            min-height: 100vh;
            box-sizing: border-box;
            display: block;
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .empty-cart-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 30px;
            background: #f8fafc;
            min-height: 100vh;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.6s ease-in;
        }

        .empty-cart {
            animation: fadeInScale 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin: 0 auto 2rem auto;
            max-width: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-card:hover {
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .cart-content-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0;
            min-height: 700px;
        }

        .cart-items-card {
            background: #fff;
            padding: 32px;
            min-height: 600px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            flex: 1;
        }

        .cart-items {
            padding: 0;
        }

        .cart-item {
            display: flex;
            gap: 2rem;
            padding: 2rem;
            background: #fff;
            border-bottom: 1px solid var(--border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: slideInLeft 0.5s ease-out;
            transform-origin: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item:hover {
            background: #f8fafc;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .cart-item.removing {
            animation: slideOutRight 0.4s ease-in forwards;
            opacity: 0;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(30px);
            }
        }

        .item-image {
            width: 150px;
            height: 150px;
            min-width: 150px;
            object-fit: cover;
            border-radius: var(--radius);
            background: #f8fafc;
            border: 1px solid var(--border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInScale 0.6s ease-out;
        }

        .item-image:hover {
            transform: scale(1.05) rotate(1deg);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .item-details {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex: 1;
            position: relative;
        }

        .item-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text);
            transition: var(--transition);
            text-decoration: none;
            display: block;
        }

        .item-name:hover {
            color: var(--primary);
        }

        .item-variant {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 12px;
        }

        .item-price {
            font-size: 24px;
            font-weight: 700;
            color: #1ABB9C;
            margin-bottom: 6px;
            transition: all 0.3s ease;
        }

        .item-stock {
            font-size: 13px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .item-stock.low {
            color: #dc3545;
            font-weight: 600;
        }

        .item-stock.low i {
            animation: shake 0.5s infinite;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-3px);
            }

            75% {
                transform: translateX(3px);
            }
        }

        .item-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .qty-control {
            display: flex;
            align-items: center;
            margin-top: 0.75rem;
        }

        .qty-btn {
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            color: var(--text);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .qty-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(79, 70, 229, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.4s ease, height 0.4s ease;
        }

        .qty-btn:hover:not(:disabled) {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(79, 70, 229, 0.3);
        }

        .qty-btn:hover:not(:disabled)::before {
            width: 100px;
            height: 100px;
        }

        .qty-btn:active:not(:disabled) {
            transform: scale(0.95);
        }

        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .qty-input {
            width: 3rem;
            height: 2rem;
            text-align: center;
            border: 1px solid var(--border);
            border-radius: 4px;
            margin: 0 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .qty-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
        }

        .remove-btn {
            background: transparent;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 16px;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            position: relative;
        }

        .remove-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(220, 53, 69, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }

        .remove-btn:hover {
            background: #fee;
            color: #dc3545;
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
        }

        .remove-btn:hover::before {
            width: 40px;
            height: 40px;
        }

        .remove-btn:active {
            transform: scale(0.9) rotate(-5deg);
        }

        .item-subtotal {
            font-size: 24px;
            font-weight: 700;
            color: #2f4050;
            margin-top: 16px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
        }

        .item-subtotal.updating {
            animation: pulsePrice 0.6s ease;
            color: #1ABB9C;
        }

        @keyframes pulsePrice {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.15);
            }
        }

        .cart-sidebar {
            position: fixed;
            top: 20px;
            right: calc((100vw - 1600px) / 2 + 30px);
            width: 450px;
            height: fit-content;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            background: #fff;
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            z-index: 100;
            animation: slideInRight 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .cart-sidebar:hover {
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @media (min-width: 1600px) {
            .cart-sidebar {
                right: calc((100vw - 1600px) / 2 + 30px);
            }
        }
        
        @media (max-width: 1630px) {
            .cart-sidebar {
                right: 30px;
            }
        }

        @media (max-width: 1024px) {
            .container {
                padding-right: 30px;
            }
            .cart-sidebar {
                position: relative;
                top: 0;
                right: auto;
                width: 100%;
                max-height: none;
            }
        }

        .summary-card {
            background: transparent;
            border-radius: 0;
            padding: 0;
            box-shadow: none;
            border: none;
            animation: fadeInUp 0.6s ease;
            width: 100%;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .summary-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            color: var(--text-light);
        }

        .summary-row.total {
            margin-bottom: 0;
            padding-top: 0.75rem;
            margin-top: 0.75rem;
            border-top: 1px solid var(--border);
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text);
        }

        .summary-row.total span:last-child {
            font-size: 1.3rem;
        }

        .summary-row span:last-child {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
        }

        .summary-row.updating span:last-child {
            animation: bounceNumber 0.5s ease;
        }

        @keyframes bounceNumber {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
                color: #1ABB9C;
            }
        }

        .promo-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .promo-section:hover {
            border-color: #1ABB9C;
            background: #f0fdf4;
            box-shadow: 0 2px 6px rgba(26, 187, 156, 0.1);
        }

        .promo-section.applied {
            background: #d1fae5;
            border-color: #1ABB9C;
            border-width: 2px;
        }

        .promo-label {
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
            color: var(--text);
            font-size: 14px;
        }

        .promo-input-group {
            display: flex;
            gap: 8px;
        }

        .promo-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #fff;
        }

        .promo-input:focus {
            outline: none;
            border-color: #1ABB9C;
            box-shadow: 0 0 0 3px rgba(26, 187, 156, 0.1);
        }

        .btn-apply {
            padding: 12px 20px;
            background: #e9ecef;
            color: var(--text);
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-apply:hover {
            background: #dee2e6;
            transform: scale(1.05);
        }

        .btn-apply:active {
            transform: scale(0.95);
        }

        .shipping-info {
            background: #e7f5ff;
            border-left: 3px solid #1ABB9C;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .shipping-info.free {
            background: #d1fae5;
            border-left-color: #10b981;
        }

        .checkout-btn {
            width: 100%;
            padding: 0.9rem;
            font-size: 1rem;
            font-weight: 600;
            margin-top: 1.25rem;
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: none;
        }

        .checkout-btn i {
            font-size: 1.1rem;
        }

        .checkout-btn.checkout-primary {
            background: #000;
            color: white;
            border: 1px solid #000;
        }

        .checkout-btn.checkout-primary:hover {
            background: #333;
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .checkout-btn.checkout-primary:active {
            transform: translateY(0) scale(0.98);
        }

        .checkout-btn.checkout-primary {
            position: relative;
            overflow: hidden;
        }

        .checkout-btn.checkout-primary::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .checkout-btn.checkout-primary:hover::after {
            width: 300px;
            height: 300px;
        }

        .payment-buttons {
            display: flex;
            gap: 0;
            margin-top: 12px;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin: auto;
            max-width: 600px;
            width: 100%;
        }

        .empty-cart i {
            font-size: 4rem;
            color: #e2e8f0;
            margin-bottom: 1.5rem;
            display: inline-block;
        }

        .empty-cart h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1rem;
        }

        .empty-cart p {
            color: var(--text-light);
            margin-bottom: 1.5rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .skeleton-item {
            height: 160px;
            margin-bottom: 16px;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #fff;
            padding: 16px 24px;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            max-width: 400px;
            cursor: pointer;
            border: 1px solid #e9ecef;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast.success {
            border-left: 4px solid #1ABB9C;
        }

        .toast.error {
            border-left: 4px solid #dc3545;
        }

        .toast.warning {
            border-left: 4px solid #ffc107;
        }

        .toast.info {
            border-left: 4px solid #667eea;
        }

        .toast-icon {
            font-size: 20px;
        }

        .toast.success .toast-icon {
            color: #1ABB9C;
        }

        .toast.error .toast-icon {
            color: #dc3545;
        }

        .toast.warning .toast-icon {
            color: #ffc107;
        }

        .toast.info .toast-icon {
            color: #1ABB9C;
        }

        .toast-message {
            flex: 1;
            font-weight: 500;
            color: var(--text);
        }

        .undo-btn {
            background: #1ABB9C;
            color: #fff;
            border: none;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-left: 8px;
            transition: all 0.2s ease;
        }

        .undo-btn:hover {
            background: #117a65;
        }

        .clear-cart-btn {
            margin-top: 16px;
            width: 100%;
        }

        .card-header-section {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .header-container {
            margin-bottom: 20px;
        }

        .breadcrumb {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 16px;
        }

        .breadcrumb a {
            color: #1ABB9C;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .breadcrumb a:hover {
            color: #117a65;
            text-decoration: underline;
        }

        .breadcrumb span {
            color: #6c757d;
        }

        .card-title-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--border);
            background: #fff;
            position: relative;
            overflow: hidden;
        }

        .btn-back::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(79, 70, 229, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }

        .btn-back:hover {
            background: #f8fafc;
            color: var(--primary);
            border-color: var(--primary);
            transform: translateX(-3px) scale(1.1);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
        }

        .btn-back:hover::before {
            width: 50px;
            height: 50px;
        }

        .btn-back:active {
            transform: translateX(-1px) scale(0.95);
        }

        .card-title h1 {
            font-size: 24px;
            font-weight: 600;
            color: #2f4050;
            margin: 0;
        }

        .card-title .badge {
            background: #10b981;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
            animation: badgePulse 2s ease-in-out infinite;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
        }

        .card-title .badge:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
        }

        .card-title .badge.updating {
            animation: badgeBounce 0.5s ease;
        }

        @keyframes badgePulse {
            0%, 100% {
                box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
            }
            50% {
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
            }
        }

        @keyframes badgeBounce {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2) rotate(5deg);
            }
        }

        .card-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn.btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }

        .btn.btn-danger::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.4s ease, height 0.4s ease;
        }

        .btn.btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .btn.btn-danger:hover::before {
            width: 200px;
            height: 200px;
        }

        .btn.btn-danger:active {
            transform: translateY(0) scale(0.98);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .main-card {
                min-height: auto;
            }

            .card-title h1 {
                font-size: 20px;
            }

            .card-title-section {
                flex-direction: column;
                align-items: flex-start;
            }

            .cart-items-card {
                padding: 16px;
            }

            .cart-item {
                flex-direction: column;
                gap: 16px;
                padding: 16px;
            }

            .item-image {
                width: 100%;
                height: 250px;
                min-width: 100%;
            }

            .item-actions {
                flex-wrap: wrap;
                width: 100%;
            }

            .item-subtotal {
                margin-top: 8px;
            }

            .cart-sidebar {
                padding: 20px;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .item-updating {
            opacity: 0.6;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        /* Smooth transitions for all interactive elements */
        * {
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Smooth number transitions */
        .summary-row span,
        .item-subtotal,
        .cart-title .badge {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Smooth card transitions */
        .summary-card,
        .cart-items-card,
        .main-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Smooth input transitions */
        input,
        button,
        .btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Smooth image loading */
        img {
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        /* Smooth promo section transitions */
        .promo-section {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #promoMessage {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: translateY(-5px);
        }

        #promoMessage[style*="display: block"] {
            opacity: 1;
            transform: translateY(0);
        }

        /* Smooth shipping info transitions */
        .shipping-info {
            transition: all 0.3s ease;
        }

        /* Enhanced cart sidebar transitions */
        .cart-sidebar {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Smooth number transitions */
        .summary-row span,
        .item-subtotal,
        .cart-title .badge {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
    <body>
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart-container">
                <div class="empty-cart" style="text-align: center; padding: 40px 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); max-width: 480px; width: 90%;">
                    <div style="font-size: 64px; color: #e0e0e0; margin-bottom: 16px;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h2 id="emptyCartHeading" style="font-size: 24px; color: #2f4050; margin-bottom: 12px; font-weight: 700; min-height: 32px;"></h2>
                    <p style="color: #6c757d; font-size: 14px; margin-bottom: 20px; max-width: 360px; margin-left: auto; margin-right: auto; line-height: 1.5;">
                        Looks like you haven't added any items to your cart yet. Start shopping to add products to your cart.
                    </p>
                    <a href="../dashboard/usr_dashboard.php" class="btn" style="background: transparent; color: #1ABB9C; padding: 8px 20px; border-radius: 6px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid #1ABB9C; font-size: 14px; position: relative; overflow: hidden; z-index: 1;">
                        <i class="fas fa-arrow-left" style="margin-right: 6px; font-size: 12px; transition: transform 0.3s ease;"></i> Continue Shopping
                        <span style="position: absolute; content: ''; top: 0; left: 0; width: 0; height: 100%; background: rgba(26, 187, 156, 0.1); transition: width 0.3s ease; z-index: -1;"></span>
                    </a>
                    <style>
                        .empty-cart .btn:hover {
                            color: #1ABB9C;
                            transform: translateY(-2px);
                            box-shadow: 0 2px 8px rgba(26, 187, 156, 0.2);
                        }
                        .empty-cart .btn:hover i {
                            transform: translateX(-3px);
                        }
                        .empty-cart .btn:hover span {
                            width: 100%;
                        }
                    </style>
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                        <p style="color: #6c757d; font-size: 14px; margin-bottom: 12px;">Need help with your order?</p>
                        <a href="#" style="color: #1ABB9C; text-decoration: none; font-weight: 500; font-size: 14px; display: inline-flex; align-items: center;">
                            <i class="fas fa-headset" style="margin-right: 6px;"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="container">
                <div class="main-card">
                    <div class="cart-content-wrapper">
                        <div class="cart-items-card">
                            <div class="card-header-section">
                                <div class="card-title-section">
                                    <div class="card-title">
                                        <a href="usr_dashboard.php" class="btn-back" title="Back to Dashboard">
                                            <i class="fas fa-arrow-left"></i>
                                        </a>
                                        <h1>Your Product</h1>
                                        <?php if ($totalItems > 0): ?>
                                            <span class="badge" id="cartBadge"><?php echo $totalItems; ?> item<?php echo $totalItems > 1 ? 's' : ''; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-actions">
                                        <?php if (!empty($cartItems)): ?>
                                            <button type="button" class="btn btn-danger" onclick="clearCart(); return false;" id="clearCartBtn">
                                                <i class="fas fa-trash"></i> Clear Cart
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="cart-items" id="cartItemsContainer">
                            <?php foreach ($cartItems as $item):
                                $subtotal = $item['price'] * $item['quantity'];
                                $isLowStock = $item['stock_quantity'] <= 5;
                            ?>
                                <div class="cart-item" data-cart-item-id="<?php echo htmlspecialchars($item['cart_item_id']); ?>">
                                    <img class="item-image"
                                        src="<?php echo '../../admin/inventory/' . htmlspecialchars($item['picture']); ?>"
                                        onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27120%27 height=%27120%27%3E%3Crect fill=%27%23f1f3f5%27 width=%27120%27 height=%27120%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';"
                                        alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                                    <div class="item-details">
                                        <div>
                                            <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                            <div class="item-variant">Color - Slate Blue • Size - M</div>
                                            <div class="item-subtotal">$<?php echo number_format($subtotal, 2); ?></div>
                                        </div>
                                        <div class="item-actions">
                                            <div class="qty-control">
                                                <button type="button" class="qty-btn" onclick="changeQty(<?php echo $item['cart_item_id']; ?>, -1); return false;"
                                                    <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>-</button>
                                                <input class="qty-input" type="text" pattern="[0-9]*" inputmode="numeric"
                                                    min="1" max="<?php echo (int)$item['stock_quantity']; ?>"
                                                    value="<?php echo str_pad((int)$item['quantity'], 2, '0', STR_PAD_LEFT); ?>"
                                                    onchange="inputQty(<?php echo $item['cart_item_id']; ?>, this.value); return false;"
                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                                <button type="button" class="qty-btn" onclick="changeQty(<?php echo $item['cart_item_id']; ?>, 1); return false;"
                                                    <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>>+</button>
                                            </div>
                                            <button type="button" class="remove-btn" onclick="removeItem(<?php echo $item['cart_item_id']; ?>); return false;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cart-sidebar">
                    <div class="summary-card">
                        <div class="summary-title">
                            Order Review
                        </div>

                        <div class="promo-section" id="promoSection">
                            <label class="promo-label">Discount code</label>
                            <div class="promo-input-group">
                                <input type="text" class="promo-input" id="promoCode" placeholder="Enter code">
                                <button type="button" class="btn-apply" onclick="applyPromo(); return false;">Apply</button>
                            </div>
                            <div id="promoMessage" style="margin-top: 8px; font-size: 12px; color: #1ABB9C; display: none;"></div>
                        </div>

                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotal">$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span id="shipping">$<?php echo number_format($shipping, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Estimated Taxes</span>
                            <span id="tax">$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span id="total">$<?php echo number_format($finalTotal, 2); ?></span>
                        </div>

                        <button class="checkout-btn checkout-primary" id="checkoutBtn">
                            Checkout
                        </button>
                        <div style="margin-top: 20px; text-align: center;">
                            <a href="usr_dashboard.php" style="text-decoration: underline; color: #2f4050; font-size: 14px;">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <script>
        // Initialize TypeIt for empty cart heading with clear message
        document.addEventListener('DOMContentLoaded', function() {
            const emptyCartHeading = document.getElementById('emptyCartHeading');
            if (emptyCartHeading) {
                new TypeIt('#emptyCartHeading', {
                    speed: 50,
                    startDelay: 300,
                    cursor: { auto: true, blink: true },
                    afterComplete: function(instance) {
                        // Keep the final message visible
                        instance.pause(1000);
                    }
                })
                .type('Your shopping cart is empty.', {delay: 2000})
                .break()
                .type('Start shopping now!', {delay: 1000})
                .go();
            }
        });

        const CART_API_URL = '../../production/includes/cart_api.php';
        let removedItems = [];
        let promoApplied = false;
        let promoDiscount = 0;

        async function callCartAPI(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            for (const key in data) formData.append(key, data[key]);
            const res = await fetch(CART_API_URL, {
                method: 'POST',
                body: formData
            });
            return res.json();
        }

        function showToast(message, type = 'success', showUndo = false) {
            // Remove existing toasts
            document.querySelectorAll('.toast').forEach(t => t.remove());

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };

            let undoHtml = '';
            if (showUndo && removedItems.length > 0) {
                undoHtml = `<button class="undo-btn" onclick="undoRemove()">Undo</button>`;
            }

            toast.innerHTML = `
                <i class="fas ${icons[type]} toast-icon"></i>
                <span class="toast-message">${message}</span>
                ${undoHtml}
            `;

            document.body.appendChild(toast);

            toast.addEventListener('click', () => {
                toast.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            });

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.style.animation = 'slideIn 0.3s ease reverse';
                    setTimeout(() => toast.remove(), 300);
                }
            }, showUndo ? 5000 : 3000);
        }

        function updateTotals(subtotal) {
            const tax = subtotal * 0.08;
            const shipping = subtotal > 50 ? 0 : 5.99;
            const discount = promoApplied ? promoDiscount : 0;
            const finalTotal = subtotal + tax + shipping - discount;

            // Add updating class for smooth transition
            const subtotalEl = document.getElementById('subtotal');
            const taxEl = document.getElementById('tax');
            const shippingEl = document.getElementById('shipping');
            const totalEl = document.getElementById('total');
            const totalRow = totalEl?.closest('.summary-row');

            [subtotalEl, taxEl, shippingEl, totalEl].forEach(el => {
                if (el) {
                    const row = el.closest('.summary-row');
                    if (row) row.classList.add('updating');
                }
            });

            // Update values with smooth transition
            if (subtotalEl) {
                subtotalEl.textContent = '$' + subtotal.toFixed(2);
            }
            if (taxEl) {
                taxEl.textContent = '$' + tax.toFixed(2);
            }
            if (shippingEl) {
                shippingEl.textContent = '$' + shipping.toFixed(2);
            }
            if (totalEl) {
                totalEl.textContent = '$' + finalTotal.toFixed(2);
            }

            // Remove updating class after transition
            setTimeout(() => {
                [subtotalEl, taxEl, shippingEl, totalEl].forEach(el => {
                    if (el) {
                        const row = el.closest('.summary-row');
                        if (row) row.classList.remove('updating');
                    }
                });
            }, 300);
        }

        async function loadCart() {
            try {
                const container = document.getElementById('cartItemsContainer');
                if (container) {
                    container.innerHTML = '<div class="skeleton skeleton-item"></div><div class="skeleton skeleton-item"></div>';
                }

                const res = await fetch(`${CART_API_URL}?action=get_cart_items`);
                const data = await res.json();
                if (!data.success) return;

                const subtotalEl = document.getElementById('subtotal');
                const totalEl = document.getElementById('total');
                const checkoutBtn = document.getElementById('checkoutBtn');
                const cartBadge = document.getElementById('cartBadge');
                const clearCartBtn = document.getElementById('clearCartBtn');

                if (!data.items || data.items.length === 0) {
                    // Just update the UI to show empty cart state
                    if (container) container.innerHTML = '';
                    updateTotals(0);
                    if (document.getElementById('checkoutBtn')) document.getElementById('checkoutBtn').disabled = true;
                    return;
                }

                let html = '';
                let total = 0;
                let totalItems = 0;

                data.items.forEach((item, index) => {
                    const subtotal = item.price * item.quantity;
                    total += subtotal;
                    totalItems += parseInt(item.quantity);
                    const isLowStock = parseInt(item.stock_quantity) <= 5;

                    html += `
                        <div class="cart-item fade-in" data-cart-item-id="${item.cart_item_id}" style="animation-delay: ${index * 0.1}s">
                            <img class="item-image" 
                                 src="../../admin/inventory/${item.picture || ''}"
                                 onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27120%27 height=%27120%27%3E%3Crect fill=%27%23f1f3f5%27 width=%27120%27 height=%27120%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';"
                                 alt="${item.item_name}">
                            <div class="item-details">
                                <div>
                                    <div class="item-name">${item.item_name}</div>
                                    <div class="item-variant">Color - Slate Blue • Size - M</div>
                                    <div class="item-subtotal">$${subtotal.toFixed(2)}</div>
                                </div>
                                <div class="item-actions">
                                    <div class="qty-control">
                                        <button type="button" class="qty-btn" onclick="changeQty(${item.cart_item_id}, -1); return false;" 
                                                ${parseInt(item.quantity) <= 1 ? 'disabled' : ''}>-</button>
                                        <input class="qty-input" type="text" pattern="[0-9]*" inputmode="numeric"
                                               min="1" max="${item.stock_quantity}"
                                               value="${String(item.quantity).padStart(2, '0')}"
                                               onchange="inputQty(${item.cart_item_id}, this.value); return false;"
                                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                        <button type="button" class="qty-btn" onclick="changeQty(${item.cart_item_id}, 1); return false;"
                                                ${parseInt(item.quantity) >= parseInt(item.stock_quantity) ? 'disabled' : ''}>+</button>
                                    </div>
                                    <button type="button" class="remove-btn" onclick="removeItem(${item.cart_item_id}); return false;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });

                if (container) container.innerHTML = html;
                updateTotals(total);
                if (checkoutBtn) checkoutBtn.disabled = total <= 0;
                if (cartBadge) {
                    cartBadge.classList.add('updating');
                    cartBadge.textContent = `${totalItems} item${totalItems > 1 ? 's' : ''}`;
                    setTimeout(() => cartBadge.classList.remove('updating'), 300);
                }
                if (clearCartBtn) {
                    clearCartBtn.style.display = totalItems > 0 ? 'inline-flex' : 'none';
                    clearCartBtn.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                }
            } catch (error) {
                showToast('Failed to load cart', 'error');
            }
        }

        async function changeQty(cartItemId, delta) {
            const item = document.querySelector(`.cart-item[data-cart-item-id="${cartItemId}"]`);
            if (!item) return false;

            item.classList.add('item-updating');
            const input = item.querySelector('.qty-input');
            const minusBtn = item.querySelector('.qty-btn:first-child');
            const plusBtn = item.querySelector('.qty-btn:last-child');
            const maxQty = parseInt(input.max);

            const currentQty = parseInt(input.value || '1', 10);
            let newQty = currentQty + delta;
            if (newQty < 1) newQty = 1;
            if (newQty > maxQty) {
                showToast(`Only ${maxQty} available in stock`, 'warning');
                newQty = maxQty;
            }

            // Get current price per unit from subtotal BEFORE updating
            const subtotalEl = item.querySelector('.item-subtotal');
            const currentSubtotal = parseFloat(subtotalEl?.textContent.replace('$', '') || '0');
            const pricePerUnit = currentSubtotal / (currentQty || 1);

            // Update input and buttons
            input.value = String(newQty).padStart(2, '0');
            minusBtn.disabled = newQty <= 1;
            plusBtn.disabled = newQty >= maxQty;

            // Optimistic update with smooth transition
            if (subtotalEl) {
                subtotalEl.classList.add('updating');
                subtotalEl.textContent = '$' + (pricePerUnit * newQty).toFixed(2);
                setTimeout(() => subtotalEl.classList.remove('updating'), 300);
            }

            await updateQty(cartItemId, newQty, pricePerUnit);
            item.classList.remove('item-updating');
            return false;
        }

        async function inputQty(cartItemId, value) {
            const item = document.querySelector(`.cart-item[data-cart-item-id="${cartItemId}"]`);
            if (!item) return false;

            item.classList.add('item-updating');
            const input = item.querySelector('.qty-input');
            const minusBtn = item.querySelector('.qty-btn:first-child');
            const plusBtn = item.querySelector('.qty-btn:last-child');
            const maxQty = parseInt(input.max);
            const currentQty = parseInt(input.value || '1', 10);
            let qty = parseInt(value || '1', 10);

            if (isNaN(qty) || qty < 1) qty = 1;
            if (qty > maxQty) {
                showToast(`Only ${maxQty} available in stock`, 'warning');
                qty = maxQty;
            }

            // Get current price per unit from subtotal BEFORE updating
            const subtotalEl = item.querySelector('.item-subtotal');
            const currentSubtotal = parseFloat(subtotalEl?.textContent.replace('$', '') || '0');
            const pricePerUnit = currentSubtotal / (currentQty || 1);

            // Update input and buttons
            input.value = String(qty).padStart(2, '0');
            minusBtn.disabled = qty <= 1;
            plusBtn.disabled = qty >= maxQty;

            // Optimistic update with smooth transition
            if (subtotalEl) {
                subtotalEl.classList.add('updating');
                subtotalEl.textContent = '$' + (pricePerUnit * qty).toFixed(2);
                setTimeout(() => subtotalEl.classList.remove('updating'), 300);
            }

            await updateQty(cartItemId, qty, pricePerUnit);
            item.classList.remove('item-updating');
            return false;
        }

        async function updateQty(cartItemId, qty, pricePerUnit) {
            try {
                const res = await callCartAPI('update_quantity', {
                    cart_item_id: cartItemId,
                    quantity: qty
                });
                if (!res.success) {
                    showToast(res.message || 'Failed to update quantity', 'error');
                    // Only reload on error to restore correct state
                    await loadCart();
                    return;
                }
                // Update totals statically without reloading cart
                updateCartTotals();
            } catch (error) {
                showToast('Failed to update quantity', 'error');
                // Only reload on error
                await loadCart();
            }
        }

        function updateCartTotals() {
            // Calculate totals from current cart items without reloading
            const cartItems = document.querySelectorAll('.cart-item');
            let total = 0;
            let totalItems = 0;

            cartItems.forEach(item => {
                const qtyInput = item.querySelector('.qty-input');
                const subtotalEl = item.querySelector('.item-subtotal');
                
                if (qtyInput && subtotalEl) {
                    const quantity = parseInt(qtyInput.value || '1', 10);
                    const subtotal = parseFloat(subtotalEl.textContent.replace('$', '') || '0');
                    
                    total += subtotal;
                    totalItems += quantity;
                }
            });

            // Update summary totals
            updateTotals(total);

            // Update cart badge
            const cartBadge = document.getElementById('cartBadge');
            if (cartBadge) {
                cartBadge.classList.add('updating');
                cartBadge.textContent = `${totalItems} item${totalItems > 1 ? 's' : ''}`;
                setTimeout(() => cartBadge.classList.remove('updating'), 300);
            }
        }

        async function removeItem(cartItemId) {
            const item = document.querySelector(`.cart-item[data-cart-item-id="${cartItemId}"]`);
            if (!item) return false;

            // Store item data for undo
            const itemData = {
                cartItemId: cartItemId,
                html: item.outerHTML
            };
            removedItems.push(itemData);

            // Animate removal
            item.classList.add('removing');

            try {
                const res = await callCartAPI('remove_from_cart', {
                    cart_item_id: cartItemId
                });
                if (!res.success) {
                    showToast(res.message || 'Failed to remove item', 'error');
                    item.classList.remove('removing');
                    return;
                }

                setTimeout(() => {
                    showToast('Item removed from cart', 'success', true);
                    loadCart();
                }, 400);
            } catch (error) {
                showToast('Failed to remove item', 'error');
                item.classList.remove('removing');
            }
        }

        async function undoRemove() {
            if (removedItems.length === 0) return;

            const lastRemoved = removedItems.pop();
            showToast('Item restored', 'success');
            await loadCart();
        }

        async function clearCart() {
            const result = await Swal.fire({
                title: 'Clear Cart?',
                text: 'Are you sure you want to clear your entire cart? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, clear it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            });

            if (!result.isConfirmed) return false;

            const container = document.getElementById('cartItemsContainer');
            const items = container.querySelectorAll('.cart-item');

            // Show loading state
            Swal.fire({
                title: 'Clearing cart...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            items.forEach((item, index) => {
                setTimeout(() => {
                    item.classList.add('removing');
                }, index * 100);
            });

            // Remove all items
            for (const item of items) {
                const cartItemId = item.dataset.cartItemId;
                await callCartAPI('remove_from_cart', {
                    cart_item_id: cartItemId
                });
            }

            setTimeout(() => {
                Swal.fire({
                    title: 'Cart Cleared!',
                    text: 'All items have been removed from your cart.',
                    icon: 'success',
                    confirmButtonColor: '#1ABB9C',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
            }, items.length * 100 + 400);
        }

        function applyPromo() {
            const code = document.getElementById('promoCode').value.trim().toUpperCase();
            const promoSection = document.getElementById('promoSection');
            const promoMessage = document.getElementById('promoMessage');

            if (!code) {
                showToast('Please enter a promo code', 'warning');
                return false;
            }

            // Example promo codes
            const promoCodes = {
                'SAVE10': 0.10,
                'WELCOME20': 0.20,
                'FREESHIP': 0
            };

            if (promoCodes[code]) {
                promoApplied = true;
                if (code === 'FREESHIP') {
                    promoDiscount = 5.99; // Free shipping value
                    promoSection.classList.add('applied');
                    promoMessage.textContent = 'Free shipping applied!';
                    promoMessage.style.display = 'block';
                    promoMessage.style.color = '#1ABB9C';
                    setTimeout(() => {
                        promoMessage.style.opacity = '1';
                        promoMessage.style.transform = 'translateY(0)';
                    }, 10);
                } else {
                    const subtotal = parseFloat(document.getElementById('subtotal').textContent.replace('$', ''));
                    promoDiscount = subtotal * promoCodes[code];
                    promoSection.classList.add('applied');
                    promoMessage.textContent = `${(promoCodes[code] * 100).toFixed(0)}% discount applied!`;
                    promoMessage.style.display = 'block';
                    promoMessage.style.color = '#1ABB9C';
                    setTimeout(() => {
                        promoMessage.style.opacity = '1';
                        promoMessage.style.transform = 'translateY(0)';
                    }, 10);
                }
                showToast('Promo code applied successfully!', 'success');
                updateTotals(parseFloat(document.getElementById('subtotal').textContent.replace('$', '')));
            } else {
                promoSection.classList.remove('applied');
                promoMessage.textContent = 'Invalid promo code';
                promoMessage.style.display = 'block';
                promoMessage.style.color = '#dc3545';
                setTimeout(() => {
                    promoMessage.style.opacity = '1';
                    promoMessage.style.transform = 'translateY(0)';
                }, 10);
                showToast('Invalid promo code', 'error');
            }
            return false;
        }

        document.getElementById('checkoutBtn')?.addEventListener('click', async function() {
            const btn = this;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> Processing...';

            try {
                const res = await fetch(`${CART_API_URL}?action=get_cart_items`);
                const data = await res.json();

                if (!data.success || !data.items || data.items.length === 0) {
                    showToast('Your cart is empty', 'warning');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    return;
                }

                showToast('Redirecting to checkout...', 'info');
                setTimeout(() => {
                    // Redirect to checkout page when ready
                    window.location.href = 'checkout.php';
                }, 1000);
            } catch (error) {
                showToast('Failed to proceed to checkout', 'error');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        });

        document.getElementById('shopPayBtn')?.addEventListener('click', async function() {
            showToast('Shop Pay checkout coming soon', 'info');
        });

        document.getElementById('paypalBtn')?.addEventListener('click', async function() {
            showToast('PayPal checkout coming soon', 'info');
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && document.activeElement.id === 'promoCode') {
                applyPromo();
            }
            if (e.key === 'Escape') {
                document.querySelectorAll('.toast').forEach(t => t.click());
            }
        });

        // Initial load
        loadCart();
    </script>
</body>

</html>