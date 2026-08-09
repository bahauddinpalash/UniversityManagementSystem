<?php
$asset_prefix = "../";
require_once "../config/auth.php";
require_role("admin");
require_once "../config/database.php";

$msg = "";
$err = "";

// Handle Unenroll / Delete Enrollment
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $enrollment_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM enrollments WHERE id = ?");
        $stmt->execute([$enrollment_id]);
        $msg = "Student unenrolled successfully!";
    } catch (PDOException $e) {
        $err = "Error removing enrollment record.";
    }
}

// Handle Subject Enrollment Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);

    if ($student_id && $subject_id) {
        try {
            // Double check duplicate on server-side
            $check = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND subject_id = ?");
            $check->execute([$student_id, $subject_id]);

            if ($check->rowCount() > 0) {
                $err = "This student is already enrolled in this subject.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, subject_id) VALUES (?, ?)");
                $stmt->execute([$student_id, $subject_id]);
                $msg = "Student successfully enrolled in subject!";
            }
        } catch (PDOException $e) {
            $err = "Failed to enroll student. Please try again.";
        }
    } else {
        $err = "Please select both a student and a subject.";
    }
}

// Fetch Students
$students = $pdo->query("
    SELECT s.id AS student_db_id, s.student_id, u.name, c.course_code 
    FROM students s 
    JOIN users u ON u.id = s.user_id 
    LEFT JOIN courses c ON c.id = s.course_id 
    ORDER BY u.name ASC
")->fetchAll();

// Fetch All Subjects
$subjects = $pdo->query("
    SELECT s.id, s.subject_code, s.subject_name, c.course_code 
    FROM subjects s 
    LEFT JOIN courses c ON c.id = s.course_id 
    ORDER BY c.course_code ASC, s.subject_code ASC
")->fetchAll();

// Fetch Enrollment Map [student_id => [array of subject_ids]]
$enrollment_map = [];
$raw_enrollments = $pdo->query("SELECT student_id, subject_id FROM enrollments")->fetchAll();
foreach ($raw_enrollments as $row) {
    $enrollment_map[$row['student_id']][] = (int)$row['subject_id'];
}

// Fetch Enrollment Records List for display
$enrollments = $pdo->query("
    SELECT e.id AS enrollment_id, 
           s.student_id AS student_no, 
           u.name AS student_name, 
           sub.subject_code, 
           sub.subject_name, 
           c.course_code AS degree_course 
    FROM enrollments e 
    JOIN students s ON s.id = e.student_id 
    JOIN users u ON u.id = s.user_id 
    JOIN subjects sub ON sub.id = e.subject_id 
    LEFT JOIN courses c ON c.id = sub.course_id 
    ORDER BY e.id DESC
")->fetchAll();

$page_title = "Subject Enrollment";
include "../partials/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Subject Enrollment</h2>
    <a href="dashboard.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
</div>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show"><?= e($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Enroll Form Card -->
<div class="card p-4 shadow-sm mb-4">
    <h5 class="fw-bold mb-3">Enroll Student into Subject</h5>
    <form method="post">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Select Student</label>
                <select name="student_id" id="studentSelect" class="form-select" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students as $st): ?>
                        <option value="<?= $st['student_db_id'] ?>">
                            <?= e($st['student_id'] . " - " . $st['name'] . ($st['course_code'] ? " (" . $st['course_code'] . ")" : "")) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-5">
                <label class="form-label fw-semibold">Select Subject</label>
                <select name="subject_id" id="subjectSelect" class="form-select" required disabled>
                    <option value="">-- First Select a Student --</option>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-person-check me-1"></i> Enroll
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Enrollments Table Card -->
<div class="card p-4 shadow-sm">
    <h5 class="fw-bold mb-3">Current Enrollments</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Degree Course</th>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($enrollments)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No subject enrollments found.</td></tr>
                <?php else: ?>
                    <?php foreach ($enrollments as $en): ?>
                        <tr>
                            <td><strong><?= e($en['student_no']) ?></strong></td>
                            <td><?= e($en['student_name']) ?></td>
                            <td>
                                <span class="badge bg-primary"><?= e($en['degree_course'] ?: 'General') ?></span>
                            </td>
                            <td><span class="badge bg-info text-dark"><?= e($en['subject_code']) ?></span></td>
                            <td><?= e($en['subject_name']) ?></td>
                            <td class="text-end">
                                <a href="enrollments.php?action=delete&id=<?= $en['enrollment_id'] ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to unenroll this student?');">
                                   <i class="bi bi-trash me-1"></i> Unenroll
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Pass PHP data to JS
const allSubjects = <?= json_encode($subjects) ?>;
const studentEnrollments = <?= json_encode($enrollment_map) ?>;

const studentSelect = document.getElementById('studentSelect');
const subjectSelect = document.getElementById('subjectSelect');

studentSelect.addEventListener('change', function() {
    const studentId = this.value;
    
    // Clear dropdown
    subjectSelect.innerHTML = '';
    
    if (!studentId) {
        subjectSelect.disabled = true;
        subjectSelect.innerHTML = '<option value="">-- First Select a Student --</option>';
        return;
    }
    
    // Get array of subject IDs student is already enrolled in
    const enrolledIds = studentEnrollments[studentId] || [];
    
    // Filter out enrolled subjects
    const availableSubjects = allSubjects.filter(sub => !enrolledIds.includes(parseInt(sub.id)));
    
    if (availableSubjects.length === 0) {
        subjectSelect.disabled = true;
        subjectSelect.innerHTML = '<option value="">-- All subjects already enrolled --</option>';
    } else {
        subjectSelect.disabled = false;
        subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
        availableSubjects.forEach(sub => {
            const courseTag = sub.course_code ? `[${sub.course_code}] ` : '';
            const opt = document.createElement('option');
            opt.value = sub.id;
            opt.textContent = `${courseTag}${sub.subject_code} - ${sub.subject_name}`;
            subjectSelect.appendChild(opt);
        });
    }
});
</script>

<?php include "../partials/footer.php"; ?>