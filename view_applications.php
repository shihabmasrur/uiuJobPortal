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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-purple-50 to-blue-100">
    <!-- Header -->
    <header class="glass-effect shadow-sm">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="text-2xl font-semibold text-gray-800">Job Portal</div>
            <ul class="flex space-x-8">
                <li><a href="index.php" class="text-gray-600 hover:text-gray-900">Home</a></li>
                <li><a href="profile.php" class="text-gray-600 hover:text-gray-900">Profile</a></li>
                <li><a href="messages.php" class="text-gray-600 hover:text-gray-900">Messages</a></li>
                <?php if ($_SESSION['user_type'] == 'employer'): ?>
                    <li><a href="post_job.php" class="text-gray-600 hover:text-gray-900">Post Job</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-3xl shadow-xl p-8 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-3xl font-semibold">Applications</h2>
                <div class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full">
                    <?php echo htmlspecialchars($job['title']); ?>
                </div>
            </div>
            
            <?php if ($applications->num_rows > 0): ?>
                <div class="grid grid-cols-1 gap-6">
                    <?php while ($application = $applications->fetch_assoc()): ?>
                        <div class="bg-gray-50 rounded-2xl p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-semibold">
                                    <?php echo htmlspecialchars($application['username']); ?>
                                </h3>
                                <span class="px-3 py-1 rounded-full text-sm <?php 
                                    echo match($application['status']) {
                                        'accepted' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        default => 'bg-blue-100 text-blue-800'
                                    };
                                ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <p class="text-gray-600">
                                        <span class="font-medium">Student ID:</span><br>
                                        <?php echo htmlspecialchars($application['student_id']); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-600">
                                        <span class="font-medium">Skills:</span><br>
                                        <?php echo htmlspecialchars($application['skills']); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <p class="text-gray-500 text-sm mb-4">
                                Applied on <?php echo date('F j, Y', strtotime($application['created_at'])); ?>
                            </p>
                            
                            <div class="flex flex-wrap items-center gap-4">
                                <a 
                                    href="profile.php?id=<?php echo $application['student_user_id']; ?>" 
                                    class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-medium px-6 py-2 rounded-xl transition-colors"
                                >
                                    View Profile
                                </a>
                                <a 
                                    href="messages.php?user=<?php echo $application['student_user_id']; ?>" 
                                    class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-medium px-6 py-2 rounded-xl transition-colors"
                                >
                                    Send Message
                                </a>
                                
                                <form method="POST" class="flex-1 flex justify-end gap-4">
                                    <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                    <input type="hidden" name="student_id" value="<?php echo $application['student_user_id']; ?>">
                                    <select 
                                        name="status"
                                        class="px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all appearance-none bg-white"
                                    >
                                        <option value="pending" <?php echo $application['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="accepted" <?php echo $application['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                        <option value="rejected" <?php echo $application['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                    <button 
                                        type="submit" 
                                        name="update_status" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-xl transition-colors"
                                    >
                                        Update Status
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 text-gray-500">
                    No applications for this job yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 