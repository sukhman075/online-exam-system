<?php
session_start();
include "../db/connection.php";

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

$adminName = $_SESSION['admin'];
$currentPage = basename($_SERVER['PHP_SELF']);

// Filters
$filter_exam = $_GET['exam'] ?? '';
$filter_teacher = $_GET['teacher'] ?? '';
$filter_percentage = $_GET['percentage'] ?? '';
$search = $_GET['search'] ?? '';

// Build query dynamically
$query = "
SELECT 
    r.id AS result_id,
    u.name AS student_name,
    u.email AS student_email,
    e.title AS exam_name,
    e.total_marks,
    e.duration,
    r.score,
    ROUND((r.score / e.total_marks) * 100, 2) AS percentage,
    r.submitted_at,
    t.name AS teacher_name
FROM results r
JOIN users u ON r.user_id = u.id
JOIN exams e ON r.exam_id = e.id
JOIN users t ON e.created_by = t.id
WHERE 1
";

if($filter_exam) $query .= " AND e.id=".intval($filter_exam);
if($filter_teacher) $query .= " AND t.id=".intval($filter_teacher);
if($filter_percentage){
    if($filter_percentage == 'high') $query .= " AND (r.score / e.total_marks)*100 >= 75";
    if($filter_percentage == 'medium') $query .= " AND (r.score / e.total_marks)*100 >= 50 AND (r.score / e.total_marks)*100 < 75";
    if($filter_percentage == 'low') $query .= " AND (r.score / e.total_marks)*100 < 50";
}
if($search) $query .= " AND (u.name LIKE '%".$conn->real_escape_string($search)."%' OR u.email LIKE '%".$conn->real_escape_string($search)."%')";

$query .= " ORDER BY r.submitted_at DESC";

$results = $conn->query($query);

// Fetch exam & teacher lists for filters
$exams = $conn->query("SELECT id, title FROM exams ORDER BY title ASC");
$teachers = $conn->query("SELECT id, name FROM users WHERE role='teacher' ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Results | TestHub CU</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body { background:#f4f6fb; color:#333; }
.navbar { height:70px; background:linear-gradient(135deg,#c70000,#ff4d4d); color:#fff; display:flex; align-items:center; justify-content:space-between; padding:0 30px; box-shadow:0 8px 20px rgba(0,0,0,0.2); position:sticky; top:0; z-index:1000; }
.navbar h2 { font-size:20px; font-weight:600; }
.navbar a { color:#fff; text-decoration:none; font-weight:600; background:rgba(255,255,255,0.2); padding:8px 18px; border-radius:12px; transition:0.3s; }
.navbar a:hover { background:#fff; color:#c70000; }
.container { display:flex; min-height:calc(100vh - 70px); }
.sidebar { width:250px; background:#fff; padding:30px 20px; box-shadow:5px 0 20px rgba(0,0,0,0.08); position:sticky; top:70px; height:calc(100vh - 70px); border-radius:0 20px 20px 0; }
.sidebar a { display:block; padding:14px 20px; margin-bottom:14px; text-decoration:none; color:#333; font-weight:500; border-radius:12px; transition:all 0.3s ease; position:relative; }
.sidebar a.active { background:#ffe1e1; color:#c70000; }
.sidebar a::before { content:''; position:absolute; left:0; top:0; width:4px; height:100%; background:#c70000; border-radius:2px; opacity:0; transition:opacity 0.3s ease; }
.sidebar a.active::before { opacity:1; }
.sidebar a:hover::before { opacity:1; }
.sidebar a:hover { background:#ffe1e1; color:#c70000; }
.main { flex:1; padding:40px 50px; }
.main h2 { font-size:26px; font-weight:600; margin-bottom:10px; }
.main .welcome { color:#777; font-size:15px; margin-bottom:25px; }

/* Filters Form */
.filter-form { display:flex; flex-wrap:wrap; gap:15px; background:#fff; padding:20px; border-radius:15px; margin-bottom:25px; box-shadow:0 10px 30px rgba(0,0,0,0.08); }
.filter-form select, .filter-form input { padding:10px 12px; border-radius:10px; border:1px solid #ccc; flex:1; min-width:150px; }
.filter-form button { padding:10px 20px; border:none; border-radius:10px; background:linear-gradient(135deg,#c70000,#ff4d4d); color:#fff; cursor:pointer; transition:0.3s; }
.filter-form button:hover { opacity:0.9; }

/* Results Table */
table { width:100%; border-collapse:collapse; background:#fff; border-radius:15px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.08); }
table th, table td { padding:12px 15px; text-align:left; font-size:14px; }
table th { background:#c70000; color:#fff; }
table tr:nth-child(even) { background:#f9f9f9; }
table a { color:#007bff; text-decoration:none; font-weight:500; }
table a:hover { text-decoration:underline; }
.badge { display:inline-block; padding:4px 10px; border-radius:5px; font-size:12px; color:#fff; }
.high { background-color:#28a745; }
.medium { background-color:#ffc107; }
.low { background-color:#dc3545; }

.footer { margin-top:30px; font-size:14px; color:#888; text-align:center; }

@media(max-width:900px) { .container { flex-direction:column; } .sidebar { width:100%; height:auto; border-radius:0 0 20px 20px; } .main { padding:25px 20px; } .filter-form { flex-direction:column; } }
</style>
</head>
<body>

<div class="navbar">
    <h2>🛠️ Admin Panel</h2>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <div class="sidebar">
        <a href="index.php">🏠 Dashboard</a>
        <a href="manage_students.php">👨‍🎓 Manage Students</a>
        <a href="manage_teachers.php">👩‍🏫Manage Teachers</a>
        <a href="view_results.php" class="active">📊 View Results</a>
        <a href="profile.php">👤 My Profile</a>
    </div>

    <div class="main">
        <h2>All Exam Results</h2>
        <div class="welcome">View detailed exam results and apply filters to manage efficiently</div>

        <!-- Filters Form -->
        <form class="filter-form" method="GET" action="">
            <input type="text" name="search" placeholder="Search by student name or email" value="<?php echo htmlspecialchars($search); ?>">
            <select name="exam">
                <option value="">All Exams</option>
                <?php while($e = $exams->fetch_assoc()): ?>
                <option value="<?php echo $e['id']; ?>" <?php if($filter_exam==$e['id']) echo 'selected'; ?>><?php echo htmlspecialchars($e['title']); ?></option>
                <?php endwhile; ?>
            </select>
            <select name="teacher">
                <option value="">All Teachers</option>
                <?php while($t = $teachers->fetch_assoc()): ?>
                <option value="<?php echo $t['id']; ?>" <?php if($filter_teacher==$t['id']) echo 'selected'; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                <?php endwhile; ?>
            </select>
            <select name="percentage">
                <option value="">All Percentages</option>
                <option value="high" <?php if($filter_percentage=='high') echo 'selected'; ?>>High (75%+)</option>
                <option value="medium" <?php if($filter_percentage=='medium') echo 'selected'; ?>>Medium (50%-74%)</option>
                <option value="low" <?php if($filter_percentage=='low') echo 'selected'; ?>>Low (<50%)</option>
            </select>
            <button type="submit">Apply Filters</button>
        </form>

        <!-- Results Table -->
        <table>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>Email</th>
                <th>Exam</th>
                <th>Score</th>
                <th>Percentage</th>
                <th>Duration</th>
                <th>Teacher</th>
                <th>Submitted At</th>
                <th>Actions</th>
            </tr>
            <?php
            if($results->num_rows>0){
                $i=1;
                while($row=$results->fetch_assoc()){
                    $perc = $row['percentage'];
                    $badge = $perc >=75 ? "high" : ($perc>=50 ? "medium" : "low");
                    echo "<tr>
                        <td>{$i}</td>
                        <td>".htmlspecialchars($row['student_name'])."</td>
                        <td>".htmlspecialchars($row['student_email'])."</td>
                        <td>".htmlspecialchars($row['exam_name'])."</td>
                        <td>{$row['score']} / {$row['total_marks']}</td>
                        <td><span class='badge {$badge}'>{$perc}%</span></td>
                        <td>{$row['duration']} min</td>
                        <td>".htmlspecialchars($row['teacher_name'])."</td>
                        <td>{$row['submitted_at']}</td>
                        <td>
                            <a href='view_result.php?id={$row['result_id']}'>View</a> | 
                            <a href='delete_result.php?id={$row['result_id']}' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                        </td>
                    </tr>";
                    $i++;
                }
            } else {
                echo "<tr><td colspan='10' style='text-align:center;'>No results found</td></tr>";
            }
            ?>
        </table>

        <div class="footer">
            TestHub CU © <?php echo date("Y"); ?> • Online Examination System
        </div>
    </div>
</div>

</body>
</html>
