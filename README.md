# University Management System

## Stack
HTML, CSS, Bootstrap 5, JavaScript, PHP 8+, MySQL.

## Features
- Admin, lecturer and student login
- Student/lecturer/course management
- Course enrollment
- Student QR code generation
- Lecturer camera-based QR scanner
- Automatic attendance validation and recording
- Duplicate attendance prevention per course/day

## Installation with XAMPP
1. Copy the `university_management_system` folder into `htdocs`.
2. Start Apache and MySQL.
3. Open phpMyAdmin.
4. Import `database/university.sql`.
5. Edit `config/database.php` if your MySQL username/password differs.
6. Open `http://localhost/university_management_system/`.

## Demo login
- Admin: admin@university.test / password
- Lecturer: lecturer@university.test / password
- Student: student@university.test / password

## Important
The QR contains the student's Student ID. The server does NOT trust the QR alone:
it verifies the student, enrollment, lecturer/course assignment, and duplicate attendance
before inserting the attendance record.

For production, add CSRF protection, stronger validation, HTTPS, audit logs, rate limits,
proper role permissions, timetable/session validation, and a password reset system.
