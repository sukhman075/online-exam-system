<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$adminId = $_SESSION['admin_id']; 
$adminName = $_SESSION['admin']; 
$currentPage = basename($_SERVER['PHP_SELF']);

// Fetch current admin info
$stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND role='admin'");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Admin not found!");
}

$admin = $result->fetch_assoc();

$successMessage = "";
$errorMessage = "";

// Handle profile update
if (isset($_POST['update_profile'])) {
    $newName = $conn->real_escape_string($_POST['name']);
    $newEmail = $conn->real_escape_string($_POST['email']);
    $passwordInput = $_POST['password'];

    $updateFields = "name='$newName', email='$newEmail'";

    if (!empty($passwordInput)) {
        $newPassword = password_hash($passwordInput, PASSWORD_DEFAULT);
        $updateFields .= ", password='$newPassword'";
    }

    $update = $conn->query("UPDATE users SET $updateFields WHERE id=$adminId");

    if ($update) {
        $_SESSION['admin'] = $newName; // update session name
        $admin['name'] = $newName;
        $admin['email'] = $newEmail;
        $successMessage = "Profile updated successfully!";
        if (!empty($passwordInput)) {
            $successMessage .= " Password was changed.";
        }
    } else {
        $errorMessage = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Profile | TestHub CU</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body { background:#f4f6fb; color:#333; }

/* Navbar */
.navbar {
    height:70px; background:linear-gradient(135deg,#c70000,#ff4d4d);
    color:#fff; display:flex; align-items:center; justify-content:space-between; padding:0 30px;
    box-shadow:0 8px 20px rgba(0,0,0,0.2); position:sticky; top:0; z-index:1000;
}
.navbar h2 { font-size:20px; font-weight:600; }
.navbar a { color:#fff; text-decoration:none; font-weight:600; background:rgba(255,255,255,0.2); padding:8px 18px; border-radius:12px; transition:0.3s; }
.navbar a:hover { background:#fff; color:#c70000; }

/* Layout */
.container { display:flex; min-height:calc(100vh - 70px); }
.sidebar {
    width:250px; background:#fff; padding:30px 20px; box-shadow:5px 0 20px rgba(0,0,0,0.08);
    position:sticky; top:70px; height:calc(100vh - 70px); border-radius:0 20px 20px 0;
}
.sidebar a {
    display:block; padding:14px 20px; margin-bottom:14px;
    text-decoration:none; color:#333; font-weight:500; border-radius:12px;
    transition:all 0.3s ease; position:relative;
}
.sidebar a.active { background:#ffe1e1; color:#c70000; }
.sidebar a::before { content:''; position:absolute; left:0; top:0; width:4px; height:100%; background:#c70000; border-radius:2px; opacity:0; transition:opacity 0.3s ease; }
.sidebar a.active::before { opacity:1; }
.sidebar a:hover::before { opacity:1; }
.sidebar a:hover { background:#ffe1e1; color:#c70000; }

/* Main content */
.main { flex:1; padding:40px 50px; }
.main h2 { font-size:26px; font-weight:600; margin-bottom:15px; }
.main .welcome { color:#777; font-size:15px; margin-bottom:25px; }

/* Profile Card */
.profile-card { background:#fff; padding:30px; border-radius:20px; box-shadow:0 20px 40px rgba(0,0,0,0.08); max-width:600px; margin:auto; }
.profile-card h3 { margin-bottom:20px; color:#c70000; }
.profile-card label { display:block; margin-top:15px; margin-bottom:6px; font-weight:500; }
.profile-card input { width:100%; padding:10px 12px; border-radius:10px; border:1px solid #ccc; font-size:14px; }
.profile-card input:focus { outline:none; border-color:#c70000; }
.profile-card button { margin-top:20px; padding:10px 18px; background:linear-gradient(135deg,#c70000,#ff4d4d); color:#fff; border:none; border-radius:10px; font-weight:600; cursor:pointer; transition:0.3s; }
.profile-card button:hover { opacity:0.9; }
.message { margin-bottom:15px; font-weight:600; color:green; }
.error { margin-bottom:15px; font-weight:600; color:red; }
.note { margin-top:45px; font-size:14px; color:#888; text-align:center; }

@media(max-width:900px) { .container { flex-direction:column; } .sidebar { width:100%; border-radius:0 0 20px 20px; height:auto; } .main { padding:25px 20px; } }
</style>
</head>
<body>

<div class="navbar">
    <h2>🛠️ TestHub CU – Admin Panel</h2>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <div class="sidebar">
        <a href="index.php">🏠 Dashboard</a>
        <a href="manage_students.php">👨‍🎓 Manage Students</a>
        <a href="manage_teachers.php">👩‍🏫Manage Teachers</a>

        <a href="view_results.php">📊 View Results</a>
        <a href="profile.php" class="active">👤 My Profile</a>
    </div>

    <div class="main">
        <h2>My Profile</h2>
        <div class="welcome">Update your personal information</div>

        <div class="profile-card">
            <?php if($successMessage) echo "<div class='message'>$successMessage</div>"; ?>
            <?php if($errorMessage) echo "<div class='error'>$errorMessage</div>"; ?>

            <h3>Profile Details</h3>
            <form method="POST" action="">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>

                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>

                <label for="password">New Password <small>(leave blank to keep current)</small></label>
                <input type="password" name="password" id="password">

                <button type="submit" name="update_profile">Update Profile</button>
            </form>
        </div>

        <div class="note">
            TestHub CU © <?php echo date("Y"); ?> • Online Examination System
        </div>
    </div>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
