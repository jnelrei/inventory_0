<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Include database connection
require_once('../../production/includes/db.php');

// Get user information from session
$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'] ?? 'Guest';
$userRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'User';
$userInitials = '';

// Generate initials
if (!empty($userName)) {
    $parts = preg_split('/\s+/', trim($userName));
    foreach ($parts as $part) {
        $userInitials .= strtoupper(substr($part, 0, 1));
        if (strlen($userInitials) === 2) break;
    }
}
$userInitials = $userInitials ?: 'U';

// Fetch user data from database
$userData = null;
try {
    $stmt = $pdo->prepare("SELECT user_id, name, username, role, created_at, picture FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $userData = null;
}

// Get profile picture path
$profilePicture = $userData['picture'] ?? null;
if ($profilePicture) {
    // Handle both absolute and relative paths
    if (strpos($profilePicture, '/') === 0 || strpos($profilePicture, 'http') === 0) {
        // Absolute path or URL
        $fullPath = $profilePicture;
    } else {
        // Relative path - check if file exists
        $fullPath = __DIR__ . '/' . $profilePicture;
        if (!file_exists($fullPath)) {
            $profilePicture = null;
        } else {
            // Use relative path for display
            $profilePicture = $profilePicture;
        }
    }
}

// Fetch cart stats
$cartStats = [
    'total_items' => 0,
    'total_value' => 0
];
try {
    $stmt = $pdo->prepare("
        SELECT SUM(ci.quantity) as total_items, SUM(ci.subtotal) as total_value
        FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.cart_id
        WHERE c.user_id = ? AND c.status = 'active'
    ");
    $stmt->execute([$userId]);
    $cartData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cartData) {
        $cartStats['total_items'] = $cartData['total_items'] ?? 0;
        $cartStats['total_value'] = $cartData['total_value'] ?? 0;
    }
} catch(PDOException $e) {
    $cartStats = ['total_items' => 0, 'total_value' => 0];
}

// Fetch recent products for the "Recent Products" section
$recentProducts = [];
try {
    $stmt = $pdo->query("
        SELECT i.item_id, i.item_name, i.picture, i.description, i.total_cost, i.quantity, 
               c.category_name 
        FROM invtry i 
        LEFT JOIN category c ON i.category_id = c.category_id 
        WHERE i.picture IS NOT NULL AND i.picture != '' 
        ORDER BY i.created_at DESC 
        LIMIT 4
    ");
    $recentProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $recentProducts = [];
}

// User description
$userDescription = "Welcome to your profile! You are a valued member of Tumandok Crafts Industries. Manage your account, view your cart, and explore our premium products.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Tumandok Crafts Industries</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../../images/logoooo.png" rel="icon">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #ffffff;
            color: #2f4050;
            line-height: 1.6;
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
            overflow: hidden;
            flex-shrink: 0;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
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
            min-height: 400px;
            background: linear-gradient(135deg,rgba(26, 187, 155, 0.88) 0%, #117a65 50%, #0d5d4d 100%);
            overflow: hidden;
            display: flex;
            align-items: center;
            padding: 30px 40px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: background-image 1s ease-in-out;
        }

        /* Background image overlay for readability - removed, using dedicated hero-overlay instead */

        /* Starry background effect - moved to hero-overlay */
        .hero-overlay::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(2px 2px at 20% 30%, white, transparent),
                radial-gradient(2px 2px at 60% 70%, white, transparent),
                radial-gradient(1px 1px at 50% 50%, white, transparent),
                radial-gradient(1px 1px at 80% 10%, white, transparent),
                radial-gradient(2px 2px at 90% 40%, white, transparent),
                radial-gradient(1px 1px at 33% 60%, white, transparent),
                radial-gradient(1px 1px at 55% 80%, white, transparent),
                radial-gradient(2px 2px at 10% 90%, white, transparent);
            background-size: 200% 200%;
            animation: twinkle 20s linear infinite;
            opacity: 0.3;
        }

        @keyframes twinkle {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-50px, -50px); }
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 40px;
            align-items: center;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Background video */
        .hero-bg-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }

        .hero-bg-video video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Hero Overlay - Above background images */
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
            pointer-events: none;
        }

        /* Optional: Add pattern overlay for texture */
        .hero-overlay::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(0, 0, 0, 0.1) 0%, transparent 50%);
            opacity: 0.6;
        }

        .hero-content {
            color: white;
            display: flex;
            gap: 30px;
            align-items: flex-start;
            animation: fadeInRight 1s ease-out 0.7s both;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero-content-main {
            flex: 1;
        }

        .hero-content-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: auto auto;
            gap: 12px;
            min-width: 200px;
            position: relative;
            animation: fadeIn 1s ease-out 1s both;
        }

        .hero-content-stats .hero-stat-card:first-child {
            grid-column: 1 / -1;
            justify-self: center;
            width: 80%;
        }

        .hero-content-stats .hero-stat-card:nth-child(2) {
            grid-column: 1;
            justify-self: end;
        }

        .hero-content-stats .hero-stat-card:nth-child(3) {
            grid-column: 2;
            justify-self: start;
        }

        .hero-logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            opacity: 0.9;
            white-space: nowrap;
            overflow: hidden;
            display: inline-block;
            line-height: 1.2;
            vertical-align: baseline;
            animation: fadeIn 1s ease-out 0.8s both;
        }

        .hero-logo .typed-cursor {
            opacity: 1;
            font-weight: 700;
            animation: typedjsBlink 0.7s infinite;
            display: inline-block;
            vertical-align: baseline;
            line-height: inherit;
            margin-left: 2px;
        }

        @keyframes typedjsBlink {
            50% { opacity: 0; }
        }

        .hero-name {
            font-size: 42px;
            font-weight: 900;
            margin-bottom: 12px;
            line-height: 1.2;
            animation: fadeInUp 0.8s ease-out 0.9s both;
        }

        .hero-description {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
            opacity: 0.95;
            max-width: 450px;
            animation: fadeIn 1s ease-out 1s both;
        }

        .btn-read-more {
            background: white;
            color: #1ABB9C;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-read-more:hover {
            background: #1ABB9C;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 187, 156, 0.3);
        }


        .hero-image {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 16px;
            animation: fadeInLeft 1s ease-out 0.5s both;
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }


        /* Hero Stats Cards */
        .hero-stats {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .hero-stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 12px 16px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: bounceIn 0.6s ease-out both;
        }

        .hero-stat-card:nth-child(1) {
            animation-delay: 1.2s;
        }

        .hero-stat-card:nth-child(2) {
            animation-delay: 1.4s;
        }

        .hero-stat-card:nth-child(3) {
            animation-delay: 1.6s;
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3) translateY(20px);
            }
            50% {
                opacity: 1;
                transform: scale(1.05) translateY(-5px);
            }
            70% {
                transform: scale(0.9) translateY(0);
            }
            100% {
                transform: scale(1) translateY(0);
            }
        }

        .hero-content-stats .hero-stat-card {
            width: auto;
        }

        .hero-stat-card:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-5px);
        }

        .hero-stat-value {
            font-size: 24px;
            font-weight: 900;
            color: white;
            margin-bottom: 3px;
        }

        .hero-stat-label {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.9);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Hero Badge */
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 6px 12px;
            border-radius: 18px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: slideInLeft 0.6s ease-out 0.4s both;
            transition: all 0.3s ease;
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

        .hero-badge:hover {
            transform: translateX(5px);
            background: rgba(255, 255, 255, 0.3);
        }

        .hero-badge i {
            color: #ffd700;
        }

        .profile-hero-avatar {
            width: 220px;
            height: 220px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            border: 5px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: scaleIn 0.8s ease-out 0.6s both;
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .profile-hero-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
        }

        .profile-hero-avatar:hover .avatar-upload-overlay {
            opacity: 1;
        }

        .avatar-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 10;
        }

        .avatar-upload-overlay-content {
            text-align: center;
            color: white;
        }

        .avatar-upload-overlay-content i {
            font-size: 32px;
            margin-bottom: 8px;
            display: block;
        }

        .avatar-upload-overlay-content span {
            font-size: 14px;
            font-weight: 600;
        }

        .profile-hero-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-hero-avatar .initials {
            font-size: 66px;
            font-weight: 900;
            color: white;
        }


        /* Content Section */
        .content-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.8s ease-out both;
        }

        .content-card:nth-child(1) {
            animation-delay: 1.4s;
        }

        .content-card:nth-child(2) {
            animation-delay: 1.6s;
        }

        .content-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-5px) scale(1.02);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #2f4050;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: #1ABB9C;
        }

        .card-link {
            color: #1ABB9C;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .card-link:hover {
            color: #117a65;
            text-decoration: underline;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .product-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, #1ABB9C 0%, #117a65 100%);
            aspect-ratio: 1;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
        }

        .product-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-card-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            padding: 15px;
            color: white;
        }

        .product-card-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .product-card-credit {
            font-size: 12px;
            opacity: 0.8;
        }

        .btn-view-more {
            width: 100%;
            padding: 12px;
            background: white;
            color: #1ABB9C;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-view-more:hover {
            background: #1ABB9C;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 187, 156, 0.3);
        }

        /* Quick Actions */
        .quick-actions-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }

        .action-tag {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #e6f7f4;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-tag.active {
            background: #1ABB9C;
            color: white;
        }

        .action-tag:hover:not(.active) {
            background: #b8e6d9;
        }

        .action-tag-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-tag-icon {
            color: #1ABB9C;
        }

        .action-tag.active .action-tag-icon {
            color: white;
        }

        .action-tag-text {
            font-weight: 500;
            font-size: 14px;
        }

        .action-tag-close {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .action-tag.active .action-tag-close {
            color: white;
        }

        .action-tag-close:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        .info-cards {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .info-card {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            background: #e9ecef;
        }

        .info-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            flex-shrink: 0;
        }

        .info-card-content {
            flex: 1;
        }

        .info-card-title {
            font-weight: 700;
            font-size: 16px;
            color: #1a1a1a;
            margin-bottom: 6px;
        }

        .info-card-text {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
        }

        /* Edit Profile Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            padding: 24px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            border-radius: 16px 16px 0 0;
        }

        .modal-header h3 {
            color: white;
            font-size: 20px;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #1ABB9C;
        }

        .profile-picture-preview {
            width: 150px;
            height: 150px;
            border-radius: 12px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 60px;
            font-weight: 700;
            overflow: hidden;
            border: 5px solid #e9ecef;
        }

        .profile-picture-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .file-upload-wrapper {
            text-align: center;
        }

        .file-upload-label {
            display: inline-block;
            padding: 12px 24px;
            background: #f8f9fa;
            border: 2px dashed #1ABB9C;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #1ABB9C;
            font-weight: 600;
        }

        .file-upload-label:hover {
            background: #1ABB9C;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 187, 156, 0.3);
        }

        .file-upload-info {
            margin-top: 10px;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }

        .file-upload-info i {
            color: #1ABB9C;
            margin-right: 4px;
        }

        .file-upload-input {
            display: none;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1ABB9C, #117a65);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 187, 156, 0.3);
        }

        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
            }

            .hero-content {
                flex-direction: column;
            }

            .hero-content-stats {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
                width: 100%;
                min-width: auto;
            }

            .hero-content-stats .hero-stat-card:first-child,
            .hero-content-stats .hero-stat-card:nth-child(2),
            .hero-content-stats .hero-stat-card:nth-child(3) {
                grid-column: 1;
                justify-self: stretch;
                width: 100%;
            }

            .content-section {
                grid-template-columns: 1fr;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .nav-container {
                flex-wrap: wrap;
            }

        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 0 20px;
            }

            .hero-section {
                padding: 25px 20px;
                min-height: 350px;
            }

            .hero-container {
                gap: 30px;
            }

            .hero-name {
                font-size: 32px;
            }

            .hero-logo {
                font-size: 22px;
            }

            .content-section {
                padding: 40px 20px;
            }

            .profile-hero-avatar {
                width: 180px;
                height: 180px;
            }

            .profile-hero-avatar .initials {
                font-size: 54px;
            }

            .hero-stats {
                justify-content: center;
            }

            .hero-stat-card {
                min-width: 100px;
                padding: 12px 16px;
            }

            .hero-stat-value {
                font-size: 24px;
            }

            .hero-name {
                font-size: 42px;
            }

            .hero-logo {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <a href="usr_dashboard.php" class="logo"><img src="../../images/furn.webp" alt="Tumandok Crafts Industries"></a>

            <div class="nav-icons">
                <a class="nav-icon" href="usr_dashboard.php" style="cursor: pointer; text-decoration: none;" title="Dashboard">
                    <i class="fas fa-home"></i>
                </a>
                <a class="nav-icon" href="cart.php" style="cursor: pointer; text-decoration: none;">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-badge"><?php echo number_format($cartStats['total_items']); ?></span>
                </a>
                <div class="user-profile" id="userProfile" onclick="event.stopPropagation(); toggleUserDropdown()">
                    <div class="user-avatar">
                        <?php if ($profilePicture): ?>
                            <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <?php echo $userInitials; ?>
                        <?php endif; ?>
                    </div>
                    <span style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-chevron-down user-profile-arrow"></i>
                    <div class="user-profile-dropdown">
                        <div class="user-dropdown-header">
                            <div class="user-dropdown-name"><?php echo htmlspecialchars($userName); ?></div>
                            <div class="user-dropdown-role"><?php echo htmlspecialchars($userRole); ?></div>
                        </div>
                        <ul class="user-dropdown-menu">
                            <li class="user-dropdown-item">
                                <a href="profile.php" class="user-dropdown-link">
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
    <section class="hero-section" id="heroSection">
        <!-- Background Video -->
        <div class="hero-bg-video">
            <video autoplay muted loop playsinline>
                <source src="video/video.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
                </div>
        
        <!-- Hero Overlay - Above background video -->
        <div class="hero-overlay"></div>
        
        <div class="hero-container">
            <div class="hero-image">
                <div class="profile-hero-avatar" onclick="openEditModal()" title="Click to upload profile picture">
                    <?php if ($profilePicture): ?>
                        <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <div class="initials"><?php echo htmlspecialchars($userInitials); ?></div>
                    <?php endif; ?>
                    <div class="avatar-upload-overlay">
                        <div class="avatar-upload-overlay-content">
                            <i class="fas fa-camera"></i>
                            <span>Upload Photo</span>
                        </div>
                    </div>
                </div>
                <div class="hero-badge">
                    <i class="fas fa-star"></i>
                    <span><?php echo htmlspecialchars($userRole); ?> Member</span>
            </div>
        </div>
            <div class="hero-content">
                <div class="hero-content-main">
                    <div class="hero-logo" id="typed-logo"></div>
                    <h1 class="hero-name"><?php echo htmlspecialchars($userName); ?></h1>
                    <p class="hero-description"><?php echo htmlspecialchars($userDescription); ?></p>
                </div>
                
                <!-- Hero Stats - Vertical -->
                <div class="hero-content-stats">
                    <div class="hero-stat-card">
                        <div class="hero-stat-value"><?php echo number_format($cartStats['total_items']); ?></div>
                        <div class="hero-stat-label">Cart Items</div>
                    </div>
                    <div class="hero-stat-card">
                        <div class="hero-stat-value">₱<?php echo number_format($cartStats['total_value'], 0); ?></div>
                        <div class="hero-stat-label">Cart Value</div>
                    </div>
                    <?php if ($userData && isset($userData['created_at'])): 
                        $joinDate = new DateTime($userData['created_at']);
                        $now = new DateTime();
                        $daysSince = $now->diff($joinDate)->days;
                    ?>
                    <div class="hero-stat-card">
                        <div class="hero-stat-value"><?php echo $daysSince; ?></div>
                        <div class="hero-stat-label">Days Member</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Content Section -->
    <section class="content-section">
        <!-- Left Column: Recent Products -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-images"></i>
                    Recent Products
                </h2>
                <a href="usr_dashboard.php" class="card-link">
                    View All <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <div class="products-grid">
                <?php if (!empty($recentProducts)): ?>
                    <?php foreach (array_slice($recentProducts, 0, 2) as $product): ?>
                    <div class="product-card" onclick="window.location.href='usr_dashboard.php'">
                        <img src="../../admin/inventory/<?php echo htmlspecialchars($product['picture']); ?>" 
                             alt="<?php echo htmlspecialchars($product['item_name']); ?>"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27200%27 height=%27200%27%3E%3Crect fill=%27%231ABB9C%27 width=%27200%27 height=%27200%27/%3E%3Ctext fill=%27%23fff%27 font-family=%27sans-serif%27 font-size=%2714%27 dy=%2710.5%27 font-weight=%27bold%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3E<?php echo htmlspecialchars($product['item_name']); ?>%3C/text%3E%3C/svg%3E';">
                        <div class="product-card-overlay">
                            <div class="product-card-name"><?php echo htmlspecialchars($product['item_name']); ?></div>
                            <div class="product-card-credit">₱<?php echo number_format($product['total_cost'], 2); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="product-card" style="background: linear-gradient(135deg, #1ABB9C 0%, #117a65 100%); display: flex; align-items: center; justify-content: center; color: white;">
                        <div style="text-align: center; padding: 20px;">
                            <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 10px; opacity: 0.7;"></i>
                            <div>No products yet</div>
                        </div>
                    </div>
                    <div class="product-card" style="background: linear-gradient(135deg, #1ABB9C 0%, #117a65 100%); display: flex; align-items: center; justify-content: center; color: white;">
                        <div style="text-align: center; padding: 20px;">
                            <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 10px; opacity: 0.7;"></i>
                            <div>No products yet</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <button class="btn-view-more" onclick="window.location.href='usr_dashboard.php'">
                View More
            </button>
        </div>

        <!-- Right Column: Quick Actions -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h2>
            </div>
            <div class="quick-actions-list">
                <div class="action-tag active" onclick="window.location.href='cart.php'">
                    <div class="action-tag-left">
                        <i class="fas fa-shopping-cart action-tag-icon"></i>
                        <span class="action-tag-text">View Cart (<?php echo number_format($cartStats['total_items']); ?> items)</span>
                    </div>
                </div>
                <div class="action-tag" onclick="window.location.href='usr_dashboard.php'">
                    <div class="action-tag-left">
                        <i class="fas fa-home action-tag-icon"></i>
                        <span class="action-tag-text">Browse Products</span>
                    </div>
                </div>
                <div class="action-tag" onclick="openEditModal()">
                    <div class="action-tag-left">
                        <i class="fas fa-user-edit action-tag-icon"></i>
                        <span class="action-tag-text">Edit Profile</span>
                    </div>
                </div>
                <div class="action-tag" onclick="window.location.href='profile.php'">
                    <div class="action-tag-left">
                        <i class="fas fa-info-circle action-tag-icon"></i>
                        <span class="action-tag-text">Account Information</span>
                    </div>
                </div>
                <div class="action-tag" onclick="window.location.href='../../production/includes/logout.php'">
                    <div class="action-tag-left">
                        <i class="fas fa-sign-out-alt action-tag-icon"></i>
                        <span class="action-tag-text">Logout</span>
                    </div>
                </div>
            </div>

            <div class="info-cards">
                <div class="info-card">
                    <div class="info-card-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-title">Cart Status</div>
                        <div class="info-card-text">
                            You have <?php echo number_format($cartStats['total_items']); ?> items in your cart with a total value of ₱<?php echo number_format($cartStats['total_value'], 2); ?>.
                        </div>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-card-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-title">Account Type</div>
                        <div class="info-card-text">
                            You are registered as a <?php echo htmlspecialchars($userRole); ?> member with full access to browse and purchase products.
                        </div>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-card-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-title">Member Since</div>
                        <div class="info-card-text">
                            <?php if ($userData && isset($userData['created_at'])): ?>
                                You joined on <?php echo date('F d, Y', strtotime($userData['created_at'])); ?>.
                            <?php else: ?>
                                Welcome to Tumandok Crafts Industries!
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <button class="modal-close" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm" enctype="multipart/form-data">
                    <input type="hidden" id="existing_picture" name="existing_picture" value="<?php echo htmlspecialchars($profilePicture ?? ''); ?>">
                    <input type="hidden" id="delete_picture" name="delete_picture" value="0">
                    
                    <div class="form-group">
                        <label class="form-label">Profile Picture</label>
                        <div class="profile-picture-preview" id="picturePreview">
                            <?php if ($profilePicture): ?>
                                <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile" id="previewImg">
                            <?php else: ?>
                                <span id="previewInitials"><?php echo htmlspecialchars($userInitials); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="file-upload-wrapper">
                            <label for="profile_picture" class="file-upload-label">
                                <i class="fas fa-upload"></i> Choose Image
                            </label>
                            <input type="file" class="file-upload-input" id="profile_picture" name="picture" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="handleImagePreview(this)">
                            <div class="file-upload-info">
                                <i class="fas fa-info-circle"></i>
                                Max size: 5MB | Formats: JPEG, PNG, GIF, WebP
                            </div>
                        </div>
                        <?php if ($profilePicture): ?>
                        <button type="button" class="btn btn-secondary" onclick="deletePicture()" style="width: 100%; margin-top: 10px;">
                            <i class="fas fa-trash"></i> Remove Picture
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="edit_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" value="<?php echo htmlspecialchars($userName); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userRole); ?>" disabled style="background-color: #e9ecef;">
                        <small style="color: #6c757d; font-size: 12px;">Role cannot be changed</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveProfile()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <script>
        // Ensure video plays on page load
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.querySelector('.hero-bg-video video');
            if (video) {
                video.play().catch(function(error) {
                    // Auto-play was prevented, handle it gracefully
                    console.log('Video autoplay prevented:', error);
                });
            }

            // Initialize Typed.js for TUMANDOK CRAFTS INDUSTRIES
            if (typeof Typed !== 'undefined') {
                new Typed('#typed-logo', {
                    strings: ['TUMANDOK CRAFTS INDUSTRIES'],
                    typeSpeed: 100,
                    backSpeed: 50,
                    backDelay: 2000,
                    startDelay: 500,
                    loop: false,
                    showCursor: false,
                    cursorChar: '_',
                    autoInsertCss: true,
                    fadeOut: false,
                    fadeOutClass: 'typed-fade-out',
                    fadeOutDelay: 0
                });
            }
        });

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

        function openEditModal() {
            document.getElementById('editModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function handleImagePreview(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                // Validate file size
                if (file.size > maxSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Image size exceeds 5MB limit. Please choose a smaller image.',
                        confirmButtonColor: '#1ABB9C'
                    });
                    input.value = '';
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid File Type',
                        text: 'Please choose a JPEG, PNG, GIF, or WebP image.',
                        confirmButtonColor: '#1ABB9C'
                    });
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('picturePreview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" id="previewImg" style="width: 100%; height: 100%; object-fit: cover;">`;
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Image Selected',
                        text: 'Click "Save Changes" to upload your profile picture.',
                        confirmButtonColor: '#1ABB9C',
                        timer: 2000,
                        timerProgressBar: true
                    });
                };
                reader.readAsDataURL(file);
            }
        }

        function deletePicture() {
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to remove your profile picture?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete_picture').value = '1';
                    const preview = document.getElementById('picturePreview');
                    const initials = '<?php echo htmlspecialchars($userInitials); ?>';
                    preview.innerHTML = `<span id="previewInitials">${initials}</span>`;
                    document.getElementById('profile_picture').value = '';
                }
            });
        }

        function saveProfile() {
            const formData = new FormData(document.getElementById('editProfileForm'));
            
            Swal.fire({
                title: 'Saving...',
                html: 'Please wait while we update your profile.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#1ABB9C',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: data.message,
                        confirmButtonColor: '#1ABB9C'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred: ' + error,
                    confirmButtonColor: '#1ABB9C'
                });
            });
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
