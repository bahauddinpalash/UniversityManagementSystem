<?php
$asset_prefix = "../";
require_once "../config/auth.php";
require_role("lecturer");
require_once "../config/database.php";

$msg = "";
$err = "";

// Standardize session user ID
$user_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0;

// Get logged-in lecturer's DB ID
$stmt = $pdo->prepare("SELECT id FROM lecturers WHERE user_id = ?");
$stmt->execute([$user_id]);
$lid = $stmt->fetchColumn();

// Get lecturer's assigned subjects
$subjects_stmt = $pdo->prepare("
    SELECT s.id, s.subject_code, s.subject_name, s.course_id, c.course_code 
    FROM subjects s 
    LEFT JOIN courses c ON c.id = s.course_id 
    WHERE s.lecturer_id = ?
    ORDER BY s.subject_code ASC
");
$subjects_stmt->execute([$lid]);
$subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Selected subject filter (0 = All Subjects)
$selected_subject_id = (int)($_GET['subject_id'] ?? 0);

// Handle Manual Attendance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_mark'])) {
    $student_code = trim($_POST['student_id'] ?? '');
    $subject_id   = (int)($_POST['subject_id'] ?? 0);
    
    if ($student_code && $subject_id) {
        $stmt = $pdo->prepare("SELECT s.id, u.name FROM students s JOIN users u ON u.id = s.user_id WHERE s.student_id = ?");
        $stmt->execute([$student_code]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            $date = date('Y-m-d');
            $time = date('H:i:s');
            try {
                // Fetch course_id for foreign key constraints
                $c_stmt = $pdo->prepare("SELECT course_id FROM subjects WHERE id = ?");
                $c_stmt->execute([$subject_id]);
                $course_id = $c_stmt->fetchColumn();

                // Verify student enrollment in this subject
                $chk = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND subject_id = ?");
                $chk->execute([$student['id'], $subject_id]);
                
                if ($chk->rowCount() === 0) {
                    $err = "Student is not enrolled in this subject.";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO attendance (student_id, subject_id, course_id, attendance_date, attendance_time, status) 
                        VALUES (?, ?, ?, ?, ?, 'Present')
                        ON DUPLICATE KEY UPDATE status = 'Present', attendance_time = VALUES(attendance_time)
                    ");
                    $stmt->execute([$student['id'], $subject_id, $course_id, $date, $time]);
                    $msg = "Manually marked attendance for " . $student['name'] . ".";
                }
            } catch (Throwable $e) {
                $err = "Failed to mark attendance: " . $e->getMessage();
            }
        } else {
            $err = "Student ID not found.";
        }
    } else {
        $err = "Please select a subject and enter a Student ID.";
    }
}

// Fetch detailed attendance logs
$query = "
    SELECT 
        a.attendance_date, 
        a.status, 
        s.student_id, 
        u.name, 
        sub.subject_code, 
        sub.subject_name,
        c.course_code AS degree_course,
        sub.id AS subject_id,
        s.id AS student_pk,
        (
            SELECT COUNT(DISTINCT a1.attendance_date) 
            FROM attendance a1 
            WHERE a1.student_id = s.id AND a1.subject_id = sub.id AND a1.status IN ('Present', 'Late')
        ) AS days_attended,
        (
            SELECT COUNT(DISTINCT a2.attendance_date) 
            FROM attendance a2 
            WHERE a2.subject_id = sub.id
        ) AS total_sessions
    FROM attendance a
    JOIN students s ON s.id = a.student_id
    JOIN users u ON u.id = s.user_id
    JOIN subjects sub ON sub.id = a.subject_id
    LEFT JOIN courses c ON c.id = sub.course_id
    WHERE sub.lecturer_id = ?
";
$params = [$lid];

if ($selected_subject_id > 0) {
    $query .= " AND sub.id = ?";
    $params[] = $selected_subject_id;
}

$query .= " ORDER BY a.attendance_date DESC, sub.subject_code ASC";

$logs_stmt = $pdo->prepare($query);
$logs_stmt->execute($params);
$logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Subject Attendance Records";
include "../partials/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Subject Attendance Records</h2>
    <div>
        <a href="scan-attendance.php<?= $selected_subject_id ? '?subject_id='.$selected_subject_id : '' ?>" class="btn btn-primary me-2">
            <i class="bi bi-qr-code-scan me-1"></i> Open Scanner
        </a>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show"><?= e($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Filter & Manual Entry Card -->
<div class="card p-4 mb-4 shadow-sm border-0">
    <div class="row g-3">
        <!-- Filter Form -->
        <div class="col-md-6 border-end">
            <h5 class="fw-bold mb-3"><i class="bi bi-funnel me-1 text-primary"></i> Filter by Subject</h5>
            <form method="get" class="row g-2">
                <div class="col-8">
                    <select name="subject_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">All My Subjects</option>
                        <?php foreach ($subjects as $sb): ?>
                            <option value="<?= $sb['id'] ?>" <?= $selected_subject_id == $sb['id'] ? 'selected' : '' ?>>
                                <?= e(($sb['course_code'] ? "[" . $sb['course_code'] . "] " : "") . $sb['subject_code'] . " - " . $sb['subject_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-4">
                    <a href="attendance.php" class="btn btn-secondary w-100">Reset</a>
                </div>
            </form>
        </div>

        <!-- Manual Entry Form -->
        <div class="col-md-6">
            <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square me-1 text-primary"></i> Manually Mark Attendance</h5>
            <form method="post" class="row g-2">
                <input type="hidden" name="manual_mark" value="1">
                <div class="col-5">
                    <select name="subject_id" class="form-select" required>
                        <option value="">-- Subject --</option>
                        <?php foreach ($subjects as $sb): ?>
                            <option value="<?= $sb['id'] ?>" <?= $selected_subject_id == $sb['id'] ? 'selected' : '' ?>>
                                <?= e($sb['subject_code']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-4">
                    <input name="student_id" class="form-control" placeholder="Student ID" required>
                </div>
                <div class="col-3">
                    <button class="btn btn-primary w-100">Mark</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Attendance Logs Table -->
<div class="card p-4 shadow-sm border-0">
    <h5 class="fw-bold mb-3"><i class="bi bi-journal-check me-2 text-primary"></i>Attendance History Logs</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Degree Program</th>
                    <th>Subject</th>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th class="text-center">Attended / Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No attendance records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><strong><?= e($log['attendance_date']) ?></strong></td>
                            <td><span class="badge bg-primary"><?= e($log['degree_course'] ?: 'N/A') ?></span></td>
                            <td><span class="badge bg-info text-dark me-1"><?= e($log['subject_code']) ?></span> <?= e($log['subject_name']) ?></td>
                            <td><strong><?= e($log['student_id']) ?></strong></td>
                            <td><?= e($log['name']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-success fs-6">
                                    <?= e($log['days_attended']) ?> / <?= e($log['total_sessions']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../partials/footer.php"; ?>