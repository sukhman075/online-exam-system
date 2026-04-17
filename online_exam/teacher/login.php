<?php
session_start();
include "../db/connection.php";

$error = "";
$success = "";
$showReset = false;

/* ================= LOGIN ================= */
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='teacher'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['teacher'] = $row['name'];
            $_SESSION['teacher_id'] = $row['id'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "No teacher account found with that email.";
    }
}

/* ================= RESET PASSWORD ================= */
if (isset($_POST['reset_password'])) {
    $showReset = true;
    $email = trim($_POST['reset_email']);
    $newPassword = $_POST['new_password'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND role='teacher'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password=? WHERE email=? AND role='teacher'");
        $update->bind_param("ss", $hashedPassword, $email);
        $update->execute();

        $success = "Password reset successfully! You can now log in.";
        $showReset = false;
    } else {
        $error = "This email is not registered in our system.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Portal | TestHub CU</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #c70000; 
            --primary-soft: rgba(199, 0, 0, 0.1);
            --dark: #0f172a; 
            --slate: #1e293b;
            --transition: cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            height: 100vh;
            display: flex;
            background-color: var(--dark);
            overflow: hidden;
        }

        /* LEFT SIDE - BRANDING & STATS */
        .side-panel {
            flex: 1.4;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px;
            color: white;
            position: relative;
        }

        /* Subtle Background Pattern */
        .side-panel::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: radial-gradient(var(--primary) 0.5px, transparent 0.5px);
            background-size: 30px 30px;
            opacity: 0.1;
        }

        .brand-content { position: relative; z-index: 10; }
        .brand-content h1 {
            font-size: 4.5rem;
            font-weight: 800;
            letter-spacing: -3px;
            line-height: 1;
            animation: slideUp 0.8s var(--transition) both;
        }
        .brand-content h1 span { color: var(--primary); }
        
        .brand-content p {
            color: #94a3b8;
            margin-top: 25px;
            font-size: 1.15rem;
            max-width: 500px;
            line-height: 1.6;
            animation: slideUp 0.8s var(--transition) 0.15s both;
        }

        /* RIGHT SIDE - LOGIN FORM */
        .auth-panel {
            flex: 1;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            border-top-left-radius: 40px;
            border-bottom-left-radius: 40px;
            box-shadow: -20px 0 80px rgba(0,0,0,0.3);
            animation: panelSlide 0.8s var(--transition);
        }

        .auth-container { 
            width: 100%; 
            max-width: 400px;
            animation: fadeIn 1s ease 0.4s both;
        }

        .auth-header { margin-bottom: 40px; }
        .auth-header h2 { font-size: 2.2rem; font-weight: 800; color: var(--dark); letter-spacing: -1px; }
        .auth-header p { color: #64748b; font-weight: 500; margin-top: 5px; }

        /* FORM STYLING */
        .input-group { position: relative; margin-bottom: 20px; }
        .input-group i { 
            position: absolute; left: 18px; top: 50%; transform: translateY(-50%); 
            color: #94a3b8; transition: 0.3s;
        }

        input {
            width: 100%;
            padding: 16px 16px 16px 52px;
            border-radius: 16px;
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            font-size: 15px;
            transition: all 0.3s var(--transition);
            outline: none;
        }

        input:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-soft);
        }

        input:focus + i { color: var(--primary); }

        button {
            width: 100%;
            padding: 18px;
            border: none;
            border-radius: 16px;
            background: var(--primary);
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.4s var(--transition);
            margin-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(199,0,0,0.25);
            filter: brightness(1.1);
        }

        /* ALERTS */
        .alert {
            padding: 14px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: bounceIn 0.5s var(--transition);
        }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bcf0da; }

        .links {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 600;
        }
        .links a { text-decoration: none; color: #64748b; transition: 0.2s; cursor: pointer; }
        .links a:hover { color: var(--primary); }

        /* ANIMATIONS */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes panelSlide {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes bounceIn {
            0% { transform: scale(0.9); opacity: 0; }
            70% { transform: scale(1.03); }
            100% { transform: scale(1); opacity: 1; }
        }

        @media (max-width: 1024px) {
            .side-panel { display: none; }
            .auth-panel { border-radius: 0; }
        }
    </style>
</head>
<body>

<section class="side-panel">
    <div class="brand-content">
        <h1>TestHub<span>CU</span></h1>
        <p>Advanced Management Portal for Educators. Create, monitor, and evaluate examinations with precision and ease.</p>
    </div>
</section>

<section class="auth-panel">
    <div class="auth-container">
        <div class="auth-header">
            <h2 id="formTitle">Teacher Login</h2>
            <p id="formSubtitle">Secure access to your faculty dashboard</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <div id="loginSection" style="<?php if($showReset) echo 'display:none;'; ?>">
            <form method="POST">
                <div class="input-group">
                    <i class="fa-solid fa-envelope-open-text"></i>
                    <input type="email" name="email" placeholder="Faculty Email" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-shield-halved"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" name="login">
                    Enter Dashboard <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>
            <div class="links">
                <a onclick="toggleForms()">Password Recovery</a>
                <a href="../index.php">Portal Home</a>
            </div>
        </div>

        <div id="resetSection" style="<?php if(!$showReset) echo 'display:none;'; ?>">
            <form method="POST">
                <div class="input-group">
                    <i class="fa-solid fa-at"></i>
                    <input type="email" name="reset_email" placeholder="Registered Email" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-key"></i>
                    <input type="password" name="new_password" placeholder="New Dashboard Password" required>
                </div>
                <button type="submit" name="reset_password">
                    Update Credentials <i class="fa-solid fa-rotate"></i>
                </button>
            </form>
            <div class="links">
                <a onclick="toggleForms()">Return to Login</a>
            </div>
        </div>
    </div>
</section>

<script>
    function toggleForms() {
        const login = document.getElementById("loginSection");
        const reset = document.getElementById("resetSection");
        const title = document.getElementById("formTitle");
        const subtitle = document.getElementById("formSubtitle");

        if(login.style.display === "none") {
            login.style.display = "block";
            reset.style.display = "none";
            title.innerText = "Teacher Login";
            subtitle.innerText = "Secure access to your faculty dashboard";
        } else {
            login.style.display = "none";
            reset.style.display = "block";
            title.innerText = "Recover Access";
            subtitle.innerText = "Reset your faculty account credentials";
        }
    }
</script>

</body>
</html>