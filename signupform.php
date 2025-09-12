<?php
require_once('dbconnect.php');
session_start();

$successMsg = "";
$errorMsg = "";

// Handle the signup logic
if (isset($_POST["submit"])) {
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $role = 'customer';

    // Simple validation
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
        $errorMsg = "Please fill in all fields.";
    } else {
        // ✅ Hash the password before saving
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        try {
            $stmt->execute([$firstname, $lastname, $email, $hashedPassword, $role]);
            $successMsg = "Account created successfully! <a href='signinform.php'>Sign in here</a>";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errorMsg = "Email already exists.";
            } else {
                $errorMsg = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Verve Timepieces</title>
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
            padding: 20px;
            background: #ffffff;
            overflow-y: auto;
        }
        
        .form-container {
            width: 100%;
            max-width: 420px;
            margin: 20px 0;
        }
        
        .brand-logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #352826;
            margin-bottom: 6px;
            text-align: center;
        }
        
        .brand-tagline {
            color: #785A49;
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 24px;
        }
        
        .welcome-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #352826;
            margin-bottom: 6px;
        }
        
        .welcome-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 24px;
            line-height: 1.4;
        }
        
        .form-row {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .form-group-full {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            color: #352826;
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 0.9rem;
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
        
        .password-strength {
            margin-top: 6px;
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .strength-bar {
            width: 100%;
            height: 3px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 3px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            background: #dc3545;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .strength-fill.medium {
            background: #ffc107;
        }
        
        .strength-fill.strong {
            background: #28a745;
        }
        
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .terms-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #A57A5B;
            margin-top: 2px;
        }
        
        .terms-checkbox label {
            color: #6c757d;
            font-size: 0.8rem;
            line-height: 1.3;
            cursor: pointer;
        }
        
        .terms-checkbox a {
            color: #A57A5B;
            text-decoration: none;
            font-weight: 500;
        }
        
        .terms-checkbox a:hover {
            text-decoration: underline;
        }
        
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #352826;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
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
            margin-top: 20px;
            color: #6c757d;
            font-size: 0.8rem;
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
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            border: 1px solid #c3e6cb;
        }
        
        .promo-section {
            flex: 1;
            background: linear-gradient(135deg, #352826 0%, #2a1f1d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 12px;
            line-height: 1.2;
        }
        
        .promo-subtitle {
            font-size: 1rem;
            margin-bottom: 24px;
            opacity: 0.9;
            line-height: 1.4;
        }
        
        .benefits-list {
            text-align: left;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            backdrop-filter: blur(10px);
        }
        
        .benefit-icon {
            width: 20px;
            height: 20px;
            background: #A57A5B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 10px;
            color: #ffffff;
        }
        
        .benefit-text {
            font-size: 0.8rem;
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
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .form-group {
                margin-bottom: 24px;
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
                
                <div class="welcome-title">Create Your Account</div>
                <div class="welcome-subtitle">Join our community of watch enthusiasts and discover luxury timepieces</div>
                
                <?php if (!empty($errorMsg)): ?>
                    <div class="error-message"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($successMsg)): ?>
                    <div class="success-message"><?= $successMsg ?></div>
                <?php endif; ?>
                
                <?php if (empty($successMsg)): ?>
                    <form method="post" autocomplete="off">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="firstname">First Name</label>
                                <input required type="text" class="form-control" id="firstname" name="firstname" placeholder="Enter your first name">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="lastname">Last Name</label>
                                <input required type="text" class="form-control" id="lastname" name="lastname" placeholder="Enter your last name">
                            </div>
                        </div>
                        
                        
                        <div class="form-group-full">
                            <label class="form-label" for="email">Email Address</label>
                            <input required type="email" class="form-control" id="email" name="email" placeholder="Enter your email address">
                        </div>
                        
                        <div class="form-group-full">
                            <label class="form-label" for="password">Password</label>
                            <input required type="password" class="form-control" id="password" name="password" placeholder="Create a secure password">
                            <div class="password-strength">
                                Password strength: <span id="strength-text">Weak</span>
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strength-fill" style="width: 25%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="terms-checkbox">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">
                                I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <button class="btn-submit" type="submit" name="submit">Create Account</button>
                    </form>
                <?php endif; ?>
                
                <div class="form-note">
                    Already have an account? <a href="signinform.php">Log in</a>
                </div>
            </div>
        </div>
        
        <div class="promo-section">
            <div class="promo-content">
                <h1 class="promo-title">Start Your Journey</h1>
                <p class="promo-subtitle">Join thousands of watch enthusiasts who trust Verve Timepieces for their luxury timepiece needs</p>
                
                <div class="benefits-list">
                    <div class="benefit-item">
                        <div class="benefit-icon">⏰</div>
                        <div class="benefit-text">Access to exclusive watch collections</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">💎</div>
                        <div class="benefit-text">Special member discounts</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">🎯</div>
                        <div class="benefit-text">Personalized recommendations</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">⭐</div>
                        <div class="benefit-text">Priority booking for popular watches</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthFill = document.getElementById('strength-fill');
            const strengthText = document.getElementById('strength-text');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const percentage = (strength / 5) * 100;
            strengthFill.style.width = percentage + '%';
            
            if (strength <= 2) {
                strengthFill.className = 'strength-fill';
                strengthText.textContent = 'Weak';
            } else if (strength <= 3) {
                strengthFill.className = 'strength-fill medium';
                strengthText.textContent = 'Medium';
            } else {
                strengthFill.className = 'strength-fill strong';
                strengthText.textContent = 'Strong';
            }
        });
    </script>
</body>
</html>
