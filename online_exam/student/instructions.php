<?php
session_start();
include "../db/connection.php";

// Protect page
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$studentName = $_SESSION['student'];
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Instructions | TestHub CU</title>
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

        /* --- INSTRUCTIONS CARD --- */
        .card {
            background: white; border-radius: 24px; padding: 40px;
            box-shadow: var(--shadow); border: 1px solid rgba(0,0,0,0.03);
        }
        .card-header { margin-bottom: 30px; }
        .card-header h2 { font-size: 24px; font-weight: 700; color: var(--sidebar-bg); }
        .card-header p { color: var(--text-muted); font-size: 15px; margin-top: 5px; }

        .instruction-list { list-style: none; }
        .instruction-item {
            display: flex; align-items: flex-start; gap: 15px;
            padding: 18px; border-radius: 16px; transition: 0.3s;
            margin-bottom: 10px; border: 1px solid transparent;
        }
        .instruction-item:hover { background: #fdf2f2; border-color: #fee2e2; }
        
        .icon-box {
            min-width: 40px; height: 40px; background: #fff1f1;
            color: var(--primary); border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }

        .instruction-text { line-height: 1.6; }
        .instruction-text b { color: var(--sidebar-bg); font-weight: 600; }
        .instruction-text p { font-size: 14px; color: var(--text-muted); margin-top: 2px; }

        .logout-btn-side { margin-top: auto; color: #ef4444; text-decoration: none; font-weight: 600; text-align: center; padding: 15px; background: #fee2e2; border-radius: 12px; transition: 0.3s; }
        .logout-btn-side:hover { background: #fca5a5; }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar .brand span, .sidebar a span { display: none; }
            .content-area { margin-left: 80px; }
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
            <a href="profile.php">
                <i class="fa-solid fa-user-gear"></i> <span>Profile Settings</span>
            </a>
            <a href="instructions.php" class="active">
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

        <div class="card">
            <div class="card-header">
                <h2>General Guidelines</h2>
                <p>Ensure you follow these rules to maintain exam integrity.</p>
            </div>

            <div class="instruction-list">
                <div class="instruction-item">
                    <div class="icon-box"><i class="fa-solid fa-stopwatch"></i></div>
                    <div class="instruction-text">
                        <b>Time Management</b>
                        <p>Each exam has a fixed time limit. The countdown begins immediately when you click <b>Begin Exam</b>.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="icon-box"><i class="fa-solid fa-window-restore"></i></div>
                    <div class="instruction-text">
                        <b>Browser Restrictions</b>
                        <p>Do not refresh the page or close the browser tab. Switching tabs may trigger an <b>auto-submission</b>.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="icon-box"><i class="fa-solid fa-mobile-screen-button"></i></div>
                    <div class="instruction-text">
                        <b>Electronic Devices</b>
                        <p>Mobile phones, smartwatches, or secondary screens are strictly prohibited during the session.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="icon-box"><i class="fa-solid fa-user-shield"></i></div>
                    <div class="instruction-text">
                        <b>Identity Verification</b>
                        <p>Using multiple devices or sharing credentials will lead to immediate <b>disqualification</b>.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="icon-box"><i class="fa-solid fa-list-check"></i></div>
                    <div class="instruction-text">
                        <b>Mandatory Questions</b>
                        <p>All questions are compulsory unless stated otherwise in the specific exam title.</p>
                    </div>
                </div>

                <div class="instruction-item" style="background: #fef2f2; border: 1px dashed #f87171;">
                    <div class="icon-box" style="background: #ef4444; color: #fff;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="instruction-text">
                        <b style="color: #991b1b;">Zero Tolerance Policy</b>
                        <p style="color: #b91c1c;">Any detected unfair means or suspicious activity will result in an instant block from the system.</p>
                    </div>
                </div>
            </div>
        </div>

        <p style="margin-top: 30px; text-align: center; color: var(--text-muted); font-size: 13px;">
            TestHub CU &copy; <?php echo date("Y"); ?> • Innovation in Education
        </p>
    </main>
</div>

</body>
</html>