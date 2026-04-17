<?php
session_start();
if (!isset($_SESSION['student'])) {
    header("Location: login.php");
    exit();
}
$studentName = $_SESSION['student'];
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | TestHub CU</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #c70000;
            --primary-dark: #a00000;
            --accent: #ff4d4d;
            --bg-soft: #f8fafc;
            --sidebar-bg: #1e293b;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --glass: rgba(255, 255, 255, 0.95);
            --shadow: 0 10px 40px rgba(0,0,0,0.06);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background-color: var(--bg-soft);
            background-image: 
                radial-gradient(at 0% 0%, rgba(199, 0, 0, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(199, 0, 0, 0.05) 0px, transparent 50%);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* ================= LAYOUT ================= */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: white;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .brand {
            padding: 0 15px 40px;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
        }

        .brand span { color: var(--accent); }

        .sidebar-nav { flex-grow: 1; }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            text-decoration: none;
            color: #94a3b8;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar a i { font-size: 18px; }

        .sidebar a:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }

        .sidebar a.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 20px rgba(199, 0, 0, 0.3);
        }

        /* ================= TOPBAR ================= */
        .content-area {
            flex: 1;
            margin-left: 280px;
            padding: 0 40px 40px;
        }

        .topbar {
            height: 90px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 8px 20px 8px 8px;
            border-radius: 50px;
            box-shadow: var(--shadow);
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .logout-btn {
            background: #fee2e2;
            color: #ef4444;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s;
        }

        .logout-btn:hover { background: #fca5a5; }

        /* ================= DASHBOARD CONTENT ================= */
        .welcome-section {
            background: linear-gradient(135deg, var(--sidebar-bg), #0f172a);
            padding: 40px;
            border-radius: 30px;
            color: white;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::after {
            content: '🎓';
            position: absolute;
            right: 40px;
            bottom: -10px;
            font-size: 120px;
            opacity: 0.1;
        }

        .welcome-section h1 { font-size: 32px; margin-bottom: 8px; }
        .welcome-section p { color: #94a3b8; font-size: 16px; }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .glass-card {
            background: var(--glass);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 35px;
            border-radius: 24px;
            box-shadow: var(--shadow);
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .glass-card:hover {
            transform: translateY(-12px);
            border-color: var(--primary);
        }

        .icon-box {
            width: 55px;
            height: 55px;
            background: #fff5f5;
            color: var(--primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .glass-card h3 { font-size: 20px; font-weight: 700; }
        .glass-card p { color: var(--text-muted); font-size: 14px; line-height: 1.6; }

        .card-link {
            margin-top: auto;
            text-decoration: none;
            color: var(--primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: gap 0.3s;
        }

        .card-link:hover { gap: 15px; }

        .footer {
            margin-top: 60px;
            padding: 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 30px 10px; }
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
            <a href="index.php" class="<?php echo ($currentPage=='index.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> <span>Dashboard</span>
            </a>
            <a href="available_exams.php" class="<?php echo ($currentPage=='available_exams.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-pen"></i> <span>Available Exams</span>
            </a>
            <a href="results.php" class="<?php echo ($currentPage=='results.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-simple"></i> <span>My Results</span>
            </a>
            <a href="profile.php" class="<?php echo ($currentPage=='profile.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-gear"></i> <span>Profile Settings</span>
            </a>
            <a href="instructions.php" class="<?php echo ($currentPage=='instructions.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-circle-info"></i> <span>Instructions</span>
            </a>
        </nav>

        <a href="logout.php" class="logout-btn" style="margin-top: auto; text-align: center; display: block;">
            <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
        </a>
    </aside>

    <main class="content-area">
        <header class="topbar">
            <div>
                <span style="color: var(--text-muted); font-weight: 500;">Academic Session 2024-25</span>
            </div>
            <div class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($studentName, 0, 1)); ?></div>
                <span style="font-weight: 600;"><?php echo htmlspecialchars($studentName); ?></span>
            </div>
        </header>

        <section class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($studentName); ?>!</h1>
            <p>You have 2 exams scheduled for this week. Stay prepared!</p>
        </section>

        <div class="grid-container">
            <div class="glass-card">
                <div class="icon-box"><i class="fa-solid fa-calendar-check"></i></div>
                <h3>Active Exams</h3>
                <p>Check your portal for any ongoing or upcoming live examinations.</p>
                <a href="available_exams.php" class="card-link">Start Testing <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="glass-card">
                <div class="icon-box"><i class="fa-solid fa-bolt"></i></div>
                <h3>Quick Results</h3>
                <p>Instantly view your performance metrics and download certificates.</p>
                <a href="results.php" class="card-link">View Analytics <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="glass-card">
                <div class="icon-box"><i class="fa-solid fa-shield-halved"></i></div>
                <h3>Account Security</h3>
                <p>Update your password and manage multi-factor authentication settings.</p>
                <a href="profile.php" class="card-link">Manage Profile <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="glass-card">
                <div class="icon-box"><i class="fa-solid fa-book-open"></i></div>
                <h3>Exam Guide</h3>
                <p>Review the proctoring rules and technical requirements for tests.</p>
                <a href="instructions.php" class="card-link">Read Manual <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>

        <footer class="footer">
            <p>TestHub CU &copy; <?php echo date("Y"); ?> | Built for Excellence</p>
        </footer>
    </main>
</div>

</body>
</html>