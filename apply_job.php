<?php
require_once 'config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: login.php");
    exit();
}

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Get job details
$sql = "SELECT * FROM jobs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();

if (!$job) {
    header("Location: index.php");
    exit();
}

// Check if already applied
$sql = "SELECT id FROM applications WHERE job_id = ? AND student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $error = "You have already applied for this job.";
} else {
    // Insert application
    $sql = "INSERT INTO applications (job_id, student_id, status) VALUES (?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Create notification for employer
        $notification_message = "New application for your job: " . $job['title'];
        $sql = "INSERT INTO notifications (user_id, message, type, reference_id) VALUES (?, ?, 'application', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $job['employer_id'], $notification_message, $job_id);
        $stmt->execute();
        
        $success = "Application submitted successfully!";
    } else {
        $error = "Something went wrong. Please try again.";
    }
}

// Get application count for this job
$sql = "SELECT COUNT(*) as count FROM applications WHERE job_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
$application_count = $result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job - Job Portal</title>
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
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-3xl shadow-xl p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-3xl font-semibold"><?php echo htmlspecialchars($job['title']); ?></h2>
                <span class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full font-medium">
                    <?php echo ucfirst($job['category']); ?>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Job Details -->
                <div class="space-y-6">
                    <div class="bg-gray-50 rounded-2xl p-6">
                        <h3 class="text-xl font-semibold mb-4">Job Description</h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($job['description']); ?></p>
                    </div>

                    <div class="bg-gray-50 rounded-2xl p-6">
                        <h3 class="text-xl font-semibold mb-4">Application Details</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Total Applications</span>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                    <?php echo $application_count; ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Posted On</span>
                                <span class="text-gray-500">
                                    <?php echo date('F j, Y', strtotime($job['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Form -->
                <div class="bg-gray-50 rounded-2xl p-6">
                    <h3 class="text-xl font-semibold mb-6">Apply for this Position</h3>
                    
                    <?php if (!$error && !$success): ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $job_id); ?>" class="space-y-6">
                            <div>
                                <label class="block text-gray-700 mb-2" for="cover_letter">Cover Letter</label>
                                <textarea 
                                    id="cover_letter" 
                                    name="cover_letter" 
                                    rows="5" 
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all resize-none"
                                    placeholder="Write a brief cover letter explaining why you're a good fit for this position..."
                                ></textarea>
                            </div>

                            <button 
                                type="submit" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-xl transition-colors"
                            >
                                Submit Application
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">
                                <?php echo $error ? $error : "Your application has been submitted successfully!"; ?>
                            </p>
                            <a 
                                href="index.php" 
                                class="inline-block mt-4 bg-gray-600 hover:bg-gray-700 text-white font-medium px-6 py-2 rounded-xl transition-colors"
                            >
                                Back to Jobs
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
