<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Handle message sending
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $message = sanitizeInput($_POST['message']);
    
    // Validate receiver exists
    $sql = "SELECT id FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $error = "Invalid recipient";
    } elseif (empty($message)) {
        $error = "Message cannot be empty";
    } else {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $_SESSION['user_id'], $receiver_id, $message);
        
        if ($stmt->execute()) {
            // Create notification for receiver
            $notification_message = "New message from " . $_SESSION['username'];
            $sql = "INSERT INTO notifications (user_id, message, type, reference_id) VALUES (?, ?, 'message', ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isi", $receiver_id, $notification_message, $_SESSION['user_id']);
            $stmt->execute();
            
            $success = "Message sent successfully!";
        } else {
            $error = "Failed to send message";
        }
    }
}

// Get conversations
$sql = "SELECT DISTINCT 
            CASE 
                WHEN sender_id = ? THEN receiver_id 
                ELSE sender_id 
            END as other_user_id,
            u.username as other_username,
            u.user_type as other_user_type,
            MAX(m.created_at) as last_message_time
        FROM messages m
        JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id) AND u.id != ?
        WHERE sender_id = ? OR receiver_id = ?
        GROUP BY other_user_id, other_username, other_user_type
        ORDER BY last_message_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$conversations = $stmt->get_result();

// Get messages for selected conversation
$selected_user = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$messages_result = null;
if ($selected_user) {
    // Validate selected user exists
    $sql = "SELECT id FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $sql = "SELECT m.*, u.username as sender_name 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE (sender_id = ? AND receiver_id = ?) 
                OR (sender_id = ? AND receiver_id = ?) 
                ORDER BY m.created_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $_SESSION['user_id'], $selected_user, $selected_user, $_SESSION['user_id']);
        $stmt->execute();
        $messages_result = $stmt->get_result();
    } else {
        $error = "Invalid user selected";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Job Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-image: linear-gradient(rgba(255,255,255,0.85), rgba(255,255,255,0.85)), url('Images/messagebg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head> 
 <!--changing to add image -->
<body class="min-h-screen"> 
    <!-- Header -->
    <header class="glass-effect shadow-sm">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="text-2xl font-semibold text-gray-800">Job Portal</div>
            <ul class="flex space-x-8">
                <li><a href="index.php" class="text-gray-600 hover:text-gray-900">Home</a></li>
                <li><a href="profile.php" class="text-gray-600 hover:text-gray-900">Profile</a></li>
                <li><a href="messages.php" class="text-gray-900 font-medium">Messages</a></li>
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Conversations List -->
                <div class="md:col-span-1">
                    <h2 class="text-2xl font-semibold mb-6">Conversations</h2>
                    <div class="space-y-4">
                        <?php if ($conversations->num_rows > 0): ?>
                            <?php while ($conversation = $conversations->fetch_assoc()): ?>
                                <a href="?user=<?php echo $conversation['other_user_id']; ?>" 
                                   class="block p-4 rounded-2xl transition-colors <?php echo $selected_user == $conversation['other_user_id'] ? 'bg-gray-100' : 'hover:bg-gray-50'; ?>">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="font-medium"><?php echo htmlspecialchars($conversation['other_username']); ?></span>
                                            <span class="text-sm text-gray-500">(<?php echo ucfirst($conversation['other_user_type']); ?>)</span>
                                        </div>
                                        <span class="text-sm text-gray-400"><?php echo date('M j, g:i a', strtotime($conversation['last_message_time'])); ?></span>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500">No conversations yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="md:col-span-3">
                    <?php if ($selected_user): ?>
                        <div class="flex flex-col h-[600px]">
                            <!-- Messages List -->
                            <div class="flex-1 overflow-y-auto space-y-4 mb-6">
                                <?php if ($messages_result && $messages_result->num_rows > 0): ?>
                                    <?php while ($message = $messages_result->fetch_assoc()): ?>
                                        <div class="flex <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'justify-end' : 'justify-start'; ?>">
                                            <div class="max-w-[70%] p-4 rounded-2xl <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800'; ?>">
                                                <p class="mb-1"><?php echo htmlspecialchars($message['message']); ?></p>
                                                <span class="text-xs <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'text-blue-100' : 'text-gray-500'; ?>">
                                                    <?php echo date('M j, g:i a', strtotime($message['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-8">No messages yet. Start the conversation!</p>
                                <?php endif; ?>
                            </div>

                            <!-- Message Form -->
                            <form method="POST" class="flex gap-4">
                                <input type="hidden" name="receiver_id" value="<?php echo $selected_user; ?>">
                                <textarea 
                                    name="message" 
                                    placeholder="Type your message..." 
                                    required
                                    class="flex-1 px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all resize-none"
                                ></textarea>
                                <button 
                                    type="submit" 
                                    name="send_message" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-xl transition-colors"
                                >
                                    Send
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-500">
                            <p>Select a conversation to start messaging</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
