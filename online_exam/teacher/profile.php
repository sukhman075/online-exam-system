<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

$teacherId = $_SESSION['teacher_id']; 
$teacherName = $_SESSION['teacher']; 
$currentPage = basename($_SERVER['PHP_SELF']);

// Fetch teacher info
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id=? AND role='teacher'");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Teacher not found!");
}

$teacher = $result->fetch_assoc();

$successMessage = "";
$errorMessage = "";

// Handle profile update
if (isset($_POST['update_profile'])) {
    $newName = trim($_POST['name']);
    $newEmail = trim($_POST['email']);
    $newPasswordInput = $_POST['password'];

    if (!empty($newPasswordInput)) {
        // Update with Password
        $hashedPassword = password_hash($newPasswordInput, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=?");
        $upd->bind_param("sssi", $newName, $newEmail, $hashedPassword, $teacherId);
    } else {
        // Update without Password
        $upd = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
        $upd->bind_param("ssi", $newName, $newEmail, $teacherId);
    }

    if ($upd->execute()) {
        $_SESSION['teacher'] = $newName; 
        $teacher['name'] = $newName;
        $teacher['email'] = $newEmail;
        $successMessage = "Profile updated successfully!";
    } else {
        $errorMessage = "Failed to update profile. Email might already be in use.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | TestHub CU</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #c70000; --sidebar-bg: #1e293b; --bg-soft: #f4f6fb; --shadow: 0 10px 40px rgba(0,0,0,0.06); }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-soft); color: #1e293b; }

        /* NAVBAR */
        .navbar {
            height: 75px; background: #fff; display: flex; align-items: center; 
            justify-content: space-between; padding: 0 40px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); position: sticky; top: 0; z-index: 1000;
        }
        .navbar h2 { font-size: 20px; color: var(--primary); font-weight: 800; }
        .logout-btn { background: #f1f5f9; color: #ef4444; text-decoration: none; padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 14px; transition: 0.3s; }

        .container { display: flex; min-height: calc(100vh - 75px); }

        /* SIDEBAR */
        .sidebar { width: 260px; background: var(--sidebar-bg); padding: 40px 20px; color: #fff; position: sticky; top: 75px; height: calc(100vh - 75px); }
        .sidebar a {
            display: flex; align-items: center; gap: 12px; padding: 14px 18px;
            text-decoration: none; color: #94a3b8; border-radius: 12px; margin-bottom: 8px; transition: 0.3s;
        }
        .sidebar a.active { background: var(--primary); color: white; }
        .sidebar a:hover:not(.active) { background: rgba(255,255,255,0.05); color: #fff; }

        /* MAIN CONTENT */
        .main { flex: 1; padding: 40px; }
        .header-box { margin-bottom: 35px; }
        .header-box h2 { font-size: 28px; font-weight: 800; }
        .header-box p { color: #64748b; }

        /* PROFILE CARD */
        .profile-card { 
            background: #fff; padding: 40px; border-radius: 24px; 
            box-shadow: var(--shadow); max-width: 700px; 
            border: 1px solid rgba(0,0,0,0.02);
        }
        .profile-card h3 { font-size: 20px; font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .profile-card h3 i { color: var(--primary); }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; }
        .form-group input { 
            width: 100%; padding: 14px 18px; border-radius: 12px; 
            border: 1px solid #e2e8f0; font-size: 15px; transition: 0.3s; outline: none; 
        }
        .form-group input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(199,0,0,0.05); }

        .btn-update { 
            background: var(--primary); color: white; border: none; 
            padding: 16px 30px; border-radius: 12px; font-weight: 700; 
            cursor: pointer; transition: 0.3s; width: 100%; font-size: 16px;
        }
        .btn-update:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(199,0,0,0.2); }

        .msg { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
        .msg-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bcf0da; }
        .msg-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        @media (max-width: 900px) { .sidebar { display: none; } .main { padding: 20px; } }
    </style>
</head>
<body>

<div class="navbar">
    <h2>TestHub<span>CU</span></h2>
    <div style="display: flex; align-items: center; gap: 20px;">
        <span style="font-weight: 600; font-size: 14px;"><i class="fa-solid fa-circle-user"></i> Admin Mode</span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <aside class="sidebar">
        <nav>
            <a href="index.php" class="<?php if($currentPage=='index.php') echo 'active'; ?>"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="create_exam.php" class="<?php if($currentPage=='create_exam.php') echo 'active'; ?>"><i class="fa-solid fa-file-circle-plus"></i> Create Exam</a>
            <a href="add_questions.php" class="<?php if($currentPage=='add_questions.php') echo 'active'; ?>"><i class="fa-solid fa-list-check"></i> Add Questions</a>
            <a href="results.php" class="<?php if($currentPage=='results.php') echo 'active'; ?>"><i class="fa-solid fa-chart-line"></i> Exam Results</a>
            <a href="profile.php" class="<?php if($currentPage=='profile.php') echo 'active'; ?>"><i class="fa-solid fa-gears"></i> My Profile</a>
        </nav>
    </aside>

    <main class="main">
        <div class="header-box">
            <h2>Account Settings</h2>
            <p>Update your credentials and personal information.</p>
        </div>

        <div class="profile-card">
            <?php if($successMessage): ?>
                <div class="msg msg-success"><i class="fa-solid fa-circle-check"></i> <?php echo $successMessage; ?></div>
            <?php endif; ?>
            <?php if($errorMessage): ?>
                <div class="msg msg-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <h3><i class="fa-solid fa-user-pen"></i> Profile Details</h3>
            
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($teacher['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>New Password <span style="font-weight:400; text-transform:none; color:#94a3b8;">(Leave blank to keep current)</span></label>
                    <input type="password" name="password" placeholder="••••••••">
                </div>

                <button type="submit" name="update_profile" class="btn-update">Save Profile Changes</button>
            </form>
        </div>

        <p style="margin-top: 40px; color: #94a3b8; font-size: 13px; text-align: center;">
            TestHub CU &copy; <?php echo date("Y"); ?> • Security encrypted session
        </p>
    </main>
</div>

</body>
</html>