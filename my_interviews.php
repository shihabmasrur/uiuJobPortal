<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's interviews
$query = "SELECT i.*, j.title as job_title, u.username as employer_name 
          FROM interview_slots i 
          JOIN jobs j ON i.job_id = j.id 
          JOIN users u ON i.employer_id = u.id 
          WHERE i.candidate_id = ? 
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
    <title>My Interviews - Job Portal</title>
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
                <li><a href="my_interviews.php" class="text-gray-600 hover:text-gray-900">My Interviews</a></li>
                <li><a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container mx-auto px-6 py-8">
        <h1 class="text-2xl font-semibold mb-6">My Interviews</h1>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php while ($interview = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 card-hover">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($interview['job_title']); ?></h3>
                                <p class="text-gray-600">With <?php echo htmlspecialchars($interview['employer_name']); ?></p>
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
                        
                        <div class="flex justify-end space-x-4">
                            <?php if ($interview['status'] === 'scheduled'): ?>
                                <button 
                                    onclick="addToCalendar(<?php echo htmlspecialchars(json_encode($interview)); ?>)" 
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                >
                                    Add to Calendar
                                </button>
                            <?php endif; ?>
                        </div>
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
    <script>
        function addToCalendar(interview) {
            const startTime = new Date(interview.start_time);
            const endTime = new Date(interview.end_time);
            
            const calendarEvent = {
                title: `Interview - ${interview.job_title}`,
                description: `Interview with ${interview.employer_name}\nNotes: ${interview.notes}\nMeeting Link: ${interview.meeting_link}`,
                startTime: startTime.toISOString(),
                endTime: endTime.toISOString(),
                location: interview.meeting_link
            };
            
            // Create .ics file
            const icsContent = [
                'BEGIN:VCALENDAR',
                'VERSION:2.0',
                'BEGIN:VEVENT',
                `SUMMARY:${calendarEvent.title}`,
                `DTSTART:${startTime.toISOString().replace(/[-:]/g, '').split('.')[0]}Z`,
                `DTEND:${endTime.toISOString().replace(/[-:]/g, '').split('.')[0]}Z`,
                `DESCRIPTION:${calendarEvent.description}`,
                `LOCATION:${calendarEvent.location}`,
                'END:VEVENT',
                'END:VCALENDAR'
            ].join('\n');
            
            // Create and download .ics file
            const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `interview-${interview.job_title.toLowerCase().replace(/\s+/g, '-')}.ics`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html> 