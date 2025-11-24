<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all students
$sql = "SELECT * FROM users WHERE user_type = 'student' ORDER BY username";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profiles - Job Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <h1>Job Portal</h1>
            </div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="messages.php">Messages</a></li>
                <?php if ($_SESSION['user_type'] == 'employer'): ?>
                    <li><a href="view_applications.php">View Applications</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2>Student Profiles</h2>
        <div class="student-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($student = $result->fetch_assoc()): ?>
                    <div class="student-card">
                        <h3><?php echo htmlspecialchars($student['username']); ?></h3>
                        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                        <p><strong>Skills:</strong> <?php echo htmlspecialchars($student['skills']); ?></p>
                        <div class="student-actions">
                            <a href="profile.php?id=<?php echo $student['id']; ?>" class="btn">View Profile</a>
                            <a href="messages.php?user=<?php echo $student['id']; ?>" class="btn">Send Message</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No students found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 
