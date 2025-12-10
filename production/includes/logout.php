<?php
session_start();

// Destroy session
session_unset();
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logoooo.png" type="image/png">
    <title>Session Terminated - TCI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .redirect-container {
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
            animation: fadeInScale 0.5s ease-out;
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

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4a7c35 0%, #3d6b2a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: iconBounce 0.6s ease-out 0.2s both;
        }

        @keyframes iconBounce {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        .success-icon svg {
            width: 45px;
            height: 45px;
            fill: white;
        }

        h1 {
            color: #2d5016;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .welcome-message {
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .countdown {
            color: #4a7c35;
            font-weight: 600;
            font-size: 20px;
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4a7c35;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-top: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .redirect-text {
            color: #999;
            font-size: 14px;
            margin-top: 15px;
        }

        @media (max-width: 480px) {
            .redirect-container {
                padding: 40px 30px;
            }

            h1 {
                font-size: 24px;
            }

            .welcome-message {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="redirect-container">
        <div class="success-icon">
            <svg viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
        </div>
        <h1>Session Terminated</h1>
        <p class="welcome-message">
            You have been successfully logged out.<br>
            Redirecting in <span class="countdown" id="countdown">3</span> seconds...
        </p>
        <div class="loading-spinner"></div>
        <p class="redirect-text">Please wait a moment</p>
    </div>

    <script>
        let timeLeft = 3; // 3 seconds countdown
        const countdownElement = document.getElementById('countdown');
        
        const countdownInterval = setInterval(() => {
            timeLeft--;
            if (countdownElement) {
                countdownElement.textContent = timeLeft;
            }
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                window.location.href = '../../index.php';
            }
        }, 1000);
    </script>
</body>
</html>


