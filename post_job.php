<?php
require_once 'config.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = sanitizeInput($_POST['title']);
    $category = sanitizeInput($_POST['category']);
    $description = sanitizeInput($_POST['description']);
    $employer_id = $_SESSION['user_id'];
    
    if (empty($title) || empty($category) || empty($description)) {
        $error = "Please fill in all fields";
    } else {
        $sql = "INSERT INTO jobs (title, category, description, employer_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $title, $category, $description, $employer_id);
        
        if ($stmt->execute()) {
            $success = "Job posted successfully!";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job - Job Portal</title>
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
                    <li><a href="post_job.php" class="text-gray-900 font-medium">Post Job</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-8 flex justify-center items-center min-h-[calc(100vh-76px)]">
        <div class="w-full max-w-2xl">
            <div class="bg-white rounded-3xl shadow-xl p-8">
                <h2 class="text-3xl font-semibold mb-6">Post a New Job</h2>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2" for="title">Job Title</label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                            placeholder="Enter job title"
                        >
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="category">Category</label>
                        <select 
                            id="category" 
                            name="category" 
                            required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all appearance-none bg-white"
                        >
                            <option value="">Select a category</option>
                            <option value="tuition">Tuition</option>
                            <option value="creative">Creative</option>
                            <option value="tech">Tech</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="description">Job Description</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="5" 
                            required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all resize-none"
                            placeholder="Enter detailed job description"
                        ></textarea>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-xl transition-colors"
                    >
                        Post Job
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 