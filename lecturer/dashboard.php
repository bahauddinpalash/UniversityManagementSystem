<?php
$asset_prefix = "../";
require_once "../config/auth.php";
require_role("lecturer");
require_once "../config/database.php";

$user_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0;
$user_name = $_SESSION['name'] ?? $_SESSION['user']['name'] ?? 'Lecturer';

// Fetch Lecturer primary key ID
$stmt = $pdo->prepare("SELECT id FROM lecturers WHERE user_id = ?");
$stmt->execute([$user_id]);
$lecturer_id = $stmt->fetchColumn();

$assigned_subjects = [];

if ($lecturer_id) {
    // Fetch assigned subjects with enrolled student count
    $stmt = $pdo->prepare("
        SELECT 
            s.id AS subject_id,
            s.subject_code, 
            s.subject_name, 
            s.credit_hour, 
            c.course_code AS degree_course,
            COUNT(e.id) AS student_count
        FROM subjects s
        LEFT JOIN courses c ON c.id = s.course_id
        LEFT JOIN enrollments e ON e.subject_id = s.id
        WHERE s.lecturer_id = ?
        GROUP BY s.id
        ORDER BY s.subject_code ASC
    ");
    $stmt->execute([$lecturer_id]);
    $assigned_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "Lecturer Portal";
include "../partials/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Lecturer Portal</h2>
    <div>
        <span class="text-muted me-2">Welcome, <strong><?= e($user_name) ?></strong></span>
    </div>
</div>

<div class="card p-4 shadow-sm border-0 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-book me-2 text-primary"></i>My Assigned Subjects</h5>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Degree Program</th>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Credits</th>
                    <th>Enrolled Students</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assigned_subjects)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No subjects assigned yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assigned_subjects as $sub): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?= e($sub['degree_course'] ?: 'N/A') ?></span></td>
                            <td><strong><?= e($sub['subject_code']) ?></strong></td>
                            <td><?= e($sub['subject_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= e($sub['credit_hour']) ?></span></td>
                            <td><span class="badge bg-success"><?= $sub['student_count'] ?> Students</span></td>
                            <td class="text-center">
                                <a href="scan-attendance.php?subject_id=<?= $sub['subject_id'] ?>" class="btn btn-sm btn-primary me-1">
                                    <i class="bi bi-qr-code-scan me-1"></i> Scan QR
                                </a>
                                <a href="attendance.php?subject_id=<?= $sub['subject_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-calendar-check me-1"></i> Attendance Logs
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../partials/footer.php"; ?>