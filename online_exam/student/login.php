<?php
session_start();
include "../db/connection.php";

$error = "";
$success = "";
$showReset = false;

/* ================= LOGIN LOGIC ================= */
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='student'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['student'] = $row['name'];
            $_SESSION['student_id'] = $row['id'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "Student identity not found.";
    }
}

/* ================= RESET PASSWORD LOGIC ================= */
if (isset($_POST['reset_password'])) {
    $showReset = true;
    $email = trim($_POST['reset_email']);
    $newPassword = $_POST['new_password'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND role='student'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password=? WHERE email=? AND role='student'");
        $update->bind_param("ss", $hashedPassword, $email);
        $update->execute();

        $success = "Password updated successfully. Login now.";
        $showReset = false;
    } else {
        $error = "Email not registered.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal | TestHub CU</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-glow: rgba(37, 99, 235, 0.2);
            --bg-dark: #0f172a;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --transition: cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            height: 100vh;
            display: flex;
            background-color: var(--bg-dark);
            overflow: hidden;
        }

        /* Brand Side - Visual Polish */
        .brand-side {
            flex: 1.2;
            background: radial-gradient(circle at 0% 0%, #1e293b 0%, #0f172a 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px;
            color: white;
            position: relative;
        }

        /* Decorative circles for Brand Side */
        .brand-side::before {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: var(--primary);
            filter: blur(120px);
            opacity: 0.15;
            top: 10%; left: 10%;
        }

        .brand-content h1 {
            font-size: clamp(3rem, 5vw, 4.5rem);
            font-weight: 800;
            letter-spacing: -3px;
            animation: slideInUp 0.8s var(--transition);
        }

        .brand-content p {
            color: #94a3b8;
            margin-top: 20px;
            font-size: 1.1rem;
            animation: slideInUp 0.8s var(--transition) 0.1s backwards;
        }

        /* Auth Side - Entrance Transition */
        .auth-side {
            flex: 1;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            border-top-left-radius: 40px;
            border-bottom-left-radius: 40px;
            box-shadow: -20px 0 80px rgba(0,0,0,0.3);
            animation: slideInRight 0.8s var(--transition);
        }

        .auth-container { 
            width: 100%; 
            max-width: 400px; 
            animation: fadeIn 1s var(--transition) 0.3s backwards;
        }

        .input-group { position: relative; margin-bottom: 20px; }
        .input-group i { 
            position: absolute; left: 18px; top: 50%; 
            transform: translateY(-50%); color: var(--text-muted);
            transition: 0.3s;
        }

        .input-group input {
            width: 100%; padding: 16px 16px 16px 50px;
            border: 2px solid #f1f5f9; border-radius: 16px;
            background: #f8fafc;
            font-size: 15px;
            transition: all 0.3s var(--transition);
        }

        .input-group input:focus { 
            border-color: var(--primary); 
            background: #fff;
            box-shadow: 0 0 0 4px var(--primary-glow);
            outline: none; 
        }

        .input-group input:focus + i { color: var(--primary); }

        button {
            width: 100%; background: var(--primary); color: white;
            padding: 16px; border: none; border-radius: 16px;
            font-weight: 700; font-size: 16px; cursor: pointer;
            transition: all 0.4s var(--transition);
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        button:hover { 
            background: #1d4ed8; 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px var(--primary-glow);
        }

        button:active { transform: translateY(0); }

        /* Smooth Form Toggle Animation */
        .form-wrapper {
            position: relative;
            transition: 0.5s var(--transition);
        }

        .fade-in { animation: fadeIn 0.5s var(--transition); }

        /* Alerts with bounce */
        .alert { 
            padding: 14px; border-radius: 14px; margin-bottom: 25px; 
            font-size: 0.95rem; font-weight: 500;
            animation: bounceIn 0.5s var(--transition);
            display: flex; align-items: center; gap: 10px;
        }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }

        .footer-links { 
            margin-top: 30px; display: flex; 
            justify-content: space-between; font-size: 0.95rem; 
            font-weight: 600;
        }
        .footer-links a { color: var(--text-muted); text-decoration: none; transition: 0.3s; }
        .footer-links a:hover { color: var(--primary); }

        /* Keyframes */
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.95); }
            70% { transform: scale(1.02); }
            100% { opacity: 1; transform: scale(1); }
        }

        @media (max-width: 1024px) { 
            .brand-side { display: none; } 
            .auth-side { border-radius: 0; } 
        }
    </style>
</head>
<body>

    <section class="brand-side">
        <div class="brand-content">
            <h1>TestHub<span style="color:var(--primary)">CU</span></h1>
            <p>Empowering students with a seamless, high-integrity examination experience.</p>
        </div>
    </section>

    <section class="auth-side">
        <div class="auth-container">
            <div style="margin-bottom: 40px;">
                <h2 style="font-size: 2.2rem; color: var(--text-main); font-weight: 800; letter-spacing: -1px;">
                    Student Portal
                </h2>
                <p style="color: var(--text-muted); font-weight: 500;">Secure login for authorized candidates</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $success ?></div>
            <?php endif; ?>

            <div id="loginForm" class="form-wrapper" style="<?= $showReset ? 'display:none;' : '' ?>">
                <form method="post">
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Student Email" required>
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Password" required>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <button type="submit" name="login">Sign In <i class="fa-solid fa-arrow-right" style="margin-left: 8px; font-size: 0.8rem;"></i></button>
                </form>
                <div class="footer-links">
                    <a onclick="toggleForms()">Forgot Password?</a>
                    <a href="../index.php">Return Home</a>
                </div>
            </div>

            <div id="resetForm" class="form-wrapper" style="<?= !$showReset ? 'display:none;' : '' ?>">
                <form method="post">
                    <div class="input-group">
                        <input type="email" name="reset_email" placeholder="Confirm Student Email" required>
                        <i class="fa-solid fa-at"></i>
                    </div>
                    <div class="input-group">
                        <input type="password" name="new_password" placeholder="New Password" required>
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <button type="submit" name="reset_password">Update Credentials</button>
                </form>
                <div class="footer-links">
                    <a onclick="toggleForms()">Back to Sign In</a>
                </div>
            </div>
        </div>
    </section>

    <script>
        function toggleForms() {
            const login = document.getElementById("loginForm");
            const reset = document.getElementById("resetForm");
            
            // Adding a simple fade-out/in class
            [login, reset].forEach(f => f.classList.add('fade-in'));

            if (login.style.display === "none") {
                login.style.display = "block";
                reset.style.display = "none";
            } else {
                login.style.display = "none";
                reset.style.display = "block";
            }
            
            setTimeout(() => {
                [login, reset].forEach(f => f.classList.remove('fade-in'));
            }, 500);
        }
    </script>
</body>
</html>