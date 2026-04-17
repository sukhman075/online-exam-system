<?php
session_start();
date_default_timezone_set('Asia/Kolkata'); // Change to your local timezone
include "../db/connection.php";

// Ensure FPDF path is correct based on your folder structure
require(__DIR__ . "/certificate/fpdf.php");

// 1. Security Check
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['exam_id'])) {
    die("Invalid Request: No Exam ID provided.");
}

$student_id = $_SESSION['student_id'];
$exam_id = intval($_GET['exam_id']);

// 2. Fetch Data (Removed r.created_at to fix the SQL error)
$query = "SELECT u.name, r.score, e.total_marks, e.title 
          FROM results r
          JOIN users u ON r.user_id = u.id
          JOIN exams e ON r.exam_id = e.id
          WHERE r.user_id = ? AND r.exam_id = ?
          ORDER BY r.id DESC LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $student_id, $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: No result found for this exam.");
}

$data = $result->fetch_assoc();
$percentage = ($data['score'] / $data['total_marks']) * 100;

// 3. Passing Grade Check
if ($percentage < 33) {
    die("Result found, but score (" . round($percentage) . "%) is below the 33% passing threshold.");
}

// 4. PDF Generation Logic
class Certificate extends FPDF {
    // Helper to draw the frame
    function DrawFrame() {
        // Dark Blue Outer Border
        $this->SetDrawColor(44, 62, 80); 
        $this->SetLineWidth(3);
        $this->Rect(5, 5, 287, 200);

        // Gold Inner Border
        $this->SetDrawColor(190, 160, 50); 
        $this->SetLineWidth(1.5);
        $this->Rect(10, 10, 277, 190);
    }
}

$pdf = new Certificate("L", "mm", "A4");
$pdf->AddPage();
$pdf->DrawFrame();

// --- Header ---
$pdf->Ln(25);
$pdf->SetTextColor(44, 62, 80);
$pdf->SetFont("Arial", "B", 35);
$pdf->Cell(0, 20, "CERTIFICATE OF ACHIEVEMENT", 0, 1, "C");

$pdf->SetFont("Arial", "I", 16);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 10, "This is to certify that", 0, 1, "C");

$pdf->Ln(10);

// --- Student Name ---
$pdf->SetTextColor(190, 160, 50); // Gold
$pdf->SetFont("Arial", "B", 32);
$pdf->Cell(0, 20, strtoupper($data['name']), 0, 1, "C");

$pdf->Ln(5);

// --- Content Body ---
$pdf->SetTextColor(44, 62, 80);
$pdf->SetFont("Arial", "", 16);
$pdf->MultiCell(0, 10, "has successfully completed the examination for", 0, "C");

$pdf->SetFont("Arial", "B", 22);
$pdf->Cell(0, 15, '"' . $data['title'] . '"', 0, 1, "C");

$pdf->Ln(10);

// --- Scores ---
$pdf->SetFont("Arial", "", 14);
$pdf->Cell(0, 10, "Final Result: " . $data['score'] . " / " . $data['total_marks'] . " (" . round($percentage) . "%)", 0, 1, "C");

// --- Footer / Signatures ---
$pdf->SetY(-55);

// Date and ID on the left
$cert_id = "VERIFY-" . strtoupper(substr(md5($student_id . $exam_id), 0, 8));
$pdf->SetX(25);
$pdf->SetFont("Arial", "", 11);
$pdf->Cell(0, 6, "Issue Date: " . date("F j, Y"), 0, 1, "L");
$pdf->SetX(25);
$pdf->Cell(0, 6, "Certificate ID: " . $cert_id, 0, 0, "L");

// Signature on the right
$pdf->SetY(-65);
$pdf->SetX(200);
$pdf->SetFont("Arial", "B", 14);
$pdf->Cell(70, 10, "EXAM DIRECTOR", 0, 1, "C"); 
$pdf->Line(210, 153, 270, 153); // Signature Line
$pdf->SetX(200);
$pdf->SetFont("Arial", "I", 10);
$pdf->Cell(70, 10, "Online Examination System", 0, 1, "C");

// 5. Output PDF to Browser
$pdf->Output("I", "Certificate_" . $data['name'] . ".pdf");
exit();
?>