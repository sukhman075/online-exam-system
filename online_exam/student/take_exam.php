<?php
session_start();
include "../db/connection.php";

if(!isset($_SESSION['student_id'])){
    header("Location: login.php");
    exit();
}

$student_id  = $_SESSION['student_id'];
$studentName = $_SESSION['student'];
$exam_id     = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$submitted   = false;

if(!$exam_id) die("No exam selected!");

$stmt = $conn->prepare("SELECT * FROM exams WHERE id=?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
if(!$exam) die("Exam not found!");

if(!isset($_SESSION['exam_start_'.$exam_id])){
    $_SESSION['exam_start_'.$exam_id] = time();
}

if(isset($_POST['submit_exam'])){

    $user_answers = $_POST['answers'] ?? [];
    $score = 0;
    $questions = $_SESSION['exam_questions_'.$exam_id] ?? [];
    $answers_to_save = [];

    if(count($questions) > 0){
        $marks_per_q = $exam['total_marks'] / count($questions);

        foreach($questions as $q){
            $qid = $q['id'];
            $correct = trim($q['correct_option']);
            $provided = isset($user_answers[(string)$qid]) 
                        ? trim($user_answers[(string)$qid]) 
                        : '';

            $answers_to_save[(string)$qid] = strtolower($provided);

            if(strtolower($provided) === strtolower($correct)){
                $score += $marks_per_q;
            }
        }
    }

    $final_score = round($score, 2);
    $answers_json = json_encode($answers_to_save);

    $st = $conn->prepare("INSERT INTO results (user_id, exam_id, score, answers_json) VALUES (?, ?, ?, ?)");
    $st->bind_param("iids", $student_id, $exam_id, $final_score, $answers_json);

    if($st->execute()){
        unset($_SESSION['exam_questions_'.$exam_id]);
        unset($_SESSION['exam_start_'.$exam_id]);
        $submitted = true;
    }
}

if(!$submitted && !isset($_SESSION['exam_questions_'.$exam_id])){
    $q_res = $conn->query("SELECT * FROM questions WHERE exam_id=$exam_id ORDER BY RAND()");
    $qs = [];
    while($r = $q_res->fetch_assoc()) $qs[] = $r;
    $_SESSION['exam_questions_'.$exam_id] = $qs;
}

$questions = $_SESSION['exam_questions_'.$exam_id] ?? [];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Live Exam</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body{font-family:'Plus Jakarta Sans', sans-serif;background:#f4f6fb;margin:0;}
.exam-header{background:#1e293b;color:#fff;display:flex;justify-content:space-between;align-items:center;padding:20px 40px;}
.container{width:70%;margin:30px auto;}
.q-card{background:#fff;padding:20px;border-radius:10px;margin-bottom:20px;box-shadow:0 5px 15px rgba(0,0,0,0.05);}
.option{display:block;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:8px;cursor:pointer;}
.option:hover{background:#f1f5ff;}
.option.active{border-color:#2563eb;background:#e0e7ff;}
.btn{padding:12px 20px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;}
.btn:hover{background:#1d4ed8;}
#start-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.8);display:flex;flex-direction:column;justify-content:center;align-items:center;color:#fff;z-index:1000;}
.success-overlay{position:fixed;inset:0;background:#f4f6fb;display:flex;justify-content:center;align-items:center;}
.success-card{background:#fff;padding:40px;border-radius:10px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);}

/* Professional Alert */
#warningBox{position:fixed;top:20px;right:20px;z-index:9999;}
.alert-box{min-width:280px;padding:14px 18px;margin-bottom:12px;border-radius:8px;font-weight:600;color:#fff;box-shadow:0 8px 25px rgba(0,0,0,0.15);}
.alert-warning{background:#f59e0b;}
.alert-danger{background:#ef4444;}
.alert-success{background:#22c55e;}
</style>
</head>
<body>

<?php if($submitted): ?>
<div class="success-overlay">
<div class="success-card">
<i class="fa-solid fa-circle-check" style="font-size:50px;color:green;"></i>
<h1>Submission Successful</h1>
<a href="exam_complete.php?exam_id=<?php echo $exam_id; ?>" class="btn">
    Continue
</a>
</div>
</div>
<?php else: ?>

<div id="start-overlay">
<h2>Ready to Begin?</h2>
<button onclick="initExam()" class="btn">Enter Exam Room</button>
</div>

<header class="exam-header">
<div>
<h2><?php echo htmlspecialchars($exam['title']); ?></h2>
<span><?php echo htmlspecialchars($studentName); ?></span>
</div>
<div><i class="fa-solid fa-clock"></i> <span id="time">00:00</span></div>
</header>

<div class="container">
<form method="post" id="examForm">

<?php foreach($questions as $i => $q): ?>
<div class="q-card">
<p><strong>Question <?php echo $i+1; ?></strong></p>
<p><?php echo htmlspecialchars($q['question']); ?></p>

<?php foreach(['a','b','c','d'] as $o): ?>
<label class="option">
<input type="radio"
name="answers[<?php echo $q['id']; ?>]"
value="<?php echo strtoupper($o); ?>"
onchange="highlight(this)">
<?php echo htmlspecialchars($q['option_'.$o]); ?>
</label>
<?php endforeach; ?>
</div>
<?php endforeach; ?>

<button type="submit" name="submit_exam" class="btn">SUBMIT FINAL ANSWERS</button>
</form>
</div>

<script>
let timeLeft = <?php
$allowed = $exam['duration'] * 60;
$elapsed = time() - $_SESSION['exam_start_'.$exam_id];
echo max(0, $allowed - $elapsed);
?>;

let timerStarted=false;
let violationCount=0;
let maxViolations=3;
let timerInterval;
let isRestoringFullscreen=false;

function showAlert(msg,type="warning"){
let box=document.getElementById("warningBox");
let div=document.createElement("div");
div.className="alert-box alert-"+type;
div.innerText=msg;
box.appendChild(div);
setTimeout(()=>div.remove(),3000);
}

function initExam(){
document.getElementById('start-overlay').style.display='none';
document.documentElement.requestFullscreen().catch(()=>{});
startTimer();
}

function startTimer(){
if(timerStarted)return;
timerStarted=true;
timerInterval=setInterval(()=>{
let m=Math.floor(timeLeft/60);
let s=timeLeft%60;
document.getElementById('time').textContent=m+":"+(s<10?"0":"")+s;
if(timeLeft<=0){
clearInterval(timerInterval);
showAlert("Time is up. Submitting exam...","danger");
setTimeout(()=>autoSubmitExam(),1200);
}
timeLeft--;
},1000);
}

function autoSubmitExam(){
clearInterval(timerInterval);
let form=document.getElementById("examForm");
let hidden=document.createElement("input");
hidden.type="hidden";
hidden.name="submit_exam";
hidden.value="1";
form.appendChild(hidden);
form.submit();
}

function handleViolation(type){
violationCount++;
showAlert(type+" detected ("+violationCount+"/3)","warning");

if(violationCount>=maxViolations){
showAlert("Exam terminated due to rule violations.","danger");
setTimeout(()=>autoSubmitExam(),1200);
return;
}

setTimeout(()=>{
if(!document.fullscreenElement){
isRestoringFullscreen=true;
document.documentElement.requestFullscreen().catch(()=>{});
}
},600);
}

document.addEventListener("visibilitychange",()=>{
if(document.hidden && timerStarted){
handleViolation("Tab switching");
}
});

document.addEventListener("fullscreenchange",()=>{
if(!timerStarted)return;
if(isRestoringFullscreen){isRestoringFullscreen=false;return;}
if(!document.fullscreenElement){
handleViolation("Fullscreen exit");
}
});

document.querySelector("button[name='submit_exam']")
.addEventListener("click",function(e){
e.preventDefault();
showAlert("Submitting your exam. Please wait...","success");
setTimeout(()=>autoSubmitExam(),800);
});

function highlight(el){
let card=el.closest('.q-card');
card.querySelectorAll('.option').forEach(o=>o.classList.remove('active'));
el.parentElement.classList.add('active');
}

document.addEventListener('contextmenu',e=>e.preventDefault());
</script>

<?php endif; ?>
<div id="warningBox"></div>
</body>
</html>