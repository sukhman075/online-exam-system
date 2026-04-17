<?php
session_start();
include "../db/connection.php";

$error = "";
$success = "";
$showReset = false;

/* ================= ADMIN LOGIN ================= */
if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {

            session_regenerate_id(true);
            $_SESSION['admin'] = $row['name'];
            $_SESSION['admin_id'] = $row['id'];

            header("Location: index.php");
            exit();

        } else {
            $error = "Invalid email or password";
        }

    } else {
        $error = "Invalid email or password";
    }
}

/* ================= ADMIN RESET PASSWORD ================= */
if (isset($_POST['reset_password'])) {

    $showReset = true;

    $email = trim($_POST['reset_email']);
    $newPassword = $_POST['new_password'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND role='admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        if (strlen($newPassword) < 6) {

            $error = "Password must be at least 6 characters.";

        } else {

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password=? WHERE email=? AND role='admin'");
            $update->bind_param("ss", $hashedPassword, $email);
            $update->execute();

            $success = "Password updated successfully. You can login now.";
            $showReset = false;
        }

    } else {
        $error = "Admin email not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | TestHub CU</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

/* ===== Background ===== */
body{
min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
background:linear-gradient(135deg,#ffecec,#ffd6d6,#ffc0c0,#ffecec);
overflow:hidden;
position:relative;
}

/* ===== Floating Shapes ===== */
.shapes{
position:absolute;
width:100%;
height:100%;
top:0;
left:0;
overflow:hidden;
z-index:0;
}

.shapes span{
position:absolute;
display:block;
border-radius:50%;
background:rgba(199,0,0,0.15);
animation:moveShapes 20s linear infinite;
bottom:-150px;
}

.shapes span:nth-child(1){ left:10%; width:80px; height:80px; }
.shapes span:nth-child(2){ left:25%; width:50px; height:50px; animation-duration:18s; }
.shapes span:nth-child(3){ left:40%; width:100px; height:100px; animation-delay:4s; }
.shapes span:nth-child(4){ left:60%; width:60px; height:60px; animation-duration:22s; }
.shapes span:nth-child(5){ left:75%; width:90px; height:90px; animation-delay:3s; }
.shapes span:nth-child(6){ left:90%; width:40px; height:40px; animation-delay:5s; }

@keyframes moveShapes{
0%{
transform:translateY(0) rotate(0deg);
opacity:0.6;
}
100%{
transform:translateY(-110vh) rotate(360deg);
opacity:0;
}
}

/* ===== Card ===== */
.login-card{
width:380px;
background:#fff;
border-radius:22px;
padding:45px 35px;
text-align:center;
box-shadow:0 25px 60px rgba(0,0,0,0.15);
animation:fadeIn 0.7s ease forwards;
position:relative;
z-index:1;
}

.icon{
width:70px;
height:70px;
background:linear-gradient(135deg,#c70000,#ff4d4d);
color:#fff;
border-radius:50%;
display:flex;
align-items:center;
justify-content:center;
font-size:30px;
margin:0 auto 20px;
}

h2{
color:#c70000;
margin-bottom:6px;
font-weight:600;
}

.subtitle{
font-size:14px;
color:#666;
margin-bottom:25px;
}

input{
width:100%;
padding:14px;
margin-bottom:15px;
border-radius:12px;
border:1px solid #ddd;
transition:0.3s;
font-size:14px;
}

input:focus{
border-color:#c70000;
outline:none;
box-shadow:0 0 0 3px rgba(199,0,0,0.15);
}

button{
width:100%;
padding:14px;
border:none;
border-radius:14px;
background:linear-gradient(135deg,#c70000,#ff4d4d);
color:#fff;
cursor:pointer;
font-weight:500;
transition:0.3s;
}

button:hover{
transform:translateY(-3px);
box-shadow:0 10px 25px rgba(199,0,0,0.35);
}

.extra-links{
margin-top:12px;
font-size:13px;
display:flex;
justify-content:space-between;
}

.extra-links a{
text-decoration:none;
color:#c70000;
cursor:pointer;
}

.error{
background:#ffe5e5;
color:#cc0000;
padding:10px;
border-radius:8px;
font-size:13px;
margin-bottom:15px;
}

.success{
background:#e6ffea;
color:#008a2e;
padding:10px;
border-radius:8px;
font-size:13px;
margin-bottom:15px;
}

@keyframes fadeIn{
from{opacity:0;transform:translateY(20px);}
to{opacity:1;transform:translateY(0);}
}
</style>

<script>
function showResetForm(){
document.getElementById("loginForm").style.display="none";
document.getElementById("resetForm").style.display="block";
}
function showLoginForm(){
document.getElementById("resetForm").style.display="none";
document.getElementById("loginForm").style.display="block";
}
</script>

</head>
<body>

<div class="shapes">
<span></span>
<span></span>
<span></span>
<span></span>
<span></span>
<span></span>
</div>

<div class="login-card">

<div class="icon">🛠️</div>
<h2>Admin Login</h2>
<div class="subtitle">Sign in to continue</div>

<?php if($error!="") echo "<div class='error'>$error</div>"; ?>
<?php if($success!="") echo "<div class='success'>$success</div>"; ?>

<div id="loginForm" style="<?php if($showReset) echo 'display:none;'; ?>">
<form method="post">
<input type="email" name="email" placeholder="Admin Email" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit" name="login">Login</button>
</form>

<div class="extra-links">
<a onclick="showResetForm()">Forgot Password?</a>
<a href="../index.php">Back</a>
</div>
</div>

<div id="resetForm" style="<?php if(!$showReset) echo 'display:none;'; ?>">
<form method="post">
<input type="email" name="reset_email" placeholder="Enter admin email" required>
<input type="password" name="new_password" placeholder="New Password" required>
<button type="submit" name="reset_password">Update Password</button>
</form>

<div class="extra-links">
<a onclick="showLoginForm()">Back to Login</a>
</div>
</div>

</div>

</body>
</html>
