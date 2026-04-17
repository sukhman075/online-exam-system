<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
$adminName = $_SESSION['admin'];
$currentPage = basename($_SERVER['PHP_SELF']);

include "../db/connection.php";

// Analytics queries
$studentsCount = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='student'")->fetch_assoc()['total'];
$examsCount = $conn->query("SELECT COUNT(*) AS total FROM exams")->fetch_assoc()['total'];
$avgScore = $conn->query("SELECT ROUND(AVG(score),2) AS avg_score FROM results")->fetch_assoc()['avg_score'];
$passCount = $conn->query("SELECT COUNT(*) AS total FROM results r JOIN exams e ON r.exam_id=e.id WHERE (r.score/e.total_marks)*100 >= 50")->fetch_assoc()['total'];
$failCount = $conn->query("SELECT COUNT(*) AS total FROM results r JOIN exams e ON r.exam_id=e.id WHERE (r.score/e.total_marks)*100 < 50")->fetch_assoc()['total'];

// Recent 5 results
$resultsQuery = "
SELECT 
    r.id AS result_id,
    u.name AS student_name,
    u.email AS student_email,
    e.title AS exam_name,
    e.total_marks,
    r.score,
    ROUND((r.score / e.total_marks) * 100, 2) AS percentage,
    r.submitted_at,
    t.name AS teacher_name
FROM results r
JOIN users u ON r.user_id = u.id
JOIN exams e ON r.exam_id = e.id
JOIN users t ON e.created_by = t.id
ORDER BY r.submitted_at DESC
LIMIT 5
";
$recentResults = $conn->query($resultsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel | TestHub CU</title>
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
.main h2 { font-size:26px; font-weight:600; margin-bottom:10px; }
.main .welcome { color:#777; font-size:15px; margin-bottom:25px; }

/* Cards */
.cards { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:40px; }
.card {
    background:#fff; padding:30px 20px; border-radius:20px;
    box-shadow:0 20px 40px rgba(0,0,0,0.08); text-align:center;
    transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover { transform: translateY(-5px); box-shadow:0 35px 70px rgba(0,0,0,0.15); }
.card h3 { color:#c70000; margin-bottom:15px; }
.card a { display:inline-block; text-decoration:none; background:#c70000; color:#fff; padding:10px 18px; border-radius:12px; font-weight:600; transition:0.3s; }
.card a:hover { background:#ff4d4d; }

/* Analytics cards */
.analytics-cards { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:40px; }
.analytics-card {
    flex:1; min-width:150px; background:#fff; padding:20px; border-radius:12px; text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,0.08); font-weight:600;
}
.analytics-card h4 { color:#c70000; margin-bottom:10px; font-size:16px; }
.analytics-card p { font-size:22px; color:#333; }

/* Results table */
.results-table { width:100%; border-collapse: collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.08); }
.results-table th, .results-table td { padding:12px 15px; text-align:left; border-bottom:1px solid #eee; font-size:14px; }
.results-table th { background:#c70000; color:#fff; font-weight:600; }
.results-table tr:hover { background:#ffe1e1; }
.badge { padding:3px 6px; border-radius:4px; color:#fff; font-size:12px; }
.high { background:#28a745; }
.medium { background:#ffc107; color:#333; }
.low { background:#dc3545; }

/* Footer */
.footer { margin-top:40px; font-size:14px; color:#888; text-align:center; }

/* Responsive */
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
        <a href="index.php" class="<?php if($currentPage=='index.php') echo 'active'; ?>">🏠 Dashboard</a>
        <a href="manage_students.php" class="<?php if($currentPage=='manage_students.php') echo 'active'; ?>">👨‍🎓 Manage Students</a>
        <a href="manage_teachers.php" class="<?php if($currentPage=='manage_teachers.php') echo 'active'; ?>">👩‍🏫Manage Teachers</a>
        <a href="view_results.php" class="<?php if($currentPage=='view_results.php') echo 'active'; ?>">📊 View Results</a>
        <a href="profile.php" class="<?php if($currentPage=='profile.php') echo 'active'; ?>">👤 My Profile</a>
    </div>

    <div class="main">
        <h2>Welcome, <?php echo htmlspecialchars($adminName); ?>!</h2>
        <div class="welcome">Use the options below to manage the system</div>

        <!-- Navigation cards -->
        <div class="cards">
            <div class="card"><h3>Manage Students</h3><a href="manage_students.php">Go</a></div>
            <div class="card"><h3>Manage Teachers</h3><a href="manage_teachers.php">Go</a></div>
            <div class="card"><h3>View Results</h3><a href="view_results.php">Go</a></div>
            <div class="card"><h3>My Profile</h3><a href="profile.php">Go</a></div>
        </div>

        <!-- Analytics cards -->
        <h3>Quick Analytics</h3>
        <div class="analytics-cards">
            <div class="analytics-card"><h4>Total Students</h4><p><?php echo $studentsCount; ?></p></div>
            <div class="analytics-card"><h4>Total Exams</h4><p><?php echo $examsCount; ?></p></div>
            <div class="analytics-card"><h4>Average Score</h4><p><?php echo $avgScore; ?>%</p></div>
            <div class="analytics-card"><h4>Pass / Fail</h4><p><?php echo $passCount; ?> / <?php echo $failCount; ?></p></div>
        </div>

        <!-- Recent Results Table -->
        <h3>Recent Exam Results</h3>
        <table class="results-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Exam Name</th>
                    <th>Total Marks</th>
                    <th>Score</th>
                    <th>Percentage</th>
                    <th>Teacher</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if($recentResults->num_rows > 0){
                $i = 1;
                while($row = $recentResults->fetch_assoc()){
                    $perc = $row['percentage'];
                    if($perc >= 75) $badge = "high";
                    elseif($perc >= 50) $badge = "medium";
                    else $badge = "low";

                    echo "<tr>
                        <td>{$i}</td>
                        <td>".htmlspecialchars($row['student_name'])."</td>
                        <td>".htmlspecialchars($row['student_email'])."</td>
                        <td>".htmlspecialchars($row['exam_name'])."</td>
                        <td>{$row['total_marks']}</td>
                        <td>{$row['score']}</td>
                        <td><span class='badge {$badge}'>{$perc}%</span></td>
                        <td>".htmlspecialchars($row['teacher_name'])."</td>
                        <td>{$row['submitted_at']}</td>
                    </tr>";
                    $i++;
                }
            } else {
                echo "<tr><td colspan='9' style='text-align:center;'>No recent results</td></tr>";
            }
            ?>
            </tbody>
        </table>

        <div class="footer">
            TestHub CU © <?php echo date("Y"); ?> • Online Examination System
        </div>
    </div>
</div>
</body>
</html>
