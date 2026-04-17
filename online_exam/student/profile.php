<?php
session_start();
include "../db/connection.php";

// 1. PROTECT PAGE
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$studentName = $_SESSION['student'];
$currentPage = basename($_SERVER['PHP_SELF']);

// 2. FETCH STUDENT INFO
$stmt = $conn->prepare("SELECT name, email, password FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    header("Location: login.php?error=account_not_found");
    exit();
}

// 3. HANDLE PASSWORD UPDATE
$message = "";
$messageType = "";

if (isset($_POST['update_password'])) {
    $currentPass = $_POST['current_password'];
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    if (!password_verify($currentPass, $student['password'])) {
        $message = "Current password is incorrect";
        $messageType = "error";
    } elseif (strlen($newPass) < 6) {
        $message = "New password must be at least 6 characters";
        $messageType = "error";
    } elseif ($newPass !== $confirmPass) {
        $message = "New passwords do not match";
        $messageType = "error";
    } else {
        $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $updateStmt->bind_param("si", $hashedPass, $student_id);
        
        if ($updateStmt->execute()) {
            $_SESSION['msg'] = "Password updated successfully!";
            $_SESSION['msg_type'] = "success";
            header("Location: profile.php"); 
            exit();
        } else {
            $message = "System error. Please try again.";
            $messageType = "error";
        }
        $updateStmt->close();
    }
}

if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    $messageType = $_SESSION['msg_type'];
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | TestHub CU</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #c70000;
            --primary-hover: #a00000;
            --bg-soft: #f8fafc;
            --sidebar-bg: #1e293b;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --shadow: 0 10px 40px rgba(0,0,0,0.06);
            --success: #059669;
            --error: #ef4444;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-soft); color: var(--text-main); overflow-x: hidden; }
        .app-container { display: flex; min-height: 100vh; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 280px; background: var(--sidebar-bg); color: white;
            padding: 30px 20px; display: flex; flex-direction: column;
            position: fixed; height: 100vh; z-index: 100;
        }
        .brand { padding: 0 15px 40px; font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
        .brand span span { color: #ff4d4d; }
        .sidebar-nav { flex-grow: 1; }
        .sidebar a {
            display: flex; align-items: center; gap: 12px; padding: 14px 18px;
            text-decoration: none; color: #94a3b8; border-radius: 12px;
            margin-bottom: 8px; transition: 0.3s;
        }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: white; box-shadow: 0 10px 20px rgba(199, 0, 0, 0.3); }

        /* --- MAIN CONTENT --- */
        .content-area { flex: 1; margin-left: 280px; padding: 0 40px 40px; }
        .topbar {
            height: 90px; display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 20px;
        }
        .user-profile {
            display: flex; align-items: center; gap: 12px; background: white;
            padding: 8px 15px; border-radius: 50px; box-shadow: var(--shadow);
        }
        .avatar {
            width: 35px; height: 35px; background: var(--primary); color: white;
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-weight: bold; font-size: 14px;
        }

        /* --- PROFILE CARDS --- */
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card {
            background: white; border-radius: 24px; padding: 30px;
            box-shadow: var(--shadow); border: 1px solid rgba(0,0,0,0.03);
        }
        .card-header { margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
        .card-header i { color: var(--primary); font-size: 20px; }
        .card-header h2 { font-size: 20px; font-weight: 700; }

        .info-group { margin-bottom: 20px; }
        .info-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .info-value { font-size: 16px; font-weight: 600; color: var(--sidebar-bg); margin-top: 4px; }

        /* --- FORM ELEMENTS --- */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-muted); }
        .form-control {
            width: 100%; padding: 12px 15px; border-radius: 12px;
            border: 1px solid #e2e8f0; outline: none; transition: 0.3s;
            font-size: 14px;
        }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(199, 0, 0, 0.1); }

        .btn-update {
            width: 100%; background: var(--primary); color: white; border: none;
            padding: 14px; border-radius: 12px; font-weight: 700; font-size: 14px;
            cursor: pointer; transition: 0.3s; margin-top: 10px;
        }
        .btn-update:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(199, 0, 0, 0.2); }

        /* --- ALERTS --- */
        .alert {
            padding: 15px 20px; border-radius: 12px; margin-bottom: 25px;
            font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background: #ecfdf5; color: var(--success); border: 1px solid #d1fae5; }
        .alert-error { background: #fef2f2; color: var(--error); border: 1px solid #fee2e2; }

        .logout-btn-side { margin-top: auto; color: #ef4444; text-decoration: none; font-weight: 600; text-align: center; padding: 15px; background: #fee2e2; border-radius: 12px; transition: 0.3s; }
        .logout-btn-side:hover { background: #fca5a5; }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar .brand span, .sidebar a span { display: none; }
            .content-area { margin-left: 80px; }
            .profile-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="app-container">
    <aside class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>TestHub<span>CU</span></span>
        </div>
        
        <nav class="sidebar-nav">
            <a href="index.php">
                <i class="fa-solid fa-house"></i> <span>Dashboard</span>
            </a>
            <a href="available_exams.php">
                <i class="fa-solid fa-file-pen"></i> <span>Available Exams</span>
            </a>
            <a href="results.php">
                <i class="fa-solid fa-chart-simple"></i> <span>My Results</span>
            </a>
            <a href="profile.php" class="active">
                <i class="fa-solid fa-user-gear"></i> <span>Profile Settings</span>
            </a>
            <a href="instructions.php">
                <i class="fa-solid fa-circle-info"></i> <span>Instructions</span>
            </a>
        </nav>

        <a href="logout.php" class="logout-btn-side">
            <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
        </a>
    </aside>

    <main class="content-area">
        <header class="topbar">
            <div style="color: var(--text-muted); font-size: 14px; font-weight: 500;">
                <i class="fa-regular fa-calendar"></i> <?php echo date("l, d M Y"); ?>
            </div>
            <div class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($studentName, 0, 1)); ?></div>
                <span style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($studentName); ?></span>
            </div>
        </header>

        <?php if($message): ?>
            <div class="alert alert-<?php echo ($messageType == 'success') ? 'success' : 'error'; ?>" id="alert-box">
                <i class="fa-solid <?php echo ($messageType == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-id-card"></i>
                    <h2>Personal Information</h2>
                </div>
                
                <div class="info-group">
                    <div class="info-label">Student Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['name']); ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">Registration ID</div>
                    <div class="info-value">#STU-<?php echo str_pad($student_id, 4, '0', STR_PAD_LEFT); ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">Account Status</div>
                    <div class="info-value" style="color: #059669;"><i class="fa-solid fa-circle-check"></i> Verified Student</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-shield-halved"></i>
                    <h2>Security Settings</h2>
                </div>
                
                <form method="POST" action="profile.php">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-type new password" required>
                    </div>

                    <button type="submit" name="update_password" class="btn-update">
                        Update Password <i class="fa-solid fa-arrow-right-long" style="margin-left:8px;"></i>
                    </button>
                </form>
            </div>
        </div>

        <p style="margin-top: 30px; text-align: center; color: var(--text-muted); font-size: 13px;">
            TestHub CU &copy; <?php echo date("Y"); ?> • Student Account Security Management
        </p>
    </main>
</div>

<script>
    // Auto-hide alert after 4 seconds
    const alertBox = document.getElementById('alert-box');
    if(alertBox){
        setTimeout(() => {
            alertBox.style.transition = '0.5s';
            alertBox.style.opacity = '0';
            setTimeout(() => alertBox.remove(), 500);
        }, 4000);
    }
</script>

</body>
</html>