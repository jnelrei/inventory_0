<?php
session_start();
$login_errors = isset($_SESSION['login_errors']) ? $_SESSION['login_errors'] : [];
$signup_errors = isset($_SESSION['signup_errors']) ? $_SESSION['signup_errors'] : [];
$signup_success = isset($_SESSION['signup_success']) ? $_SESSION['signup_success'] : '';
$show_login_form = isset($_SESSION['show_login_form']) ? $_SESSION['show_login_form'] : false;

// Combine all errors for top banner display
$all_errors = array_merge($login_errors, $signup_errors);

// Clear session messages after retrieving them
unset($_SESSION['login_errors']);
unset($_SESSION['signup_errors']);
unset($_SESSION['signup_success']);
unset($_SESSION['show_login_form']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tumandok Crafts Industries</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="images/logoooo.png" rel="icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #2d5016 0%, #3d6b2a 30%, #4a7c35 60%, #5a8f42 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            overflow-y: auto;
            position: relative;
            -webkit-text-size-adjust: 100%;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25% 40%, rgba(255, 255, 255, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 75% 70%, rgba(255, 248, 220, 0.1) 0%, transparent 50%),
                repeating-linear-gradient(
                    45deg,
                    transparent,
                    transparent 2px,
                    rgba(255, 255, 255, 0.02) 2px,
                    rgba(255, 255, 255, 0.02) 4px
                );
            animation: pulse 10s ease-in-out infinite;
            z-index: 0;
        }

        .container {
            display: flex;
            width: 100%;
            min-height: 100vh;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        /* Left Side - Login Section */
        .login-section {
            width: 42%;
            background: rgba(240, 240, 240, 0.98);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 15px 50px 30px 50px;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
            z-index: 10;
            overflow-y: auto;
            position: relative;
            min-height: 100vh;
        }

        .login-section.no-scroll {
            overflow-y: hidden;
        }

        .top-logo {
            position: absolute;
            top: 10px;
            left: 20px;
            z-index: 20;
            max-width: 50px;
            height: auto;
            animation: fadeInUp 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.3s both;
        }

        .top-logo img {
            width: 100%;
            height: auto;
            display: block;
        }

        @keyframes slideLeftToRight {
            from {
                transform: translateX(-100%);
                opacity: 0;
                background: rgba(240, 240, 240, 0);
            }
            to {
                transform: translateX(0);
                opacity: 1;
                background: rgba(240, 240, 240, 0.98);
            }
        }

        .login-content {
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.2s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: left;
            margin-bottom: 20px;
            width: 100%;
        }

        .logo p {
            margin-bottom: 0;
        }

        .login-content.form-active .logo {
            margin-bottom: 0;
        }

        .logo img {
            max-width: 350px;
            height: auto;
            margin-bottom: 8px;
            display: block;
        }

        .login-content.form-active .logo {
            text-align: center;
        }

        .login-content.form-active .logo img {
            max-width: 200px;
            margin-bottom: 4px;
            margin-left: auto;
            margin-right: auto;
        }

        .login-content.form-active .logo p {
            font-size: 0.85rem;
            margin-bottom: 0;
            text-align: center;
        }

        .logo h1 {
            font-size: 2.2rem;
            color: #1a1a1a;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .logo p {
            color: #6b7280;
            font-size: 0.95rem;
            font-weight: 400;
        }

        .login-form {
            width: 100%;
            display: none;
            opacity: 0;
            transform: translateY(20px) scale(0.98);
            transition: opacity 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                        transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            margin-top: 0;
        }

        .login-form.show {
            display: block;
            opacity: 1;
            transform: translateY(0) scale(1);
            margin-top: 0;
            padding-top: 0;
            position: relative;
        }

        .close-form-btn {
            position: absolute;
            top: -10px;
            right:-7px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: transparent;
            color: #6b7280;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 600;
            transition: all 0.2s ease;
            z-index: 10;
            line-height: 1;
            padding: 0;
        }

        .close-form-btn:hover {
            color: #374151;
            transform: scale(1.1);
        }

        .close-form-btn:active {
            transform: scale(0.95);
        }

        .login-form.show .close-form-btn {
            animation: fadeInSlideUp 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.1s both;
        }

        .login-form.show .form-group {
            animation: fadeInSlideUp 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
        }

        .login-form.show .form-group:nth-of-type(1) {
            animation-delay: 0.1s;
        }

        .login-form.show .form-group:nth-of-type(2) {
            animation-delay: 0.2s;
        }

        .login-form.show .remember-forgot {
            animation: fadeInSlideUp 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.3s both;
        }

        .login-form.show .login-btn {
            animation: fadeInSlideUp 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.4s both;
        }

        .login-form.show .signup-link {
            animation: fadeInSlideUp 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.5s both;
        }

        @keyframes fadeInSlideUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-button-container {
            width: 100%;
            text-align: center;
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                        transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            margin-bottom: 0;
        }

        .show-login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4a7c35 0%, #3d6b2a 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(74, 124, 53, 0.2);
        }

        .show-login-btn:hover {
            background: linear-gradient(135deg, #5a8f42 0%, #4a7c35 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 124, 53, 0.3);
        }

        .login-button-container.hide {
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
            pointer-events: none;
            transition: opacity 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                        transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group:first-child {
            margin-top: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: #ffffff;
            color: #1f2937;
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4a7c35;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(74, 124, 53, 0.1);
        }

        .form-group input.error {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .form-group input.error:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            font-size: 0.875rem;
        }

        .remember-forgot label {
            display: flex;
            align-items: center;
            color: #6b7280;
            cursor: pointer;
            font-weight: 400;
        }

        .remember-forgot input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            cursor: pointer;
            accent-color: #4a7c35;
        }

        .remember-forgot a {
            color: #4a7c35;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .remember-forgot a:hover {
            color: #3d6b2a;
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4a7c35 0%, #3d6b2a 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(74, 124, 53, 0.2);
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #5a8f42 0%, #4a7c35 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 124, 53, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .signup-link {
            text-align: center;
            margin-top: 32px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .signup-link a {
            color: #4a7c35;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
            cursor: pointer;
        }

        .signup-link a:hover {
            color: #3d6b2a;
            text-decoration: underline;
        }

        .signup-form {
            width: 100%;
            display: none;
            opacity: 0;
            transform: translateY(20px) scale(0.98);
            transition: opacity 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                        transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            margin-top: 0;
            position: relative;
        }

        .signup-form.show {
            display: block;
            opacity: 1;
            transform: translateY(0) scale(1);
            margin-top: 0;
            padding-top: 0;
            position: relative;
        }

        .signup-form.show .close-form-btn {
            animation: fadeInSlideUp 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.1s both;
        }

        .signup-form.show .form-group {
            animation: fadeInSlideUp 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
        }

        .signup-form.show .form-group:nth-of-type(1) {
            animation-delay: 0.1s;
        }

        .signup-form.show .form-group:nth-of-type(2) {
            animation-delay: 0.15s;
        }

        .signup-form.show .form-group:nth-of-type(3) {
            animation-delay: 0.2s;
        }

        .signup-form.show .form-group:nth-of-type(4) {
            animation-delay: 0.25s;
        }

        .signup-form.show .form-group:nth-of-type(5) {
            animation-delay: 0.3s;
        }

        .signup-form.show .signup-btn {
            animation: fadeInSlideUp 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.35s both;
        }

        .signup-form.show .login-link {
            animation: fadeInSlideUp 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.4s both;
        }

        .signup-form .form-group {
            margin-bottom: 12px;
        }

        .signup-form .form-group label {
            margin-bottom: 5px;
        }

        .signup-form .form-group input,
        .signup-form .form-group select {
            padding: 12px 16px;
        }

        .signup-form .signup-btn {
            margin-top: 4px;
            padding: 14px;
        }

        .signup-form .login-link {
            margin-top: 14px;
            font-size: 0.85rem;
        }

        .password-strength {
            margin-top: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .password-strength.weak {
            color: #ef4444;
        }

        .password-strength.fair {
            color: #f59e0b;
        }

        .password-strength.good {
            color: #3b82f6;
        }

        .password-strength.strong {
            color: #10b981;
        }

        .password-requirements {
            margin-top: 8px;
            font-size: 0.75rem;
            list-style: none;
            padding: 0;
        }

        .password-requirements li {
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .password-requirements li::before {
            content: '';
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .password-requirements li.valid {
            color: #10b981;
        }

        .password-requirements li.valid::before {
            content: '✓';
            background: #10b981;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: bold;
        }

        .password-requirements li.invalid {
            color: #ef4444;
        }

        .password-requirements li.invalid::before {
            content: '✗';
            background: #ef4444;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: bold;
        }

        .password-match {
            margin-top: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .password-match.match {
            color: #10b981;
        }

        .password-match.no-match {
            color: #ef4444;
        }

        .password-input-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 0.85rem;
            padding: 4px 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            z-index: 5;
        }

        .password-toggle:hover {
            color: #4a7c35;
        }

        .password-toggle:focus {
            outline: none;
        }

        .form-group input[type="password"],
        .form-group input[type="text"] {
            padding-right: 45px;
        }


        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: #ffffff;
            color: #1f2937;
            cursor: pointer;
        }

        .form-group select:focus {
            outline: none;
            border-color: #4a7c35;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(74, 124, 53, 0.1);
        }

        .signup-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4a7c35 0%, #3d6b2a 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(74, 124, 53, 0.2);
        }

        .signup-btn:hover {
            background: linear-gradient(135deg, #5a8f42 0%, #4a7c35 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 124, 53, 0.3);
        }

        .signup-btn:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 32px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .login-link a {
            color: #4a7c35;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
            cursor: pointer;
        }

        .login-link a:hover {
            color: #3d6b2a;
            text-decoration: underline;
        }

        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            text-align: center;
            animation: fadeInSlideUp 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
        }

        .error-message ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .error-message li {
            margin-bottom: 5px;
        }

        .error-message li:last-child {
            margin-bottom: 0;
        }

        .success-message {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            text-align: center;
            animation: fadeInSlideUp 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
        }

        .top-success-banner {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #4a7c35 0%, #3d6b2a 100%);
            color: white;
            padding: 14px 30px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            text-align: center;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            animation: slideDown 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
            max-width: 500px;
            width: auto;
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.5;
        }

        .top-error-banner {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 14px 30px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            text-align: center;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            animation: slideDown 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
            max-width: 500px;
            width: auto;
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.5;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        /* Right Side - Design Section */
        .design-section {
            width: 58%;
            background:rgba(255, 255, 255, 0.98);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.8;
            }
        }

        .design-content {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-top-left-radius: 50px;
            border-bottom-left-radius: 50px;
        }

        .design-content video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            position: absolute;
            top: 0;
            left: 0;
            border-top-left-radius: 50px;
            border-bottom-left-radius: 50px;
        }

        @media (max-width: 1024px) {
            .design-content video {
                border-radius: 0;
            }
        }

        .design-content video::-webkit-media-controls {
            display: none !important;
        }

        .design-content video::-webkit-media-controls-enclosure {
            display: none !important;
        }

        .design-content video::-webkit-media-controls-panel {
            display: none !important;
        }

        .design-content video::-webkit-media-controls-play-button {
            display: none !important;
        }

        .design-content video::-webkit-media-controls-start-playback-button {
            display: none !important;
        }

        .design-content video::-moz-media-controls {
            display: none !important;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.8));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2;
            padding: 60px;
            text-align: center;
        }

        @keyframes slideRightToLeft {
            from {
                transform: translateX(100%);
                opacity: 0;
                background: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0));
            }
            to {
                transform: translateX(0);
                opacity: 1;
                background: linear-gradient(to bottom, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.8));
            }
        }

        .hero-overlay h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.1;
            letter-spacing: -1px;
            color: #ffffff;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
        }

        .hero-overlay p {
            font-size: 1rem;
            margin-bottom: 32px;
            line-height: 1.7;
            color: #ffffff;
            font-weight: 400;
            max-width: 700px;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);
        }

        .furniture-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 40px;
            max-width: 600px;
            width: 100%;
        }

        .furniture-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 28px 24px;
            text-align: left;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .furniture-card:hover {
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
        }

        .furniture-icon {
            font-size: 2rem;
            margin-bottom: 12px;
            display: block;
        }

        .furniture-card h3 {
            font-size: 0.95rem;
            margin-bottom: 6px;
            color: #ffffff;
            font-weight: 600;
        }

        .furniture-card p {
            font-size: 0.8rem;
            color: #94a3b8;
            margin: 0;
            font-weight: 400;
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px;
            background: #ffffff;
            color: #2d5016;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
            background: #f8f9fa;
        }

        .cta-button::after {
            content: '→';
            font-size: 1.2rem;
            transition: transform 0.3s;
        }

        .cta-button:hover::after {
            transform: translateX(4px);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
            }

            .login-section {
                width: 100%;
                height: 100vh;
                min-height: 100vh;
                padding: 0;
                justify-content: center;
                align-items: center;
            }

            .login-content {
                width: 100%;
                max-width: 100%;
                margin: -25px auto 0 auto;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 5px 20px 20px 20px;
            }

            .logo {
                text-align: center;
                margin-left: auto;
                margin-right: auto;
                width: 100%;
            }

            .logo img {
                margin-left: auto;
                margin-right: auto;
                display: block;
            }

            .login-button-container,
            .login-form,
            .signup-form {
                width: 100%;
                max-width: 100%;
                margin: 0 auto;
            }

            .design-section {
                display: none;
            }

            .design-content {
                text-align: center;
                border-radius: 0;
            }

            .design-content video {
                border-radius: 0;
            }

            .hero-overlay {
                padding: 40px 30px;
                border-radius: 0;
            }

            .hero-overlay h2 {
                font-size: 2rem;
            }

            .hero-overlay p {
                font-size: 0.9rem;
            }

            .furniture-grid {
                grid-template-columns: repeat(2, 1fr);
                max-width: 600px;
                margin: 30px auto 20px;
                gap: 12px;
            }

            .spacer-section:first-of-type {
                display: none;
            }

            .products-section {
                padding: 0 40px 30px 40px;
                margin-top: 0;
            }

            .products-section h2 {
                margin-top: 0;
            }

            .products-section .subtitle {
                font-size: 0.95rem;
                margin-bottom: 30px;
                padding: 0 20px;
                line-height: 1.75;
            }

            .carousel-wrapper {
                padding: 0 20px;
            }

            .carousel-item {
                flex: 0 0 calc(100% - 20px);
                min-width: calc(100% - 20px);
                max-width: calc(100% - 20px);
            }

            .product-card {
                padding: 15px;
            }

            .product-card h3 {
                font-size: 1rem;
            }

            .product-image {
                margin-bottom: 10px;
            }

            .footer {
                padding: 40px 40px 20px 40px;
            }

            .footer-section.contacts-section {
                padding-left: 0;
            }

            .footer-section.follow-us-section {
                padding-left: 0;
            }

            .top-success-banner,
            .top-error-banner {
                max-width: 95%;
                font-size: 0.95rem;
                padding: 14px 24px;
                white-space: nowrap;
                overflow: visible;
                line-height: 1.4;
            }
        }

        @media (max-width: 768px) {
            .login-section {
                width: 100%;
                height: 100vh;
                min-height: 100vh;
                padding: 0;
                justify-content: center;
                align-items: center;
            }

            .login-content {
                width: 100%;
                max-width: 100%;
                margin: -30px auto 0 auto;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 3px 20px 20px 20px;
            }

            .logo {
                text-align: center;
                margin-left: auto;
                margin-right: auto;
            }

            .logo img {
                margin: 0 auto 8px;
                display: block;
            }

            .logo h1 {
                text-align: center;
            }

            .logo p {
                text-align: center;
            }

            .login-button-container,
            .login-form,
            .signup-form {
                width: 100%;
                max-width: 100%;
                margin: 0 auto;
            }

            .top-logo {
                top: 8px;
                left: 15px;
                max-width: 45px;
            }

            .design-section {
                display: none;
            }

            .logo img {
                max-width: 350px;
            }

            .logo h1 {
                font-size: 1.8rem;
            }

            .logo p {
                font-size: 0.85rem;
            }

            .login-content.form-active .logo img {
                max-width: 220px;
                margin-left: auto;
                margin-right: auto;
            }

            .design-content h2 {
                font-size: 2rem;
            }

            .design-content p {
                font-size: 1rem;
            }

            .hero-overlay {
                padding: 30px 20px;
            }

            .hero-overlay h2 {
                font-size: 1.75rem;
                margin-bottom: 15px;
            }

            .hero-overlay p {
                font-size: 0.85rem;
                margin-bottom: 25px;
            }

            .furniture-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-top: 25px;
            }

            .furniture-card {
                padding: 20px;
            }

            .furniture-icon {
                font-size: 1.75rem;
            }

            .furniture-card h3 {
                font-size: 0.9rem;
            }

            .furniture-card p {
                font-size: 0.75rem;
            }

            .spacer-section:first-of-type {
                display: none;
            }

            .products-section {
                padding: 0 15px 30px 15px;
                margin-top: 0;
            }

            .products-section h2 {
                font-size: 1.75rem;
                margin-bottom: 25px;
                margin-top: 0;
                padding-top: 0;
            }

            .products-section .subtitle {
                font-size: 0.9rem;
                margin-bottom: 25px;
                padding: 0 10px;
                line-height: 1.7;
            }

            .carousel-wrapper {
                padding: 0 10px;
                margin-bottom: 20px;
            }

            .carousel-container {
                gap: 15px;
            }

            .carousel-item {
                flex: 0 0 calc(100% - 15px);
                min-width: calc(100% - 15px);
                max-width: calc(100% - 15px);
                padding: 15px;
            }

            .product-card {
                padding: 15px;
                width: 100%;
            }

            .product-card h3 {
                font-size: 0.95rem;
            }

            .product-image {
                margin-bottom: 10px;
            }

            .footer {
                padding: 30px 30px 20px 30px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .footer-section.contacts-section {
                padding-left: 0;
            }

            .footer-section.follow-us-section {
                padding-left: 0;
            }

            .top-success-banner,
            .top-error-banner {
                top: 10px;
                max-width: 98%;
                font-size: 1rem;
                padding: 12px 20px;
                white-space: nowrap;
                overflow: visible;
                line-height: 1.4;
            }

            .scroll-to-top {
                width: 45px;
                height: 45px;
                bottom: 20px;
                right: 20px;
                font-size: 1.1rem;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group input,
            .form-group select {
                padding: 11px 14px;
                font-size: 0.9rem;
            }

            .login-btn,
            .signup-btn,
            .show-login-btn {
                padding: 12px;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 480px) {
            .login-section {
                width: 100%;
                height: 100vh;
                min-height: 100vh;
                padding: 0;
                justify-content: center;
                align-items: center;
            }

            .login-content {
                width: 100%;
                max-width: 100%;
                margin: -35px auto 0 auto;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 0 15px 15px 15px;
            }

            .logo {
                text-align: center;
                margin-left: auto;
                margin-right: auto;
            }

            .logo img {
                max-width: 320px;
                margin: 0 auto 8px;
                display: block;
            }

            .logo h1 {
                font-size: 1.5rem;
                text-align: center;
            }

            .logo p {
                font-size: 0.8rem;
                text-align: center;
            }

            .login-button-container,
            .login-form,
            .signup-form {
                width: 100%;
                max-width: 100%;
                margin: 0 auto;
            }

            .login-content.form-active .logo img {
                max-width: 200px;
                margin-left: auto;
                margin-right: auto;
            }

            .top-logo {
                top: 5px;
                left: 10px;
                max-width: 40px;
            }

            .design-section {
                display: none;
            }

            .login-content.form-active .logo p {
                font-size: 0.75rem;
            }

            .design-content h2 {
                font-size: 1.5rem;
            }

            .hero-overlay {
                padding: 25px 15px;
            }

            .hero-overlay h2 {
                font-size: 1.5rem;
                margin-bottom: 12px;
            }

            .hero-overlay p {
                font-size: 0.8rem;
                margin-bottom: 20px;
            }

            .furniture-grid {
                margin-top: 20px;
                gap: 10px;
            }

            .furniture-card {
                padding: 16px;
            }

            .furniture-icon {
                font-size: 1.5rem;
                margin-bottom: 8px;
            }

            .furniture-card h3 {
                font-size: 0.85rem;
                margin-bottom: 4px;
            }

            .furniture-card p {
                font-size: 0.7rem;
            }

            .cta-button {
                padding: 12px 24px;
                font-size: 0.9rem;
            }

            .spacer-section:first-of-type {
                display: none;
            }

            .products-section {
                padding: 0 10px 25px 10px;
                margin-top: 0;
            }

            .products-section h2 {
                font-size: 1.5rem;
                margin-bottom: 20px;
                margin-top: 0;
                padding-top: 0;
            }

            .products-section .subtitle {
                font-size: 0.85rem;
                margin-bottom: 20px;
                padding: 0 5px;
                line-height: 1.6;
            }

            .carousel-wrapper {
                padding: 0 5px;
                margin-bottom: 15px;
            }

            .carousel-container {
                gap: 10px;
            }

            .carousel-item {
                flex: 0 0 calc(100% - 10px);
                min-width: calc(100% - 10px);
                max-width: calc(100% - 10px);
                padding: 10px;
            }

            .product-card {
                padding: 12px;
                width: 100%;
            }

            .product-card h3 {
                font-size: 0.85rem;
            }

            .product-image {
                margin-bottom: 8px;
            }

            .footer {
                padding: 25px 20px 15px 20px;
            }

            .footer-section h3 {
                font-size: 1rem;
                margin-bottom: 12px;
            }

            .footer-section p,
            .footer-section a {
                font-size: 0.85rem;
            }

            .social-links {
                gap: 12px;
            }

            .social-links a {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }

            .top-success-banner,
            .top-error-banner {
                top: 5px;
                max-width: 98%;
                font-size: 0.9rem;
                padding: 10px 16px;
                border-radius: 8px;
                white-space: nowrap;
                overflow: visible;
                line-height: 1.4;
            }

            .scroll-to-top {
                width: 40px;
                height: 40px;
                bottom: 15px;
                right: 15px;
                font-size: 1rem;
            }

            .form-group {
                margin-bottom: 18px;
            }

            .form-group label {
                font-size: 0.8rem;
                margin-bottom: 6px;
            }

            .form-group input,
            .form-group select {
                padding: 10px 12px;
                font-size: 0.85rem;
            }

            .login-btn,
            .signup-btn,
            .show-login-btn {
                padding: 11px;
                font-size: 0.9rem;
            }

            .remember-forgot {
                font-size: 0.8rem;
                margin-bottom: 22px;
            }

            .signup-link,
            .login-link {
                font-size: 0.85rem;
                margin-top: 24px;
            }

            .password-strength,
            .password-match {
                font-size: 0.75rem;
            }

            .password-requirements {
                font-size: 0.7rem;
            }

            .close-form-btn {
                width: 28px;
                height: 28px;
                font-size: 20px;
                top: -8px;
                right: -5px;
            }

            .spacer-section {
                padding: 20px 0;
            }
        }

        @media (max-width: 360px) {
            .login-section {
                width: 100%;
                height: 100vh;
                min-height: 100vh;
                padding: 0;
                justify-content: center;
                align-items: center;
            }

            .login-content {
                margin-top: -35px;
                padding: 0 15px 15px 15px;
            }

            .design-section {
                display: none;
            }

            .logo img {
                max-width: 280px;
            }

            .logo h1 {
                font-size: 1.3rem;
            }

            .hero-overlay h2 {
                font-size: 1.3rem;
            }

            .products-section {
                padding: 0 8px 20px 8px;
            }

            .products-section h2 {
                font-size: 1.3rem;
                margin-bottom: 15px;
            }

            .carousel-wrapper {
                padding: 0 5px;
                margin-bottom: 15px;
            }

            .carousel-item {
                flex: 0 0 calc(100% - 10px);
                min-width: calc(100% - 10px);
                max-width: calc(100% - 10px);
                padding: 8px;
            }

            .product-card {
                padding: 10px;
            }

            .product-card h3 {
                font-size: 0.75rem;
            }

            .product-image {
                margin-bottom: 6px;
            }
        }

        /* Landscape orientation for mobile */
        @media (max-width: 768px) and (orientation: landscape) {
            .login-section {
                width: 100%;
                height: 100vh;
                min-height: 100vh;
                padding: 0;
                justify-content: center;
                align-items: center;
            }

            .login-content {
                margin-top: -30px;
                padding: 0 15px 15px 15px;
            }

            .design-section {
                display: none;
            }

            .hero-overlay {
                padding: 20px 30px;
            }

            .hero-overlay h2 {
                font-size: 1.5rem;
                margin-bottom: 10px;
            }

            .hero-overlay p {
                font-size: 0.8rem;
                margin-bottom: 15px;
            }

            .furniture-grid {
                grid-template-columns: repeat(2, 1fr);
                margin-top: 15px;
                gap: 10px;
            }
        }

        /* Ensure minimum touch target sizes for mobile */
        @media (max-width: 768px) {
            button,
            .login-btn,
            .signup-btn,
            .show-login-btn,
            .close-form-btn,
            .password-toggle,
            .cta-button {
                min-height: 44px;
                min-width: 44px;
            }

            .remember-forgot input[type="checkbox"] {
                min-width: 20px;
                min-height: 20px;
            }

            .remember-forgot a,
            .signup-link a,
            .login-link a,
            .footer-section a,
            .social-links a {
                min-height: 44px;
                display: inline-flex;
                align-items: center;
            }
        }

        /* Spacer Section */
        .spacer-section {
            width: 100%;
            background: #ECEDEC;
            padding: 30px 0;
            z-index: 10;
            position: relative;
        }

        /* Products Carousel Section */
        .products-section {
            width: 100%;
            background: #ECEDEC;
            padding: 60px 50px 30px 50px;
            z-index: 10;
            position: relative;
        }

        .products-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .products-section h2 {
            font-size: 2rem;
            color: #1a1a1a;
            font-weight: 700;
            margin-bottom: 40px;
            text-align: center;
        }

        .products-section .subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 1rem;
            margin-bottom: 40px;
            line-height: 1.8;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .carousel-wrapper {
            position: relative;
            overflow: hidden;
            margin-bottom: 30px;
            padding: 0 50px 0 150px;
        }

        @media (max-width: 1024px) {
            .carousel-wrapper {
                padding: 0 20px;
            }
        }

        .carousel-container {
            display: flex;
            gap: 10px;
            transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: transform;
        }

        .carousel-item {
            flex: 0 0 calc(53% - 10px);
            min-width: 0;
            padding: 20px;
            box-sizing: border-box;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .product-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }


        .product-image {
            width: 100%;
            flex: 1;
            min-height: 0;
            border-radius: 12px;
            margin-bottom: 12px;
            object-fit: contain;
            object-position: center;
            display: block;
            background: #f8f9fa;
        }

        .product-card h3 {
            font-size: 1.4rem;
            color: #1a1a1a;
            font-weight: 700;
            margin: 0;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-shrink: 0;
            max-height: 2.6em;
        }


        /* Footer */
        .footer {
            width: 100%;
            background: #1a1a1a;
            color: #ffffff;
            padding: 40px 50px 20px 50px;
            z-index: 10;
            position: relative;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 60px;
            margin-bottom: 30px;
        }

        .footer-section {
            opacity: 0;
            transform: translateY(-30px);
            transition: opacity 1.2s ease, transform 1.2s ease;
        }

        .footer-section.animate-in {
            opacity: 1;
            transform: translateY(0);
        }

        .footer-section:nth-child(1) {
            transition-delay: 0.1s;
        }

        .footer-section:nth-child(2) {
            transition-delay: 0.2s;
        }

        .footer-section:nth-child(3) {
            transition-delay: 0.3s;
        }

        .footer-section h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #ffffff;
        }

        .footer-section p,
        .footer-section a {
            color: #94a3b8;
            font-size: 0.9rem;
            text-decoration: none;
            line-height: 1.8;
            display: block;
            margin-bottom: 8px;
        }

        .footer-section a:hover {
            color: #ffffff;
        }

        .footer-section.contacts-section {
            padding-left: 80px;
        }

        .footer-section.follow-us-section {
            padding-left: 80px;
        }

        @media (max-width: 1024px) {
            .footer-section.contacts-section {
                padding-left: 0 !important;
            }

            .footer-section.follow-us-section {
                padding-left: 0 !important;
            }
        }

        .footer-section.contacts-section p {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            transition: color 0.3s ease;
        }

        .footer-section.contacts-section p:last-child {
            margin-bottom: 0;
        }

        .footer-section.contacts-section p i {
            color: #4a7c35;
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .footer-section.contacts-section p span {
            flex: 1;
        }

        .footer-section.contacts-section p:hover {
            color: #ffffff;
        }

        .footer-section.contacts-section p:hover i {
            color: #5a8f42;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: #4a7c35;
            transform: translateY(-2px);
        }

        /* Scroll to Top Button */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: #4a7c35;
            color: #ffffff;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(74, 124, 53, 0.4);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
        }

        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .scroll-to-top:hover {
            background: #3d6b2a;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(74, 124, 53, 0.5);
        }

        .scroll-to-top:active {
            transform: translateY(-1px);
        }

    </style>
</head>
<body>
    <div class="container">
        <!-- Left Side - Login Section -->
        <div class="login-section">
            <?php if (!empty($signup_success)): ?>
                <div class="top-success-banner">
                    <?php echo htmlspecialchars($signup_success); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($all_errors)): ?>
                <div class="top-error-banner">
                    <?php echo htmlspecialchars(implode(' • ', $all_errors)); ?>
                </div>
            <?php endif; ?>
            <div class="top-logo">
                <img src="images/logoooo.png" alt="Store Logo">
            </div>
            <div class="login-content">
                <div class="logo">
                    <img src="images/furn.webp" alt="Tumandok Crafts Industries">
                    <p>Handcrafted Furniture & Home Decor from Bago City, Negros Occidental</p>
                </div>

                <div class="login-button-container">
                    <button type="button" class="show-login-btn" onclick="showLoginForm()">Login</button>
                </div>

                <form class="login-form" method="POST" action="production/includes/login.php">
                    <button type="button" class="close-form-btn" onclick="hideLoginForm()" aria-label="Close login form">×</button>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="off" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="off" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')" aria-label="Show password">
                                <i class="fa fa-eye-slash toggle-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="remember-forgot">
                        <label>
                            <input type="checkbox" name="remember">
                            Remember me
                        </label>
                        <a href="#">Forgot password?</a>
                    </div>

                    <button type="submit" class="login-btn">Sign In</button>

                    <div class="signup-link">
                        Don't have an account? <a href="#" onclick="showSignupForm(); return false;">Sign up</a>
                    </div>
                </form>

                <form class="signup-form" method="POST" action="production/includes/signup.php">
                    <button type="button" class="close-form-btn" onclick="hideSignupForm()" aria-label="Close signup form">×</button>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label for="signup-username">Username</label>
                        <input type="text" id="signup-username" name="username" placeholder="Enter your username" required>
                    </div>

                    <div class="form-group">
                        <label for="signup-password">Password</label>
                        <input type="text" id="signup-password" name="password" placeholder="Enter your password" required>
                        <div id="password-strength" class="password-strength"></div>
                        <ul id="password-requirements" class="password-requirements"></ul>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="text" id="confirm-password" name="confirm_password" placeholder="Confirm your password" required>
                        <div id="password-match" class="password-match"></div>
                    </div>

                    <button type="submit" class="signup-btn">Sign Up</button>

                    <div class="login-link">
                        Already have an account? <a href="#" onclick="showLoginForm(); return false;">Sign in</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side - Design Section -->
        <div class="design-section">
            <div class="design-content">
                <video autoplay loop muted playsinline>
                    <source src="images/02.mp4" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <div class="hero-overlay">
                    <h2>Handcrafted with Passion</h2>
                    <p>We transform waste materials from raw natural resources into timeless and sophisticated handicrafts. Every piece is meticulously handmade by our artisans in Bago City, Negros Occidental, empowering local communities through creativity and skill development.</p>
                    
                    <div class="furniture-grid">
                        <div class="furniture-card">
                            <div class="furniture-icon">🏺</div>
                            <h3>Decorative Bowls</h3>
                            <p>Handcrafted from Hatchet Shell & Capiz</p>
                        </div>
                        <div class="furniture-card">
                            <div class="furniture-icon">🪑</div>
                            <h3>Furniture Pieces</h3>
                            <p>Unique handcrafted designs</p>
                        </div>
                        <div class="furniture-card">
                            <div class="furniture-icon">🎨</div>
                            <h3>Home Decor</h3>
                            <p>Artisanal decorative items</p>
                        </div>
                        <div class="furniture-card">
                            <div class="furniture-icon">✨</div>
                            <h3>Custom Orders</h3>
                            <p>Tailored to your preferences</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Spacer Section -->
    <section class="spacer-section"></section>

    <!-- Products Carousel Section -->
    <section class="products-section">
        <div class="products-container">
            <h2>Our Products</h2>
            <p class="subtitle">Tumandok Crafts Industries is a Filipino social enterprise based in Bago City, Negros Occidental. Tumandok, meaning "native inhabitant" in Ilonggo, recognizes the local rural community as our company's core strength.<br><br>We transform waste materials from raw natural resources into timeless and sophisticated handicrafts. Every piece is meticulously handmade by our artisans in an environment that fosters creativity and skill development, empowering them to turn great ideas into a reality.<br><br>At Tumandok, we take pride in honing the foundations of raw materials and pure talent to develop and refine both our products and workmanship.</p>
            
            <div class="carousel-wrapper">
                <div class="carousel-container" id="carouselContainer">
                    <div class="carousel-item">
                        <div class="product-card">
                            <img src="images/prod21.png" alt="Balita Vase Hatshell & Capiz Strips" class="product-image">
                            <h3>Balita Vase Hatshell & Capiz Strips</h3>
                        </div>
                    </div>
                    
                    <div class="carousel-item">
                        <div class="product-card">
                            <img src="images/prod31.png" alt="Uneven Bowl" class="product-image">
                            <h3>Uneven Bowl</h3>
                        </div>
                    </div>
                    
                    <div class="carousel-item">
                        <div class="product-card">
                            <img src="images/prod41.png" alt="Teamat" class="product-image">
                            <h3>Teamat</h3>
                        </div>
                    </div>
                    
                    <div class="carousel-item">
                        <div class="product-card">
                            <img src="images/prod51.png" alt="Hotpad" class="product-image">
                            <h3>Hotpad</h3>
                        </div>
                    </div>
                    
                    <div class="carousel-item">
                        <div class="product-card">
                            <img src="images/prod61.png" alt="Medina Vase" class="product-image">
                            <h3>Medina Vase</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Spacer Section Before Footer -->
    <section class="spacer-section"></section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Us</h3>
                    <p>Handcrafted furniture and home decor from Bago City, Negros Occidental. We transform waste materials into timeless and sophisticated handicrafts.</p>
                </div>
                
                <div class="footer-section contacts-section">
                    <h3>Contacts</h3>
                    <p><i class="fas fa-map-marker-alt"></i> <span>Maria Morena, Brgy. Calumangan, Bago City, Negros Occidental, Philippines 1601</span></p>
                    <p><i class="fas fa-phone"></i> <span><a href="tel:+639171689401">(+63) 917 168 9401</a></span></p>
                    <p><i class="fas fa-mobile-alt"></i> <span>Globe | Viber | Whatsapp</span></p>
                </div>
                
                <div class="footer-section follow-us-section">
                    <h3>Follow Us</h3>
                    <p>Stay connected with us on social media</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/tumandokcraftsindustries" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Tumandok Crafts Industries. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Auto-show forms if there are error/success messages
        document.addEventListener('DOMContentLoaded', function() {
            const showLogin = <?php echo $show_login_form ? 'true' : 'false'; ?>;
            const hasLoginErrors = <?php echo !empty($login_errors) ? 'true' : 'false'; ?>;
            const hasSignupErrors = <?php echo !empty($signup_errors) ? 'true' : 'false'; ?>;
            const successBanner = document.querySelector('.top-success-banner');
            const errorBanner = document.querySelector('.top-error-banner');
            
            // Hide success banner after 3 seconds
            if (successBanner) {
                setTimeout(function() {
                    successBanner.style.opacity = '0';
                    successBanner.style.transform = 'translateX(-50%) translateY(-100%)';
                    successBanner.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    setTimeout(function() {
                        successBanner.remove();
                    }, 500);
                }, 3000);
            }
            
            // Hide error banner after 3 seconds
            if (errorBanner) {
                setTimeout(function() {
                    errorBanner.style.opacity = '0';
                    errorBanner.style.transform = 'translateX(-50%) translateY(-100%)';
                    errorBanner.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    setTimeout(function() {
                        errorBanner.remove();
                    }, 500);
                }, 3000);
            }
            
            if (hasLoginErrors || showLogin) {
                showLoginForm();
            } else if (hasSignupErrors) {
                showSignupForm();
            }
        });

        function showLoginForm() {
            const loginForm = document.querySelector('.login-form');
            const signupForm = document.querySelector('.signup-form');
            const loginButton = document.querySelector('.login-button-container');
            const loginContent = document.querySelector('.login-content');
            
            // Hide signup form if visible
            if (signupForm.classList.contains('show')) {
                signupForm.classList.remove('show');
                setTimeout(() => {
                    signupForm.style.display = 'none';
                    // After signup form is hidden, show login form
                    showLoginFormAfterHide();
                }, 300);
            } else {
                // If signup form is not visible, show login form directly
                showLoginFormAfterHide();
            }
            
            function showLoginFormAfterHide() {
                // Hide button with smooth fade out
                loginButton.classList.add('hide');
                
                // Show form with smooth fade in after transition completes
                setTimeout(() => {
                    loginForm.style.display = 'block';
                    // Force reflow to ensure transition works
                    requestAnimationFrame(() => {
                        loginForm.offsetHeight;
                        loginForm.classList.add('show');
                        // Add class to login-content to remove logo margin
                        if (loginContent) {
                            loginContent.classList.add('form-active');
                        }
                    });
                }, 100);
            }
        }

        function hideLoginForm() {
            const loginForm = document.querySelector('.login-form');
            const loginButton = document.querySelector('.login-button-container');
            const loginContent = document.querySelector('.login-content');
            
            // Hide form with smooth fade out
            loginForm.classList.remove('show');
            
            // Show button with smooth fade in after transition completes
            setTimeout(() => {
                loginForm.style.display = 'none';
                loginButton.classList.remove('hide');
                // Remove class from login-content to restore logo margin
                if (loginContent) {
                    loginContent.classList.remove('form-active');
                }
            }, 300);
        }

        function showSignupForm() {
            const signupForm = document.querySelector('.signup-form');
            const loginForm = document.querySelector('.login-form');
            const loginButton = document.querySelector('.login-button-container');
            const loginContent = document.querySelector('.login-content');
            const loginSection = document.querySelector('.login-section');
            
            // Hide login form if visible
            if (loginForm.classList.contains('show')) {
                loginForm.classList.remove('show');
                setTimeout(() => {
                    loginForm.style.display = 'none';
                    // After login form is hidden, show signup form
                    showSignupFormAfterHide();
                }, 300);
            } else {
                // If login form is not visible, show signup form directly
                showSignupFormAfterHide();
            }
            
            function showSignupFormAfterHide() {
                // Hide button with smooth fade out
                loginButton.classList.add('hide');
                
                // Prevent scrolling when signup form is shown
                if (loginSection) {
                    loginSection.classList.add('no-scroll');
                }
                
                // Show signup form with smooth fade in after transition completes
                setTimeout(() => {
                    signupForm.style.display = 'block';
                    // Force reflow to ensure transition works
                    requestAnimationFrame(() => {
                        signupForm.offsetHeight;
                        signupForm.classList.add('show');
                        // Add class to login-content to remove logo margin
                        if (loginContent) {
                            loginContent.classList.add('form-active');
                        }
                    });
                }, 100);
            }
        }

        function hideSignupForm() {
            const signupForm = document.querySelector('.signup-form');
            const loginButton = document.querySelector('.login-button-container');
            const loginContent = document.querySelector('.login-content');
            const loginSection = document.querySelector('.login-section');
            
            // Hide signup form with smooth fade out
            signupForm.classList.remove('show');
            
            // Show button with smooth fade in after transition completes
            setTimeout(() => {
                signupForm.style.display = 'none';
                loginButton.classList.remove('hide');
                // Remove class from login-content to restore logo margin
                if (loginContent) {
                    loginContent.classList.remove('form-active');
                }
                // Re-enable scrolling
                if (loginSection) {
                    loginSection.classList.remove('no-scroll');
                }
            }, 300);
        }

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggleButton = input.nextElementSibling;
            const toggleIcon = toggleButton.querySelector('.toggle-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
                toggleButton.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
                toggleButton.setAttribute('aria-label', 'Show password');
            }
        }

        // Password validation function
        function validatePassword(password) {
            const requirements = {
                length: password.length >= 12,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
            };

            const requirementLabels = {
                length: 'At least 12 characters',
                uppercase: 'One uppercase letter (A-Z)',
                lowercase: 'One lowercase letter (a-z)',
                number: 'One number (0-9)',
                special: 'One special character (!@#$%^&*...)'
            };

            // Count how many requirements are met
            const metCount = Object.values(requirements).filter(Boolean).length;
            const allMet = metCount === 5;

            // Update strength indicator - only show "strong" when all requirements are met
            const strengthElement = document.getElementById('password-strength');
            const passwordInput = document.getElementById('signup-password');
            const requirementsList = document.getElementById('password-requirements');
            
            if (strengthElement) {
                if (password.length > 0) {
                    if (allMet) {
                        strengthElement.textContent = 'Password is strong';
                        strengthElement.className = 'password-strength strong';
                    } else {
                        strengthElement.textContent = 'Password does not meet all requirements';
                        strengthElement.className = 'password-strength weak';
                    }
                } else {
                    strengthElement.textContent = '';
                    strengthElement.className = 'password-strength';
                }
            }

            // Update requirements list - only show unmet requirements
            if (requirementsList) {
                if (password.length > 0) {
                    requirementsList.innerHTML = '';
                    Object.keys(requirements).forEach(key => {
                        // Only show requirements that are NOT met
                        if (!requirements[key]) {
                            const li = document.createElement('li');
                            li.className = 'invalid';
                            li.textContent = requirementLabels[key];
                            requirementsList.appendChild(li);
                        }
                    });
                } else {
                    requirementsList.innerHTML = '';
                }
            }

            // Update error state on input
            if (passwordInput) {
                if (password.length > 0 && !allMet) {
                    passwordInput.classList.add('error');
                } else {
                    passwordInput.classList.remove('error');
                }
            }

            return allMet;
        }

        // Add real-time password validation
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('signup-password');
            const confirmPasswordInput = document.getElementById('confirm-password');
            
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    validatePassword(this.value);
                });
            }

            // Function to validate password match
            function validatePasswordMatch() {
                const password = document.getElementById('signup-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                const matchElement = document.getElementById('password-match');
                const confirmInput = document.getElementById('confirm-password');
                
                if (confirmPassword.length > 0) {
                    if (password === confirmPassword) {
                        matchElement.textContent = 'Passwords match';
                        matchElement.className = 'password-match match';
                        confirmInput.setCustomValidity('');
                        confirmInput.style.borderColor = '#10b981';
                    } else {
                        matchElement.textContent = 'Passwords do not match';
                        matchElement.className = 'password-match no-match';
                        confirmInput.setCustomValidity('Passwords do not match');
                        confirmInput.style.borderColor = '#ef4444';
                    }
                } else {
                    matchElement.textContent = '';
                    matchElement.className = 'password-match';
                    confirmInput.setCustomValidity('');
                    confirmInput.style.borderColor = '#e5e7eb';
                }
            }

            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', validatePasswordMatch);
            }

            // Also validate when main password changes
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    validatePassword(this.value);
                    // Also check if confirm password field has value
                    const confirmPassword = document.getElementById('confirm-password').value;
                    if (confirmPassword.length > 0) {
                        validatePasswordMatch();
                    }
                });
            }

            // Prevent form submission if password doesn't meet requirements
            const signupForm = document.querySelector('.signup-form');
            if (signupForm) {
                signupForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('signup-password').value;
                    const isPasswordValid = validatePassword(password);
                    
                    if (!isPasswordValid) {
                        e.preventDefault();
                        const passwordInput = document.getElementById('signup-password');
                        passwordInput.classList.add('error');
                        passwordInput.focus();
                        
                        // Show error message
                        const strengthElement = document.getElementById('password-strength');
                        if (strengthElement) {
                            strengthElement.textContent = 'Password does not meet all requirements';
                            strengthElement.className = 'password-strength weak';
                        }
                        return false;
                    }
                });
            }
        });

        // Products Carousel Functionality - Continuous Scroll
        document.addEventListener('DOMContentLoaded', function() {
            const carouselContainer = document.getElementById('carouselContainer');
            let items = carouselContainer.querySelectorAll('.carousel-item');
            
            // Clone items multiple times for seamless infinite scroll
            const itemsToClone = Array.from(items);
            itemsToClone.forEach(item => {
                const clone = item.cloneNode(true);
                carouselContainer.appendChild(clone);
            });
            
            // Clone again for smoother continuous scroll
            itemsToClone.forEach(item => {
                const clone = item.cloneNode(true);
                carouselContainer.appendChild(clone);
            });
            
            // Refresh items list after cloning
            items = carouselContainer.querySelectorAll('.carousel-item');
            const realItemsCount = itemsToClone.length;
            
            let currentIndex = realItemsCount; // Start at first cloned set
            let scrollSpeed = 0.5; // pixels per frame (adjust for speed)
            let isScrolling = true;
            let animationFrameId;
            
            // Update carousel position continuously
            function updateCarousel() {
                const firstItem = items[0];
                const wrapper = document.querySelector('.carousel-wrapper');
                if (!firstItem || !wrapper) return;
                
                const itemWidth = firstItem.offsetWidth;
                // Get gap from computed styles (supports responsive gaps)
                const computedStyle = window.getComputedStyle(carouselContainer);
                const gap = parseFloat(computedStyle.gap) || 10;
                const totalItemWidth = itemWidth + gap;
                const wrapperWidth = wrapper.offsetWidth;
                
                // Adjust scroll speed based on screen size (slower on mobile)
                const isMobile = window.innerWidth <= 768;
                const speed = isMobile ? 0.2 : 0.45;
                
                // Continuous scroll
                currentIndex += speed / totalItemWidth;
                
                // Reset position when we've scrolled through one full set
                if (currentIndex >= realItemsCount * 2) {
                    currentIndex -= realItemsCount;
                }
                
                // On mobile, center the item; on desktop, use center offset
                let translateX;
                if (isMobile) {
                    // For mobile, center each item
                    translateX = -((currentIndex - 1) * totalItemWidth) + (wrapperWidth / 2) - (itemWidth / 2);
                } else {
                const centerOffset = (wrapperWidth / 2) - (itemWidth / 2);
                    translateX = centerOffset - (currentIndex * totalItemWidth);
                }
                
                carouselContainer.style.transition = 'none';
                carouselContainer.style.transform = `translateX(${translateX}px)`;
                
                if (isScrolling) {
                    animationFrameId = requestAnimationFrame(updateCarousel);
                }
            }
            
            // Initialize carousel to consistent starting position
            function initializeCarousel() {
                const firstItem = items[0];
                const wrapper = document.querySelector('.carousel-wrapper');
                if (!firstItem || !wrapper) return;
                
                const itemWidth = firstItem.offsetWidth;
                const computedStyle = window.getComputedStyle(carouselContainer);
                const gap = parseFloat(computedStyle.gap) || 10;
                const totalItemWidth = itemWidth + gap;
                const wrapperWidth = wrapper.offsetWidth;
                const isMobile = window.innerWidth <= 768;
                
                // Reset to consistent starting position (first item)
                currentIndex = realItemsCount;
                
                // Set initial position
                let translateX;
                if (isMobile) {
                    translateX = -((currentIndex - 1) * totalItemWidth) + (wrapperWidth / 2) - (itemWidth / 2);
                } else {
                    const centerOffset = (wrapperWidth / 2) - (itemWidth / 2);
                    translateX = centerOffset - (currentIndex * totalItemWidth);
                }
                
                carouselContainer.style.transition = 'none';
                carouselContainer.style.transform = `translateX(${translateX}px)`;
            }
            
            // Start continuous scrolling
            function startScrolling() {
                // Initialize to consistent position first
                initializeCarousel();
                // Small delay to ensure layout is ready
                setTimeout(() => {
                    isScrolling = true;
                    updateCarousel();
                }, 100);
            }
            
            // Handle window resize (only pause briefly to recalculate)
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    // Recalculate position but keep scrolling
                    updateCarousel();
                }, 250);
            });
            
            // Initialize and start continuous scrolling immediately
            startScrolling();
        });

        // Scroll to Top Button Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const scrollToTopBtn = document.getElementById('scrollToTop');
            
            // Show/hide button based on scroll position
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    scrollToTopBtn.classList.add('show');
                } else {
                    scrollToTopBtn.classList.remove('show');
                }
            });
            
            // Scroll to top when button is clicked
            scrollToTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // Footer Sections Animation on Scroll
            const footerSections = document.querySelectorAll('.footer-section');
            
            const observerOptions = {
                threshold: 0.2,
                rootMargin: '0px 0px -100px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            footerSections.forEach(section => {
                observer.observe(section);
            });
        });

    </script>
</body>
</html>

