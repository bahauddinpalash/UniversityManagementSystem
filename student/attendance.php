<?php
$asset_prefix = "../";
require_once "../config/auth.php";
require_role("student");
require_once "../config/database.php";

$user_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0;

// Fetch Student primary key ID
$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$sid = $stmt->fetchColumn();

$records = [];
$stats = [];

if ($sid) {
    // 1. Fetch detailed attendance logs along with student attendance ratio per subject
    $stmt = $pdo->prepare("
        SELECT 
            a.attendance_date, 
            a.attendance_time,
            a.status, 
            sub.id AS subject_id,
            sub.subject_code, 
            sub.subject_name,
            c.course_code AS degree_course,
            (
                SELECT COUNT(DISTINCT a1.attendance_date) 
                FROM attendance a1 
                WHERE a1.student_id = a.student_id 
                  AND a1.subject_id = sub.id 
                  AND a1.status IN ('Present', 'Late')
            ) AS days_attended,
            (
                SELECT COUNT(DISTINCT a2.attendance_date) 
                FROM attendance a2 
                WHERE a2.subject_id = sub.id
            ) AS total_sessions
        FROM attendance a
        JOIN subjects sub ON sub.id = a.subject_id
        LEFT JOIN courses c ON c.id = sub.course_id
        WHERE a.student_id = ?
        ORDER BY a.attendance_date DESC, a.attendance_time DESC
    ");
    $stmt->execute([$sid]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch attendance percentages per enrolled subject for summary cards
    $stmt = $pdo->prepare("
        SELECT 
            sub.subject_code, 
            sub.subject_name,
            COUNT(DISTINCT a_all.attendance_date) AS total_classes,
            COUNT(DISTINCT CASE WHEN a.status IN ('Present', 'Late') THEN a.attendance_date END) AS total_attended
        FROM enrollments e
        JOIN subjects sub ON sub.id = e.subject_id
        LEFT JOIN attendance a ON a.subject_id = sub.id AND a.student_id = e.student_id
        LEFT JOIN attendance a_all ON a_all.subject_id = sub.id
        WHERE e.student_id = ?
        GROUP BY sub.id
    ");
    $stmt->execute([$sid]);
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "My Attendance";
include "../partials/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Attendance</h2>
    <a href="dashboard.php" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left me-1"></i> Dashboard
    </a>
</div>

<!-- Attendance Overview Cards -->
<div class="row g-3 mb-4">
    <?php if (empty($stats)): ?>
        <div class="col-12">
            <div class="alert alert-info mb-0">No enrolled subjects found.</div>
        </div>
    <?php else: ?>
        <?php foreach ($stats as $s): 
            $total = (int)$s['total_classes'];
            $attended = (int)$s['total_attended'];
            
            if ($total > 0) {
                $percentage = round(($attended / $total) * 100);
                $badge_class = $percentage >= 80 ? 'bg-success' : ($percentage >= 60 ? 'bg-warning text-dark' : 'bg-danger');
            } else {
                $percentage = 0;
                $badge_class = 'bg-secondary';
            }
        ?>
            
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Detailed Logs Table -->
<div class="card p-4 shadow-sm border-0">
    <h5 class="fw-bold mb-3"><i class="bi bi-calendar-check me-2 text-primary"></i>Attendance History Logs</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Degree Program</th>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th class="text-center">Attended / Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No attendance records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td><strong><?= e($r['attendance_date']) ?></strong></td>
                            <td><?= e($r['attendance_time'] ? date("h:i A", strtotime($r['attendance_time'])) : '-') ?></td>
                            <td><span class="badge bg-primary"><?= e($r['degree_course'] ?: 'N/A') ?></span></td>
                            <td><span class="badge bg-info text-dark"><?= e($r['subject_code']) ?></span></td>
                            <td><?= e($r['subject_name']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-success fs-6">
                                    <?= e($r['days_attended']) ?> / <?= e($r['total_sessions']) ?>
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