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
    <title>Sign Up - Job Portal</title>
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
               <!-- <li><a href="index.php" class="text-gray-600 hover:text-gray-900">Home</a></li> --> 
                <li><a href="login.php" class="text-gray-600 hover:text-gray-900">Login</a></li>
            </ul>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-8 flex justify-center items-center min-h-[calc(100vh-76px)]">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-3xl shadow-xl p-8">
                <h2 class="text-3xl font-semibold mb-6">Sign Up</h2>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2" for="name">Username</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        >
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        >
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="user_type">I want to</label>
                        <select 
                            id="user_type" 
                            name="user_type" 
                            onchange="toggleFields()" 
                            required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all appearance-none bg-white"
                        >
                            <option value="student">Sign up as a Student</option>
                            <option value="employer">Sign up to Hire</option>
                        </select>
                    </div>

                    <div id="student_fields" class="space-y-6">
                        <div>
                            <label class="block text-gray-700 mb-2" for="student_id">Student ID</label>
                            <input 
                                type="text" 
                                id="student_id" 
                                name="student_id"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                            >
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2" for="skills">Skills (comma separated)</label>
                            <input 
                                type="text" 
                                id="skills" 
                                name="skills"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                            >
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-xl transition-colors"
                    >
                        Sign Up
                    </button>
                </form>
            </div>
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