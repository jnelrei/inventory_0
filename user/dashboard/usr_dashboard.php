<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Include database connection
require_once('../../production/includes/db.php');

// Fetch categories
$categories = [];
try {
    $stmt = $pdo->query("SELECT category_id, category_name FROM category ORDER BY category_name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $categories = [];
}

// Fetch products from inventory
$products = [];
try {
    $stmt = $pdo->query("
        SELECT i.item_id, i.item_name, i.picture, i.description, i.total_cost, i.quantity, 
               c.category_id, c.category_name 
        FROM invtry i 
        LEFT JOIN category c ON i.category_id = c.category_id 
        WHERE i.picture IS NOT NULL AND i.picture != '' 
        ORDER BY i.created_at DESC 
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $products = [];
}


// Get user info
$userName = $_SESSION['name'] ?? 'Guest';
$userRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Visitor';
$userInitials = '';
if (!empty($userName)) {
    $parts = preg_split('/\s+/', trim($userName));
    foreach ($parts as $part) {
        $userInitials .= strtoupper(substr($part, 0, 1));
        if (strlen($userInitials) === 2) break;
    }
}
$userInitials = $userInitials ?: 'U';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tumandok Crafts Industries</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../../images/logoooo.png" rel="icon">
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #ffffff;
            color: #2f4050;
            line-height: 1.6;
        }

        /* Header/Navigation */
        .header {
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 15px 0;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a1a;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo img {
            height: 50px;
            width: auto;
            display: block;
        }

        .search-bar {
            flex: 1;
            max-width: 500px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #1ABB9C;
        }

        .search-bar i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .category-filter {
            position: relative;
            min-width: 100px;
            margin-right: 300px;
        }

        .category-filter-select {
            width: 100%;
            padding: 12px 40px 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #2f4050;
            background: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%232f4050' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            transition: all 0.3s ease;
        }

        .category-filter-select:focus {
            outline: none;
            border-color: #1ABB9C;
        }

        .category-filter-select:hover {
            border-color: #1ABB9C;
        }


        .nav-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f8f9fa;
            color: #2f4050;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-icon:hover {
            background: #1ABB9C;
            color: white;
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            border-radius: 25px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .user-profile:hover {
            background: #e9ecef;
        }

        .user-profile.active {
            background: #e9ecef;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }

        .user-profile-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1001;
            overflow: hidden;
        }

        .user-profile.active .user-profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-header {
            padding: 15px 18px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        .user-dropdown-name {
            font-weight: 700;
            font-size: 15px;
            color: #1a1a1a;
            margin-bottom: 3px;
        }

        .user-dropdown-role {
            font-size: 12px;
            color: #6c757d;
        }

        .user-dropdown-menu {
            list-style: none;
            padding: 8px 0;
        }

        .user-dropdown-item {
            padding: 0;
        }

        .user-dropdown-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            color: #2f4050;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .user-dropdown-link:hover {
            background: #f8f9fa;
            color: #1ABB9C;
        }

        .user-dropdown-link i {
            width: 18px;
            text-align: center;
            font-size: 14px;
        }

        .user-dropdown-divider {
            height: 1px;
            background: #e9ecef;
            margin: 8px 0;
        }

        .user-dropdown-link.logout {
            color: #dc3545;
        }

        .user-dropdown-link.logout:hover {
            background: #fff5f5;
            color: #dc3545;
        }

        .user-profile-arrow {
            font-size: 10px;
            color: #6c757d;
            transition: transform 0.3s ease;
        }

        .user-profile.active .user-profile-arrow {
            transform: rotate(180deg);
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            min-height: 370px;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            overflow: hidden;
        }

        .hero-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            object-fit: cover;
            pointer-events: none;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                135deg,
                rgba(0, 0, 0, 0.6) 0%,
                rgba(0, 0, 0, 0.5) 50%,
                rgba(0, 0, 0, 0.55) 100%
            );
            z-index: 1;
        }

        .hero-overlay::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                to bottom,
                rgba(0, 0, 0, 0.3) 0%,
                transparent 50%,
                rgba(0, 0, 0, 0.2) 100%
            );
            z-index: 1;
        }

        .hero-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
            position: relative;
            z-index: 2;
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            min-height: 100%;
        }

        .hero-text-content {
            margin-top: auto;
            margin-bottom: 0;
            padding-bottom: 0;
            width: 100%;
        }

        .hero-title {
            font-size: 50px;
            font-weight: 900;
            color: #ffffff;
            margin-top: 0;
            margin-bottom: 10px;
            line-height: 1.1;
            letter-spacing: -2px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            text-align: center;
            min-height: 1.2em;
            transition: all 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .typing-cursor {
            animation: blink 1s infinite;
            color: #ffffff;
        }

        @keyframes blink {
            0%, 50% {
                opacity: 1;
            }
            51%, 100% {
                opacity: 0;
            }
        }


        .hero-description {
            font-size: 18px;
            color: #f0f0f0;
            margin-bottom: 20px;
            margin-left: auto;
            margin-right: auto;
            max-width: 600px;
            line-height: 1.7;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.5);
            text-align: center;
        }

        .shop-now-btn {
            background:rgba(14, 241, 89, 0.59);
            color: white;
            padding: 16px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
        }

        .shop-now-btn:hover {
            background: #1ABB9C;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 187, 156, 0.3);
        }

        .hero-product-card {
            position: relative;
            background: transparent;
            backdrop-filter: none;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
            max-width: 700px;
            width: 100%;
            margin: 100px auto 40px;
            z-index: 3;
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .hero-product-card img {
            width: 700px;
            height: 700px;
            object-fit: contain;
            border-radius: 12px;
            margin-bottom: 0;
            transition: opacity 0.5s ease;
            background: transparent;
            mix-blend-mode: multiply;
            filter: contrast(1.2) brightness(1.1) saturate(1.1);
            -webkit-filter: contrast(1.2) brightness(1.1) saturate(1.1);
            image-rendering: -webkit-optimize-contrast;
        }

        @media (max-width: 800px) {
            .hero-product-card img {
                width: 100%;
                max-width: 700px;
                height: auto;
            }
        }

        .hero-product-name {
            font-weight: 700;
            font-size: 18px;
            color: #1a1a1a;
            margin-bottom: 10px;
            transition: opacity 0.5s ease;
        }

        .hero-product-price {
            font-size: 24px;
            font-weight: 800;
            color: #1ABB9C;
            transition: opacity 0.5s ease;
        }

        .hero-product-card.updating {
            opacity: 0.7;
        }


        /* Features Section */
        .features-section {
            max-width: 1400px;
            margin: 60px auto;
            padding: 0 40px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .feature-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            flex-shrink: 0;
        }

        .feature-text {
            font-size: 14px;
            font-weight: 600;
            color: #2f4050;
            line-height: 1.5;
        }

        /* Products Section */
        .products-section {
            max-width: 1400px;
            margin: 80px auto;
            padding: 0 40px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 36px;
            font-weight: 800;
            color: #1a1a1a;
        }

        .typing-text {
            display: inline-block;
        }

        .typing-char {
            display: inline-block;
            opacity: 0;
            animation: typingFadeIn 0.1s ease forwards;
        }

        @keyframes typingFadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .see-all-link {
            color: #1ABB9C;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .see-all-link:hover {
            gap: 10px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .product-image-wrapper {
            position: relative;
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: #f8f9fa;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.1);
        }

        .product-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .product-card:hover .product-overlay {
            opacity: 1;
        }

        .product-action-btn {
            background: white;
            color: #1a1a1a;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .product-action-btn:hover {
            background: #1ABB9C;
            color: white;
        }

        .product-card {
            cursor: pointer;
        }

        .product-card .product-action-btn,
        .product-card .product-action-icon {
            cursor: pointer;
            position: relative;
            z-index: 10;
        }

        .product-action-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .product-action-icon:hover {
            background: #1ABB9C;
            color: white;
        }

        .product-info {
            padding: 24px;
        }

        .product-name {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .product-description {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .price-current {
            font-size: 24px;
            font-weight: 800;
            color: #1ABB9C;
        }

        .price-original {
            font-size: 18px;
            color: #adb5bd;
            text-decoration: line-through;
        }

        /* Product Preview Modal */
        .product-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            overflow-y: auto;
            animation: fadeIn 0.3s ease;
        }

        .product-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #2f4050;
            z-index: 10001;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .modal-close:hover {
            background: #1ABB9C;
            color: white;
            transform: rotate(90deg);
        }

        .modal-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 40px;
        }

        .modal-image-wrapper {
            width: 100%;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            background: #f8f9fa;
            position: sticky;
            top: 20px;
        }

        .modal-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .modal-title {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a1a;
            margin: 0;
            line-height: 1.2;
        }

        .modal-category {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .modal-price-section {
            display: flex;
            align-items: baseline;
            gap: 15px;
            padding: 20px 0;
            border-top: 1px solid #e9ecef;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-price-current {
            font-size: 36px;
            font-weight: 800;
            color: #1ABB9C;
        }

        .modal-price-original {
            font-size: 24px;
            color: #adb5bd;
            text-decoration: line-through;
        }

        .modal-description {
            font-size: 16px;
            color: #6c757d;
            line-height: 1.8;
        }

        .modal-stock {
            font-size: 14px;
            color: #6c757d;
            font-weight: 600;
        }

        .modal-stock.in-stock {
            color: #28a745;
        }

        .modal-stock.low-stock {
            color: #ffc107;
        }

        .modal-stock.out-of-stock {
            color: #dc3545;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .modal-add-cart-btn {
            flex: 1;
            background: #1ABB9C;
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .modal-add-cart-btn:hover {
            background: #117a65;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 187, 156, 0.3);
        }

        .modal-add-cart-btn:disabled {
            background: #adb5bd;
            cursor: not-allowed;
            transform: none;
        }

        .modal-wishlist-btn {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            background: white;
            color: #2f4050;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .modal-wishlist-btn:hover {
            border-color: #1ABB9C;
            color: #1ABB9C;
        }

        .modal-wishlist-btn.active {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        @media (max-width: 768px) {
            .modal-body {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 30px 20px;
            }

            .modal-image-wrapper {
                height: 250px;
                position: relative;
            }

            .modal-title {
                font-size: 24px;
            }

            .modal-price-current {
                font-size: 28px;
            }
        }

        /* Confirmation Modal */
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 20000;
            overflow-y: auto;
            animation: fadeIn 0.2s ease;
        }

        .confirmation-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .confirmation-content {
            background: white;
            border-radius: 8px;
            max-width: 900px;
            width: 100%;
            max-height: 95vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .confirmation-header {
            padding: 15px 20px;
            text-align: center;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .confirmation-icon {
            width: 36px;
            height: 36px;
            border-radius: 4px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-size: 16px;
            color: #1ABB9C;
        }

        .confirmation-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            color: #2f4050;
        }

        .confirmation-body {
            padding: 20px;
            text-align: center;
        }

        .confirmation-product {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: left;
        }

        .confirmation-product-image {
            width: 120px;
            height: 120px;
            border-radius: 4px;
            object-fit: cover;
            background: #e9ecef;
            flex-shrink: 0;
        }

        .confirmation-product-info {
            flex: 1;
            min-width: 0;
        }

        .confirmation-product-name {
            font-size: 14px;
            font-weight: 600;
            color: #2f4050;
            margin-bottom: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .confirmation-product-price {
            font-size: 14px;
            font-weight: 600;
            color: #1ABB9C;
            margin-bottom: 8px;
        }

        .confirmation-quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #e9ecef;
        }

        .confirmation-quantity-label {
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .quantity-btn {
            width: 28px;
            height: 28px;
            background: white;
            border: none;
            color: #2f4050;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-btn:hover {
            background: #1ABB9C;
            color: white;
        }

        .quantity-btn:disabled {
            background: #f8f9fa;
            color: #adb5bd;
            cursor: not-allowed;
        }

        .quantity-input {
            width: 45px;
            height: 28px;
            border: none;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: #1a1a1a;
            background: white;
            outline: none;
        }

        .quantity-input::-webkit-inner-spin-button,
        .quantity-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .confirmation-total-price {
            margin-left: auto;
            font-size: 14px;
            font-weight: 700;
            color: #dc3545;
        }

        .confirmation-message {
            font-size: 13px;
            color: #6c757d;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .confirmation-actions {
            display: flex;
            gap: 10px;
        }

        .confirmation-btn {
            flex: 1;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .confirmation-btn-cancel {
            background: #e9ecef;
            color: #2f4050;
        }

        .confirmation-btn-cancel:hover {
            background: #dee2e6;
        }

        .confirmation-btn-confirm {
            background: #1ABB9C;
            color: white;
        }

        .confirmation-btn-confirm:hover {
            background: #117a65;
        }

        @media (max-width: 480px) {
            .confirmation-content {
                max-width: 100%;
            }

            .confirmation-actions {
                flex-direction: column;
            }

            .confirmation-product {
                flex-direction: column;
                text-align: center;
            }
        }

        /* Cart Modal */
        .cart-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            overflow-y: auto;
            animation: fadeIn 0.3s ease;
        }

        .cart-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .cart-modal-content {
            background: white;
            border-radius: 8px;
            max-width: 1000px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .cart-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }

        .cart-modal-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2f4050;
        }

        .cart-modal-close {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            background: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #6c757d;
            transition: all 0.2s ease;
        }

        .cart-modal-close:hover {
            background: #e9ecef;
            color: #2f4050;
        }

        .cart-modal-body {
            padding: 20px;
        }

        .cart-items-list {
            margin-bottom: 15px;
            max-height: 45vh;
            overflow-y: auto;
            padding-right: 4px;
        }

        .cart-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-image {
            width: 60px;
            height: 60px;
            border-radius: 4px;
            object-fit: cover;
            background: #f8f9fa;
            flex-shrink: 0;
        }

        .cart-item-info {
            flex: 1;
            min-width: 0;
        }

        .cart-item-name {
            font-size: 14px;
            font-weight: 600;
            color: #2f4050;
            margin-bottom: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cart-item-price {
            font-size: 14px;
            font-weight: 600;
            color: #1ABB9C;
            margin-bottom: 6px;
        }

        .cart-item-quantity-controls {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cart-item-quantity-btn {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            background: white;
            color: #2f4050;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .cart-item-quantity-btn:hover {
            background: #1ABB9C;
            color: white;
            border-color: #1ABB9C;
        }

        .cart-item-quantity-input {
            width: 40px;
            height: 24px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
        }

        .cart-item-remove {
            color: #dc3545;
            cursor: pointer;
            font-size: 14px;
            padding: 4px;
            transition: all 0.2s ease;
        }

        .cart-item-remove:hover {
            color: #c82333;
        }

        .cart-item-subtotal {
            font-size: 14px;
            font-weight: 600;
            color: #2f4050;
            margin-left: auto;
            min-width: 70px;
            text-align: right;
        }

        .cart-empty {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .cart-empty-icon {
            font-size: 48px;
            color: #adb5bd;
            margin-bottom: 12px;
        }

        .cart-empty-text {
            font-size: 14px;
            margin-bottom: 20px;
        }

        .cart-summary {
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-top: 12px;
        }

        .cart-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
            color: #6c757d;
        }

        .cart-summary-row.total {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
            padding-top: 8px;
            border-top: 1px solid #e9ecef;
            margin-top: 8px;
            margin-bottom: 0;
        }

        .cart-modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .cart-btn {
            flex: 1;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .cart-btn-continue {
            background: #e9ecef;
            color: #2f4050;
        }

        .cart-btn-continue:hover {
            background: #dee2e6;
        }

        .cart-btn-checkout {
            background: #1ABB9C;
            color: white;
        }

        .cart-btn-checkout:hover {
            background: #117a65;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 187, 156, 0.3);
        }

        @media (max-width: 768px) {
            .cart-item {
                flex-wrap: wrap;
            }

            .cart-item-subtotal {
                margin-left: 0;
                width: 100%;
                text-align: left;
                margin-top: 10px;
            }

            .cart-modal-actions {
                flex-direction: column;
            }
        }

        /* About Us Section */
        .about-section {
            max-width: 1400px;
            margin: 80px auto;
            padding: 0 40px;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }

        .about-text h2 {
            font-size: 36px;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 25px;
        }

        .about-text p {
            font-size: 16px;
            color: #6c757d;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .about-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .about-subsection h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .about-subsection p {
            font-size: 14px;
            color: #6c757d;
            line-height: 1.6;
        }

        .about-images {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .about-image {
            width: 100%;
            border-radius: 12px;
            object-fit: cover;
            height: 200px;
            background: #f8f9fa;
        }

        /* Footer */
        .footer {
            position: relative;
            background: #1a1a1a;
            color: white;
            padding: 20px 20px 10px;
            margin-top: 20px;
            overflow: hidden;
        }

        .footer-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            object-fit: cover;
            pointer-events: none;
        }

        .footer-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1;
        }

        .footer-content {
            max-width: 900px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr;
            justify-items: center;
            text-align: center;
            gap: 20px;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }

        .footer-section h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
            line-height: 1.7;
            color: #e9ecef;
            font-size: 14px;
        }

        .footer-section ul li {
            margin-bottom: 8px;
        }

        .footer-section a {
            color: #adb5bd;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: #1ABB9C;
        }

        .footer-bottom {
            max-width: 1400px;
            margin: 0 auto;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: #adb5bd;
            font-size: 14px;
            position: relative;
            z-index: 2;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .nav-container {
                flex-wrap: wrap;
            }

            .search-bar {
                order: 3;
                width: 100%;
                max-width: 100%;
            }

            .hero-title {
                font-size: 48px;
            }

            .hero-product-card {
                margin: 0 auto 30px;
            }

            .about-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .category-filter {
                min-width: 150px;
            }

            .hero-title {
                font-size: 36px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <a href="#" class="logo"><img src="../../images/furn.webp" alt="Tumandok Crafts Industries"></a>
            
            <div class="search-bar">
                <input type="text" placeholder="Search for Sofa chair">
                <i class="fas fa-search"></i>
            </div>

            <div class="category-filter">
                <select id="categoryFilter" class="category-filter-select" onchange="filterProductsByCategory()">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['category_id']); ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="nav-icons">
                <a class="nav-icon" href="cart.php" style="cursor: pointer; text-decoration: none;">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-badge">0</span>
                </a>
                <div class="user-profile" id="userProfile" onclick="event.stopPropagation(); toggleUserDropdown()">
                    <div class="user-avatar"><?php echo $userInitials; ?></div>
                    <span style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-chevron-down user-profile-arrow"></i>
                    <div class="user-profile-dropdown">
                        <div class="user-dropdown-header">
                            <div class="user-dropdown-name"><?php echo htmlspecialchars($userName); ?></div>
                            <div class="user-dropdown-role"><?php echo htmlspecialchars($userRole); ?></div>
                        </div>
                        <ul class="user-dropdown-menu">
                            <li class="user-dropdown-item">
                                <a href="#" class="user-dropdown-link">
                                    <i class="fas fa-user"></i>
                                    <span>My Profile</span>
                                </a>
                            </li>
                            <li class="user-dropdown-item">
                                <a href="#" class="user-dropdown-link">
                                    <i class="fas fa-shopping-bag"></i>
                                    <span>My Orders</span>
                                </a>
                            </li>
                            <li class="user-dropdown-item">
                                <a href="#" class="user-dropdown-link">
                                    <i class="fas fa-heart"></i>
                                    <span>Wishlist</span>
                                </a>
                            </li>
                            <li class="user-dropdown-item">
                                <a href="#" class="user-dropdown-link">
                                    <i class="fas fa-cog"></i>
                                    <span>Settings</span>
                                </a>
                            </li>
                            <li class="user-dropdown-item">
                                <div class="user-dropdown-divider"></div>
                            </li>
                            <li class="user-dropdown-item">
                                <a href="../../production/includes/logout.php" class="user-dropdown-link logout">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <!-- Hero Video -->
        <video class="hero-video" autoplay muted loop playsinline preload="auto">
            <source src="video/video.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-text-content">
                <h1 class="hero-title" id="heroTitle">TUMANDOK CRAFTS INDUSTRIES</h1>
                <p class="hero-description">"Where Raw Materials Become Remarkable."</p>
               
            </div>
        </div>
    </section>


    <script>
        // Cart Management - Using Database API
        let cart = []; // Keep for local reference, but sync with database
        const CART_API_URL = '../../production/includes/cart_api.php';

        // Initialize cart badge on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadCartFromDatabase();
            initTypingAnimation();
            initHeroTypingAnimation();
        });

        // Load cart from database
        async function loadCartFromDatabase() {
            try {
                const response = await fetch(`${CART_API_URL}?action=get_cart_items`);
                const data = await response.json();
                
                if (data.success) {
                    cart = data.items.map(item => ({
                        itemId: item.item_id,
                        itemName: item.item_name,
                        price: parseFloat(item.price),
                        quantity: parseInt(item.quantity),
                        image: item.picture,
                        stock: parseInt(item.stock_quantity),
                        cartItemId: item.cart_item_id
                    }));
                    // Update badge with loaded data
                    updateCartBadgeFromData(data.total_items);
                } else {
                    updateCartBadge();
                }
            } catch (error) {
                console.error('Error loading cart:', error);
                updateCartBadge();
            }
        }

        // Update cart badge with specific count
        function updateCartBadgeFromData(totalItems) {
            const cartBadge = document.querySelector('.cart-badge');
            if (!cartBadge) return;
            
            cartBadge.textContent = totalItems || 0;
            cartBadge.style.display = (totalItems > 0) ? 'flex' : 'none';
        }

        // API Helper Functions
        async function callCartAPI(action, data = {}) {
            try {
                const formData = new FormData();
                formData.append('action', action);
                for (const key in data) {
                    formData.append(key, data[key]);
                }
                
                const response = await fetch(CART_API_URL, {
                    method: 'POST',
                    body: formData
                });
                
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return { success: false, message: 'Network error occurred' };
            }
        }

        // Typing Animation for "Our Products"
        function initTypingAnimation() {
            const titleElement = document.getElementById('productsTitle');
            if (!titleElement) return;

            const text = 'Our Products';
            const chars = text.split('');
            
            // Clear the element
            titleElement.innerHTML = '';
            
            chars.forEach((char, index) => {
                const span = document.createElement('span');
                span.className = 'typing-char';
                span.textContent = char === ' ' ? '\u00A0' : char; // Use non-breaking space for spaces
                span.style.animationDelay = `${index * 0.1}s`;
                titleElement.appendChild(span);
            });
        }

        // Hero Title Typing Animation - Second letter to last, then reverse
        function initHeroTypingAnimation() {
            const heroTitleElement = document.getElementById('heroTitle');
            if (!heroTitleElement) return;

            const fullText = 'TUMANDOK CRAFTS INDUSTRIES';
            const firstLetter = fullText.substring(0, 1); // Keep first letter always
            const textToAnimate = fullText.substring(1); // Text from second letter to end
            
            // Set first letter immediately
            heroTitleElement.innerHTML = firstLetter + '<span class="typing-cursor">_</span>';
            
            let currentIndex = 0;
            let isReverse = false;
            let timeoutId;
            const typingSpeed = 120; // Slow and smooth speed
            const pauseAfterForward = 2500; // Pause after typing to last letter
            const pauseAfterReverse = 2500; // Pause after backspacing to second letter

            function typeForward() {
                if (currentIndex <= textToAnimate.length) {
                    const displayText = textToAnimate.substring(0, currentIndex);
                    heroTitleElement.innerHTML = firstLetter + displayText + '<span class="typing-cursor">_</span>';
                    currentIndex++;
                    timeoutId = setTimeout(typeForward, typingSpeed);
                } else {
                    // Finished typing from second to last letter
                    clearTimeout(timeoutId);
                    setTimeout(() => {
                        isReverse = true;
                        typeBackward();
                    }, pauseAfterForward);
                }
            }

            function typeBackward() {
                if (currentIndex > 0) {
                    currentIndex--;
                    const displayText = textToAnimate.substring(0, currentIndex);
                    heroTitleElement.innerHTML = firstLetter + displayText + '<span class="typing-cursor">_</span>';
                    timeoutId = setTimeout(typeBackward, typingSpeed);
                } else {
                    // Finished backspacing to second letter
                    clearTimeout(timeoutId);
                    setTimeout(() => {
                        isReverse = false;
                        typeForward();
                    }, pauseAfterReverse);
                }
            }

            // Start typing forward from second letter
                    setTimeout(() => {
                typeForward();
            }, 500);
        }

        // Smooth Scroll to Products Section
        function smoothScrollToProducts(event) {
            event.preventDefault();
            const productsSection = document.getElementById('products');
            if (productsSection) {
                const headerOffset = 80; // Account for sticky header
                const elementPosition = productsSection.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        }

        // Filter Products by Category
        function filterProductsByCategory() {
            const filterValue = document.getElementById('categoryFilter').value;
            const productCards = document.querySelectorAll('.product-card');
            let visibleCount = 0;

            productCards.forEach(card => {
                const categoryId = card.dataset.categoryId || '';
                
                if (filterValue === 'all' || categoryId === filterValue) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Show message if no products found
            const productsGrid = document.querySelector('.products-grid');
            let noResultsMsg = productsGrid.querySelector('.no-results-message');
            
            if (visibleCount === 0) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('p');
                    noResultsMsg.className = 'no-results-message';
                    noResultsMsg.style.cssText = 'grid-column: 1 / -1; text-align: center; color: #6c757d; padding: 40px; font-size: 16px;';
                    noResultsMsg.textContent = 'No products found in this category.';
                    productsGrid.appendChild(noResultsMsg);
                }
            } else {
                if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }
        }

        // Toggle User Profile Dropdown
        function toggleUserDropdown() {
            const userProfile = document.getElementById('userProfile');
            userProfile.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userProfile = document.getElementById('userProfile');
            if (!userProfile.contains(event.target)) {
                userProfile.classList.remove('active');
            }
        });

        // Store pending cart item data
        let pendingCartItem = null;
        let pendingButton = null;

        // Add to Cart from product card -> go to cart page (no modal)
        async function addToCart(button) {
            const productCard = button.closest('.product-card');
            const itemId = productCard.dataset.itemId;
            const itemName = productCard.dataset.itemName;
            const itemPrice = parseFloat(productCard.dataset.itemPrice);
            const itemImage = productCard.dataset.itemImage;
            const availableQuantity = parseInt(productCard.dataset.itemQuantity) || 0;

            await addItemToCartAndGo(itemId, itemName, itemPrice, availableQuantity, itemImage, button);
        }

        // Show Confirmation Modal
        function showConfirmationModal(itemName, itemPrice, itemImage, availableQuantity) {
            const modal = document.getElementById('confirmationModal');
            const productModal = document.getElementById('productModal');
            const quantityInput = document.getElementById('confirmQuantity');
            
            // Close product modal if it's open
            if (productModal && productModal.classList.contains('active')) {
                productModal.classList.remove('active');
            }
            
            // Reset quantity to 1
            quantityInput.value = 1;
            quantityInput.max = availableQuantity;
            
            document.getElementById('confirmProductName').textContent = itemName;
            document.getElementById('confirmProductPrice').textContent = '$' + itemPrice.toFixed(2);
            document.getElementById('confirmProductImage').src = '../../admin/inventory/' + itemImage;
            document.getElementById('confirmProductImage').alt = itemName;
            
            // Update total price
            updateConfirmationTotal(itemPrice, 1);
            
            // Update button states
            updateQuantityButtons(1, availableQuantity);
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Increase Quantity
        function increaseQuantity() {
            const quantityInput = document.getElementById('confirmQuantity');
            const currentQuantity = parseInt(quantityInput.value) || 1;
            const maxQuantity = parseInt(quantityInput.max) || 999;
            
            if (currentQuantity < maxQuantity) {
                const newQuantity = currentQuantity + 1;
                quantityInput.value = newQuantity;
                updateConfirmationTotalAndButtons();
            }
        }

        // Decrease Quantity
        function decreaseQuantity() {
            const quantityInput = document.getElementById('confirmQuantity');
            const currentQuantity = parseInt(quantityInput.value) || 1;
            
            if (currentQuantity > 1) {
                const newQuantity = currentQuantity - 1;
                quantityInput.value = newQuantity;
                updateConfirmationTotalAndButtons();
            }
        }

        // Update Quantity from Input
        function updateQuantityFromInput() {
            const quantityInput = document.getElementById('confirmQuantity');
            let quantity = parseInt(quantityInput.value) || 1;
            const maxQuantity = parseInt(quantityInput.max) || 999;
            
            // Validate quantity
            if (quantity < 1) {
                quantity = 1;
                quantityInput.value = 1;
            } else if (quantity > maxQuantity) {
                quantity = maxQuantity;
                quantityInput.value = maxQuantity;
                showNotification(`Maximum available quantity is ${maxQuantity}`, 'warning');
            }
            
            updateConfirmationTotalAndButtons();
        }

        // Update Confirmation Total Price and Buttons
        function updateConfirmationTotalAndButtons() {
            if (!pendingCartItem) return;
            
            const quantityInput = document.getElementById('confirmQuantity');
            const quantity = parseInt(quantityInput.value) || 1;
            const maxQuantity = parseInt(quantityInput.max) || 999;
            
            updateConfirmationTotal(pendingCartItem.price, quantity);
            updateQuantityButtons(quantity, maxQuantity);
        }

        // Update Confirmation Total Price
        function updateConfirmationTotal(price, quantity) {
            const total = price * quantity;
            document.getElementById('confirmTotalPrice').textContent = 'Total: $' + total.toFixed(2);
        }

        // Update Quantity Button States
        function updateQuantityButtons(quantity, maxQuantity) {
            const decreaseBtn = document.getElementById('decreaseQtyBtn');
            const increaseBtn = document.getElementById('increaseQtyBtn');
            
            decreaseBtn.disabled = quantity <= 1;
            increaseBtn.disabled = quantity >= maxQuantity;
                        }
                        
        // Close Confirmation Modal
        function closeConfirmationModal() {
            const modal = document.getElementById('confirmationModal');
            const quantityInput = document.getElementById('confirmQuantity');
            
            modal.classList.remove('active');
            document.body.style.overflow = '';
            pendingCartItem = null;
            pendingButton = null;
            
            // Reset quantity to 1
            if (quantityInput) {
                quantityInput.value = 1;
            }
        }

        // Direct add to cart helper (no confirmation modal)
        async function addItemToCartAndGo(itemId, itemName, price, availableQuantity, itemImage, triggerButton = null) {
            // Refresh cart from database to get latest quantities
            await loadCartFromDatabase();

            // Check stock first
            const existingItem = cart.find(item => item.itemId === itemId);
            if (existingItem && existingItem.quantity >= availableQuantity) {
                showNotification('Stock limit reached for this item!', 'warning');
                return;
            }

            // Disable trigger button while processing
            if (triggerButton) {
                triggerButton.disabled = true;
            }

            const result = await callCartAPI('add_to_cart', {
                item_id: itemId,
                quantity: 1,
                price: price
            });

            if (triggerButton) {
                triggerButton.disabled = false;
            }

            if (!result.success) {
                showNotification(result.message || 'Failed to add item to cart', 'warning');
                return;
            }

            await loadCartFromDatabase();
            updateCartBadge();
            showNotification(`${itemName} added to cart!`, 'success');

            // Go to cart page
            window.location.href = 'cart.php';
        }

        // Open Product Modal
        function openProductModal(productCard) {
            const modal = document.getElementById('productModal');
            const itemId = productCard.dataset.itemId;
            const itemName = productCard.dataset.itemName;
            const itemPrice = parseFloat(productCard.dataset.itemPrice);
            const itemImage = productCard.dataset.itemImage;
            const itemQuantity = parseInt(productCard.dataset.itemQuantity) || 0;
            const itemDescription = productCard.dataset.itemDescription || '';
            const itemCategory = productCard.dataset.itemCategory || '';

            // Set modal content
            document.getElementById('modalImage').src = '../../admin/inventory/' + itemImage;
            document.getElementById('modalImage').alt = itemName;
            document.getElementById('modalTitle').textContent = itemName;
            document.getElementById('modalCategory').textContent = itemCategory || 'Product';
            document.getElementById('modalDescription').textContent = itemDescription;
            
            // Set price
            const priceElement = document.getElementById('modalPrice');
            priceElement.textContent = '$' + itemPrice.toFixed(2);

            // Show original price if applicable
            const originalPriceElement = document.getElementById('modalOriginalPrice');
            if (itemPrice > 500) {
                const originalPrice = itemPrice * 1.5;
                originalPriceElement.textContent = '$' + originalPrice.toFixed(2);
                originalPriceElement.style.display = 'inline';
            } else {
                originalPriceElement.style.display = 'none';
            }
            
            // Set stock status
            const stockElement = document.getElementById('modalStock');
            stockElement.classList.remove('in-stock', 'low-stock', 'out-of-stock');
            
            if (itemQuantity > 10) {
                stockElement.textContent = `In Stock (${itemQuantity} available)`;
                stockElement.classList.add('in-stock');
                document.getElementById('modalAddCartBtn').disabled = false;
            } else if (itemQuantity > 0) {
                stockElement.textContent = `Low Stock (Only ${itemQuantity} left)`;
                stockElement.classList.add('low-stock');
                document.getElementById('modalAddCartBtn').disabled = false;
            } else {
                stockElement.textContent = 'Out of Stock';
                stockElement.classList.add('out-of-stock');
                document.getElementById('modalAddCartBtn').disabled = true;
            }
            
            // Store current product data in modal
            modal.dataset.currentItemId = itemId;
            modal.dataset.currentItemName = itemName;
            modal.dataset.currentItemPrice = itemPrice;
            modal.dataset.currentItemImage = itemImage;
            modal.dataset.currentItemQuantity = itemQuantity;
            
            // Show modal
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Close Product Modal
        function closeProductModal() {
            const modal = document.getElementById('productModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
            }

        // Add to Cart from product detail modal -> go to cart page (no modal)
        async function addToCartFromModal() {
            const modal = document.getElementById('productModal');
            const itemId = modal.dataset.currentItemId;
            const itemName = modal.dataset.currentItemName;
            const itemPrice = parseFloat(modal.dataset.currentItemPrice);
            const itemImage = modal.dataset.currentItemImage;
            const availableQuantity = parseInt(modal.dataset.currentItemQuantity) || 0;

            await addItemToCartAndGo(itemId, itemName, itemPrice, availableQuantity, itemImage, document.getElementById('modalAddCartBtn'));
        }

        // Toggle Wishlist
        function toggleWishlist() {
            const btn = document.getElementById('modalWishlistBtn');
            btn.classList.toggle('active');
            const isActive = btn.classList.contains('active');
            showNotification(isActive ? 'Added to wishlist!' : 'Removed from wishlist!', 'success');
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeProductModal();
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const productModal = document.getElementById('productModal');
                const confirmationModal = document.getElementById('confirmationModal');
                
                if (confirmationModal.classList.contains('active')) {
                    closeConfirmationModal();
                } else if (productModal.classList.contains('active')) {
                    closeProductModal();
                }
            }
        });

        // Close confirmation modal when clicking outside
        document.addEventListener('click', function(event) {
            const confirmationModal = document.getElementById('confirmationModal');
            if (event.target === confirmationModal) {
                closeConfirmationModal();
            }
        });

        // Update Cart Badge - Fetch from database
        async function updateCartBadge() {
            const cartBadge = document.querySelector('.cart-badge');
            if (!cartBadge) return;
            
            try {
                const response = await fetch(`${CART_API_URL}?action=get_cart_count`);
                const data = await response.json();
                
                if (data.success) {
                    const totalItems = data.total_items || 0;
                    cartBadge.textContent = totalItems;
                    
                    if (totalItems > 0) {
                        cartBadge.style.display = 'flex';
                    } else {
                        cartBadge.style.display = 'none';
                    }
                } else {
                    // Fallback to local cart if API fails
                    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                    cartBadge.textContent = totalItems;
                    cartBadge.style.display = totalItems > 0 ? 'flex' : 'none';
                }
            } catch (error) {
                console.error('Error updating cart badge:', error);
                // Fallback to local cart
                const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                cartBadge.textContent = totalItems;
                cartBadge.style.display = totalItems > 0 ? 'flex' : 'none';
            }
        }

        // Remove item from cart (for future use in cart page)
        async function removeFromCart(cartItemId) {
            const result = await callCartAPI('remove_from_cart', {
                cart_item_id: cartItemId
            });
            
            if (result.success) {
                await loadCartFromDatabase();
                showNotification('Item removed from cart', 'success');
            } else {
                showNotification(result.message || 'Failed to remove item', 'warning');
            }
            
            return result.success;
        }

        // Update cart item quantity (for future use in cart page)
        async function updateCartItemQuantity(cartItemId, quantity) {
            if (quantity < 1) {
                return await removeFromCart(cartItemId);
            }
            
            const result = await callCartAPI('update_quantity', {
                cart_item_id: cartItemId,
                quantity: quantity
            });
            
            if (result.success) {
                await loadCartFromDatabase();
            } else {
                showNotification(result.message || 'Failed to update quantity', 'warning');
            }
            
            return result.success;
        }

        // Open Cart Modal
        async function openCartModal() {
            const modal = document.getElementById('cartModal');
            if (!modal) return;
            
            // Load cart items from database
            await loadCartFromDatabase();
            
            // Render cart items
            renderCartItems();
            
            // Show modal
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Close Cart Modal
        function closeCartModal() {
            const modal = document.getElementById('cartModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        // Render Cart Items in Modal
        function renderCartItems() {
            const cartItemsList = document.getElementById('cartItemsList');
            const cartSubtotal = document.getElementById('cartSubtotal');
            const cartTotal = document.getElementById('cartTotal');
            const checkoutBtn = document.getElementById('checkoutBtn');
            
            if (!cartItemsList) return;
            
            if (cart.length === 0) {
                cartItemsList.innerHTML = `
                    <div class="cart-empty">
                        <div class="cart-empty-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="cart-empty-text">Your cart is empty</div>
                        <button class="cart-btn cart-btn-continue" onclick="closeCartModal()">
                            <i class="fas fa-arrow-left"></i>
                            Continue Shopping
                        </button>
                    </div>
                `;
                if (cartSubtotal) cartSubtotal.textContent = '$0.00';
                if (cartTotal) cartTotal.textContent = '$0.00';
                if (checkoutBtn) checkoutBtn.disabled = true;
                return;
            }
            
            // Render cart items
            let html = '';
            let subtotal = 0;
            
            cart.forEach(item => {
                const itemSubtotal = item.price * item.quantity;
                subtotal += itemSubtotal;
                
                html += `
                    <div class="cart-item" data-cart-item-id="${item.cartItemId}">
                        <img src="../../admin/inventory/${item.image}" 
                             alt="${item.itemName}" 
                             class="cart-item-image"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27100%27 height=%27100%27%3E%3Crect fill=%27%23f8f9fa%27 width=%27100%27 height=%27100%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.itemName}</div>
                            <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                            <div class="cart-item-quantity-controls">
                                <button class="cart-item-quantity-btn" onclick="updateCartItemQty(${item.cartItemId}, ${item.quantity - 1})">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       class="cart-item-quantity-input" 
                                       value="${item.quantity}" 
                                       min="1" 
                                       max="${item.stock}"
                                       onchange="updateCartItemQty(${item.cartItemId}, parseInt(this.value))">
                                <button class="cart-item-quantity-btn" onclick="updateCartItemQty(${item.cartItemId}, ${item.quantity + 1})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="cart-item-subtotal">$${itemSubtotal.toFixed(2)}</div>
                        <div class="cart-item-remove" onclick="removeCartItem(${item.cartItemId})" title="Remove item">
                            <i class="fas fa-trash"></i>
                        </div>
                    </div>
                `;
            });
            
            cartItemsList.innerHTML = html;
            
            // Update totals
            if (cartSubtotal) cartSubtotal.textContent = '$' + subtotal.toFixed(2);
            if (cartTotal) cartTotal.textContent = '$' + subtotal.toFixed(2);
            if (checkoutBtn) checkoutBtn.disabled = false;
        }

        // Update Cart Item Quantity from Modal
        async function updateCartItemQty(cartItemId, newQuantity) {
            const result = await updateCartItemQuantity(cartItemId, newQuantity);
            if (result) {
                renderCartItems();
                updateCartBadge();
            }
        }

        // Remove Cart Item from Modal
        async function removeCartItem(cartItemId) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }
            
            const result = await removeFromCart(cartItemId);
            if (result) {
                renderCartItems();
                updateCartBadge();
            }
        }

        // Proceed to Checkout
        function proceedToCheckout() {
            if (cart.length === 0) {
                showNotification('Your cart is empty', 'warning');
                return;
            }
            
            // Close cart modal
            closeCartModal();
            
            // TODO: Redirect to checkout page or show checkout form
            showNotification('Redirecting to checkout...', 'success');
            // window.location.href = 'checkout.php';
        }

        // Close cart modal when clicking outside
        document.addEventListener('click', function(event) {
            const cartModal = document.getElementById('cartModal');
            if (cartModal && event.target === cartModal) {
                closeCartModal();
            }
        });

        // Close cart modal on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const cartModal = document.getElementById('cartModal');
                if (cartModal && cartModal.classList.contains('active')) {
                    closeCartModal();
                }
            }
        });

        // Show Notification
        function showNotification(message, type = 'success') {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: ${type === 'success' ? '#1ABB9C' : '#ffc107'};
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                z-index: 10000;
                font-weight: 600;
                font-size: 14px;
                animation: slideIn 0.3s ease-out;
                max-width: 300px;
            `;
            notification.textContent = message;
            
            // Add animation
            const style = document.createElement('style');
            style.textContent = `
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
            `;
            if (!document.querySelector('#notification-style')) {
                style.id = 'notification-style';
                document.head.appendChild(style);
            }
            
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease-out reverse';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    </script>

    <!-- Products Section -->
    <section class="products-section" id="products">
        <div class="section-header">
            <h2 class="section-title typing-text" id="productsTitle"></h2>
            <a href="#" class="see-all-link">See All <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="products-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                <div class="product-card" 
                     data-item-id="<?php echo htmlspecialchars($product['item_id']); ?>"
                     data-item-name="<?php echo htmlspecialchars($product['item_name']); ?>"
                     data-item-price="<?php echo htmlspecialchars($product['total_cost']); ?>"
                     data-item-image="<?php echo htmlspecialchars($product['picture']); ?>"
                     data-item-quantity="<?php echo htmlspecialchars($product['quantity']); ?>"
                     data-item-description="<?php echo htmlspecialchars($product['description'] ?? $product['category_name'] ?? 'Premium furniture'); ?>"
                     data-item-category="<?php echo htmlspecialchars($product['category_name'] ?? ''); ?>"
                     data-category-id="<?php echo htmlspecialchars($product['category_id'] ?? ''); ?>"
                     onclick="openProductModal(this)">
                    <div class="product-image-wrapper">
                        <img src="../../admin/inventory/<?php echo htmlspecialchars($product['picture']); ?>" 
                             alt="<?php echo htmlspecialchars($product['item_name']); ?>" 
                             class="product-image"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27280%27 height=%27280%27%3E%3Crect fill=%27%23f8f9fa%27 width=%27280%27 height=%27280%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';">
                        <div class="product-overlay">
                            <button class="product-action-btn" onclick="event.stopPropagation(); addToCart(this);">Add to cart</button>
                            <div class="product-action-icon" onclick="event.stopPropagation();">
                                <i class="fas fa-share-alt"></i>
                            </div>
                            <div class="product-action-icon" onclick="event.stopPropagation();">
                                <i class="fas fa-heart"></i>
                            </div>
                        </div>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($product['item_name']); ?></div>
                        <div class="product-description"><?php echo htmlspecialchars($product['description'] ?? $product['category_name'] ?? 'Premium furniture'); ?></div>
                        <div class="product-price">
                            <span class="price-current">$<?php echo number_format($product['total_cost'], 2); ?></span>
                            <?php if ($product['total_cost'] > 500): ?>
                            <span class="price-original">$<?php echo number_format($product['total_cost'] * 1.5, 2); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center; color: #6c757d; padding: 40px;">No products available at the moment.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Product Preview Modal -->
    <div id="productModal" class="product-modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeProductModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-body">
                <div class="modal-image-wrapper">
                    <img id="modalImage" class="modal-image" src="" alt="Product Image"
                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27500%27 height=%27500%27%3E%3Crect fill=%27%23f8f9fa%27 width=%27500%27 height=%27500%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';">
                    </div>
                <div class="modal-info">
                    <div class="modal-category" id="modalCategory"></div>
                    <h2 class="modal-title" id="modalTitle"></h2>
                    <div class="modal-price-section">
                        <span class="modal-price-current" id="modalPrice"></span>
                        <span class="modal-price-original" id="modalOriginalPrice" style="display: none;"></span>
                    </div>
                    <div class="modal-stock" id="modalStock"></div>
                    <div class="modal-description" id="modalDescription"></div>
                    <div class="modal-actions">
                        <button class="modal-add-cart-btn" id="modalAddCartBtn" onclick="addToCartFromModal()">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Add to Cart</span>
                        </button>
                        <button class="modal-wishlist-btn" id="modalWishlistBtn" onclick="toggleWishlist()">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                    </div>
                </div>
            </div>
            </div>

    <!-- Cart Modal -->
    <div id="cartModal" class="cart-modal">
        <div class="cart-modal-content">
            <div class="cart-modal-header">
                <h2 class="cart-modal-title">
                    <i class="fas fa-shopping-cart"></i>
                    Shopping Cart
                </h2>
                <button class="cart-modal-close" onclick="closeCartModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="cart-modal-body">
                <div class="cart-items-list" id="cartItemsList">
                    <!-- Cart items will be loaded here -->
                </div>
                <div class="cart-summary" id="cartSummary">
                    <div class="cart-summary-row">
                        <span>Subtotal:</span>
                        <span id="cartSubtotal">$0.00</span>
                    </div>
                    <div class="cart-summary-row total">
                        <span>Total:</span>
                        <span id="cartTotal">$0.00</span>
                    </div>
                </div>
                <div class="cart-modal-actions">
                    <button class="cart-btn cart-btn-continue" onclick="closeCartModal()">
                        <i class="fas fa-arrow-left"></i>
                        Continue Shopping
                    </button>
                    <button class="cart-btn cart-btn-checkout" id="checkoutBtn" onclick="proceedToCheckout()">
                        <i class="fas fa-check"></i>
                        Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="confirmation-content">
            <div class="confirmation-header">
                <div class="confirmation-icon">
                    <i class="fas fa-shopping-cart"></i>
        </div>
                <h3 class="confirmation-title">Add to Cart?</h3>
            </div>
            <div class="confirmation-body">
                <div class="confirmation-product">
                    <img id="confirmProductImage" class="confirmation-product-image" src="" alt="Product">
                    <div class="confirmation-product-info">
                        <div class="confirmation-product-name" id="confirmProductName"></div>
                        <div class="confirmation-product-price" id="confirmProductPrice"></div>
                        <div class="confirmation-quantity-controls">
                            <span class="confirmation-quantity-label">Quantity:</span>
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="decreaseQuantity()" id="decreaseQtyBtn">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="quantity-input" id="confirmQuantity" value="1" min="1" max="999" onchange="updateQuantityFromInput()">
                                <button class="quantity-btn" onclick="increaseQuantity()" id="increaseQtyBtn">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="confirmation-total-price" id="confirmTotalPrice"></div>
                        </div>
                    </div>
                </div>
                <p class="confirmation-message">Are you sure you want to add this item to your cart?</p>
                <div class="confirmation-actions">
                    <button class="confirmation-btn confirmation-btn-cancel" onclick="closeConfirmationModal()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button class="confirmation-btn confirmation-btn-confirm" onclick="confirmAddToCart()">
                        <i class="fas fa-check"></i>
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <!-- Footer Video Background -->
        <video class="footer-video" autoplay muted loop playsinline preload="auto">
            <source src="video/video.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="footer-overlay"></div>
        
        <div class="footer-content">
            <div class="footer-section">
                <h3>TUMANDOK FACTORY</h3>
                <ul>
                    <li>Maria Morena, Brgy. Calumangan, Bago City,</li>
                    <li>Negros Occidental, Philippines 1601</li>
                    <li>(+63) 917 168 9401</li>
                    <li>Globe | Viber | Whatsapp</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 TUMANDOK FACTORY. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>

