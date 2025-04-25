<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mark notification as read if specified
if (isset($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
    $stmt->execute();
}

// Get all notifications for the user
$sql = "SELECT n.*, u.username as sender_name 
        FROM notifications n 
        LEFT JOIN users u ON n.reference_id = u.id 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Job Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <h1>Job Portal</h1>
            </div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="notifications">
            <h2>Notifications</h2>
            
            <?php if ($notifications->num_rows > 0): ?>
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div class="notification <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        <span class="time"><?php echo date('M j, g:i a', strtotime($notification['created_at'])); ?></span>
                        
                        <?php if ($notification['type'] == 'application'): ?>
                            <a href="view_applications.php?job_id=<?php echo $notification['reference_id']; ?>" class="btn">View Applications</a>
                        <?php elseif ($notification['type'] == 'message'): ?>
                            <a href="messages.php?user=<?php echo $notification['reference_id']; ?>" class="btn">View Messages</a>
                        <?php endif; ?>
                        
                        <?php if (!$notification['is_read']): ?>
                            <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn">Mark as Read</a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No notifications</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 