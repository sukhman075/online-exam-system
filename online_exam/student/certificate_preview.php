<?php
session_start();

if(!isset($_GET['exam_id'])){
    die("Invalid request");
}

$exam_id = intval($_GET['exam_id']);
?>

<!DOCTYPE html>
<html>
<head>
<title>Certificate Preview</title>
<style>
body{
    margin:0;
    background:#f4f6fb;
    font-family:Arial;
    text-align:center;
}
.topbar{
    padding:15px;
    background:white;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}
.btn{
    padding:10px 18px;
    background:#c70000;
    color:white;
    text-decoration:none;
    border-radius:6px;
    margin:5px;
    display:inline-block;
}
.btn:hover{
    background:#a50000;
}
iframe{
    width:90%;
    height:85vh;
    border:none;
    margin-top:15px;
    box-shadow:0 10px 30px rgba(0,0,0,0.15);
}
</style>
</head>
<body>

<div class="topbar">
    <a href="generate_certificate.php?exam_id=<?php echo $exam_id; ?>" class="btn">
        Download Certificate
    </a>

    <a href="index.php" class="btn" style="background:#555;">
        Back to Dashboard
    </a>
</div>

<iframe src="generate_certificate.php?exam_id=<?php echo $exam_id; ?>"></iframe>

</body>
</html>