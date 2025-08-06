<?php
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get category filter if set
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$salary_type = isset($_GET['salary_type']) ? sanitizeInput($_GET['salary_type']) : '';
$work_location = isset($_GET['work_location']) ? sanitizeInput($_GET['work_location']) : '';
$job_type = isset($_GET['job_type']) ? sanitizeInput($_GET['job_type']) : '';

// Build SQL query based on filters
$sql = "SELECT j.*, u.username as employer_name 
        FROM jobs j 
        JOIN users u ON j.employer_id = u.id
        WHERE 1=1";
$params = array();
$types = "";

if ($category) {
    $sql .= " AND j.category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($salary_type) {
    $sql .= " AND j.salary_type = ?";
    $params[] = $salary_type;
    $types .= "s";
}

if ($work_location) {
    $sql .= " AND j.work_location = ?";
    $params[] = $work_location;
    $types .= "s";
}

if ($job_type) {
    $sql .= " AND j.job_type = ?";
    $params[] = $job_type;
    $types .= "s";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ... existing styles ... */
        
        /* Filter Styles */
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .filter-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background-color: var(--background-color);
            color: var(--text-color);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .filter-group select:hover {
            border-color: var(--primary-color);
        }

        .filter-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
        }
        /* ... existing styles ... */
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">Job Portal</div>
            <ul>
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="messages.php">Messages</a></li>
                <?php if ($_SESSION['user_type'] == 'employer'): ?>
                    <li><a href="post_job.php">Post Job</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <!-- Category Tabs -->
        <div class="categories">
            <a href="index.php" class="category <?php echo !$category ? 'active' : ''; ?>">
                All Jobs
            </a>
            <a href="index.php?category=tuition<?php echo $salary_type ? '&salary_type='.$salary_type : ''; ?><?php echo $work_location ? '&work_location='.$work_location : ''; ?><?php echo $job_type ? '&job_type='.$job_type : ''; ?>" 
               class="category <?php echo $category == 'tuition' ? 'active' : ''; ?>">
                Tutors
            </a>
            <a href="index.php?category=creative<?php echo $salary_type ? '&salary_type='.$salary_type : ''; ?><?php echo $work_location ? '&work_location='.$work_location : ''; ?><?php echo $job_type ? '&job_type='.$job_type : ''; ?>" 
               class="category <?php echo $category == 'creative' ? 'active' : ''; ?>">
                Creative
            </a>
            <a href="index.php?category=tech<?php echo $salary_type ? '&salary_type='.$salary_type : ''; ?><?php echo $work_location ? '&work_location='.$work_location : ''; ?><?php echo $job_type ? '&job_type='.$job_type : ''; ?>" 
               class="category <?php echo $category == 'tech' ? 'active' : ''; ?>">
                Tech
            </a>
        </div>

        <!-- Filter Dropdowns -->
        <div class="filters">
            <div class="filter-group">
                <label for="salary_type">Salary Type</label>
                <select id="salary_type" name="salary_type" onchange="updateFilters(this.value, 'salary_type')" class="form-group">
                    <option value="">All Salary Types</option>
                    <option value="project" <?php echo $salary_type == 'project' ? 'selected' : ''; ?>>Project Based</option>
                    <option value="monthly" <?php echo $salary_type == 'monthly' ? 'selected' : ''; ?>>Monthly Salary</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="work_location">Work Location</label>
                <select id="work_location" name="work_location" onchange="updateFilters(this.value, 'work_location')" class="form-group">
                    <option value="">All Locations</option>
                    <option value="onsite" <?php echo $work_location == 'onsite' ? 'selected' : ''; ?>>On Site</option>
                    <option value="remote" <?php echo $work_location == 'remote' ? 'selected' : ''; ?>>Remote</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="job_type">Job Type</label>
                <select id="job_type" name="job_type" onchange="updateFilters(this.value, 'job_type')" class="form-group">
                    <option value="">All Types</option>
                    <option value="job" <?php echo $job_type == 'job' ? 'selected' : ''; ?>>Job</option>
                    <option value="internship" <?php echo $job_type == 'internship' ? 'selected' : ''; ?>>Internship</option>
                </select>
            </div>
        </div>

        <!-- Job Grid -->
        <div class="job-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($job = $result->fetch_assoc()): ?>
                    <div class="job-card">
                        <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                        <div class="job-details">
                            <p><span class="label">Category:</span> <?php echo htmlspecialchars($job['category']); ?></p>
                            <p><span class="label">Salary:</span> <?php echo number_format($job['salary'], 2); ?> 
                                <?php echo $job['salary_type'] == 'monthly' ? '/month' : '/project'; ?></p>
                            <p><span class="label">Work Location:</span> <?php echo ucfirst(htmlspecialchars($job['work_location'])); ?></p>
                            <p><span class="label">Job Type:</span> <?php echo ucfirst(htmlspecialchars($job['job_type'])); ?></p>
                            <p><span class="label">Posted by:</span> <?php echo htmlspecialchars($job['employer_name']); ?></p>
                            <p class="description"><span class="label">Description:</span> <?php echo htmlspecialchars($job['description']); ?></p>
                            <p class="posted-date">Posted on <?php echo date('F j, Y', strtotime($job['created_at'])); ?></p>
                        </div>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student'): ?>
                            <a href="apply_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">
                                Apply Now
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-jobs">
                    No jobs found in this category.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateFilters(value, type) {
            const url = new URL(window.location.href);
            if (value) {
                url.searchParams.set(type, value);
            } else {
                url.searchParams.delete(type);
            }
            window.location.href = url.toString();
        }
    </script>
</body>
</html> 
