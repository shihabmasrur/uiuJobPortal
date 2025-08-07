<?php
require_once 'config.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header("Location: login.php");
    exit();
}

// Get employer's jobs and their application counts
$sql = "SELECT j.*, 
        (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as application_count,
        (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND status = 'pending') as pending_count
        FROM jobs j 
        WHERE j.employer_id = ? 
        ORDER BY j.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$jobs = $stmt->get_result();

// Get employer's scheduled interviews
$sql = "SELECT i.*, j.title as job_title, u.username as candidate_name 
        FROM interview_slots i 
        JOIN jobs j ON i.job_id = j.id 
        JOIN users u ON i.candidate_id = u.id 
        WHERE i.employer_id = ? AND i.status = 'scheduled'
        ORDER BY i.start_time ASC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$interviews = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard - Job Portal</title>
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
    <header class="glass-effect shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="text-2xl font-semibold text-gray-800">Job Portal</div>
            <ul class="flex items-center space-x-8">
                <li><a href="index.php" class="text-gray-900 font-medium">Home</a></li>
                <li><a href="profile.php" class="text-gray-600 hover:text-gray-900">Profile</a></li>
                <li><a href="messages.php" class="text-gray-600 hover:text-gray-900">Messages</a></li>
                <li><a href="post_job.php" class="text-gray-600 hover:text-gray-900">Post Job</a></li>
                <li><a href="manage_interviews.php" class="text-gray-600 hover:text-gray-900">Manage Interviews</a></li>
                <li><a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container mx-auto px-6 py-8">
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6" role="alert">
                <span class="block sm:inline">Interview scheduled successfully!</span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Posted Jobs Section -->
            <div class="bg-white rounded-3xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Posted Jobs</h2>
                    <a href="post_job.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-xl transition-colors">
                        Post New Job
                    </a>
                </div>

                <?php if ($jobs->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while ($job = $jobs->fetch_assoc()): ?>
                            <div class="bg-gray-50 rounded-xl p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-lg font-medium text-gray-800"><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                        <?php echo $job['application_count']; ?> Applications
                                    </span>
                                </div>
                                <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($job['description']); ?></p>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">
                                        <?php echo $job['pending_count']; ?> pending applications
                                    </span>
                                    <a href="view_applications.php?job_id=<?php echo $job['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800 font-medium">
                                        View Applications
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">You haven't posted any jobs yet.</p>
                <?php endif; ?>
            </div>

            <!-- Upcoming Interviews Section -->
            <div class="bg-white rounded-3xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Upcoming Interviews</h2>
                    <a href="manage_interviews.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        View All
                    </a>
                </div>

                <?php if ($interviews->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while ($interview = $interviews->fetch_assoc()): ?>
                            <div class="bg-gray-50 rounded-xl p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-lg font-medium text-gray-800"><?php echo htmlspecialchars($interview['job_title']); ?></h3>
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                        Scheduled
                                    </span>
                                </div>
                                <p class="text-gray-600 mb-2">With <?php echo htmlspecialchars($interview['candidate_name']); ?></p>
                                <p class="text-gray-600 mb-2">
                                    <?php echo date('F j, Y', strtotime($interview['start_time'])); ?> at 
                                    <?php echo date('h:i A', strtotime($interview['start_time'])); ?>
                                </p>
                                <a href="<?php echo htmlspecialchars($interview['meeting_link']); ?>" 
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                    Join Meeting
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No upcoming interviews scheduled.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 
