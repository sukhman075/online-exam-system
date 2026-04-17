<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['student_id'])) exit();

$user_id = $_SESSION['student_id'];
$exam_id = $_POST['exam_id'];
$answers = $_POST['answers'];
$time_left = $_POST['time_left'];

$stmt = $conn->prepare(
    "UPDATE results SET answers_json=?, time_left=? 
     WHERE user_id=? AND exam_id=? AND status='in_progress'"
);
$stmt->bind_param("siii", $answers, $time_left, $user_id, $exam_id);
$stmt->execute();
