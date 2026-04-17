<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$studentName = $_SESSION['student'];
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Exams | TestHub CU</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #c70000;
            --primary-hover: #a00000;
            --bg-soft: #f8fafc;
            --sidebar-bg: #1e293b;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --shadow: 0 10px 40px rgba(0,0,0,0.06);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body { background-color: var(--bg-soft); color: var(--text-main); overflow-x: hidden; }

        .app-container { display: flex; min-height: 100vh; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 280px; background: var(--sidebar-bg); color: white;
            padding: 30px 20px; display: flex; flex-direction: column;
            position: fixed; height: 100vh; z-index: 100;
        }

        .brand { padding: 0 15px 40px; font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
        .brand span span { color: #ff4d4d; }

        .sidebar-nav { flex-grow: 1; }
        .sidebar a {
            display: flex; align-items: center; gap: 12px; padding: 14px 18px;
            text-decoration: none; color: #94a3b8; border-radius: 12px;
            margin-bottom: 8px; transition: 0.3s;
        }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: white; box-shadow: 0 10px 20px rgba(199, 0, 0, 0.3); }

        /* --- MAIN CONTENT --- */
        .content-area { flex: 1; margin-left: 280px; padding: 0 40px 40px; }

        .topbar {
            height: 90px; display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 20px;
        }

        .user-profile {
            display: flex; align-items: center; gap: 12px; background: white;
            padding: 8px 15px; border-radius: 50px; box-shadow: var(--shadow);
        }

        .avatar {
            width: 35px; height: 35px; background: var(--primary); color: white;
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-weight: bold; font-size: 14px;
        }

        /* --- EXAM TABLE CARD --- */
        .table-card {
            background: white; border-radius: 24px; padding: 30px;
            box-shadow: var(--shadow); border: 1px solid rgba(0,0,0,0.03);
        }

        .table-header { margin-bottom: 25px; }
        .table-header h2 { font-size: 24px; font-weight: 700; }
        .table-header p { color: var(--text-muted); font-size: 14px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--text-muted); font-weight: 600; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 20px 15px; border-bottom: 1px solid #f1f5f9; font-size: 15px; vertical-align: middle; }

        .exam-title-cell { font-weight: 600; color: var(--sidebar-bg); }
        .score-badge { font-size: 12px; color: #059669; background: #ecfdf5; padding: 2px 8px; border-radius: 6px; margin-left: 8px; font-weight: 700; }

        .status-pill { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
        .status-open { background: #dcfce7; color: #166534; }
        .status-completed { background: #f1f5f9; color: #64748b; }

        .btn-start {
            background: var(--primary); color: white; text-decoration: none;
            padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 13px;
            transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-start:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(199, 0, 0, 0.2); }

        .logout-btn-side { margin-top: auto; color: #ef4444; text-decoration: none; font-weight: 600; text-align: center; padding: 15px; background: #fee2e2; border-radius: 12px; transition: 0.3s; }
        .logout-btn-side:hover { background: #fca5a5; }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
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
            <a href="index.php">
                <i class="fa-solid fa-house"></i> <span>Dashboard</span>
            </a>
            <a href="available_exams.php" class="active">
                <i class="fa-solid fa-file-pen"></i> <span>Available Exams</span>
            </a>
            <a href="results.php">
                <i class="fa-solid fa-chart-simple"></i> <span>My Results</span>
            </a>
            <a href="profile.php">
                <i class="fa-solid fa-user-gear"></i> <span>Profile Settings</span>
            </a>
            <a href="instructions.php">
                <i class="fa-solid fa-circle-info"></i> <span>Instructions</span>
            </a>
        </nav>

        <a href="logout.php" class="logout-btn-side">
            <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
        </a>
    </aside>

    <main class="content-area">
        <header class="topbar">
            <div style="color: var(--text-muted); font-size: 14px; font-weight: 500;">
                <i class="fa-regular fa-calendar"></i> <?php echo date("l, d M Y"); ?>
            </div>
            <div class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($studentName, 0, 1)); ?></div>
                <span style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($studentName); ?></span>
            </div>
        </header>

        <div class="table-card">
            <div class="table-header">
                <h2>Available Examinations</h2>
                <p>Select an exam to begin. Ensure you have a stable internet connection.</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Exam Information</th>
                        <th>Max Marks</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $exams = $conn->query("SELECT * FROM exams ORDER BY id DESC");

                    if ($exams && $exams->num_rows > 0) {
                        while ($exam = $exams->fetch_assoc()) {
                            // Corrected logic for checking if exam is taken
                            $exam_id_val = $exam['id'];
                            $check = $conn->query("SELECT * FROM results WHERE user_id=$student_id AND exam_id=$exam_id_val");
                            $taken = ($check && $check->num_rows > 0);
                            
                            $status_html = $taken 
                                ? "<span class='status-pill status-completed'><i class='fa-solid fa-check-double'></i> Attempted</span>" 
                                : "<span class='status-pill status-open'>Active</span>";
                            
                            $action_html = $taken 
                                ? "<span style='color:var(--text-muted); font-size:13px;'>Locked</span>" 
                                : "<a href='take_exam.php?exam_id=$exam_id_val' class='btn-start'>Begin Exam <i class='fa-solid fa-chevron-right'></i></a>";

                            $score_html = '';
                            if ($taken) {
                                $res_data = $check->fetch_assoc();
                                $score_html = "<span class='score-badge'>Scored: " . $res_data['score'] . "/" . $exam['total_marks'] . "</span>";
                            }

                            // Corrected Echo with single quotes for HTML attributes
                            echo "<tr>
                                <td>
                                    <div class='exam-title-cell'>" . htmlspecialchars($exam['title']) . "</div>
                                    $score_html
                                </td>
                                <td><i class='fa-solid fa-star' style='color:#f59e0b'></i> " . $exam['total_marks'] . " Pts</td>
                                <td><i class='fa-regular fa-clock'></i> " . $exam['duration'] . " Min</td>
                                <td>$status_html</td>
                                <td>$action_html</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:40px; color:var(--text-muted);'>No examinations are currently scheduled.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <p style="margin-top: 30px; text-align: center; color: var(--text-muted); font-size: 13px;">
            TestHub CU &copy; <?php echo date("Y"); ?> • Innovation in Education
        </p>
    </main>
</div>

</body>
</html>