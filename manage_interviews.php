<?php
require_once 'config.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header("Location: login.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $interview_id = (int)$_POST['interview_id'];
    $status = $_POST['action'] === 'complete' ? 'completed' : 'cancelled';
    
    $update_query = "UPDATE interview_slots SET status = ? WHERE id = ? AND employer_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sii", $status, $interview_id, $_SESSION['user_id']);
    $stmt->execute();
    
    // Get interview details for email notification
    $interview_query = "SELECT i.*, u.email, u.username, j.title as job_title 
                       FROM interview_slots i 
                       JOIN users u ON i.candidate_id = u.id 
                       JOIN jobs j ON i.job_id = j.id 
                       WHERE i.id = ?";
    $stmt = $conn->prepare($interview_query);
    $stmt->bind_param("i", $interview_id);
    $stmt->execute();
    $interview = $stmt->get_result()->fetch_assoc();
    
    // Send email notification
    $to = $interview['email'];
    $subject = "Interview Update - " . $interview['job_title'];
    $message = "Dear " . $interview['username'] . ",\n\n";
    $message .= "Your interview for the position: " . $interview['job_title'] . " has been " . $status . ".\n";
    $message .= "Date: " . date('F j, Y', strtotime($interview['start_time'])) . "\n";
    $message .= "Time: " . date('h:i A', strtotime($interview['start_time'])) . " - " . date('h:i A', strtotime($interview['end_time'])) . "\n\n";
    $message .= "Best regards,\nJob Portal Team";
    
    mail($to, $subject, $message);
    
    header("Location: manage_interviews.php?success=1");
    exit();
}

// Get employer's interviews
$query = "SELECT i.*, j.title as job_title, u.username as candidate_name, u.email as candidate_email 
          FROM interview_slots i 
          JOIN jobs j ON i.job_id = j.id 
          JOIN users u ON i.candidate_id = u.id 
          WHERE i.employer_id = ? 
          ORDER BY i.start_time DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Interviews - Job Portal</title>
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
                <li><a href="manage_interviews.php" class="text-gray-600 hover:text-gray-900">Manage Interviews</a></li>
                <li><a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container mx-auto px-6 py-8">
        <h1 class="text-2xl font-semibold mb-6">Manage Interviews</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">Interview status updated successfully.</span>
            </div>
        <?php endif; ?>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php while ($interview = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 card-hover">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($interview['job_title']); ?></h3>
                                <p class="text-gray-600">With <?php echo htmlspecialchars($interview['candidate_name']); ?></p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium 
                                <?php echo $interview['status'] === 'scheduled' ? 'bg-green-100 text-green-800' : 
                                    ($interview['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'); ?>">
                                <?php echo ucfirst($interview['status']); ?>
                            </span>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <p class="text-gray-600">
                                <span class="font-medium">Date:</span> 
                                <?php echo date('F j, Y', strtotime($interview['start_time'])); ?>
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">Time:</span> 
                                <?php echo date('h:i A', strtotime($interview['start_time'])); ?> - 
                                <?php echo date('h:i A', strtotime($interview['end_time'])); ?>
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">Meeting Link:</span> 
                                <a href="<?php echo htmlspecialchars($interview['meeting_link']); ?>" 
                                   target="_blank" 
                                   class="text-blue-600 hover:text-blue-800">
                                    Join Meeting
                                </a>
                            </p>
                            <?php if ($interview['notes']): ?>
                                <p class="text-gray-600">
                                    <span class="font-medium">Notes:</span> 
                                    <?php echo htmlspecialchars($interview['notes']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($interview['status'] === 'scheduled'): ?>
                            <div class="flex justify-end space-x-4">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="interview_id" value="<?php echo $interview['id']; ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button 
                                        type="submit" 
                                        class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-xl transition-colors btn-hover"
                                    >
                                        Mark as Completed
                                    </button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="interview_id" value="<?php echo $interview['id']; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button 
                                        type="submit" 
                                        class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-xl transition-colors btn-hover"
                                    >
                                        Cancel Interview
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500">You don't have any scheduled interviews yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/ui.js"></script>
</body>
</html> 