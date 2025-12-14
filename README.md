Student Attendance System

Group Members
Ubalde IBYIMANIKORA - 24RP05770
Docile IMBEREYEMASO - 24RP05672

Project Overview
A complete web-based Student Attendance Management System built with PHP and MySQL. This system allows administrators, teachers, and students to manage and track attendance records in an educational institution.

Live Demo
Live Application URL: https://studentattendancee.rf.gd/

GitHub Repository: https://github.com/UbaldeOfficial/student-attendance-system

Features
Role-Based Access Control: Three user roles (Admin, Teacher, Student)

Attendance Management: Teachers can mark student attendance

User Management: Admins can manage all users and courses

Reports & Analytics: Generate attendance reports and statistics

Secure Authentication: Password encryption and session management

Live Application Access
Test Credentials:
Administrator
URL: https://studentattendancee.rf.gd/

Username: admin

Password: 123456

Email: admin@school.rw

Teacher
URL: https://studentattendancee.rf.gd/

Username: teacher1

Password: 123456

Email: teacher@school.rw

Student
URL: https://studentattendancee.rf.gd/

Username: student1

Password: 123456

Email: student@school.rw

Note: Additional test accounts include teacher2-teacher5 and student2-student30 with the same password.

User Roles & Functions
Administrator
Manage all users (add, edit, delete)

Manage courses and assign teachers

View system-wide reports

Access all attendance records

Teacher
Mark attendance for assigned courses

View student attendance records

Generate class reports

Manage own courses

Student
View personal attendance records

Check attendance statistics

View enrolled courses

Update personal profile

Technical Requirements
PHP 7.4 or higher

MySQL 5.7 or higher

Web server (Apache/Nginx)

PDO extension enabled

Installation Instructions
1. Database Setup
sql
-- Run the database.sql file in your MySQL server
-- This will create all necessary tables and sample data
2. File Upload
Upload all PHP files to your web server

Ensure proper file permissions (755 for directories, 644 for files)

3. Configuration
Edit config.php with your database credentials:

php
define('DB_HOST', 'your_host');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
Project Structure
text
student-attendance-system/
├── config.php              # Database configuration
├── index.php              # Home page with login
├── login.php              # Login processing
├── dashboard.php          # Main dashboard
├── logout.php             # Logout functionality
├── admin.php              # Admin panel
├── teacher.php            # Teacher panel
├── student.php            # Student panel
├── mark_attendance.php    # Mark attendance (CRUD)
├── manage_users.php       # User management (CRUD)
├── manage_courses.php     # Course management (CRUD)
├── view_attendance.php    # View attendance records
├── reports.php            # Reports and analytics
├── my_attendance.php      # Student attendance view
├── my_courses.php         # Student courses view
├── profile.php            # User profile management
├── database.sql           # Database schema and data
└── assets/                # CSS and JavaScript files
Database Schema
The system uses 4 main tables:

users - Stores all user accounts

students - Student-specific information

courses - Course details and schedules

attendance - Attendance records

Security Features
Password encryption using PHP password_hash()

PDO prepared statements to prevent SQL injection

Session-based authentication

Input validation and sanitization

Role-based access control

Secure logout with session destruction

Features Implemented
1. CRUD Operations
Users: Create, Read, Update, Delete user accounts

Courses: Manage courses and assign teachers

Attendance: Mark, view, and update attendance records

2. Form Validation
Required field validation

Email format validation

Unique username/email checks

User-friendly error messages

3. Session Management
Secure login/logout

Session timeout handling

Protected pages require authentication

Role-based redirects

4. Error Handling
Try-catch blocks for database operations

Clean error messages (no raw errors displayed)

Graceful error recovery

Testing Instructions
Visit: https://studentattendancee.rf.gd/

Login as admin to manage the system

Login as teacher to mark attendance

Login as student to view attendance records

Test all CRUD operations

Verify form validations

Check session security by attempting to access protected pages without login

Deployment Information
Hosting Provider: InfinityFree (Free Hosting)

Domain: studentattendancee.rf.gd

PHP Version: PHP 8.3+

MySQL Version: MySQL 5.7+

Deployment Date: December 2025

Troubleshooting
Common Issues
Connection Error: Check database credentials in config.php

Login Failed: Verify user exists in database and password is correct

Session Issues: Ensure cookies are enabled and session storage is writable

Permission Errors: Check file permissions on server

Solutions
Verify database server is running

Check PHP error logs for detailed error messages

Ensure all required PHP extensions are enabled (PDO, mysqli)

Confirm database tables are properly created

Development Notes
Built with vanilla PHP (no external frameworks)

Uses inline CSS for simplicity

All files are in a single folder for easy deployment

Code follows procedural programming style

Successfully deployed on InfinityFree hosting

Browser Compatibility
Chrome (latest)

Firefox (latest)

Safari (latest)

Edge (latest)

Assignment Information
Course: Backend Development Using PHP

Institution: Rwanda Polytechnic

Program: Information and Communication Technology

Academic Year: 2025

Submission Deadline: December 14, 2025

License
Educational Use Only - Rwanda Polytechnic Assignment

Support
For technical issues with the live application, visit: https://studentattendancee.rf.gd/

For source code access, visit: https://github.com/YOUR-USERNAME/student-attendance-system

Acknowledgments
Rwanda Polytechnic Backend Development Course

PHP and MySQL documentation

Open source community resources

InfinityFree hosting services

Version
1.0.0 - Initial Release (December 2025)

Live Application: https://studentattendancee.rf.gd/
Source Code: https:https://github.com/UbaldeOfficial/student-attendance-system
