<?php
$asset_prefix = "../";
require_once "../config/auth.php";
require_role("student");
require_once "../config/database.php";

// Standardize session values
$user_id    = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0;
$user_name  = $_SESSION['name'] ?? $_SESSION['user']['name'] ?? 'Student';
$user_email = $_SESSION['email'] ?? $_SESSION['user']['email'] ?? 'N/A';

// Fetch Student & Course Details
$stmt = $pdo->prepare("
    SELECT s.id AS student_db_id, s.student_id, s.phone, 
           c.course_code, c.course_name 
    FROM students s 
    LEFT JOIN courses c ON c.id = s.course_id 
    WHERE s.user_id = ?
");
$stmt->execute([$user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$enrolled_subjects = [];
$attendance_stats = [];
$overall_percentage = 0;

if (!empty($student['student_db_id'])) {
    $student_db_id = $student['student_db_id'];

    // 1. Fetch Enrolled Subjects & Assigned Lecturers
    $stmt = $pdo->prepare("
        SELECT sub.id AS subject_id, sub.subject_code, sub.subject_name, sub.credit_hour, 
               u.name AS lecturer_name 
        FROM enrollments e 
        JOIN subjects sub ON sub.id = e.subject_id 
        LEFT JOIN lecturers l ON l.id = sub.lecturer_id 
        LEFT JOIN users u ON u.id = l.user_id 
        WHERE e.student_id = ? 
        ORDER BY sub.subject_code ASC
    ");
    $stmt->execute([$student_db_id]);
    $enrolled_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch Attendance Summary Statistics for Student Dashboard Quick-View
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(a.id) AS total_classes,
            SUM(CASE WHEN a.status IN ('Present', 'Late') THEN 1 ELSE 0 END) AS attended_classes
        FROM enrollments e
        LEFT JOIN attendance a ON a.subject_id = e.subject_id AND a.student_id = e.student_id
        WHERE e.student_id = ?
    ");
    $stmt->execute([$student_db_id]);
    $attendance_stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_classes' => 0, 'attended_classes' => 0];

    $total = (int)$attendance_stats['total_classes'];
    $attended = (int)$attendance_stats['attended_classes'];
    $overall_percentage = $total > 0 ? round(($attended / $total) * 100) : 0;
}

$page_title = "Student Dashboard";
include "../partials/header.php";
?>

<style>
/* Print Stylesheet to extract only the digital ID card when printing */
@media print {
    body * {
        visibility: hidden;
    }
    #printable-card, #printable-card * {
        visibility: visible;
    }
    #printable-card {
        position: absolute;
        left: 50%;
        top: 20%;
        transform: translate(-50%, 0);
        width: 380px !important;
        border: 2px solid #0d6efd !important;
        border-radius: 12px !important;
        padding: 20px !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Student Dashboard</h2>
    <div>
        <span class="text-muted me-2">Welcome, <strong><?= e($user_name) ?></strong></span>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Profile Card & Digital Pass -->
    <div class="col-md-4">
        <!-- Student Profile -->
        <div class="card p-4 shadow-sm border-0 bg-primary text-white mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-person-badge me-2"></i>My Profile</h5>
            <p class="mb-2"><strong>Student ID:</strong> <?= e($student['student_id'] ?? 'N/A') ?></p>
            <p class="mb-2"><strong>Email:</strong> <?= e($user_email) ?></p>
            <p class="mb-2">
                <strong>Degree Program:</strong> 
                <?= !empty($student['course_code']) ? e($student['course_code'] . " - " . $student['course_name']) : 'Not Assigned' ?>
            </p>
            <p class="mb-0"><strong>Phone:</strong> <?= e($student['phone'] ?? 'N/A') ?></p>
        </div>

        <!-- Digital Pass Card (Printable Target) -->
        <div class="card p-4 shadow-sm border-0 text-center" id="printable-card">
            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-qr-code-scan me-2 text-primary"></i>Student Digital Pass</h6>
            <p class="text-muted small mb-3"><?= e($user_name) ?></p>

            <div class="d-flex justify-content-center mb-3">
                <div id="qrcode" class="p-2 border rounded bg-white"></div>
            </div>

            <div class="mb-3">
                <span class="badge bg-primary fs-6"><?= e($student['student_id'] ?? 'N/A') ?></span>
                <small class="d-block text-muted mt-1"><?= e($student['course_code'] ?? 'N/A') ?></small>
            </div>

            <button onclick="window.print()" class="btn btn-outline-primary btn-sm no-print w-100">
                <i class="bi bi-printer me-1"></i> Print Digital Pass
            </button>
        </div>
    </div>

    <!-- Main Content Area: Attendance Banner & Enrolled Subjects -->
    <div class="col-md-8">
        
        <!-- Attendance Quick Link Banner -->
        <div class="card p-4 shadow-sm border-0 mb-4 bg-light">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-calendar-check me-2 text-primary"></i>My Attendance Record</h5>
                    <p class="text-muted small mb-0">Track your attendance history and subject percentage rates.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="attendance.php" class="btn btn-primary w-100">
                        <i class="bi bi-calendar-check me-1"></i> View Attendance
                    </a>
                </div>
            </div>
            
            <?php if (!empty($attendance_stats['total_classes'])): ?>
                <hr class="my-3">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Total Sessions: <strong><?= $attendance_stats['total_classes'] ?></strong> | 
                        Attended: <strong><?= $attendance_stats['attended_classes'] ?></strong>
                    </small>
                    <span class="badge <?= $overall_percentage >= 80 ? 'bg-success' : ($overall_percentage >= 60 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                        <?= $overall_percentage ?>% Overall Attendance
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Enrolled Subjects Table Card -->
        <div class="card p-4 shadow-sm border-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-book me-2 text-primary"></i>Enrolled Subjects</h5>
                
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th>Lecturer</th>
                            <th class="text-center">Credits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enrolled_subjects)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    You are not enrolled in any subjects yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enrolled_subjects as $sub): ?>
                                <tr>
                                    <td><span class="badge bg-info text-dark"><?= e($sub['subject_code']) ?></span></td>
                                    <td><strong><?= e($sub['subject_name']) ?></strong></td>
                                    <td><?= e($sub['lecturer_name'] ?: 'Unassigned') ?></td>
                                    <td class="text-center"><span class="badge bg-secondary"><?= e($sub['credit_hour']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    const studentId = "<?= e($student['student_id'] ?? '') ?>";
    if (studentId) {
        new QRCode(document.getElementById("qrcode"), {
            text: studentId,
            width: 150,
            height: 150,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    } else {
        document.getElementById("qrcode").innerHTML = "<p class='text-muted small my-3'>No ID Assigned</p>";
    }
</script>

<?php include "../partials/footer.php"; ?>