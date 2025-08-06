# Job Portal Website

A web application that connects students with job opportunities in various categories including tuition, creative, and tech jobs.

## Features

- User registration and login system
- Two types of users: Students and Employers
- Job posting and browsing
- Job filtering by categories
- Student profiles with skills showcase
- Review system for students
- Secure password hashing
- Responsive design

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## Setup Instructions

1. Clone the repository to your web server directory
2. Create a MySQL database named `job_portal`
3. Import the database structure from `database.sql`
4. Update the database credentials in `config.php`:
   ```php
   define('DB_SERVER', 'localhost');
   define('DB_USERNAME', 'your_username');
   define('DB_PASSWORD', 'your_password');
   define('DB_NAME', 'job_portal');
   ```
5. Make sure your web server has write permissions for the uploads directory
6. Access the website through your web browser

## File Structure

- `config.php` - Database configuration and common functions
- `index.php` - Homepage with job listings
- `login.php` - User login page
- `signup.php` - User registration page
- `post_job.php` - Job posting page for employers
- `profile.php` - User profile page
- `logout.php` - Logout handler
- `style.css` - Main stylesheet
- `database.sql` - Database structure

## Security Features

- Password hashing using PHP's password_hash()
- Input sanitization
- Prepared statements for database queries
- Session management
- XSS prevention

## Usage

1. Register as either a student or employer
2. Students can:
   - Browse and filter jobs
   - Apply for jobs
   - Showcase their skills
   - Receive reviews
3. Employers can:
   - Post new jobs
   - Browse student profiles
   - Review students

## Contributing


Updates : README.md
apply_job.php
config.php
database.sql
index.php : are upto date untill final project show rest are previous verison code.

Feel free to submit issues and enhancement requests. 
