<?php
session_start();
include "../db/connection.php";

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

$adminName = $_SESSION['admin'];
$currentPage = basename($_SERVER['PHP_SELF']);

// Handle Add Student
$successMessage = $errorMessage = "";
if(isset($_POST['add_student'])){
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if($check->num_rows > 0){
        $errorMessage = "Student with this email already exists!";
    } else {
        $insert = $conn->query("INSERT INTO users (name, email, password, role) VALUES ('$name','$email','$password','student')");
        if($insert){
            $successMessage = "Student added successfully!";
        } else {
            $errorMessage = "Failed to add student.";
        }
    }
}

// Handle Delete Student
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id=$id AND role='student'");
    header("Location: manage_students.php");
    exit();
}

// Fetch all students
$students = $conn->query("SELECT id, name, email FROM users WHERE role='student' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Students | Admin Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body { background:#f4f6fb; color:#333; }
.navbar { height:70px; background:linear-gradient(135deg,#c70000,#ff4d4d); color:#fff; display:flex; align-items:center; justify-content:space-between; padding:0 30px; box-shadow:0 8px 20px rgba(0,0,0,0.2); position:sticky; top:0; z-index:1000; }
.navbar h2 { font-size:20px; font-weight:600; }
.navbar a { color:#fff; text-decoration:none; font-weight:600; background:rgba(255,255,255,0.2); padding:8px 18px; border-radius:12px; transition:0.3s; }
.navbar a:hover { background:#fff; color:#c70000; }
.container { display:flex; min-height:calc(100vh - 70px); }
.sidebar { width:250px; background:#fff; padding:30px 20px; box-shadow:5px 0 20px rgba(0,0,0,0.08); position:sticky; top:70px; height:calc(100vh - 70px); border-radius:0 20px 20px 0; }
.sidebar a { display:block; padding:14px 20px; margin-bottom:14px; text-decoration:none; color:#333; font-weight:500; border-radius:12px; transition:all 0.3s ease; position:relative; }
.sidebar a.active { background:#ffe1e1; color:#c70000; }
.sidebar a::before { content:''; position:absolute; left:0; top:0; width:4px; height:100%; background:#c70000; border-radius:2px; opacity:0; transition:opacity 0.3s ease; }
.sidebar a.active::before { opacity:1; }
.sidebar a:hover::before { opacity:1; }
.sidebar a:hover { background:#ffe1e1; color:#c70000; }
.main { flex:1; padding:40px 50px; }
.main h2 { font-size:26px; font-weight:600; margin-bottom:10px; }
.main .welcome { color:#777; font-size:15px; margin-bottom:25px; }
.message { color:green; margin-bottom:15px; font-weight:600; }
.error { color:red; margin-bottom:15px; font-weight:600; }

/* Form */
form { background:#fff; padding:20px; border-radius:15px; box-shadow:0 20px 40px rgba(0,0,0,0.08); margin-bottom:30px; max-width:600px; }
form input { width:100%; padding:12px 10px; margin-bottom:12px; border-radius:10px; border:1px solid #ccc; }
form button { padding:12px 20px; background:linear-gradient(135deg,#c70000,#ff4d4d); color:#fff; border:none; border-radius:10px; font-weight:600; cursor:pointer; transition:0.3s; }
form button:hover { opacity:0.9; }

/* Table */
table { width:100%; border-collapse:collapse; background:#fff; border-radius:15px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.08); }
table th, table td { padding:12px 15px; text-align:left; }
table th { background:#c70000; color:#fff; }
table tr:nth-child(even) { background:#f9f9f9; }
table a { color:#dc3545; text-decoration:none; font-weight:600; }
table a:hover { text-decoration:underline; }

/* Footer */
.footer { margin-top:30px; font-size:14px; color:#888; text-align:center; }
@media(max-width:900px) { .container { flex-direction:column; } .sidebar { width:100%; height:auto; border-radius:0 0 20px 20px; } .main { padding:25px 20px; } }
</style>
</head>
<body>

<div class="navbar">
    <h2>🛠️ Admin Panel</h2>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <div class="sidebar">
        <a href="index.php">🏠 Dashboard</a>
        <a href="manage_students.php" class="active">👨‍🎓 Manage Students</a>
        <a href="manage_teachers.php">👩‍🏫Manage Teachers</a>

        <a href="view_results.php">📊 View Results</a>
        <a href="profile.php">👤 My Profile</a>
    </div>

    <div class="main">
        <h2>Manage Students</h2>
        <div class="welcome">Add, edit, or remove students</div>

        <?php if($successMessage) echo "<div class='message'>$successMessage</div>"; ?>
        <?php if($errorMessage) echo "<div class='error'>$errorMessage</div>"; ?>

        <!-- Add Student Form -->
        <form method="POST" action="">
            <h3>Add New Student</h3>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="add_student">Add Student</button>
        </form>

        <!-- Students Table -->
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
            <?php while($row = $students->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <a href="manage_students.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <div class="footer">
            TestHub CU © <?php echo date("Y"); ?> • Online Examination System
        </div>
    </div>
</div>

</body>
</html>
