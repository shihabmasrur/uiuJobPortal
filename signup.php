<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitizeInput($_POST['name']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    // Validate input
    if (empty($name) || empty($password)) {
        $error = "Please fill in all required fields";
    } else {
        // Check if username already exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already exists";
        } else {
            // Hash password
            $hashed_password = hashPassword($password);
            
            // Insert into database
            if ($user_type == 'student') {
                $student_id = sanitizeInput($_POST['student_id']);
                $skills = sanitizeInput($_POST['skills']);
                
                $sql = "INSERT INTO users (username, password, user_type, student_id, skills) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $name, $hashed_password, $user_type, $student_id, $skills);
            } else {
                $sql = "INSERT INTO users (username, password, user_type) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $name, $hashed_password, $user_type);
            }
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Freelance Job Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">Find Job As A Student</div>
            <ul>
                <li><a href="login.php">Login</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="form-container">
            <h2>Sign Up</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="name">Username</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="user_type">I want to</label>
                    <select id="user_type" name="user_type" onchange="toggleFields()" required>
                        <option value="student">Sign up as a Student</option>
                        <option value="employer">Sign up to Hire</option>
                    </select>
                </div>

                <div id="student_fields" class="student-fields">
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" name="student_id">
                    </div>

                    <div class="form-group">
                        <label for="skills">Skills (comma separated)</label>
                        <input type="text" id="skills" name="skills">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Sign Up</button>
            </form>
        </div>
    </div>

    <script>
        function toggleFields() {
            const userType = document.getElementById('user_type').value;
            const studentFields = document.getElementById('student_fields');
            const studentIdInput = document.getElementById('student_id');
            const skillsInput = document.getElementById('skills');
            
            if (userType === 'student') {
                studentFields.style.display = 'block';
                studentIdInput.required = true;
                skillsInput.required = true;
            } else {
                studentFields.style.display = 'none';
                studentIdInput.required = false;
                skillsInput.required = false;
            }
        }

        // Initialize the form state
        document.addEventListener('DOMContentLoaded', toggleFields);
    </script>
</body>
</html> 
