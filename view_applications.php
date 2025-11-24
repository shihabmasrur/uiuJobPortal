<?php
require_once 'config.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: login.php");
    exit();
}

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

// Get job details
$sql = "SELECT * FROM jobs WHERE id = ? AND employer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();

if (!$job) {
    header("Location: index.php");
    exit();
}

// Get applications for this job
$sql = "SELECT a.*, u.username, u.student_id, u.skills, u.id as student_user_id 
        FROM applications a 
        JOIN users u ON a.student_id = u.id 
        WHERE a.job_id = ? 
        ORDER BY a.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$applications = $stmt->get_result();

// Handle application status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $application_id = (int)$_POST['application_id'];
    $status = sanitizeInput($_POST['status']);
    
    $sql = "UPDATE applications SET status = ? WHERE id = ? AND job_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $application_id, $job_id);
    
    if ($stmt->execute()) {
        // Create notification for student
        $notification_message = "Your application status has been updated to: " . ucfirst($status);
        $sql = "INSERT INTO notifications (user_id, message, type, reference_id) VALUES (?, ?, 'application_status', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $_POST['student_id'], $notification_message, $job_id);
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications - Job Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">Job Portal</div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="messages.php">Messages</a></li>
                <?php if ($_SESSION['user_type'] == 'employer'): ?>
                    <li><a href="post_job.php">Post Job</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <h2>Applications</h2>
                <div class="job-title">
                    <?php echo htmlspecialchars($job['title']); ?>
                </div>
            </div>
            
            <?php if ($applications->num_rows > 0): ?>
                <div class="application-list">
                    <?php while ($application = $applications->fetch_assoc()): ?>
                        <div class="application-card">
                            <div class="flex items-center justify-between mb-4">
                                <h3>
                                    <?php echo htmlspecialchars($application['username']); ?>
                                </h3>
                                <span class="status-badge <?php 
                                    echo match($application['status']) {
                                        'accepted' => 'status-accepted',
                                        'rejected' => 'status-rejected',
                                        default => 'status-pending'
                                    };
                                ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                            </div>
                            
                            <div class="application-details">
                                <p>
                                    <span class="label">Student ID:</span> 
                                    <?php echo htmlspecialchars($application['student_id']); ?>
                                </p>
                                <p>
                                    <span class="label">Skills:</span> 
                                    <?php echo htmlspecialchars($application['skills']); ?>
                                </p>
                                <p class="posted-date">
                                    Applied on <?php echo date('F j, Y', strtotime($application['created_at'])); ?>
                                </p>
                            </div>
                            
                            <div class="application-actions">
                                <a href="messages.php?user=<?php echo $application['student_user_id']; ?>" class="btn btn-outline">
                                    Message
                                </a>
                                
                                <?php if ($application['status'] === 'accepted'): ?>
                                    <a href="schedule_interview.php?job_id=<?php echo $job_id; ?>&candidate_id=<?php echo $application['student_user_id']; ?>" class="btn btn-primary">
                                        Schedule Interview
                                    </a>
                                <?php endif; ?>
                                
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                    <input type="hidden" name="student_id" value="<?php echo $application['student_user_id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="form-group">
                                        <option value="pending" <?php echo $application['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="accepted" <?php echo $application['status'] === 'accepted' ? 'selected' : ''; ?>>Accept</option>
                                        <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Reject</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-applications">
                    No applications for this job yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 
