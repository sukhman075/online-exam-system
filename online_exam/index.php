<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TestHub CU | Next-Gen Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #c70000;
            --primary-dark: #8b0000;
            --secondary: #0f172a;
            --accent: #ffd700;
            --glass: rgba(255, 255, 255, 0.85);
            --shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0b0f19; /* Deep space background */
            overflow: hidden;
        }

        /* Animated Background Elements */
        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(199, 0, 0, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            z-index: -1;
            filter: blur(80px);
            animation: float 20s infinite alternate;
        }

        @keyframes float {
            0% { transform: translate(-10%, -10%); }
            100% { transform: translate(20%, 20%); }
        }

        /* Main Container */
        .master-wrapper {
            width: 95%;
            max-width: 1300px;
            height: 88vh;
            background: var(--glass);
            backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 40px;
            display: flex;
            box-shadow: var(--shadow);
            overflow: hidden;
            position: relative;
        }

        /* LEFT PANEL: The Experience */
        .left-hero {
            width: 45%;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 70px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .left-hero::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('https://www.transparenttextures.com/patterns/carbon-fibre.png');
            opacity: 0.1;
        }

        .brand-box h1 {
            font-size: 52px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            margin-bottom: 20px;
        }

        .brand-box h1 span { color: var(--primary); }

        .brand-box p {
            font-size: 18px;
            color: #94a3b8;
            line-height: 1.6;
            font-weight: 400;
        }

        .feature-grid {
            display: grid;
            gap: 20px;
            margin: 40px 0;
        }

        .f-item {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 15px;
            color: #f8fafc;
            background: rgba(255,255,255,0.05);
            padding: 15px;
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .f-item i { color: var(--primary); font-size: 18px; }

        /* RIGHT PANEL: The Portal */
        .right-portal {
            width: 55%;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
        }

        .portal-header { margin-bottom: 45px; }
        .portal-header h2 { font-size: 38px; font-weight: 700; color: var(--secondary); letter-spacing: -1px; }
        .portal-header p { color: #64748b; font-weight: 500; }

        /* CARDS */
        .role-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .role-card {
            background: white;
            padding: 40px 20px;
            border-radius: 32px;
            text-align: center;
            text-decoration: none;
            border: 1px solid #f1f5f9;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .role-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }

        .icon-wrap {
            width: 75px;
            height: 75px;
            background: #f8fafc;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 20px;
            transition: 0.3s;
            color: var(--secondary);
        }

        .role-card:hover .icon-wrap {
            background: var(--primary);
            color: white;
            transform: rotate(-10deg);
        }

        .role-card h3 { font-size: 18px; color: var(--secondary); margin-bottom: 10px; }
        .role-card p { font-size: 13px; color: #64748b; line-height: 1.4; margin-bottom: 20px; }

        .go-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary);
            transition: 0.3s;
        }

        .role-card:hover .go-btn {
            background: var(--primary);
            color: white;
            width: 100%;
            border-radius: 12px;
        }

        footer {
            margin-top: 50px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }

        @media (max-width: 1100px) {
            .master-wrapper { flex-direction: column; height: auto; }
            .left-hero, .right-portal { width: 100%; padding: 40px; }
            .role-cards { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="blob"></div>

<div class="master-wrapper">
    <div class="left-hero">
        <div class="brand-box">
            <h1>TestHub<span>CU.</span></h1>
            <p>Intelligence-driven examination ecosystem for the leaders of tomorrow.</p>
        </div>

        <div class="feature-grid">
            <div class="f-item">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Proctoring AI & Fraud Detection</span>
            </div>
            <div class="f-item">
                <i class="fa-solid fa-bolt"></i>
                <span>Instant Adaptive Evaluation</span>
            </div>
            <div class="f-item">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Deep Learning Performance Reports</span>
            </div>
        </div>

        <div style="font-size: 12px; opacity: 0.5; letter-spacing: 2px;">
            PLATFORM v4.0 • 2026 EDITION
        </div>
    </div>

    <div class="right-portal">
        <div class="portal-header">
            <h2>Welcome Back</h2>
            <p>Identity verification required. Choose your gateway.</p>
        </div>

        <div class="role-cards">
            <a href="student/login.php" class="role-card">
                <div class="icon-wrap"><i class="fa-solid fa-user-graduate"></i></div>
                <h3>Student</h3>
                <p>Attend assessments & track your growth.</p>
                <div class="go-btn"><i class="fa-solid fa-arrow-right"></i></div>
            </a>

            <a href="teacher/login.php" class="role-card">
                <div class="icon-wrap"><i class="fa-solid fa-chalkboard-user"></i></div>
                <h3>Faculty</h3>
                <p>Design curriculum & evaluate students.</p>
                <div class="go-btn"><i class="fa-solid fa-arrow-right"></i></div>
            </a>

            <a href="admin/login.php" class="role-card">
                <div class="icon-wrap"><i class="fa-solid fa-user-shield"></i></div>
                <h3>Admin</h3>
                <p>System control & high-level oversight.</p>
                <div class="go-btn"><i class="fa-solid fa-arrow-right"></i></div>
            </a>
        </div>

        <footer>
            <i class="fa-solid fa-lock"></i> SSL Encrypted Portal • Chandigarh University
        </footer>
    </div>
</div>

</body>
</html>