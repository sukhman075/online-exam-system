<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$studentName = $_SESSION['student'] ?? 'Student';
$studentId   = $_SESSION['student_id'];
$exam_id     = intval($_GET['exam_id']);

$stmt = $conn->prepare("
    SELECT r.id as result_id, r.score, e.title, e.total_marks
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.user_id = ? AND r.exam_id = ?
    ORDER BY r.id DESC LIMIT 1
");
$stmt->bind_param("ii", $studentId, $exam_id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

if(!$data){
    die("Result not found.");
}

$percentage = ($data['score'] / $data['total_marks']) * 100;
$status = ($percentage >= 33) ? "PASS" : "FAIL";
$status_color = ($status == "PASS") ? "#10b981" : "#c70000";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Summary | <?php echo htmlspecialchars($data['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    
    <style>
        :root {
            --primary: #c70000;
            --sidebar-bg: #1e293b;
            --bg-soft: #f8fafc;
            --text-main: #0f172a; /* Darker for better visibility */
            --text-muted: #64748b;
            --glass: rgba(255, 255, 255, 0.98);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-soft);
            background-image: 
                radial-gradient(at 0% 0%, rgba(199, 0, 0, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(199, 0, 0, 0.08) 0px, transparent 50%);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
            color: var(--text-main);
        }

        /* Fixed Visibility & Layering */
        .result-container {
            width: 90%;
            max-width: 480px;
            position: relative;
            z-index: 10;
        }

        .glass-card {
            background: var(--glass);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 40px;
            padding: 50px 35px;
            text-align: center;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.1);
            animation: revealUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes revealUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-header {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted);
            margin-bottom: 25px;
        }

        .exam-info h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 5px;
        }

        /* Enhanced Score Circle */
        .score-circle {
            width: 180px;
            height: 180px;
            margin: 35px auto;
            border-radius: 50%;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border: 8px solid #f1f5f9;
            position: relative;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.05);
        }

        .percentage-text {
            font-size: 56px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 25px;
            border-radius: 100px;
            font-weight: 800;
            font-size: 14px;
            background: <?php echo $status_color; ?>;
            color: white;
            margin-bottom: 35px;
            box-shadow: 0 10px 20px <?php echo $status_color; ?>40;
        }

        /* Visible Action Buttons */
        .action-btns {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn {
            padding: 18px;
            border-radius: 18px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary { 
            background: var(--primary); 
            color: white !important; /* Force visibility */
        }
        
        .btn-primary:hover {
            background: #a00000;
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(199, 0, 0, 0.3);
        }

        .btn-outline {
            background: #ffffff;
            color: var(--text-main);
            border: 1px solid #e2e8f0;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #fffcfc;
        }

        .footer-text {
            margin-top: 35px;
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .anim-item { opacity: 0; animation: revealUp 0.6s ease forwards; }
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.4s; }
    </style>
</head>
<body>

<div class="result-container">
    <div class="glass-card">
        <div class="brand-header anim-item">
            <i class="fa-solid fa-graduation-cap"></i> TestHub<span style="color:var(--primary)">CU</span>
        </div>

        <div class="exam-info anim-item delay-1">
            <p style="color:var(--primary); font-weight:800; font-size:12px; margin-bottom:10px; text-transform:uppercase;">Assessment Result</p>
            <h1><?php echo htmlspecialchars($data['title']); ?></h1>
            <p style="color:var(--text-muted); font-size:14px;">Academic Record for <strong><?php echo htmlspecialchars($studentName); ?></strong></p>
        </div>

        <div class="score-circle anim-item delay-2">
            <span class="percentage-text" id="counter">0%</span>
            <span style="font-size:12px; color:var(--text-muted); font-weight:700; letter-spacing:1px;">TOTAL SCORE</span>
        </div>

        <div class="status-badge anim-item delay-2">
            <i class="fas <?php echo ($status == 'PASS') ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
            RESULT: <?php echo $status; ?>
        </div>

        <div class="action-btns anim-item delay-2">
            <?php if($status=="PASS"): ?>
                <a href="certificate_preview.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-primary">
                    <i class="fas fa-award"></i> Download Certificate
                </a>
            <?php endif; ?>

            <a href="review_ajax.php?exam_id=<?php echo $exam_id; ?>&result_id=<?php echo $data['result_id']; ?>" class="btn btn-outline">
                <i class="fas fa-magnifying-glass"></i> Review Performance
            </a>

            <a href="index.php" class="btn" style="color:var(--text-muted); font-size:14px;">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>
        </div>

        <div class="footer-text anim-item delay-2">
            REF ID: #<?php echo str_pad($data['result_id'], 6, '0', STR_PAD_LEFT); ?> • Digital Signature Verified
        </div>
    </div>
</div>

<script>
    // 1. Counter Animation
    const targetScore = <?php echo round($percentage); ?>;
    const counterElement = document.getElementById('counter');
    let current = 0;
    
    const animateCounter = () => {
        if (current < targetScore) {
            current++;
            counterElement.innerText = current + "%";
            requestAnimationFrame(animateCounter);
        } else {
            counterElement.innerText = targetScore + "%";
            // 2. Trigger Celebration if score > 80
            if (targetScore >= 80) {
                triggerCelebration();
            }
        }
    };

    function triggerCelebration() {
        const duration = 3 * 1000;
        const end = Date.now() + duration;

        (function frame() {
            confetti({
                particleCount: 3,
                angle: 60,
                spread: 55,
                origin: { x: 0 },
                colors: ['#c70000', '#000000', '#ffffff']
            });
            confetti({
                particleCount: 3,
                angle: 120,
                spread: 55,
                origin: { x: 1 },
                colors: ['#c70000', '#000000', '#ffffff']
            });

            if (Date.now() < end) {
                requestAnimationFrame(frame);
            }
        }());
    }

    setTimeout(animateCounter, 800);
</script>

</body>
</html>