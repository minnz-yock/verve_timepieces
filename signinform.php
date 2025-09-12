<?php
require_once('dbconnect.php');
session_start();

$errorMsg = "";

if (isset($_POST["submit"])) {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $errorMsg = "Please fill in all fields.";
    } else {
        
        $sql = "SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["first_name"] = $user["first_name"];
            $_SESSION["last_name"] = $user["last_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            
            if ($user["role"] === "admin") {
                header("Location: admin\admin_dashboard.php");
            } else {
                header("Location: customer\index.php");
            }
            exit;
        } else {
            $errorMsg = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Verve Timepieces</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
        }
        
        .container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        
        .form-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: #ffffff;
        }
        
        .form-container {
            width: 100%;
            max-width: 400px;
        }
        
        .brand-logo {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: #352826;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .brand-tagline {
            color: #785A49;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            color: #352826;
            margin-bottom: 8px;
        }
        
        .welcome-subtitle {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 32px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            color: #352826;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            background: #ffffff;
            color: #352826;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #A57A5B;
            box-shadow: 0 0 0 3px rgba(165, 122, 91, 0.1);
        }
        
        .form-control::placeholder {
            color: #adb5bd;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #A57A5B;
        }
        
        .remember-me label {
            color: #6c757d;
            font-size: 0.875rem;
            cursor: pointer;
        }
        
        .forgot-password {
            color: #A57A5B;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #352826;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-submit:hover {
            background: #785A49;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(53, 40, 38, 0.3);
        }
        
        .form-note {
            text-align: center;
            margin-top: 24px;
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .form-note a {
            color: #A57A5B;
            text-decoration: none;
            font-weight: 600;
        }
        
        .form-note a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            border: 1px solid #f5c6cb;
        }
        
        .promo-section {
            flex: 1;
            background: linear-gradient(135deg, #352826 0%, #2a1f1d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .promo-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(165, 122, 91, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(222, 210, 200, 0.1) 0%, transparent 50%);
        }
        
        .promo-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: #ffffff;
            max-width: 400px;
        }
        
        .promo-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
            line-height: 1.2;
        }
        
        .promo-subtitle {
            font-size: 1.1rem;
            margin-bottom: 32px;
            opacity: 0.9;
            line-height: 1.5;
        }
        
        .benefits-list {
            text-align: left;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .benefit-icon {
            width: 24px;
            height: 24px;
            background: #A57A5B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 12px;
            color: #ffffff;
        }
        
        .benefit-text {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .form-section,
            .promo-section {
                flex: none;
                min-height: 50vh;
            }
            
            .promo-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-section">
            <div class="form-container">
                <div class="brand-logo">Verve Timepieces</div>
                <div class="brand-tagline">Crafting Time, Creating Legacy</div>
                
                <div class="welcome-title">Welcome Back</div>
                <div class="welcome-subtitle">Sign in to continue your journey with luxury timepieces</div>
                
                <?php if (!empty($errorMsg)): ?>
                    <div class="error-message"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>
                
                <form method="post" autocomplete="off">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input required type="email" class="form-control" id="email" name="email" placeholder="Enter your email address">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input required type="password" class="form-control" id="password" name="password" placeholder="Enter your password">
                    </div>
                    
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button class="btn-submit" type="submit" name="submit">Sign In</button>
                </form>
                
                <div class="form-note">
                    New to Verve Timepieces? <a href="signupform.php">Create an account</a>
                </div>
            </div>
        </div>
        
        <div class="promo-section">
            <div class="promo-content">
                <h1 class="promo-title">Discover Timeless Elegance</h1>
                <p class="promo-subtitle">Join our community of watch enthusiasts and explore our curated collection of luxury timepieces</p>
                
                <div class="benefits-list">
                    <div class="benefit-item">
                        <div class="benefit-icon">⏰</div>
                        <div class="benefit-text">Access to exclusive watch collections</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">💎</div>
                        <div class="benefit-text">Priority customer service</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">🎯</div>
                        <div class="benefit-text">Personalized recommendations</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">⭐</div>
                        <div class="benefit-text">Special member pricing</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
