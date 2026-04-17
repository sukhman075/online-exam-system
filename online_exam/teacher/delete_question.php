<?php
session_start();
include "../db/connection.php";

if(!isset($_SESSION['teacher_id'])){
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($question_id <= 0) die("Invalid question ID.");

// Check ownership
$stmt = $conn->prepare("
    SELECT q.exam_id, e.created_by 
    FROM questions q
    JOIN exams e ON q.exam_id = e.id
    WHERE q.id=?
");
$stmt->bind_param("i", $question_id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows == 0) die("Question not found.");
$row = $res->fetch_assoc();
if($row['created_by'] != $teacher_id) die("Unauthorized action.");

// Delete question
$stmt_del = $conn->prepare("DELETE FROM questions WHERE id=?");
$stmt_del->bind_param("i", $question_id);
$stmt_del->execute();
$stmt_del->close();

header("Location: add_questions.php?exam_id=".$row['exam_id']);
exit();
