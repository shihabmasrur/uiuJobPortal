<?php
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get category filter if set
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';

// Build SQL query based on category filter
$sql = "SELECT j.*, u.username as employer_name 
        FROM jobs j 
        JOIN users u ON j.employer_id = u.id";
if ($category) {
    $sql .= " WHERE j.category = ?";
}

$stmt = $conn->prepare($sql);
if ($category) {
    $stmt->bind_param("s", $category);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Portal - Find Your Next Opportunity</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/ui.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
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
                <?php if ($_SESSION['user_type'] == 'employer'): ?>
                    <li><a href="post_job.php" class="text-gray-600 hover:text-gray-900">Post Job</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Category Tabs -->
        <div class="flex space-x-4 mb-8">
            <a 
                href="index.php" 
                class="px-6 py-3 rounded-full <?php echo !$category ? 'bg-gray-800 text-white' : 'bg-white text-gray-800 hover:bg-gray-50'; ?> font-medium transition-colors"
            >
                All Jobs
            </a>
            <a 
                href="index.php?category=tuition" 
                class="px-6 py-3 rounded-full <?php echo $category == 'tuition' ? 'bg-gray-800 text-white' : 'bg-white text-gray-800 hover:bg-gray-50'; ?> font-medium transition-colors"
            >
                Tutors
            </a>
            <a 
                href="index.php?category=creative" 
                class="px-6 py-3 rounded-full <?php echo $category == 'creative' ? 'bg-gray-800 text-white' : 'bg-white text-gray-800 hover:bg-gray-50'; ?> font-medium transition-colors"
            >
                Creative
            </a>
            <a 
                href="index.php?category=tech" 
                class="px-6 py-3 rounded-full <?php echo $category == 'tech' ? 'bg-gray-800 text-white' : 'bg-white text-gray-800 hover:bg-gray-50'; ?> font-medium transition-colors"
            >
                Tech
            </a>
        </div>

        <!-- Job Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($job = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 card-hover">
                        <h3 class="text-xl font-semibold mb-2 text-gray-900"><?php echo htmlspecialchars($job['title']); ?></h3>
                        <div class="space-y-2 mb-4">
                            <p class="text-gray-600">
                                <span class="font-medium">Category:</span> 
                                <?php echo htmlspecialchars($job['category']); ?>
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">Posted by:</span> 
                                <?php echo htmlspecialchars($job['employer_name']); ?>
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">Description:</span> 
                                <?php echo htmlspecialchars($job['description']); ?>
                            </p>
                            <p class="text-gray-500 text-sm">
                                Posted on <?php echo date('F j, Y', strtotime($job['created_at'])); ?>
                            </p>
                        </div>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student'): ?>
                            <a 
                                href="apply_job.php?id=<?php echo $job['id']; ?>" 
                                class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-xl transition-colors btn-hover"
                            >
                                Apply Now
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-2 text-center py-12 text-gray-500">
                    No jobs found in this category.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/ui.js"></script>
</body>
</html> 