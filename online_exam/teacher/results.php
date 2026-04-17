<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

$teacherName = $_SESSION['teacher'];
$teacher_id = $_SESSION['teacher_id'];
$currentPage = basename($_SERVER['PHP_SELF']);

// Check if a specific exam_id is requested
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($exam_id > 0) {
    // Fetch exam info
    $exam_res = $conn->query("SELECT * FROM exams WHERE id=$exam_id AND created_by=$teacher_id");
    if ($exam_res->num_rows == 0) {
        die("Exam not found!");
    }
    $exam = $exam_res->fetch_assoc();

    // Fetch results for this exam
    $results = $conn->query("
        SELECT r.score, r.submitted_at, u.name 
        FROM results r 
        JOIN users u ON r.user_id = u.id
        WHERE r.exam_id=$exam_id
        ORDER BY r.score DESC
    ");
    
    // Quick Stats for the header
    $stats = $conn->query("SELECT AVG(score) as avg_score, COUNT(*) as total FROM results WHERE exam_id=$exam_id")->fetch_assoc();
} else {
    // Fetch all exams created by the teacher
    $exams = $conn->query("
        SELECT e.id, e.title, e.total_marks,
                (SELECT COUNT(*) FROM results r WHERE r.exam_id=e.id) as submissions
        FROM exams e
        WHERE e.created_by=$teacher_id
        ORDER BY e.id DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results | TestHub CU</title>
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

        /* STATS ROW */
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 20px; box-shadow: var(--shadow); border: 1px solid rgba(0,0,0,0.02); }
        .stat-card p { font-size: 13px; color: #64748b; font-weight: 600; }
        .stat-card h3 { font-size: 24px; font-weight: 800; margin-top: 5px; }

        /* CARDS */
        .results-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        .result-card { 
            background: #fff; padding: 25px; border-radius: 24px; 
            box-shadow: var(--shadow); border: 1px solid rgba(0,0,0,0.02);
            transition: 0.3s; position: relative;
        }
        .result-card:hover { transform: translateY(-5px); }
        .result-card .user-name { font-size: 18px; font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .result-card .score-pill { 
            display: inline-block; padding: 6px 12px; border-radius: 10px; 
            background: #f1f5f9; font-weight: 800; color: var(--primary); font-size: 14px;
        }
        .result-card .date { font-size: 12px; color: #94a3b8; margin-top: 15px; }

        .btn-back { 
            display: inline-flex; align-items: center; gap: 8px; text-decoration: none; 
            color: #64748b; font-weight: 700; margin-bottom: 25px; transition: 0.3s;
        }
        .btn-back:hover { color: var(--primary); }

        .badge-count { background: #fff1f1; color: var(--primary); padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 800; }

        @media (max-width: 900px) { .sidebar { display: none; } .main { padding: 20px; } }
    </style>
</head>
<body>

<div class="navbar">
    <h2>TestHub<span>CU</span></h2>
    <div style="display: flex; align-items: center; gap: 20px;">
        <span style="font-weight: 600; font-size: 14px;"><i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($teacherName); ?></span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <aside class="sidebar">
        <nav>
            <a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="create_exam.php"><i class="fa-solid fa-file-circle-plus"></i> Create Exam</a>
            <a href="add_questions.php"><i class="fa-solid fa-list-check"></i> Add Questions</a>
            <a href="results.php" class="active"><i class="fa-solid fa-chart-line"></i> Exam Results</a>
            <a href="profile.php"><i class="fa-solid fa-gears"></i> My Settings</a>
        </nav>
    </aside>

    <main class="main">
        <?php if($exam_id > 0): ?>
            <a href="results.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to All Exams</a>
            
            <div class="header-box">
                <h2><?php echo htmlspecialchars($exam['title']); ?></h2>
                <p>Performance breakdown for this assessment.</p>
            </div>

            <div class="stats-row">
                <div class="stat-card">
                    <p>Participants</p>
                    <h3><?php echo $stats['total']; ?></h3>
                </div>
                <div class="stat-card">
                    <p>Average Score</p>
                    <h3><?php echo number_format($stats['avg_score'], 1); ?> <span style="font-size: 14px; color: #94a3b8;">/ <?php echo $exam['total_marks']; ?></span></h3>
                </div>
                <div class="stat-card">
                    <p>Passing Grade</p>
                    <h3>50%</h3>
                </div>
            </div>

            <div class="results-grid">
                <?php while($row = $results->fetch_assoc()): ?>
                <div class="result-card">
                    <div class="user-name">
                        <i class="fa-solid fa-user-graduate" style="color: #cbd5e1;"></i>
                        <?php echo htmlspecialchars($row['name']); ?>
                    </div>
                    <div class="score-pill">
                        Score: <?php echo $row['score']; ?> / <?php echo $exam['total_marks']; ?>
                    </div>
                    <div class="date">
                        <i class="fa-regular fa-clock"></i> Submitted on <?php echo date("M d, Y - h:i A", strtotime($row['submitted_at'])); ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

        <?php else: ?>
            <div class="header-box">
                <h2>Exam Results Overview</h2>
                <p>Select an exam to view detailed student submissions.</p>
            </div>

            <div class="results-grid">
                <?php while($exam = $exams->fetch_assoc()): ?>
                <div class="result-card" style="border-top: 4px solid <?php echo ($exam['submissions'] > 0) ? 'var(--primary)' : '#cbd5e1'; ?>;">
                    <h3 style="margin-bottom: 10px; font-weight: 800;"><?php echo htmlspecialchars($exam['title']); ?></h3>
                    <p style="color: #64748b; font-size: 14px; margin-bottom: 15px;">Max Marks: <?php echo $exam['total_marks']; ?></p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                        <span class="badge-count"><?php echo $exam['submissions']; ?> Submissions</span>
                        
                        <?php if($exam['submissions'] > 0): ?>
                            <a href="results.php?exam_id=<?php echo $exam['id']; ?>" style="color: var(--primary); text-decoration: none; font-weight: 800; font-size: 14px;">
                                View List <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 13px; font-weight: 600;">No data yet</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <footer style="margin-top: 50px; color: #94a3b8; font-size: 13px; text-align: center;">
            TestHub CU &copy; <?php echo date("Y"); ?> • Analytics Dashboard
        </footer>
    </main>
</div>

</body>
</html>