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
        
        $sql = "SELECT id, username, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
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
    <style>
        body {
            background-color: #FFFFFF;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .form-container {
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(53, 40, 38, 0.10);
            padding: 40px 35px 30px 35px;
            border: 1px solid #DED2C8;
            width: 100%;
            max-width: 430px;
        }
        h2 { color: #352826; font-weight: 700; margin-bottom: 30px; }
        .form-label { color: #785A49; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem; }
        .form-control {
            background: #DED2C8;
            border: 1.5px solid #A57A5B;
            color: #352826;
            border-radius: 6px;
            font-size: 0.95rem;
            padding: 0.75rem 0.9rem;
        }
        .btn-submit {
            background: #352826;
            border: none;
            color: #DED2C8;
            font-weight: 700;
            border-radius: 6px;
            padding: 0.8rem;
            margin-top: 8px;
        }
        .btn-submit:hover { background: #785A49; }
        .error-message {
            color: #fff;
            background: #A57A5B;
            border: 1px solid #785A49;
            border-radius: 7px;
            padding: 12px 16px;
            text-align: center;
            margin-bottom: 18px;
            font-weight: 500;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 class="mb-3 text-center">Sign In</h2>
        <?php if (!empty($errorMsg)): ?>
            <div class="error-message"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input required type="email" class="form-control" id="email" name="email" placeholder="you@email.com">
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input required type="password" class="form-control" id="password" name="password" placeholder="Your password">
            </div>
            <button class="btn btn-submit w-100" type="submit" name="submit">Sign In</button>
        </form>
        <div class="form-note mt-3 text-center">
            <span>Don’t have an account? <a href="signupform.php">Create one</a></span>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
