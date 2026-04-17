<?php
session_start();
include "../db/connection.php";

if(!isset($_SESSION['student_id'])) die("Access Denied");

$studentId = $_SESSION['student_id'];
$examId    = intval($_GET['exam_id'] ?? 0);
$resultId  = intval($_GET['result_id'] ?? 0);

if(!$examId || !$resultId) die("Invalid request");

// Fetch result and exam details
$stmt = $conn->prepare("
    SELECT r.score, r.answers_json, e.total_marks, e.title
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.user_id=? AND r.exam_id=? AND r.id=?
");
$stmt->bind_param("iii", $studentId, $examId, $resultId);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if(!$data) die("No data found.");

$student_answers = json_decode($data['answers_json'], true) ?? [];
$student_answers = array_combine(
    array_map('strval', array_keys($student_answers)),
    array_values($student_answers)
);

// Fetch questions
$stmtQ = $conn->prepare("SELECT * FROM questions WHERE exam_id=? ORDER BY id ASC");
$stmtQ->bind_param("i", $examId);
$stmtQ->execute();
$q_res = $stmtQ->get_result();
?>

<style>
.review-question { border:1px solid #e5e7eb; border-radius:16px; padding:20px; margin-bottom:15px; }
.q-header { display:flex; justify-content:space-between; margin-bottom:12px; }
.q-num { font-weight:700; color:#64748b; }
.q-status { font-weight:700; text-transform:uppercase; font-size:12px; }
.option-item { padding:12px 18px; border-radius:12px; margin-bottom:8px; border:1px solid #e5e7eb; display:flex; justify-content:space-between; font-size:14px; }
.correct { background:#f0fdf4; border-color:#bbf7d0; color:#16a34a; }
.wrong { background:#fef2f2; border-color:#fecaca; color:#dc2626; }
.selected { border-color:#94a3b8; background:#f8fafc; }
</style>

<h2 style="margin-bottom:20px;"><?php echo htmlspecialchars($data['title']); ?> - Review</h2>
<p style="margin-bottom:15px;">Score: <strong><?php echo $data['score']; ?>/<?php echo $data['total_marks']; ?></strong></p>

<?php
$qIndex = 1;
while($q = $q_res->fetch_assoc()):
    $qid = (string)$q['id'];
    $student_ans = $student_answers[$qid] ?? null;
    $correct_ans = strtolower($q['correct_option']);
?>
<div class="review-question">
    <div class="q-header">
        <span class="q-num">Q<?php echo $qIndex; ?>.</span>
        <span class="q-status">
            <?php
            if(!$student_ans) echo 'Not Answered';
            elseif($student_ans === $correct_ans) echo 'Correct';
            else echo 'Incorrect';
            ?>
        </span>
    </div>
    <div style="margin-bottom:12px;"><?php echo htmlspecialchars($q['question']); ?></div>
    <?php foreach(['a','b','c','d'] as $o):
        $cls = '';
        if($o === $correct_ans) $cls = 'correct';
        if($student_ans === $o && $student_ans !== $correct_ans) $cls = 'wrong';
        if($student_ans === $o && $student_ans === $correct_ans) $cls = 'correct';
    ?>
        <div class="option-item <?php echo $cls; ?>">
            <span><?php echo strtoupper($o); ?>. <?php echo htmlspecialchars($q['option_'.$o]); ?></span>
            <?php if($o === $correct_ans) echo '<span>✅</span>'; ?>
        </div>
    <?php endforeach; ?>
    <?php if($student_ans && $student_ans !== $correct_ans): ?>
        <div style="margin-top:5px; font-size:13px; color:#dc2626;">Your answer: <?php echo strtoupper($student_ans); ?></div>
    <?php endif; ?>
</div>
<?php
$qIndex++;
endwhile;

$stmt->close();
$stmtQ->close();
$conn->close();
?>
