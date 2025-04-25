<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from URL or use logged-in user's ID
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: index.php");
    exit();
}

// If user is employer, get their posted jobs
if ($user['user_type'] == 'employer') {
    $sql = "SELECT j.*, 
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as application_count 
            FROM jobs j 
            WHERE j.employer_id = ? 
            ORDER BY j.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $jobs = $stmt->get_result();
}

// If user is student, get their applications
if ($user['user_type'] == 'student') {
    $sql = "SELECT a.*, j.title as job_title, j.category, u.username as employer_name 
            FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            JOIN users u ON j.employer_id = u.id 
            WHERE a.student_id = ? 
            ORDER BY a.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $applications = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile - Job Portal</title>
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
                <li><a href="profile.php" class="text-gray-900 font-medium">Profile</a></li>
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
                <h2 class="text-3xl font-semibold"><?php echo htmlspecialchars($user['username']); ?>'s Profile</h2>
                <span class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full font-medium">
                    <?php echo ucfirst($user['user_type']); ?>
                </span>
            </div>
            
            <?php if ($user['user_type'] == 'student'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <p class="text-gray-600">
                            <span class="font-medium">Student ID:</span><br>
                            <?php echo htmlspecialchars($user['student_id']); ?>
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <p class="text-gray-600">
                            <span class="font-medium">Skills:</span><br>
                            <?php echo htmlspecialchars($user['skills']); ?>
                        </p>
                    </div>
                </div>

                <h3 class="text-2xl font-semibold mb-4">Job Applications</h3>
                <?php if ($applications->num_rows > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php while ($application = $applications->fetch_assoc()): ?>
                            <div class="bg-gray-50 rounded-2xl p-6 hover:shadow-md transition-shadow">
                                <h4 class="text-xl font-semibold mb-3"><?php echo htmlspecialchars($application['job_title']); ?></h4>
                                <div class="space-y-2">
                                    <p class="text-gray-600">
                                        <span class="font-medium">Category:</span> 
                                        <?php echo htmlspecialchars($application['category']); ?>
                                    </p>
                                    <p class="text-gray-600">
                                        <span class="font-medium">Employer:</span> 
                                        <?php echo htmlspecialchars($application['employer_name']); ?>
                                    </p>
                                    <p class="text-gray-600">
                                        <span class="font-medium">Status:</span> 
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                            <?php echo ucfirst($application['status']); ?>
                                        </span>
                                    </p>
                                    <p class="text-gray-500 text-sm">
                                        Applied on <?php echo date('F j, Y', strtotime($application['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No applications submitted yet.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($user['user_type'] == 'employer'): ?>
                <h3 class="text-2xl font-semibold mb-4">Posted Jobs</h3>
                <?php if ($jobs->num_rows > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php while ($job = $jobs->fetch_assoc()): ?>
                            <div class="bg-gray-50 rounded-2xl p-6 hover:shadow-md transition-shadow">
                                <h4 class="text-xl font-semibold mb-3"><?php echo htmlspecialchars($job['title']); ?></h4>
                                <div class="space-y-2 mb-4">
                                    <p class="text-gray-600">
                                        <span class="font-medium">Category:</span> 
                                        <?php echo htmlspecialchars($job['category']); ?>
                                    </p>
                                    <p class="text-gray-600">
                                        <span class="font-medium">Description:</span> 
                                        <?php echo htmlspecialchars($job['description']); ?>
                                    </p>
                                    <p class="text-gray-600">
                                        <span class="font-medium">Applications:</span> 
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                            <?php echo $job['application_count']; ?>
                                        </span>
                                    </p>
                                    <p class="text-gray-500 text-sm">
                                        Posted on <?php echo date('F j, Y', strtotime($job['created_at'])); ?>
                                    </p>
                                </div>
                                <a 
                                    href="view_applications.php?job_id=<?php echo $job['id']; ?>" 
                                    class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-xl transition-colors"
                                >
                                    View Applications
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No jobs posted yet.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 