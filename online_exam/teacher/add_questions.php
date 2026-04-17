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

$success = '';
$error   = '';

// 1. Get current exam ID from URL if redirected from create_exam
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// 2. Handle adding new question
if(isset($_POST['add_question'])){
    $exam_id = intval($_POST['exam_id']);
    $question = trim($_POST['question']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_option = strtoupper(trim($_POST['correct_option']));

    if($exam_id <= 0){
        $error = "Please select an exam first.";
    } else {
        $stmt = $conn->prepare("INSERT INTO questions (exam_id, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $exam_id, $question, $option_a, $option_b, $option_c, $option_d, $correct_option);

        if($stmt->execute()){
            $success = "Question added to the database!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// 3. Fetch data for dropdown and table
$exams_list = $conn->query("SELECT id, title FROM exams WHERE created_by=$teacher_id ORDER BY id DESC");

$questions = [];
if($exam_id > 0){
    $stmt_q = $conn->prepare("SELECT * FROM questions WHERE exam_id=? ORDER BY id DESC");
    $stmt_q->bind_param("i", $exam_id);
    $stmt_q->execute();
    $res_q = $stmt_q->get_result();
    while($row = $res_q->fetch_assoc()){ $questions[] = $row; }
    $stmt_q->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Questions | TestHub CU</title>
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

        /* MAIN */
        .main { flex: 1; padding: 40px; }
        .header-box { margin-bottom: 35px; }
        .header-box h2 { font-size: 28px; font-weight: 800; }

        /* FORM CARD */
        .content-card { background: #fff; border-radius: 24px; padding: 35px; box-shadow: var(--shadow); margin-bottom: 30px; border: 1px solid rgba(0,0,0,0.02); }
        .content-card h3 { font-size: 20px; font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }

        label { display: block; font-size: 13px; font-weight: 700; color: #64748b; margin-bottom: 8px; }
        input, select { 
            width: 100%; padding: 14px 18px; border-radius: 12px; 
            border: 1px solid #e2e8f0; font-size: 15px; outline: none; transition: 0.3s;
        }
        input:focus, select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(199,0,0,0.05); }

        .btn-add { 
            background: var(--primary); color: white; border: none; padding: 16px; 
            border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s;
            margin-top: 20px; width: 100%; font-size: 16px;
        }
        .btn-add:hover { opacity: 0.9; transform: translateY(-2px); }

        /* TABLE */
        .table-container { background: #fff; border-radius: 24px; padding: 20px; box-shadow: var(--shadow); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f1f5f9; }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .correct-badge { background: #f0fdf4; color: #16a34a; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 12px; }
        .action-btns a { color: #94a3b8; margin-right: 15px; transition: 0.2s; }
        .action-btns a:hover { color: var(--primary); }

        .msg-success { background: #f0fdf4; color: #16a34a; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; }

        @media (max-width: 1000px) { .form-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } .sidebar { display: none; } }
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
            <a href="add_questions.php" class="active"><i class="fa-solid fa-list-check"></i> Add Questions</a>
            <a href="results.php"><i class="fa-solid fa-chart-line"></i> Exam Results</a>
            <a href="profile.php"><i class="fa-solid fa-gears"></i> My Settings</a>
        </nav>
    </aside>

    <main class="main">
        <div class="header-box">
            <h2>Question Bank</h2>
            <p>Select an exam and populate it with multiple-choice questions.</p>
        </div>

        <?php if($success): ?>
            <div class="msg-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="content-card">
            <h3><i class="fa-solid fa-pen-to-square"></i> Question Details</h3>
            <form method="post">
                <div class="form-grid">
                    <div class="full-width">
                        <label>Select Target Exam</label>
                        <select name="exam_id" required onchange="location.href='add_questions.php?exam_id=' + this.value">
                            <option value="">-- Click to choose exam --</option>
                            <?php while($ex = $exams_list->fetch_assoc()): ?>
                                <option value="<?php echo $ex['id']; ?>" <?php if($ex['id']==$exam_id) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($ex['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="full-width">
                        <label>The Question</label>
                        <input type="text" name="question" placeholder="e.g. What is the time complexity of a Binary Search?" required>
                    </div>

                    <div>
                        <label>Option A</label>
                        <input type="text" name="option_a" placeholder="Enter option A" required>
                    </div>
                    <div>
                        <label>Option B</label>
                        <input type="text" name="option_b" placeholder="Enter option B" required>
                    </div>
                    <div>
                        <label>Option C</label>
                        <input type="text" name="option_c" placeholder="Enter option C" required>
                    </div>
                    <div>
                        <label>Option D</label>
                        <input type="text" name="option_d" placeholder="Enter option D" required>
                    </div>

                    <div class="full-width">
                        <label>Correct Answer</label>
                        <select name="correct_option" required>
                            <option value="A">Option A</option>
                            <option value="B">Option B</option>
                            <option value="C">Option C</option>
                            <option value="D">Option D</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_question" class="btn-add">Confirm & Add Question</button>
            </form>
        </div>

        <?php if($exam_id > 0): ?>
            <div class="header-box" style="margin-top: 50px; display: flex; justify-content: space-between; align-items: center;">
                <h3>Questions List <span style="font-size: 14px; background: #eee; padding: 4px 10px; border-radius: 8px; margin-left: 10px;"><?php echo count($questions); ?> total</span></h3>
            </div>
            
            <div class="table-container">
                <?php if(count($questions) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Question Text</th>
                                <th width="100">Correct</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($questions as $i => $q): ?>
                                <tr>
                                    <td><?php echo $i+1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($q['question']); ?></strong></td>
                                    <td><span class="correct-badge"><?php echo $q['correct_option']; ?></span></td>
                                    <td class="action-btns">
                                        <a href="edit_question.php?id=<?php echo $q['id']; ?>"><i class="fa-solid fa-pen"></i></a>
                                        <a href="delete_question.php?id=<?php echo $q['id']; ?>" onclick="return confirm('Remove this question permanentely?');"><i class="fa-solid fa-trash-can"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #94a3b8; padding: 20px;">No questions found for this exam. Start by adding one above!</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

</body>
</html>