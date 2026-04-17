<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$studentId   = $_SESSION['student_id'];
$studentName = $_SESSION['student'];
$currentPage = basename($_SERVER['PHP_SELF']);

$stmt = $conn->prepare("
    SELECT 
        e.title AS exam_name,
        e.total_marks,
        r.score,
        r.exam_id,
        r.id as result_id
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.user_id = ?
    ORDER BY r.id DESC
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$totalExams = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Results | TestHub CU</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<style>
:root {
    --primary: #c70000; --bg-soft: #f8fafc; --sidebar-bg: #1e293b;
    --text-main: #1e293b; --text-muted: #64748b; --glass: rgba(255,255,255,0.95);
    --shadow: 0 10px 40px rgba(0,0,0,0.06); --success: #10b981; --danger: #ef4444;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Plus Jakarta Sans',sans-serif;}
body{background:var(--bg-soft);color:var(--text-main);}
.app-container{display:flex;min-height:100vh;}
.sidebar{width:280px;background:var(--sidebar-bg);color:white;padding:30px 20px;display:flex;flex-direction:column;position:fixed;height:100vh;z-index:100;}
.brand{padding:0 15px 40px;font-size:22px;font-weight:700;display:flex;align-items:center;gap:12px;}
.brand span span{color:#ff4d4d;}
.sidebar-nav{flex-grow:1;}
.sidebar a{display:flex;align-items:center;gap:12px;padding:14px 18px;text-decoration:none;color:#94a3b8;border-radius:12px;margin-bottom:8px;transition:0.3s;}
.sidebar a:hover{background:rgba(255,255,255,0.05);color:#fff;}
.sidebar a.active{background:var(--primary);color:white;box-shadow:0 10px 20px rgba(199,0,0,0.3);}
.logout-btn-side{margin-top:auto;color:#ef4444;text-decoration:none;font-weight:600;text-align:center;padding:15px;background:#fee2e2;border-radius:12px;}
.content-area{flex:1;margin-left:280px;padding:0 40px 40px;}
.topbar{height:90px;display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.user-profile{display:flex;align-items:center;gap:12px;background:white;padding:8px 15px;border-radius:50px;box-shadow:var(--shadow);}
.avatar{width:35px;height:35px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:14px;}
.results-card{background:white;border-radius:24px;padding:35px;box-shadow:var(--shadow);border:1px solid rgba(0,0,0,0.03);}
.header-flex{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:30px;}
.header-flex h2{font-size:24px;font-weight:700;}
.stats-badge{background:#f1f5f9;padding:8px 16px;border-radius:12px;font-weight:600;color:var(--text-muted);font-size:13px;}
table{width:100%;border-collapse:collapse;}
th{text-align:left;padding:15px;color:var(--text-muted);font-weight:600;font-size:12px;text-transform:uppercase;border-bottom:2px solid #f1f5f9;}
td{padding:20px 15px;border-bottom:1px solid #f1f5f9;font-size:14px;}
.exam-name{font-weight:700;color:var(--sidebar-bg);}
.marks-obtained{font-weight:600;color:var(--primary);font-size:16px;}
.percentage-container{width:100px;}
.progress-bar{height:6px;background:#e2e8f0;border-radius:10px;overflow:hidden;margin-top:5px;}
.progress-fill{height:100%;border-radius:10px;}
.status-pill{padding:6px 14px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.5px;}
.pass{background:#dcfce7;color:#065f46;}
.fail{background:#fee2e2;color:#991b1b;}
.review-btn{padding:6px 12px;border:none;border-radius:8px;background:var(--primary);color:#fff;font-size:12px;cursor:pointer;transition:0.3s;}
.review-btn:hover{background:#a50000;}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;z-index:200;overflow:auto;}
.modal-content{background:white;border-radius:24px;padding:30px;width:90%;max-width:900px;max-height:90%;overflow-y:auto;box-shadow:var(--shadow);}
.modal-close{float:right;font-size:20px;font-weight:bold;cursor:pointer;color:#999;}
.print-btn{padding:6px 12px;background:var(--success);color:white;border:none;border-radius:8px;font-size:12px;margin-top:10px;cursor:pointer;transition:0.3s;}
.print-btn:hover{background:#0b6347;}
</style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="brand"><i class="fa-solid fa-graduation-cap"></i><span>TestHub<span>CU</span></span></div>
        <nav class="sidebar-nav">
            <a href="index.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="available_exams.php"><i class="fa-solid fa-file-pen"></i><span>Available Exams</span></a>
            <a href="results.php" class="active"><i class="fa-solid fa-chart-simple"></i><span>My Results</span></a>
            <a href="profile.php"><i class="fa-solid fa-user-gear"></i><span>Profile Settings</span></a>
            <a href="instructions.php"><i class="fa-solid fa-circle-info"></i><span>Instructions</span></a>
        </nav>
        <a href="logout.php" class="logout-btn-side"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </aside>

    <main class="content-area">
        <header class="topbar">
            <div><span style="color: var(--text-muted); font-weight:500;">Performance Report Card</span></div>
            <div class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($studentName,0,1)); ?></div>
                <span style="font-weight:600;"><?php echo htmlspecialchars($studentName); ?></span>
            </div>
        </header>

        <div class="results-card">
            <div class="header-flex">
                <div>
                    <h2>My Academic Results</h2>
                    <p style="color: var(--text-muted); font-size:14px;">Detailed breakdown of your performance across all attempts.</p>
                </div>
                <div class="stats-badge"><i class="fa-solid fa-medal"></i> Total Attempts: <?php echo $totalExams; ?></div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Exam Details</th>
                        <th>Score</th>
                        <th>Weightage</th>
                        <th>Performance</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($totalExams>0): ?>
                    <?php while($row=$result->fetch_assoc()):
                        $percentage = ($row['score']/$row['total_marks'])*100;
                        $status = ($percentage>=33)?"pass":"fail";
                        $barColor = ($status=="pass")?"var(--success)":"var(--danger)";
                    ?>
                    <tr>
                        <td>
                            <div class="exam-name"><?php echo htmlspecialchars($row['exam_name']); ?></div>
                            <div style="font-size:12px;color:var(--text-muted);">Attempt ID: #<?php echo $row['result_id']; ?></div>
                        </td>
                        <td class="marks-obtained"><?php echo $row['score']; ?></td>
                        <td style="color:var(--text-muted); font-weight:500;">/ <?php echo $row['total_marks']; ?></td>
                        <td>
                            <div class="percentage-container">
                                <div style="font-weight:700;font-size:13px;"><?php echo round($percentage,1); ?>%</div>
                                <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $percentage; ?>%;background:<?php echo $barColor; ?>;"></div></div>
                            </div>
                        </td>
                        <td><span class="status-pill <?php echo $status; ?>"><i class="fa-solid <?php echo ($status=='pass'?'fa-circle-check':'fa-circle-xmark'); ?>"></i> <?php echo $status; ?></span></td>
                        <td><button class="review-btn" onclick="openReview(<?php echo $row['exam_id']; ?>,<?php echo $row['result_id']; ?>)">View Review</button></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:60px 0;"><i class="fa-solid fa-folder-open" style="font-size:40px;color:#e2e8f0;margin-bottom:15px;"></i><p style="color:var(--text-muted);">No results found. Start an exam to see your performance here.</p></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal -->
<div class="modal" id="reviewModal">
    <div class="modal-content" id="modalContent">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div id="reviewBody">Loading...</div>
        <button class="print-btn" onclick="printReview()">Print Review</button>
    </div>
</div>

<script>
function openReview(examId,resultId){
    const modal=document.getElementById('reviewModal');
    const body=document.getElementById('reviewBody');
    modal.style.display='flex';
    body.innerHTML='Loading...';
    fetch('review_ajax.php?exam_id='+examId+'&result_id='+resultId)
        .then(res=>res.text())
        .then(html=>{
            body.innerHTML=html;
            // confetti if passed
            const scoreText = body.querySelector('p strong');
            if(scoreText){
                const score = parseFloat(scoreText.textContent.split('/')[0]);
                const total = parseFloat(scoreText.textContent.split('/')[1]);
                if((score/total)*100 >=33){
                    confetti({particleCount:200,spread:100,origin:{y:0.6}});
                }
            }
        })
        .catch(err=>body.innerHTML='Failed to load review.');
}
function closeModal(){document.getElementById('reviewModal').style.display='none';}
window.onclick=function(e){if(e.target==document.getElementById('reviewModal')) closeModal();}
function printReview(){
    const content=document.getElementById('reviewBody').innerHTML;
    const w=window.open('','Print','width=900,height=700');
    w.document.write('<html><head><title>Print Review</title></head><body>'+content+'</body></html>');
    w.document.close();
    w.print();
}
</script>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
