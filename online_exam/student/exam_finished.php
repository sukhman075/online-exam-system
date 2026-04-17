<?php
session_start();
include "../db/connection.php";

if(!isset($_SESSION['student_id'])){
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$studentName = $_SESSION['student'];
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$currentPage = 'results.php';

// Fetch specific result
$stmt = $conn->prepare("SELECT r.score, e.total_marks, e.title, e.duration FROM results r JOIN exams e ON r.exam_id=e.id WHERE r.user_id=? AND r.exam_id=?");
$stmt->bind_param("ii", $student_id, $exam_id);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows == 0){
    header("Location: results.php");
    exit();
}
$row = $res->fetch_assoc();

// Calculate Percentage for the UI
$percentage = ($row['score'] / $row['total_marks']) * 100;
$status_color = ($percentage >= 40) ? '#059669' : '#ef4444'; // Green if pass, Red if fail
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Result | TestHub CU</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #c70000;
            --sidebar-bg: #1e293b;
            --bg-soft: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --shadow: 0 10px 40px rgba(0,0,0,0.06);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-soft); color: var(--text-main); }
        .app-container { display: flex; min-height: 100vh; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 280px; background: var(--sidebar-bg); color: white;
            padding: 30px 20px; display: flex; flex-direction: column;
            position: fixed; height: 100vh;
        }
        .brand { padding: 0 15px 40px; font-size: 22px; font-weight: 700; }
        .brand span { color: #ff4d4d; }
        .sidebar a {
            display: flex; align-items: center; gap: 12px; padding: 14px 18px;
            text-decoration: none; color: #94a3b8; border-radius: 12px; margin-bottom: 8px; transition: 0.3s;
        }
        .sidebar a.active { background: var(--primary); color: white; }

        /* --- MAIN CONTENT --- */
        .content-area { flex: 1; margin-left: 280px; padding: 40px; display: flex; flex-direction: column; align-items: center; }
        
        .result-card {
            background: white; width: 100%; max-width: 600px;
            border-radius: 30px; padding: 50px; text-align: center;
            box-shadow: var(--shadow); border: 1px solid rgba(0,0,0,0.02);
            margin-top: 20px;
        }

        /* --- SCORE VISUAL --- */
        .score-circle {
            width: 180px; height: 180px; border-radius: 50%;
            border: 10px solid #f1f5f9; margin: 0 auto 30px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            border-top-color: <?php echo $status_color; ?>; /* Dynamic color */
            transform: rotate(-45deg);
        }
        .score-content { transform: rotate(45deg); }
        .score-big { font-size: 48px; font-weight: 800; color: var(--sidebar-bg); display: block; }
        .score-total { font-size: 16px; color: var(--text-muted); font-weight: 600; }

        .congrats-text { font-size: 24px; font-weight: 700; margin-bottom: 10px; }
        .exam-title { color: var(--text-muted); margin-bottom: 30px; font-size: 15px; }

        /* --- STATS GRID --- */
        .stats-grid { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px; 
            background: #f8fafc; padding: 25px; border-radius: 20px; margin-bottom: 35px;
        }
        .stat-item span { display: block; font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 5px; }
        .stat-item strong { font-size: 16px; color: var(--sidebar-bg); }

        .btn-home {
            background: var(--sidebar-bg); color: white; padding: 16px 35px;
            border-radius: 15px; text-decoration: none; font-weight: 700;
            display: inline-flex; align-items: center; gap: 10px; transition: 0.3s;
        }
        .btn-home:hover { background: #000; transform: translateY(-3px); }

        @media (max-width: 900px) {
            .sidebar { display: none; }
            .content-area { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="app-container">
    <aside class="sidebar">
        <div class="brand"><i class="fa-solid fa-graduation-cap"></i> TestHub<span>CU</span></div>
        <nav>
            <a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="available_exams.php"><i class="fa-solid fa-file-pen"></i> Available Exams</a>
            <a href="results.php" class="active"><i class="fa-solid fa-chart-simple"></i> My Results</a>
            <a href="profile.php"><i class="fa-solid fa-user-gear"></i> Profile Settings</a>
        </nav>
    </aside>

    <main class="content-area">
        <div class="result-card">
            <div class="score-circle">
                <div class="score-content">
                    <span class="score-big"><?php echo round($percentage); ?>%</span>
                    <span class="score-total">Score Percentage</span>
                </div>
            </div>

            <h2 class="congrats-text">
                <?php echo ($percentage >= 40) ? "Great Job, " : "Keep Practicing, "; ?> 
                <?php echo htmlspecialchars($studentName); ?>! 🎉
            </h2>
            <p class="exam-title">Performance summary for: <strong><?php echo htmlspecialchars($row['title']); ?></strong></p>

            <div class="stats-grid">
                <div class="stat-item">
                    <span>Marks Obtained</span>
                    <strong><?php echo $row['score']; ?> / <?php echo $row['total_marks']; ?></strong>
                </div>
                <div class="stat-item">
                    <span>Status</span>
                    <strong style="color: <?php echo $status_color; ?>">
                        <?php echo ($percentage >= 40) ? "PASSED" : "FAILED"; ?>
                    </strong>
                </div>
            </div>

            <a href="index.php" class="btn-home">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
            
            <p style="margin-top: 30px; font-size: 12px; color: #cbd5e1;">
                Transcript generated on <?php echo date("d M Y, H:i"); ?>
            </p>
        </div>
    </main>
</div>

</body>
</html>