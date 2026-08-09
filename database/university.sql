CREATE DATABASE IF NOT EXISTS university_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE university_db;

CREATE TABLE users(
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','lecturer','student') NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students(
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL UNIQUE,
 student_id VARCHAR(50) NOT NULL UNIQUE,
 phone VARCHAR(30),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE lecturers(
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL UNIQUE,
 lecturer_id VARCHAR(50) NOT NULL UNIQUE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE courses(
 id INT AUTO_INCREMENT PRIMARY KEY,
 course_code VARCHAR(30) NOT NULL UNIQUE,
 course_name VARCHAR(150) NOT NULL,
 credit_hour INT NOT NULL DEFAULT 3,
 lecturer_id INT NULL,
 FOREIGN KEY(lecturer_id) REFERENCES lecturers(id) ON DELETE SET NULL
);

CREATE TABLE enrollments(
 id INT AUTO_INCREMENT PRIMARY KEY,
 student_id INT NOT NULL,
 course_id INT NOT NULL,
 enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE(student_id,course_id),
 FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
 FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE attendance(
 id INT AUTO_INCREMENT PRIMARY KEY,
 student_id INT NOT NULL,
 course_id INT NOT NULL,
 attendance_date DATE NOT NULL,
 attendance_time TIME NOT NULL,
 status ENUM('Present','Absent') NOT NULL DEFAULT 'Present',
 UNIQUE(student_id,course_id,attendance_date),
 FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
 FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Demo accounts. Password for all demo users: password
INSERT INTO users(name,email,password,role) VALUES
('System Admin','admin@university.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro5rQj1n2m5Vv6a7b8c9d0e', 'admin'),
('Demo Lecturer','lecturer@university.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro5rQj1n2m5Vv6a7b8c9d0e','lecturer'),
('Demo Student','student@university.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro5rQj1n2m5Vv6a7b8c9d0e','student');

INSERT INTO lecturers(user_id,lecturer_id) VALUES(2,'LEC001');
INSERT INTO students(user_id,student_id,phone) VALUES(3,'BIT23001','0123456789');

INSERT INTO courses(course_code,course_name,credit_hour,lecturer_id) VALUES
('BIT101','Introduction to Information Technology',3,1),
('BIT201','Object Oriented Programming',3,1);
INSERT INTO enrollments(student_id,course_id) VALUES(1,1),(1,2);
