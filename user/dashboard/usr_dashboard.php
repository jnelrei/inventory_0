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

// Pagination
$itemsPerPage = 20;
$totalProducts = count($products);
$totalPages = ceil($totalProducts / $itemsPerPage);
$currentPage = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $totalPages)) : 1;
$startIndex = ($currentPage - 1) * $itemsPerPage;
$paginatedProducts = array_slice($products, $startIndex, $itemsPerPage);

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
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Smooth scrolling performance for animated elements */
        .scroll-reveal,
        .scroll-reveal-fade,
        .scroll-reveal-left,
        .scroll-reveal-right,
        .scroll-reveal-scale {
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            -webkit-perspective: 1000px;
            perspective: 1000px;
        }

        /* Remove will-change after animation completes for better performance */
        .scroll-reveal.revealed,
        .scroll-reveal-fade.revealed,
        .scroll-reveal-left.revealed,
        .scroll-reveal-right.revealed,
        .scroll-reveal-scale.revealed {
            will-change: auto;
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
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .product-card:hover .product-overlay {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
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

        /* Pagination */
        .pagination-container {
            margin-top: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            color: #2f4050;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .pagination-btn:hover:not(.disabled) {
            background: #1ABB9C;
            border-color: #1ABB9C;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 187, 156, 0.2);
        }

        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8f9fa;
        }

        .pagination-numbers {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .pagination-number {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            color: #2f4050;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .pagination-number:hover:not(.active) {
            background: #f8f9fa;
            border-color: #1ABB9C;
            color: #1ABB9C;
        }

        .pagination-number.active {
            background: #1ABB9C;
            border-color: #1ABB9C;
            color: white;
            cursor: default;
        }

        .pagination-ellipsis {
            padding: 0 8px;
            color: #6c757d;
            font-weight: 600;
        }

        .pagination-info {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .pagination-btn {
                padding: 8px 16px;
                font-size: 13px;
            }

            .pagination-btn span {
                display: none;
            }

            .pagination-number {
                min-width: 35px;
                height: 35px;
                padding: 0 8px;
                font-size: 13px;
            }

            .pagination-info {
                font-size: 12px;
            }
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

        /* Confirmation Modal - Premium Ecommerce Style */
        @keyframes confirmationSlideIn {
            0% {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes confirmationProductSlide {
            0% {
                opacity: 0;
                transform: translateX(-20px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            z-index: 20000;
            overflow-y: auto;
            animation: fadeIn 0.3s ease;
        }

        .confirmation-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .confirmation-content {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            max-width: 550px;
            width: 100%;
            min-height: 500px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: confirmationSlideIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            overflow: hidden;
        }

        .confirmation-header {
            padding: 30px 30px 20px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(233, 236, 239, 0.5);
            position: relative;
        }

        .confirmation-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 32px;
            color: white;
            box-shadow: 0 8px 20px rgba(26, 187, 156, 0.3);
            animation: swalSuccessPulse 2s ease-in-out infinite;
        }

        .confirmation-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: #1a1a1a;
            letter-spacing: -0.5px;
        }

        .confirmation-body {
            padding: 30px;
            text-align: center;
        }

        .confirmation-product {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: white;
            border-radius: 16px;
            margin-bottom: 25px;
            text-align: left;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(233, 236, 239, 0.8);
            transition: all 0.3s ease;
            animation: confirmationProductSlide 0.5s ease-out 0.2s both;
        }

        .confirmation-product:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .confirmation-product-image {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            background: #f8f9fa;
            flex-shrink: 0;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .confirmation-product-info {
            flex: 1;
            min-width: 0;
        }

        .confirmation-product-name {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .confirmation-product-price {
            font-size: 18px;
            font-weight: 700;
            color: #1ABB9C;
            margin-bottom: 15px;
        }

        .confirmation-quantity-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #e9ecef;
            flex-wrap: wrap;
        }

        .confirmation-quantity-label {
            font-size: 14px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .quantity-btn {
            width: 36px;
            height: 36px;
            background: white;
            border: none;
            color: #2f4050;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-btn:hover {
            background: #1ABB9C;
            color: white;
        }

        .quantity-btn:active {
            transform: scale(0.95);
        }

        .quantity-btn:disabled {
            background: #f8f9fa;
            color: #adb5bd;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .quantity-input {
            width: 60px;
            height: 36px;
            border: none;
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            color: #1a1a1a;
            background: white;
            outline: none;
            border-left: 1px solid #e9ecef;
            border-right: 1px solid #e9ecef;
        }

        .quantity-input::-webkit-inner-spin-button,
        .quantity-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .confirmation-total-price {
            margin-left: auto;
            font-size: 20px;
            font-weight: 800;
            color: #1ABB9C;
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .confirmation-message {
            font-size: 15px;
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .confirmation-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            justify-content: center;
        }

        .confirmation-btn {
            flex: 1;
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.3px;
        }

        .confirmation-btn-cancel {
            background: white;
            color: #6c757d;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .confirmation-btn-cancel:hover {
            background: #f8f9fa;
            border-color: #1ABB9C;
            color: #1ABB9C;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.15);
        }

        .confirmation-btn-cancel:active {
            transform: translateY(-1px);
        }

        .confirmation-btn-confirm {
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            color: white;
            box-shadow: 0 4px 15px rgba(26, 187, 156, 0.3);
            flex: 0 0 auto;
            min-width: 150px;
            max-width: 300px;
        }

        .confirmation-btn-confirm:hover {
            background: linear-gradient(135deg, #117a65, #0d5d4d);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(26, 187, 156, 0.4);
        }

        .confirmation-btn-confirm:active {
            transform: translateY(-1px);
        }

        .confirmation-modal .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(248, 249, 250, 0.9);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #adb5bd;
            z-index: 10001;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-right: 10px;
            margin-top: 10px;
        }

        .confirmation-modal .modal-close:hover {
            background: rgba(26, 187, 156, 0.1);
            color: #1ABB9C;
            transform: rotate(90deg) scale(1.1);
        }

        @media (max-width: 480px) {
            .confirmation-content {
                max-width: 100%;
                border-radius: 16px;
            }

            .confirmation-header {
                padding: 25px 20px 15px 20px;
            }

            .confirmation-body {
                padding: 20px;
            }

            .confirmation-actions {
                flex-direction: column;
            }

            .confirmation-product {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .confirmation-product-info {
                text-align: center;
                width: 100%;
            }

            .confirmation-quantity-controls {
                justify-content: center;
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

        /* Product Carousel */
        .products-carousel-section {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
        }

        .carousel-container {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
        }

        .carousel-wrapper {
            display: flex;
            transition: transform 0.7s cubic-bezier(0.5, 0, 0.5, 1);
            will-change: transform;
        }

        .carousel-slide {
            min-width: 100%;
            display: flex;
            flex-direction: row;
            gap: 25px;
            padding: 20px;
            flex-shrink: 0;
        }

        .carousel-product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            flex: 0 0 auto;
            width: 140px;
            min-width: 140px;
        }

        .carousel-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }

        .carousel-product-image-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 140px;
            overflow: hidden;
            background: #f8f9fa;
        }

        .carousel-product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .carousel-product-card:hover .carousel-product-image {
            transform: scale(1.1);
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            font-size: 14px;
        }

        .carousel-nav:hover {
            background: #1ABB9C;
            border-color: #1ABB9C;
            color: white;
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-nav.prev {
            left: 20px;
        }

        .carousel-nav.next {
            right: 20px;
        }

        .carousel-nav.disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .carousel-nav.disabled:hover {
            background: white;
            border-color: #e9ecef;
            color: #2f4050;
            transform: translateY(-50%);
        }

        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }

        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-dot.active {
            background: #1ABB9C;
            width: 30px;
            border-radius: 6px;
        }

        .carousel-dot:hover {
            background: #1ABB9C;
        }

        @media (max-width: 1024px) {
            .carousel-product-card {
                width: 130px;
                min-width: 130px;
            }
            .carousel-slide {
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .carousel-nav {
                width: 35px;
                height: 35px;
                font-size: 12px;
            }

            .carousel-nav.prev {
                left: 10px;
            }

            .carousel-nav.next {
                right: 10px;
            }

            .carousel-slide {
                gap: 20px;
                padding: 15px;
            }

            .carousel-product-card {
                width: 120px;
                min-width: 120px;
            }

            .carousel-product-image-wrapper {
                min-height: 120px;
            }

            .products-carousel-section {
                padding: 0 20px;
                margin: 30px auto;
            }
        }

        @media (max-width: 480px) {
            .carousel-slide {
                gap: 15px;
                padding: 12px;
            }

            .carousel-product-card {
                width: 100px;
                min-width: 100px;
            }

            .carousel-product-image-wrapper {
                min-height: 100px;
            }
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

        /* Scroll Animation Styles */
        .scroll-reveal {
            opacity: 0;
            transform: translate3d(0, 40px, 0);
            transition: opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                        transform 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: opacity, transform;
        }

        .scroll-reveal.revealed {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }

        .scroll-reveal-fade {
            opacity: 0;
            transition: opacity 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: opacity;
        }

        .scroll-reveal-fade.revealed {
            opacity: 1;
        }

        .scroll-reveal-left {
            opacity: 0;
            transform: translate3d(-60px, 0, 0);
            transition: opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                        transform 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: opacity, transform;
        }

        .scroll-reveal-left.revealed {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }

        .scroll-reveal-right {
            opacity: 0;
            transform: translate3d(60px, 0, 0);
            transition: opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                        transform 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: opacity, transform;
        }

        .scroll-reveal-right.revealed {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }

        .scroll-reveal-scale {
            opacity: 0;
            transform: translate3d(0, 0, 0) scale(0.85);
            transition: opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                        transform 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: opacity, transform;
        }

        .scroll-reveal-scale.revealed {
            opacity: 1;
            transform: translate3d(0, 0, 0) scale(1);
        }

        /* Stagger delays for grid items - smoother spacing */
        .scroll-reveal-delay-1 {
            transition-delay: 0.15s;
        }

        .scroll-reveal-delay-2 {
            transition-delay: 0.3s;
        }

        .scroll-reveal-delay-3 {
            transition-delay: 0.45s;
        }

        .scroll-reveal-delay-4 {
            transition-delay: 0.6s;
        }

        .scroll-reveal-delay-5 {
            transition-delay: 0.75s;
        }

        /* SweetAlert Ecommerce Premium Styles */
        @keyframes swalSlideIn {
            0% {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes swalSuccessPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        @keyframes swalProductSlide {
            0% {
                opacity: 0;
                transform: translateX(-20px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .swal-ecommerce-popup {
            border-radius: 20px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.1) inset !important;
            padding: 0 !important;
            overflow: hidden !important;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%) !important;
        }

        .swal-ecommerce-title {
            padding: 30px 30px 20px 30px !important;
            margin: 0 !important;
            border-bottom: 1px solid rgba(233, 236, 239, 0.5) !important;
        }

        .swal-success-icon-wrapper {
            margin-bottom: 15px;
        }

        .swal-success-icon {
            font-size: 70px !important;
            color: #1ABB9C !important;
            background: linear-gradient(135deg, #1ABB9C, #117a65) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: swalSuccessPulse 2s ease-in-out infinite !important;
            display: inline-block !important;
            filter: drop-shadow(0 4px 8px rgba(26, 187, 156, 0.3)) !important;
        }

        .swal-success-title {
            font-size: 24px !important;
            font-weight: 700 !important;
            color: #1a1a1a !important;
            letter-spacing: -0.5px !important;
        }

        .swal-ecommerce-html {
            padding: 0 30px 30px 30px !important;
        }

        .swal-product-container {
            animation: swalProductSlide 0.5s ease-out 0.2s both;
        }

        .swal-product-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            border: 1px solid rgba(233, 236, 239, 0.8);
            transition: all 0.3s ease;
        }

        .swal-product-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .swal-product-image-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            flex-shrink: 0;
        }

        .swal-product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            background: #f8f9fa;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .swal-product-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(26, 187, 156, 0.4);
            border: 3px solid white;
            animation: swalSuccessPulse 1.5s ease-in-out infinite;
        }

        .swal-product-details {
            flex: 1;
            min-width: 0;
        }

        .swal-product-name {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 12px 0;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .swal-product-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .swal-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .swal-info-label {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
        }

        .swal-info-value {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
            background: #f8f9fa;
            padding: 4px 10px;
            border-radius: 6px;
        }

        .swal-info-price {
            font-size: 16px;
            font-weight: 700;
            color: #1ABB9C;
        }

        .swal-total-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 12px;
            border: 2px solid #e9ecef;
            margin-top: 5px;
        }

        .swal-total-label {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .swal-total-amount {
            font-size: 28px;
            font-weight: 800;
            color: #1ABB9C;
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .swal-ecommerce-actions {
            margin: 0 !important;
            padding: 25px 30px 30px 30px !important;
            gap: 12px !important;
            border-top: 1px solid rgba(233, 236, 239, 0.5) !important;
            background: rgba(248, 249, 250, 0.5) !important;
        }

        .swal-ecommerce-confirm {
            background: linear-gradient(135deg, #1ABB9C, #117a65) !important;
            border-radius: 10px !important;
            padding: 14px 35px !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border: none !important;
            box-shadow: 0 4px 15px rgba(26, 187, 156, 0.3) !important;
            letter-spacing: 0.3px !important;
        }

        .swal-ecommerce-confirm:hover {
            background: linear-gradient(135deg, #117a65, #0d5d4d) !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 6px 20px rgba(26, 187, 156, 0.4) !important;
        }

        .swal-ecommerce-confirm:active {
            transform: translateY(-1px) !important;
        }

        .swal-ecommerce-cancel {
            background: white !important;
            border: 2px solid #e9ecef !important;
            border-radius: 10px !important;
            padding: 14px 35px !important;
            font-weight: 600 !important;
            font-size: 15px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            color: #6c757d !important;
            letter-spacing: 0.3px !important;
        }

        .swal-ecommerce-cancel:hover {
            background: #f8f9fa !important;
            border-color: #1ABB9C !important;
            color: #1ABB9C !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.15) !important;
        }

        .swal-ecommerce-cancel:active {
            transform: translateY(-1px) !important;
        }

        .swal2-popup .swal2-close {
            color: #adb5bd !important;
            font-size: 26px !important;
            transition: all 0.3s ease !important;
            width: 40px !important;
            height: 40px !important;
            border-radius: 50% !important;
            background: rgba(248, 249, 250, 0.8) !important;
            top: 15px !important;
            right: 15px !important;
            margin-right: 10px !important;
            margin-top: 10px !important;
            z-index: 10002 !important;
            pointer-events: auto !important;
            cursor: pointer !important;
            position: absolute !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        .swal2-popup .swal2-close:hover {
            color: #1ABB9C !important;
            background: rgba(26, 187, 156, 0.1) !important;
            transform: rotate(90deg) scale(1.1) !important;
        }
        
        .swal2-popup .swal2-close:active {
            transform: rotate(90deg) scale(0.95) !important;
        }
        
        /* Ensure close button is always accessible */
        .swal2-container .swal2-close {
            pointer-events: auto !important;
            cursor: pointer !important;
            z-index: 99999 !important;
        }

        .swal2-popup .swal2-close:focus {
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(26, 187, 156, 0.2) !important;
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

    <!-- Products Carousel Section -->
    <?php 
    // Get top 10 newest products for carousel
    $carouselProducts = array_slice($products, 0, 10);
    ?>
    <?php if (!empty($carouselProducts)): ?>
    <section class="products-carousel-section">
        <div class="section-header">
            <h2 class="section-title typing-text" id="featuredProductsTitle"></h2>
        </div>
        <div class="carousel-container">
            <button class="carousel-nav prev" id="carouselPrev" aria-label="Previous slide">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-nav next" id="carouselNext" aria-label="Next slide">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="carousel-wrapper" id="carouselWrapper">
                <div class="carousel-slide">
                    <?php 
                    // Render products twice for seamless infinite loop
                    for ($repeat = 0; $repeat < 2; $repeat++): 
                        foreach ($carouselProducts as $product): 
                    ?>
                    <div class="carousel-product-card" 
                         data-item-id="<?php echo htmlspecialchars($product['item_id']); ?>"
                         data-item-name="<?php echo htmlspecialchars($product['item_name']); ?>"
                         data-item-price="<?php echo htmlspecialchars($product['total_cost']); ?>"
                         data-item-image="<?php echo htmlspecialchars($product['picture']); ?>"
                         data-item-quantity="<?php echo htmlspecialchars($product['quantity']); ?>"
                         data-item-description="<?php echo htmlspecialchars($product['description'] ?? $product['category_name'] ?? 'Premium furniture'); ?>"
                         data-item-category="<?php echo htmlspecialchars($product['category_name'] ?? ''); ?>"
                         data-category-id="<?php echo htmlspecialchars($product['category_id'] ?? ''); ?>"
                         onclick="openProductModal(this)">
                        <div class="carousel-product-image-wrapper">
                            <img src="../../admin/inventory/<?php echo htmlspecialchars($product['picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['item_name']); ?>" 
                                 class="carousel-product-image"
                                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27250%27 height=%27250%27%3E%3Crect fill=%27%23f8f9fa%27 width=%27250%27 height=%27250%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';">
                        </div>
                    </div>
                    <?php 
                        endforeach; 
                    endfor; 
                    ?>
                </div>
            </div>
            <div class="carousel-dots" id="carouselDots" style="display: none;">
                <!-- Dots hidden for single product transition -->
            </div>
        </div>
    </section>
    <?php endif; ?>

    <script>
        // Cart Management - Using Database API
        let cart = []; // Keep for local reference, but sync with database
        const CART_API_URL = '../../production/includes/cart_api.php';

        // Initialize cart badge on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadCartFromDatabase();
            initTypingAnimation();
            initFeaturedProductsTypingAnimation();
            initHeroTypingAnimation();
            initScrollAnimations();
            initPaginationScroll();
            initProductCarousel();
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

        // Typing Animation for "Featured Products"
        function initFeaturedProductsTypingAnimation() {
            const titleElement = document.getElementById('featuredProductsTitle');
            if (!titleElement) return;

            const text = 'Featured Products';
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

        // Add to Cart from product card -> show confirmation modal
        function addToCart(button) {
            const productCard = button.closest('.product-card');
            const itemId = productCard.dataset.itemId;
            const itemName = productCard.dataset.itemName;
            const itemPrice = parseFloat(productCard.dataset.itemPrice);
            const itemImage = productCard.dataset.itemImage;
            const availableQuantity = parseInt(productCard.dataset.itemQuantity) || 0;

            // Store pending cart item data
            pendingCartItem = {
                itemId: itemId,
                itemName: itemName,
                price: itemPrice,
                image: itemImage,
                availableQuantity: availableQuantity
            };
            pendingButton = button;

            // Show confirmation modal
            showConfirmationModal(itemName, itemPrice, itemImage, availableQuantity);
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
            const totalElement = document.getElementById('confirmTotalPrice');
            if (totalElement) {
                totalElement.textContent = '$' + total.toFixed(2);
            }
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

        // Direct add to cart helper (no redirect, no confirmation modal)
        async function addItemToCart(itemId, itemName, price, quantity, availableQuantity, itemImage, triggerButton = null) {
            // Refresh cart from database to get latest quantities
            await loadCartFromDatabase();

            // Check stock first
            const existingItem = cart.find(item => item.itemId === itemId);
            if (existingItem && (existingItem.quantity + quantity) > availableQuantity) {
                showNotification('Stock limit reached for this item!', 'warning');
                return false;
            }

            // Disable trigger button while processing
            if (triggerButton) {
                triggerButton.disabled = true;
            }

            const result = await callCartAPI('add_to_cart', {
                item_id: itemId,
                quantity: quantity,
                price: price
            });

            if (triggerButton) {
                triggerButton.disabled = false;
            }

            if (!result.success) {
                showNotification(result.message || 'Failed to add item to cart', 'warning');
                return false;
            }

            await loadCartFromDatabase();
            updateCartBadge();
            
            // Calculate total
            const total = (price * quantity).toFixed(2);
            const imageUrl = `../../admin/inventory/${itemImage}`;
            
            // Show Premium Ecommerce-style SweetAlert success message
            Swal.fire({
                title: '<div class="swal-success-icon-wrapper"><i class="fas fa-check-circle swal-success-icon"></i></div><div class="swal-success-title">Added to Cart!</div>',
                html: `
                    <div class="swal-product-container">
                        <div class="swal-product-card">
                            <div class="swal-product-image-wrapper">
                                <img src="${imageUrl}" 
                                     alt="${itemName}" 
                                     class="swal-product-image"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27100%27 height=%27100%27%3E%3Crect fill=%27%23f8f9fa%27 width=%27100%27 height=%27100%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3ENo Image%3C/text%3E%3C/svg%3E';">
                                <div class="swal-product-badge">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="swal-product-details">
                                <h3 class="swal-product-name">${itemName}</h3>
                                <div class="swal-product-info">
                                    <div class="swal-info-item">
                                        <span class="swal-info-label">Quantity:</span>
                                        <span class="swal-info-value">${quantity}</span>
                                    </div>
                                    <div class="swal-info-item">
                                        <span class="swal-info-label">Price:</span>
                                        <span class="swal-info-price">$${price.toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="swal-total-section">
                            <span class="swal-total-label">Subtotal:</span>
                            <span class="swal-total-amount">$${total}</span>
                        </div>
                    </div>
                `,
                width: '520px',
                padding: '0',
                showConfirmButton: true,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-shopping-cart"></i> View Cart',
                cancelButtonText: '<i class="fas fa-arrow-left"></i> Continue Shopping',
                confirmButtonColor: '#1ABB9C',
                cancelButtonColor: '#6c757d',
                buttonsStyling: true,
                reverseButtons: true,
                customClass: {
                    popup: 'swal-ecommerce-popup',
                    title: 'swal-ecommerce-title',
                    htmlContainer: 'swal-ecommerce-html',
                    confirmButton: 'swal-ecommerce-confirm',
                    cancelButton: 'swal-ecommerce-cancel',
                    actions: 'swal-ecommerce-actions'
                },
                allowOutsideClick: true,
                allowEscapeKey: true,
                showCloseButton: true,
                closeButtonHtml: '<i class="fas fa-times"></i>',
                closeButtonAriaLabel: 'Close this dialog',
                denyButtonText: false,
                didOpen: () => {
                    // Add entrance animation
                    const popup = document.querySelector('.swal-ecommerce-popup');
                    if (popup) {
                        popup.style.animation = 'swalSlideIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                    }
                    
                    // Force close button functionality - multiple attempts to ensure it works
                    const setupCloseButton = () => {
                        const closeButton = document.querySelector('.swal2-close');
                        if (!closeButton) {
                            setTimeout(setupCloseButton, 50);
                            return;
                        }
                        
                        // Ensure button is visible and clickable
                        closeButton.style.pointerEvents = 'auto';
                        closeButton.style.cursor = 'pointer';
                        closeButton.style.zIndex = '10002';
                        closeButton.style.opacity = '1';
                        closeButton.style.display = 'flex';
                        
                        // Remove all existing listeners by cloning
                        const newCloseButton = closeButton.cloneNode(true);
                        closeButton.parentNode.replaceChild(newCloseButton, closeButton);
                        
                        // Force close function
                        const forceClose = (e) => {
                            if (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();
                            }
                            
                            // Multiple methods to ensure modal closes
                            setTimeout(() => {
                                // Method 1: SweetAlert close
                                if (typeof Swal !== 'undefined' && Swal.close) {
                                    Swal.close();
                                }
                                
                                // Method 2: Manually remove container
                                const swalContainer = document.querySelector('.swal2-container');
                                if (swalContainer) {
                                    swalContainer.style.opacity = '0';
                                    setTimeout(() => {
                                        swalContainer.remove();
                                        document.body.style.overflow = '';
                                        document.body.classList.remove('swal2-height-auto');
                                    }, 200);
                                }
                                
                                // Method 3: Remove backdrop
                                const backdrop = document.querySelector('.swal2-backdrop-show');
                                if (backdrop) {
                                    backdrop.remove();
                                }
                            }, 10);
                            
                            return false;
                        };
                        
                        // Add event listeners with capture phase
                        newCloseButton.addEventListener('click', forceClose, { capture: true, once: false });
                        newCloseButton.addEventListener('mousedown', forceClose, { capture: true, once: false });
                        newCloseButton.addEventListener('touchstart', forceClose, { capture: true, once: false });
                        
                        // Direct onclick as final backup
                        newCloseButton.onclick = forceClose;
                        newCloseButton.setAttribute('onclick', 'event.preventDefault(); event.stopPropagation(); Swal.close(); return false;');
                        
                        // Store reference for debugging
                        window.swalCloseButton = newCloseButton;
                    };
                    
                    // Try multiple times to ensure button is found
                    setupCloseButton();
                    setTimeout(setupCloseButton, 100);
                    setTimeout(setupCloseButton, 200);
                },
                willClose: () => {
                    // Clean up when closing
                    document.body.style.overflow = '';
                    document.body.classList.remove('swal2-height-auto');
                    return true;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to cart page
                    window.location.href = 'cart.php';
                } else if (result.isDismissed) {
                    // Modal was closed (via close button, ESC, or Continue Shopping button)
                    // Force cleanup
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) {
                        swalContainer.remove();
                    }
                    document.body.style.overflow = '';
                    document.body.classList.remove('swal2-height-auto');
                    
                    // Refresh page if dismissed (Continue Shopping clicked)
                    if (result.dismiss === Swal.DismissReason.cancel) {
                        window.location.reload();
                    }
                }
            }).catch((error) => {
                // Force close on any error
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.remove();
                }
                document.body.style.overflow = '';
                document.body.classList.remove('swal2-height-auto');
            });
            
            // Global close function for manual triggering
            window.forceCloseSweetAlert = function() {
                try {
                    Swal.close();
                } catch(e) {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) {
                        swalContainer.remove();
                    }
                }
                document.body.style.overflow = '';
                document.body.classList.remove('swal2-height-auto');
            };
            
            return true;
        }

        // Confirm add to cart from confirmation modal
        async function confirmAddToCart() {
            if (!pendingCartItem) return;

            const quantityInput = document.getElementById('confirmQuantity');
            const quantity = parseInt(quantityInput.value) || 1;

            const success = await addItemToCart(
                pendingCartItem.itemId,
                pendingCartItem.itemName,
                pendingCartItem.price,
                quantity,
                pendingCartItem.availableQuantity,
                pendingCartItem.image,
                pendingButton
            );

            if (success) {
                closeConfirmationModal();
            }
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

        // Add to Cart from product detail modal -> show confirmation modal
        function addToCartFromModal() {
            const modal = document.getElementById('productModal');
            const itemId = modal.dataset.currentItemId;
            const itemName = modal.dataset.currentItemName;
            const itemPrice = parseFloat(modal.dataset.currentItemPrice);
            const itemImage = modal.dataset.currentItemImage;
            const availableQuantity = parseInt(modal.dataset.currentItemQuantity) || 0;

            // Store pending cart item data
            pendingCartItem = {
                itemId: itemId,
                itemName: itemName,
                price: itemPrice,
                image: itemImage,
                availableQuantity: availableQuantity
            };
            pendingButton = document.getElementById('modalAddCartBtn');

            // Close product modal and show confirmation modal
            closeProductModal();
            setTimeout(() => {
                showConfirmationModal(itemName, itemPrice, itemImage, availableQuantity);
            }, 300);
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
        
        // Continue Shopping with page refresh
        function continueShopping() {
            closeCartModal();
            // Refresh the page to update product availability and cart state
            window.location.reload();
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
                        <button class="cart-btn cart-btn-continue" onclick="continueShopping()">
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

        // Initialize Scroll Animations
        function initScrollAnimations() {
            // Create Intersection Observer with smooth triggering
            const observerOptions = {
                threshold: 0.05,
                rootMargin: '0px 0px 100px 0px' // Start animation 100px before element enters viewport
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Use requestAnimationFrame for smoother animation start
                        requestAnimationFrame(() => {
                            entry.target.classList.add('revealed');
                            
                            // Remove will-change after animation completes for better performance
                            setTimeout(() => {
                                entry.target.style.willChange = 'auto';
                            }, 1200); // Wait for animation to complete
                        });
                        // Stop observing once revealed for better performance
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // Get all divs and sections to animate
            const elementsToAnimate = document.querySelectorAll(`
                .features-section,
                .features-grid,
                .feature-card,
                .products-section,
                .section-header,
                .products-grid,
                .product-card,
                .about-section,
                .about-content,
                .about-text,
                .about-sections,
                .about-subsection,
                .about-images,
                .about-image,
                .footer-content,
                .footer-section
            `);

            // Add scroll-reveal classes to elements
            elementsToAnimate.forEach((element, index) => {
                // Skip hero section and modals
                if (element.closest('.hero-section') || 
                    element.closest('.product-modal') || 
                    element.closest('.cart-modal') || 
                    element.closest('.confirmation-modal') ||
                    element.closest('.header')) {
                    return;
                }

                // Determine animation type based on element
                let animationClass = 'scroll-reveal';
                
                if (element.classList.contains('feature-card') || 
                    element.classList.contains('product-card') ||
                    element.classList.contains('about-subsection')) {
                    // Grid items get fade-up with stagger
                    animationClass = 'scroll-reveal';
                    const delayIndex = index % 5;
                    if (delayIndex > 0) {
                        element.classList.add(`scroll-reveal-delay-${delayIndex}`);
                    }
                } else if (element.classList.contains('about-text')) {
                    animationClass = 'scroll-reveal-left';
                } else if (element.classList.contains('about-images')) {
                    animationClass = 'scroll-reveal-right';
                } else if (element.classList.contains('section-header')) {
                    animationClass = 'scroll-reveal-fade';
                } else if (element.classList.contains('footer-content')) {
                    animationClass = 'scroll-reveal-scale';
                }

                element.classList.add(animationClass);
                observer.observe(element);
            });

            // Also animate any other divs that don't match the selectors above
            const allDivs = document.querySelectorAll('div');
            allDivs.forEach((div, index) => {
                // Skip if already has scroll-reveal class or is in excluded areas
                if (div.classList.contains('scroll-reveal') ||
                    div.classList.contains('scroll-reveal-fade') ||
                    div.classList.contains('scroll-reveal-left') ||
                    div.classList.contains('scroll-reveal-right') ||
                    div.classList.contains('scroll-reveal-scale') ||
                    div.closest('.hero-section') ||
                    div.closest('.product-modal') ||
                    div.closest('.cart-modal') ||
                    div.closest('.confirmation-modal') ||
                    div.closest('.header') ||
                    div.closest('.modal-content') ||
                    div.closest('.user-profile-dropdown') ||
                    div.closest('.hero-video') ||
                    div.closest('.hero-overlay')) {
                    return;
                }

                // Only animate visible divs with meaningful content
                const hasContent = div.children.length > 0 || 
                                   (div.textContent && div.textContent.trim().length > 0);
                const hasStyles = window.getComputedStyle(div).display !== 'none' &&
                                 window.getComputedStyle(div).visibility !== 'hidden';

                if (hasContent && hasStyles && div.offsetHeight > 0) {
                    div.classList.add('scroll-reveal');
                    observer.observe(div);
                }
            });
        }

        // Initialize Pagination Scroll
        function initPaginationScroll() {
            const paginationLinks = document.querySelectorAll('.pagination-btn:not(.disabled), .pagination-number:not(.active)');
            
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Check if it's an anchor tag
                    if (this.tagName === 'A') {
                        const href = this.getAttribute('href');
                        if (href && href.includes('page=')) {
                            e.preventDefault();
                            
                            // Store the target URL
                            const targetUrl = href;
                            
                            // Smooth scroll to top with a nice transition
                            window.scrollTo({
                                top: 0,
                                behavior: 'smooth'
                            });
                            
                            // Navigate after scroll animation completes (allows for smooth transition)
                            // Using a longer delay to ensure smooth scroll is visible
                            setTimeout(() => {
                                window.location.href = targetUrl;
                            }, 500);
                        }
                    }
                });
            });
        }

        // Initialize Product Carousel
        function initProductCarousel() {
            const carouselWrapper = document.getElementById('carouselWrapper');
            const prevBtn = document.getElementById('carouselPrev');
            const nextBtn = document.getElementById('carouselNext');
            
            if (!carouselWrapper || !prevBtn || !nextBtn) return;
            
            let currentIndex = 0;
            let productWidth = 0;
            let gap = 0;
            let totalProducts = 0;
            let uniqueProducts = 0; // Number of unique products (half of total since duplicated)
            let autoPlayInterval;
            let isTransitioning = false;
            
            // Calculate product width and gap
            function calculateDimensions() {
                const firstCard = carouselWrapper.querySelector('.carousel-product-card');
                const slide = carouselWrapper.querySelector('.carousel-slide');
                if (firstCard && slide) {
                    productWidth = firstCard.offsetWidth;
                    const slideStyle = window.getComputedStyle(slide);
                    gap = parseFloat(slideStyle.gap) || 25; // Get gap from CSS grid/flex gap
                    totalProducts = carouselWrapper.querySelectorAll('.carousel-product-card').length;
                    uniqueProducts = Math.floor(totalProducts / 2); // Since we duplicated products
                }
            }
            
            // Update carousel position - move one product at a time
            function updateCarousel(resetTransition = false) {
                if (resetTransition) {
                    // Remove transition for instant reset
                    carouselWrapper.style.transition = 'none';
                } else {
                    // Restore transition
                    carouselWrapper.style.transition = '';
                }
                
                const translateX = currentIndex * (productWidth + gap);
                carouselWrapper.style.transform = `translateX(-${translateX}px)`;
                
                // Seamlessly reset to beginning when reaching the end of first set
                if (currentIndex >= uniqueProducts && !resetTransition) {
                    setTimeout(() => {
                        currentIndex = currentIndex - uniqueProducts;
                        updateCarousel(true);
                        // Restore transition after reset
                        setTimeout(() => {
                            carouselWrapper.style.transition = '';
                        }, 50);
                    }, 700); // Wait for transition to complete
                }
            }
            
            // Next product
            function nextProduct() {
                currentIndex++;
                updateCarousel();
            }
            
            // Previous product
            function prevProduct() {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateCarousel();
                } else {
                    // If at start, jump to end of first set for seamless loop
                    currentIndex = uniqueProducts - 1;
                    updateCarousel(true);
                    setTimeout(() => {
                        carouselWrapper.style.transition = '';
                    }, 50);
                }
            }
            
            // Auto-play functionality - continuous infinite loop
            function startAutoPlay() {
                // Clear any existing interval
                if (autoPlayInterval) {
                    clearInterval(autoPlayInterval);
                }
                
                autoPlayInterval = setInterval(() => {
                    nextProduct();
                }, 2500); // Change product every 2.5 seconds
            }
            
            function resetAutoPlay() {
                clearInterval(autoPlayInterval);
                startAutoPlay();
            }
            
            function stopAutoPlay() {
                clearInterval(autoPlayInterval);
            }
            
            // Event listeners
            nextBtn.addEventListener('click', () => {
                nextProduct();
                resetAutoPlay();
            });
            
            prevBtn.addEventListener('click', () => {
                prevProduct();
                resetAutoPlay();
            });
            
            // Initialize
            calculateDimensions();
            updateCarousel();
            
            // Start auto-play immediately and continuously
            startAutoPlay();
            
            // Recalculate on resize
            function handleResize() {
                calculateDimensions();
                updateCarousel();
            }
            
            window.addEventListener('resize', handleResize);
        }
    </script>

    <!-- Products Section -->
    <section class="products-section scroll-reveal" id="products">
        <div class="section-header scroll-reveal-fade">
            <h2 class="section-title typing-text" id="productsTitle"></h2>
            <a href="#" class="see-all-link">See All <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="products-grid scroll-reveal">
            <?php if (!empty($paginatedProducts)): ?>
                <?php foreach ($paginatedProducts as $product): ?>
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
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-btn pagination-prev">
                        <i class="fas fa-chevron-left"></i>
                        <span>Previous</span>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn pagination-prev disabled">
                        <i class="fas fa-chevron-left"></i>
                        <span>Previous</span>
                    </span>
                <?php endif; ?>
                
                <div class="pagination-numbers">
                    <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    if ($startPage > 1): ?>
                        <a href="?page=1" class="pagination-number">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $currentPage): ?>
                            <span class="pagination-number active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>" class="pagination-number"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $totalPages; ?>" class="pagination-number"><?php echo $totalPages; ?></a>
                    <?php endif; ?>
                </div>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-btn pagination-next">
                        <span>Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn pagination-next disabled">
                        <span>Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="pagination-info">
                Showing <?php echo $startIndex + 1; ?>-<?php echo min($startIndex + $itemsPerPage, $totalProducts); ?> of <?php echo $totalProducts; ?> products
            </div>
        </div>
        <?php endif; ?>
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
                    <button class="cart-btn cart-btn-continue" onclick="continueShopping()">
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
            <button class="modal-close" onclick="closeConfirmationModal()" style="position: absolute; top: 15px; right: 15px; z-index: 1;">
                <i class="fas fa-times"></i>
            </button>
            <div class="confirmation-header">
                <div class="confirmation-icon">
                    <i class="fas fa-shopping-cart"></i>
        </div>
                <h3 class="confirmation-title">Add to Cart</h3>
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
                        </div>
                    </div>
                </div>
                <div class="confirmation-total-section" style="display: flex; justify-content: space-between; align-items: center; padding: 18px 20px; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 12px; border: 2px solid #e9ecef; margin-bottom: 20px;">
                    <span style="font-size: 16px; font-weight: 600; color: #1a1a1a; text-transform: uppercase; letter-spacing: 0.5px;">Total:</span>
                    <span id="confirmTotalPrice" class="confirmation-total-price" style="margin-left: auto; font-size: 28px; font-weight: 800; color: #1ABB9C; background: linear-gradient(135deg, #1ABB9C, #117a65); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></span>
                </div>
                <div class="confirmation-actions">
                    <button class="confirmation-btn confirmation-btn-confirm" onclick="confirmAddToCart()">
                        <i class="fas fa-check"></i>
                        Add to Cart
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


