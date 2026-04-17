<?php
session_start();
include "../db/connection.php";

if(!isset($_SESSION['teacher_id'])){
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = $error = '';

// Fetch question and exam info
$stmt = $conn->prepare("
    SELECT q.*, e.title AS exam_title, e.created_by 
    FROM questions q 
    JOIN exams e ON q.exam_id = e.id 
    WHERE q.id = ?
");
$stmt->bind_param("i", $question_id);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows == 0){
    die("Question not found.");
}

$question = $res->fetch_assoc();

// Make sure this teacher owns the exam
if($question['created_by'] != $teacher_id){
    die("Unauthorized access.");
}

// Handle form submission
if(isset($_POST['update_question'])){
    $q_text = trim($_POST['question']);
    $opt_a = trim($_POST['option_a']);
    $opt_b = trim($_POST['option_b']);
    $opt_c = trim($_POST['option_c']);
    $opt_d = trim($_POST['option_d']);
    $correct = strtoupper(trim($_POST['correct_option']));

    if(!in_array($correct, ['A','B','C','D'])){
        $error = "Correct option must be A, B, C, or D.";
    } else {
        $stmt_up = $conn->prepare("
            UPDATE questions SET question=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=? 
            WHERE id=?
        ");
        $stmt_up->bind_param("ssssssi", $q_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $question_id);

        if($stmt_up->execute()){
            $success = "Question updated successfully!";
            $question['question'] = $q_text;
            $question['option_a'] = $opt_a;
            $question['option_b'] = $opt_b;
            $question['option_c'] = $opt_c;
            $question['option_d'] = $opt_d;
            $question['correct_option'] = $correct;
        } else {
            $error = "Error updating question: " . $stmt_up->error;
        }
        $stmt_up->close();
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Question | <?php echo htmlspecialchars($question['exam_title']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f4f6fb;padding:30px;color:#333;}
.form-card{background:#fff;padding:25px;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,0.08);max-width:600px;margin:auto;}
input{width:100%;padding:12px;margin:10px 0;border-radius:10px;border:1px solid #ccc;}
button{padding:12px 18px;background:linear-gradient(135deg,#c70000,#ff4d4d);color:#fff;border:none;border-radius:12px;font-weight:600;cursor:pointer;}
button:hover{opacity:0.9;}
.success{color:green;margin-bottom:10px;}
.error{color:red;margin-bottom:10px;}
a{color:#c70000;text-decoration:none;}
</style>
</head>
<body>
<div class="form-card">
    <h2>Edit Question for "<?php echo htmlspecialchars($question['exam_title']); ?>"</h2>

    <?php if($success) echo "<p class='success'>$success</p>"; ?>
    <?php if($error) echo "<p class='error'>$error</p>"; ?>

    <form method="post">
        <input type="text" name="question" value="<?php echo htmlspecialchars($question['question']); ?>" placeholder="Question" required>
        <input type="text" name="option_a" value="<?php echo htmlspecialchars($question['option_a']); ?>" placeholder="Option A" required>
        <input type="text" name="option_b" value="<?php echo htmlspecialchars($question['option_b']); ?>" placeholder="Option B" required>
        <input type="text" name="option_c" value="<?php echo htmlspecialchars($question['option_c']); ?>" placeholder="Option C" required>
        <input type="text" name="option_d" value="<?php echo htmlspecialchars($question['option_d']); ?>" placeholder="Option D" required>
        <input type="text" name="correct_option" maxlength="1" value="<?php echo htmlspecialchars($question['correct_option']); ?>" placeholder="Correct Option (A/B/C/D)" required>
        <button type="submit" name="update_question">Update Question</button>
    </form>
    <p><a href="add_questions.php?exam_id=<?php echo $question['exam_id']; ?>">← Back to Questions</a></p>
</div>
</body>
</html>
