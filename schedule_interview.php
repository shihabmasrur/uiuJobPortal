<?php
require_once 'config.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header("Location: login.php");
    exit();
}

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$candidate_id = isset($_GET['candidate_id']) ? (int)$_GET['candidate_id'] : 0;

// Get job and candidate details
$job_query = "SELECT title FROM jobs WHERE id = ? AND employer_id = ?";
$stmt = $conn->prepare($job_query);
$stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
$stmt->execute();
$job_result = $stmt->get_result();
$job = $job_result->fetch_assoc();

$candidate_query = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($candidate_query);
$stmt->bind_param("i", $candidate_id);
$stmt->execute();
$candidate_result = $stmt->get_result();
$candidate = $candidate_result->fetch_assoc();

if (!$job || !$candidate) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $meeting_link = $_POST['meeting_link'];
    $notes = $_POST['notes'];
    
    $insert_query = "INSERT INTO interview_slots (employer_id, job_id, candidate_id, start_time, end_time, meeting_link, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiissss", $_SESSION['user_id'], $job_id, $candidate_id, $start_time, $end_time, $meeting_link, $notes);
    
    if ($stmt->execute()) {
        // Create notification for candidate
        $notification_message = "An interview has been scheduled for the position: " . $job['title'];
        $sql = "INSERT INTO notifications (user_id, message, type, reference_id) VALUES (?, ?, 'interview', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $candidate_id, $notification_message, $job_id);
        $stmt->execute();
        
        header("Location: employer_dashboard.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Interview - Job Portal</title>
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
                <li><a href="employer_dashboard.php" class="text-gray-600 hover:text-gray-900">Dashboard</a></li>
                <li><a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container mx-auto px-6 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-3xl p-8 shadow-sm">
            <h1 class="text-2xl font-semibold mb-6">Schedule Interview</h1>
            
            <div class="mb-6">
                <h2 class="text-lg font-medium mb-2">Job Details</h2>
                <p class="text-gray-600">Position: <?php echo htmlspecialchars($job['title']); ?></p>
            </div>
            
            <div class="mb-6">
                <h2 class="text-lg font-medium mb-2">Candidate Details</h2>
                <p class="text-gray-600">Name: <?php echo htmlspecialchars($candidate['username']); ?></p>
            </div>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 mb-2" for="start_time">Start Time</label>
                    <input 
                        type="datetime-local" 
                        id="start_time" 
                        name="start_time" 
                        required
                        class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300"
                        min="<?php echo date('Y-m-d\TH:i'); ?>"
                    >
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2" for="end_time">End Time</label>
                    <input 
                        type="datetime-local" 
                        id="end_time" 
                        name="end_time" 
                        required
                        class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300"
                        min="<?php echo date('Y-m-d\TH:i'); ?>"
                    >
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2" for="meeting_link">Meeting Link</label>
                    <input 
                        type="url" 
                        id="meeting_link" 
                        name="meeting_link" 
                        required
                        placeholder="https://meet.google.com/..."
                        class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300"
                    >
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2" for="notes">Additional Notes</label>
                    <textarea 
                        id="notes" 
                        name="notes" 
                        rows="4"
                        class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300"
                        placeholder="Add any additional information for the candidate..."
                    ></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button 
                        type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-xl transition-colors btn-hover"
                    >
                        Schedule Interview
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/ui.js"></script>
    <script>
        // Validate end time is after start time
        document.getElementById('start_time').addEventListener('change', function() {
            document.getElementById('end_time').min = this.value;
        });
        
        document.getElementById('end_time').addEventListener('change', function() {
            const startTime = new Date(document.getElementById('start_time').value);
            const endTime = new Date(this.value);
            
            if (endTime <= startTime) {
                alert('End time must be after start time');
                this.value = '';
            }
        });
    </script>
</body>
</html> 
