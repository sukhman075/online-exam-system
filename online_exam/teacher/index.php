<?php
session_start();
include "../db/connection.php";

// SESSION PROTECTION
if (!isset($_SESSION['teacher'])) {
    header("Location: login.php");
    exit();
}

$teacherName = $_SESSION['teacher'];
$teacher_id = $_SESSION['teacher_id'];
$currentPage = basename($_SERVER['PHP_SELF']);

// FETCH TEACHER STATISTICS
$exam_count_res = $conn->query("SELECT COUNT(*) as total FROM exams WHERE created_by = '$teacher_id'");
$exam_count = $exam_count_res->fetch_assoc()['total'];

$result_count_res = $conn->query("SELECT COUNT(r.id) as total FROM results r 
                                  JOIN exams e ON r.exam_id = e.id 
                                  WHERE e.created_by = '$teacher_id'");
$result_count = $result_count_res->fetch_assoc()['total'];

$exams = $conn->query("SELECT * FROM exams WHERE created_by = '$teacher_id' ORDER BY id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher Dashboard | TestHub CU</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --primary: #c70000; --sidebar-bg: #1e293b; --bg-soft: #f4f6fb; --shadow: 0 10px 40px rgba(0,0,0,0.06);
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Plus Jakarta Sans',sans-serif;}
body{background:var(--bg-soft);color:#1e293b;transition: background 0.3s ease;}
/* NAVBAR */
.navbar{height:75px;background:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 40px;box-shadow:0 2px 10px rgba(0,0,0,0.03);position:sticky;top:0;z-index:1000;transition: all 0.3s;}
.navbar h2{font-size:20px;color:var(--primary);font-weight:800;}
.logout-btn{background:#f1f5f9;color:#ef4444;text-decoration:none;padding:10px 20px;border-radius:12px;font-weight:700;font-size:14px;transition: all 0.3s;}
.logout-btn:hover{background:#fee2e2;}

/* CONTAINER */
.container{display:flex;min-height:calc(100vh - 75px);transition: all 0.3s;}

/* SIDEBAR */
.sidebar{width:260px;background:var(--sidebar-bg);padding:40px 20px;color:#fff;position:fixed;left:0;top:0;height:100%;transition: all 0.4s ease;z-index:100;}
.sidebar.collapsed{width:70px;}
.sidebar a{display:flex;align-items:center;gap:12px;padding:14px 18px;text-decoration:none;color:#94a3b8;border-radius:12px;margin-bottom:8px;transition: all 0.3s;}
.sidebar a.active{background:var(--primary);color:white;}
.sidebar a:hover:not(.active){background:rgba(255,255,255,0.08);color:#fff;}
.sidebar a i{min-width:20px;text-align:center;}
.sidebar.collapsed a span{display:none;}

/* MAIN CONTENT */
.main{flex:1;padding:40px;margin-left:260px;transition: margin-left 0.4s ease;}
.sidebar.collapsed + .main{margin-left:70px;}
.header-box{margin-bottom:35px;}
.header-box h2{font-size:28px;font-weight:800;transition: all 0.3s;}
.header-box p{color:#64748b;margin-top:5px;}

/* STAT CARDS */
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:25px;margin-bottom:40px;}
.stat-card{background:#fff;padding:25px;border-radius:20px;box-shadow:var(--shadow);border:1px solid rgba(0,0,0,0.02);transition: all 0.3s ease;}
.stat-card:hover{transform:translateY(-5px);box-shadow:0 15px 40px rgba(0,0,0,0.1);}
.stat-card i{font-size:24px;color:var(--primary);margin-bottom:15px;background:#fff1f1;padding:12px;border-radius:10px;}
.stat-card h3{font-size:32px;font-weight:800;transition: all 0.3s ease;}
.stat-card p{color:#64748b;font-weight:500;font-size:14px;}

/* TABLE */
.content-card{background:#fff;border-radius:24px;padding:30px;box-shadow:var(--shadow);}
.table-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;}
.table-header h3{font-size:20px;font-weight:700;}
.btn-add{background:var(--primary);color:white;text-decoration:none;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:700;transition: all 0.3s;}
.btn-add:hover{transform:scale(1.05);}
table{width:100%;border-collapse:collapse;transition: all 0.3s;}
th{text-align:left;padding:15px;color:#64748b;font-size:13px;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid #f1f5f9;}
td{padding:18px 15px;border-bottom:1px solid #f1f5f9;font-size:15px;}
.badge{padding:5px 12px;border-radius:6px;font-size:12px;font-weight:700;background:#f0fdf4;color:#16a34a;}
.actions a{color:#64748b;margin-right:15px;font-size:18px;transition:0.2s;}
.actions a:hover{color:var(--primary);}
table tbody tr:hover{background:#f9fafb;transition: all 0.3s ease;}

/* RESPONSIVE */
@media (max-width:900px){.sidebar{display:none;}.main{padding:20px;}}

/* CHART CANVAS */
#examChart, #studentChart{background:#fff;border-radius:20px;padding:20px;box-shadow:var(--shadow);margin-bottom:30px;}
</style>
</head>
<body>

<div class="navbar">
<h2>TestHub<span>CU</span></h2>
<div style="display:flex;align-items:center;gap:20px;">
<span style="font-weight:600;font-size:14px;"><i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($teacherName); ?></span>
<a href="logout.php" class="logout-btn"><i class="fa-solid fa-power-off"></i> Logout</a>
</div>
</div>

<div class="container">
<aside class="sidebar" id="sidebar">
<nav>
<a href="index.php" class="active"><i class="fa-solid fa-house"></i> <span>Dashboard</span></a>
<a href="create_exam.php"><i class="fa-solid fa-file-circle-plus"></i> <span>Create Exam</span></a>
<a href="add_questions.php"><i class="fa-solid fa-list-check"></i> <span>Add Questions</span></a>
<a href="results.php"><i class="fa-solid fa-chart-line"></i> <span>Exam Results</span></a>
<a href="profile.php"><i class="fa-solid fa-gears"></i> <span>My Settings</span></a>
</nav>
</aside>

<main class="main">
<div class="header-box">
<h2>Welcome back, Professor! 👋</h2>
<p>Monitor your exams and student performance from here.</p>
</div>

<div class="stats-row">
<div class="stat-card">
<i class="fa-solid fa-scroll"></i>
<h3 id="examCounter">0</h3>
<p>Total Exams Created</p>
</div>
<div class="stat-card">
<i class="fa-solid fa-user-graduate"></i>
<h3 id="studentCounter">0</h3>
<p>Students Participated</p>
</div>
<div class="stat-card">
<i class="fa-solid fa-clock"></i>
<h3>Active</h3>
<p>System Status</p>
</div>
</div>

<div id="examChartContainer">
<canvas id="examChart"></canvas>
</div>
<div id="studentChartContainer">
<canvas id="studentChart"></canvas>
</div>

<div class="content-card">
<div class="table-header">
<h3>Recent Exams</h3>
<a href="create_exam.php" class="btn-add"><i class="fa-solid fa-plus"></i> New Exam</a>
</div>
<div style="overflow-x:auto;">
<table>
<thead>
<tr>
<th>Exam Title</th>
<th>Total Marks</th>
<th>Duration</th>
<th>Created On</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if($exams->num_rows > 0): ?>
<?php while($row = $exams->fetch_assoc()): ?>
<tr>
<td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
<td><span class="badge"><?php echo $row['total_marks']; ?> Marks</span></td>
<td><?php echo $row['duration']; ?> Mins</td>
<td style="color:#64748b;font-size:13px;"><?php echo date("d M, Y", strtotime($row['created_at'] ?? 'now')); ?></td>
<td class="actions">
<a href="edit_exam.php?id=<?php echo $row['id']; ?>" title="Edit Settings"><i class="fa-solid fa-pen-to-square"></i></a>
<a href="add_questions.php?exam_id=<?php echo $row['id']; ?>" title="Manage Questions" style="color:#0ea5e9;"><i class="fa-solid fa-circle-question"></i></a>
<a href="results.php?exam_id=<?php echo $row['id']; ?>" title="View Results" style="color:#10b981;"><i class="fa-solid fa-chart-column"></i></a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="5" style="text-align:center;padding:40px;color:#94a3b8;">
<i class="fa-solid fa-folder-open" style="font-size:30px;display:block;margin-bottom:10px;"></i>
No exams found. Start by creating your first exam!
</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

<footer style="margin-top:40px;color:#94a3b8;font-size:13px;text-align:center;">
TestHub CU &copy; <?php echo date("Y"); ?> • Designed for Academic Excellence
</footer>
</main>
</div>

<script>
// Animated Counters
function animateCounter(id,target){
let count=0; const el=document.getElementById(id);
const interval=setInterval(()=>{
count+=Math.ceil(target/50);
if(count>=target){count=target; clearInterval(interval);}
el.innerText=count;
},20);
}
animateCounter('examCounter',<?php echo $exam_count; ?>);
animateCounter('studentCounter',<?php echo $result_count; ?>);

// Sidebar Toggle
const sidebar=document.getElementById('sidebar');
const toggleBtn=document.createElement('button');
toggleBtn.innerHTML='<i class="fa-solid fa-bars"></i>';
toggleBtn.style.position='fixed'; toggleBtn.style.top='20px'; toggleBtn.style.left='20px';
toggleBtn.style.zIndex='999'; toggleBtn.style.background='var(--primary)'; toggleBtn.style.color='#fff';
toggleBtn.style.border='none'; toggleBtn.style.padding='10px'; toggleBtn.style.borderRadius='8px'; toggleBtn.style.cursor='pointer';
document.body.appendChild(toggleBtn);
toggleBtn.addEventListener('click',()=>{sidebar.classList.toggle('collapsed');});

// Charts
const examCtx=document.getElementById('examChart').getContext('2d');
const studentCtx=document.getElementById('studentChart').getContext('2d');

const examChart=new Chart(examCtx,{type:'bar',data:{labels:[<?php while($row=$exams->fetch_assoc()){echo '"'.htmlspecialchars($row['title']).'",';}?>],datasets:[{label:'Marks',data:[<?php $exams->data_seek(0); while($row=$exams->fetch_assoc()){echo $row['total_marks'].',';} ?>],backgroundColor:'rgba(199,0,0,0.7)',borderRadius:10}]}});
const studentChart=new Chart(studentCtx,{type:'line',data:{labels:[<?php $exams->data_seek(0); while($row=$exams->fetch_assoc()){echo '"'.htmlspecialchars($row['title']).'",';} ?>],datasets:[{label:'Student Participation',data:[<?php $exams->data_seek(0); while($row=$exams->fetch_assoc()){ $countRes=$conn->query("SELECT COUNT(*) as cnt FROM results WHERE exam_id=".$row['id']); echo $countRes->fetch_assoc()['cnt'].',';} ?>],borderColor:'rgba(16,185,129,0.8)',backgroundColor:'rgba(16,185,129,0.2)',tension:0.4,fill:true}]},options:{scales:{y:{beginAtZero:true}}}});
</script>

</body>
</html>
