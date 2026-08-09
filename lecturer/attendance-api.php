<?php
header('Content-Type: application/json');
require_once "../config/auth.php"; require_role("lecturer"); require_once "../config/database.php";
$data=json_decode(file_get_contents("php://input"),true);
$student_code=trim($data['student_id']??''); $course_id=(int)($data['course_id']??0);
if(!$student_code || !$course_id){echo json_encode(['success'=>false,'message'=>'Student ID and course are required.']);exit;}
$stmt=$pdo->prepare("SELECT l.id FROM lecturers l WHERE l.user_id=?");$stmt->execute([$_SESSION['user']['id']]);$lid=$stmt->fetchColumn();
$stmt=$pdo->prepare("SELECT id FROM courses WHERE id=? AND lecturer_id=?");$stmt->execute([$course_id,$lid]);if(!$stmt->fetch()){echo json_encode(['success'=>false,'message'=>'You are not assigned to this course.']);exit;}
$stmt=$pdo->prepare("SELECT s.id,u.name FROM students s JOIN users u ON u.id=s.user_id WHERE s.student_id=?");$stmt->execute([$student_code]);$student=$stmt->fetch();
if(!$student){echo json_encode(['success'=>false,'message'=>'Student not found.']);exit;}
$stmt=$pdo->prepare("SELECT 1 FROM enrollments WHERE student_id=? AND course_id=?");$stmt->execute([$student['id'],$course_id]);if(!$stmt->fetch()){echo json_encode(['success'=>false,'message'=>'Student is not enrolled in this course.']);exit;}
$date=date('Y-m-d');$time=date('H:i:s');
$stmt=$pdo->prepare("SELECT id FROM attendance WHERE student_id=? AND course_id=? AND attendance_date=?");$stmt->execute([$student['id'],$course_id,$date]);if($stmt->fetch()){echo json_encode(['success'=>false,'message'=>'Attendance already marked today for '. $student['name'].'.']);exit;}
$pdo->prepare("INSERT INTO attendance(student_id,course_id,attendance_date,attendance_time,status) VALUES(?,?,?,?, 'Present')")->execute([$student['id'],$course_id,$date,$time]);
echo json_encode(['success'=>true,'message'=>'Attendance marked for '.$student['name'].' ('.$student_code.').']);