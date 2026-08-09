<?php
$asset_prefix = "../";
require_once "../config/auth.php";
require_role("admin");
require_once "../config/database.php";

$msg = "";
$err = "";

// Handle Subject Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$delete_id]);
        $msg = "Subject deleted successfully!";
    } catch (PDOException $e) {
        $err = "Cannot delete subject: It has existing enrollments linked to it.";
    }
}

// Handle Subject Creation or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id   = (int)($_POST['subject_id'] ?? 0);
    $course_id    = (int)($_POST['course_id'] ?? 0);
    $subject_code = strtoupper(trim($_POST['subject_code'] ?? ''));
    $subject_name = trim($_POST['subject_name'] ?? '');
    $lecturer_id  = (int)($_POST['lecturer_id'] ?? 0);
    $credits      = (int)($_POST['credit_hour'] ?? 3);

    if ($course_id && $subject_code && $subject_name && $lecturer_id) {
        try {
            if ($subject_id > 0) {
                // Update existing subject
                $stmt = $pdo->prepare("UPDATE subjects SET course_id = ?, subject_code = ?, subject_name = ?, lecturer_id = ?, credit_hour = ? WHERE id = ?");
                $stmt->execute([$course_id, $subject_code, $subject_name, $lecturer_id, $credits, $subject_id]);
                $msg = "Subject updated successfully!";
            } else {
                // Insert new subject
                $stmt = $pdo->prepare("INSERT INTO subjects (course_id, subject_code, subject_name, lecturer_id, credit_hour) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$course_id, $subject_code, $subject_name, $lecturer_id, $credits]);
                $msg = "Subject created successfully!";
            }
        } catch (PDOException $e) {
            $err = "Subject code already exists.";
        }
    } else {
        $err = "All required fields must be filled.";
    }
}

// Fetch Courses and Lecturers for dropdowns
$courses   = $pdo->query("SELECT * FROM courses ORDER BY course_name ASC")->fetchAll();
$lecturers = $pdo->query("SELECT l.id, u.name FROM lecturers l JOIN users u ON u.id = l.user_id ORDER BY u.name ASC")->fetchAll();

// Fetch Subjects joined with Course and Lecturer
$subjects = $pdo->query("
    SELECT s.*, c.course_name, c.course_code, u.name AS lecturer_name 
    FROM subjects s 
    LEFT JOIN courses c ON c.id = s.course_id 
    LEFT JOIN lecturers l ON l.id = s.lecturer_id 
    LEFT JOIN users u ON u.id = l.user_id 
    ORDER BY c.course_name ASC, s.subject_code ASC
")->fetchAll();

$page_title = "Manage Subjects";
include "../partials/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Subjects & Modules</h2>
    <a href="dashboard.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
</div>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show"><?= e($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="row g-4">
    <!-- Add Subject Form -->
    <div class="col-md-4">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3">Add Subject</h5>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Degree Course</label>
                    <select name="course_id" class="form-select" required>
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= e($c['course_code'] . " - " . $c['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Subject Code</label>
                    <input type="text" name="subject_code" class="form-control" placeholder="e.g., BIT101" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Subject Name</label>
                    <input type="text" name="subject_name" class="form-control" placeholder="e.g., Object Oriented Programming" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Assigned Lecturer</label>
                    <select name="lecturer_id" class="form-select" required>
                        <option value="">-- Assign Lecturer --</option>
                        <?php foreach ($lecturers as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= e($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Credit Hours</label>
                    <input type="number" name="credit_hour" class="form-control" value="3" min="1" max="6" required>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i> Save Subject</button>
            </form>
        </div>
    </div>

    <!-- Subjects List -->
    <div class="col-md-8">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3">All Subjects</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Course</th>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th>Lecturer</th>
                            <th class="text-center">Credits</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subjects)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No subjects added yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($subjects as $s): ?>
                                <tr>
                                    <td><span class="badge bg-info text-dark"><?= e($s['course_code']) ?></span></td>
                                    <td><strong><?= e($s['subject_code']) ?></strong></td>
                                    <td><?= e($s['subject_name']) ?></td>
                                    <td><?= e($s['lecturer_name'] ?: 'Unassigned') ?></td>
                                    <td class="text-center"><span class="badge bg-secondary"><?= e($s['credit_hour']) ?></span></td>
                                    <td class="text-end">
                                        <!-- Edit Button (Triggers Modal) -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-warning me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editSubjectModal<?= $s['id'] ?>">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>

                                        <!-- Delete Button -->
                                        <a href="subjects.php?action=delete&id=<?= $s['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this subject?');">
                                           <i class="bi bi-trash"></i> Delete
                                        </a>

                                        <!-- Edit Modal -->
                                        <div class="modal fade text-start" id="editSubjectModal<?= $s['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold">Edit Subject</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="subject_id" value="<?= $s['id'] ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Degree Course</label>
                                                                <select name="course_id" class="form-select" required>
                                                                    <?php foreach ($courses as $c): ?>
                                                                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $s['course_id'] ? 'selected' : '' ?>>
                                                                            <?= e($c['course_code'] . " - " . $c['course_name']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Subject Code</label>
                                                                <input type="text" name="subject_code" class="form-control" value="<?= e($s['subject_code']) ?>" required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Subject Name</label>
                                                                <input type="text" name="subject_name" class="form-control" value="<?= e($s['subject_name']) ?>" required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Assigned Lecturer</label>
                                                                <select name="lecturer_id" class="form-select" required>
                                                                    <?php foreach ($lecturers as $l): ?>
                                                                        <option value="<?= $l['id'] ?>" <?= $l['id'] == $s['lecturer_id'] ? 'selected' : '' ?>>
                                                                            <?= e($l['name']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Credit Hours</label>
                                                                <input type="number" name="credit_hour" class="form-control" value="<?= e($s['credit_hour']) ?>" min="1" max="6" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include "../partials/footer.php"; ?>