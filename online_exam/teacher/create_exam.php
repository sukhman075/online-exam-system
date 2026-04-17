<?php
session_start();
include "../db/connection.php";

if(!isset($_SESSION['teacher_id'])){
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$teacherName = $_SESSION['teacher'];
$currentPage = basename($_SERVER['PHP_SELF']);

$success_msg = '';
$error_msg = '';
$last_exam_id = null;

// CREATE EXAM LOGIC
if(isset($_POST['create_exam'])){
    $title = trim($_POST['title']);
    $total_marks = intval($_POST['total_marks']);
    $duration = intval($_POST['duration']);

    $stmt_check = $conn->prepare("SELECT id FROM exams WHERE title=? AND created_by=?");
    $stmt_check->bind_param("si", $title, $teacher_id);
    $stmt_check->execute();
    if($stmt_check->get_result()->num_rows > 0){
        $error_msg = "You already have an exam with this title.";
    } else {
        $stmt = $conn->prepare("INSERT INTO exams (title, total_marks, duration, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siii", $title, $total_marks, $duration, $teacher_id);
        if($stmt->execute()){
            $success_msg = "Exam created successfully!";
            $last_exam_id = $conn->insert_id;
        }
    }
}

// DELETE LOGIC
if(isset($_GET['delete_exam'])){
    $del_id = intval($_GET['delete_exam']);
    $conn->query("DELETE FROM questions WHERE exam_id=$del_id");
    $conn->query("DELETE FROM exams WHERE id=$del_id AND created_by=$teacher_id");
    header("Location: create_exam.php?msg=deleted");
    exit();
}

// FETCH DATA
$exam_count_res = $conn->query("SELECT COUNT(*) as total FROM exams WHERE created_by = '$teacher_id'");
$exam_count = $exam_count_res->fetch_assoc()['total'];
$exams = $conn->query("SELECT * FROM exams WHERE created_by = '$teacher_id' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams | TestHub CU</title>
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
        .navbar h2 span { color: #1e293b; }
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

        /* STAT CARD (Mini version for consistency) */
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 20px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 15px; }
        .stat-card i { background: #fff1f1; color: var(--primary); padding: 12px; border-radius: 12px; font-size: 20px; }
        .stat-card div h4 { font-size: 22px; font-weight: 800; }
        .stat-card div p { font-size: 13px; color: #64748b; }

        /* CONTENT CARDS */
        .content-card { background: #fff; border-radius: 24px; padding: 30px; box-shadow: var(--shadow); margin-bottom: 30px; border: 1px solid rgba(0,0,0,0.02); }
        .content-card h3 { font-size: 20px; font-weight: 700; margin-bottom: 20px; }

        /* FORM STYLING */
        .form-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; }
        input { width: 100%; padding: 14px 18px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 15px; transition: 0.3s; outline: none; }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(199,0,0,0.05); }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 14px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; width: 100%; margin-top: 15px; }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-2px); }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f1f5f9; }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }
        .badge { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #f0fdf4; color: #16a34a; }
        
        .action-links a { color: #64748b; margin-right: 12px; font-size: 16px; transition: 0.2s; }
        .action-links a:hover { color: var(--primary); }

        /* MODAL */
        .modal { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; padding: 40px; border-radius: 24px; width: 450px; position: relative; }

        @media (max-width: 900px) { .sidebar { display: none; } .form-grid { grid-template-columns: 1fr; } }
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
            <a href="create_exam.php" class="active"><i class="fa-solid fa-file-circle-plus"></i> Create Exam</a>
            <a href="add_questions.php"><i class="fa-solid fa-list-check"></i> Add Questions</a>
            <a href="results.php"><i class="fa-solid fa-chart-line"></i> Exam Results</a>
            <a href="profile.php"><i class="fa-solid fa-gears"></i> My Settings</a>
        </nav>
    </aside>

    <main class="main">
        <div class="header-box">
            <h2>Exam Management</h2>
            <p>Create and edit your examination papers.</p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <i class="fa-solid fa-file-invoice"></i>
                <div>
                    <h4><?php echo $exam_count; ?></h4>
                    <p>Total Exams</p>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h3><i class="fa-solid fa-plus-circle" style="color: var(--primary); margin-right: 10px;"></i> Create New Exam</h3>
            <?php if($success_msg) echo "<p style='color:#16a34a; font-weight:700; margin-bottom:15px;'><i class='fa-solid fa-check-circle'></i> $success_msg</p>"; ?>
            
            <form method="post">
                <div class="form-grid">
                    <input type="text" name="title" placeholder="Enter Exam Title..." required>
                    <input type="number" name="total_marks" placeholder="Total Marks" required>
                    <input type="number" name="duration" placeholder="Duration (Mins)" required>
                </div>
                <button type="submit" name="create_exam" class="btn-submit">Initialize Exam Paper</button>
            </form>
            
            <?php if($last_exam_id): ?>
                <a href="add_questions.php?exam_id=<?php echo $last_exam_id; ?>" style="display:inline-block; margin-top:15px; color:var(--primary); font-weight:700; text-decoration:none;">Add Questions Now <i class="fa-solid fa-arrow-right"></i></a>
            <?php endif; ?>
        </div>

        <div class="content-card">
            <h3>Recent Exams</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Exam Name</th>
                            <th>Marks</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $exams->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td><span class="badge"><?php echo $row['total_marks']; ?> pts</span></td>
                            <td><?php echo $row['duration']; ?>m</td>
                            <td class="action-links">
                                <a href="add_questions.php?exam_id=<?php echo $row['id']; ?>" title="Add Questions"><i class="fa-solid fa-circle-question"></i></a>
                                <a href="javascript:void(0)" onclick="openEdit(<?php echo $row['id']; ?>, '<?php echo addslashes($row['title']); ?>', <?php echo $row['total_marks']; ?>, <?php echo $row['duration']; ?>)" title="Edit Settings"><i class="fa-solid fa-pen-to-square"></i></a>
                                <a href="create_exam.php?delete_exam=<?php echo $row['id']; ?>" onclick="return confirm('Delete this exam?')" title="Delete"><i class="fa-solid fa-trash-can" style="color:#ef4444;"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-bottom: 25px; font-weight: 800;">Edit Exam Details</h3>
        <form method="post">
            <input type="hidden" name="exam_id" id="edit_id">
            <p style="font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #64748b;">Exam Title</p>
            <input type="text" name="title" id="edit_title" required style="margin-bottom: 15px;">
            
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <p style="font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #64748b;">Marks</p>
                    <input type="number" name="total_marks" id="edit_marks" required>
                </div>
                <div style="flex: 1;">
                    <p style="font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #64748b;">Mins</p>
                    <input type="number" name="duration" id="edit_duration" required>
                </div>
            </div>
            
            <button type="submit" name="edit_exam" class="btn-submit">Save Changes</button>
            <button type="button" onclick="closeModal()" style="width: 100%; background: none; border: none; margin-top: 15px; color: #64748b; font-weight: 700; cursor: pointer;">Cancel</button>
        </form>
    </div>
</div>

<script>
    function openEdit(id, title, marks, duration) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_marks').value = marks;
        document.getElementById('edit_duration').value = duration;
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeModal() { document.getElementById('editModal').style.display = 'none'; }
</script>

</body>
</html>