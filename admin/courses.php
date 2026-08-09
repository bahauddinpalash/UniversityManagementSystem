<?php
$asset_prefix = "../";
require_once "../config/auth.php";
require_role("admin");
require_once "../config/database.php";

$msg = "";
$err = "";

// Handle Course Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$delete_id]);
        $msg = "Course deleted successfully!";
    } catch (PDOException $e) {
        $err = "Cannot delete course: It has subjects or enrollments linked to it.";
    }
}

// Handle Course Creation or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id   = (int)($_POST['course_id'] ?? 0);
    $code        = strtoupper(trim($_POST['course_code'] ?? ''));
    $name        = trim($_POST['course_name'] ?? '');

    if ($code && $name) {
        try {
            if ($course_id > 0) {
                // Update existing course
                $stmt = $pdo->prepare("UPDATE courses SET course_code = ?, course_name = ? WHERE id = ?");
                $stmt->execute([$code, $name, $course_id]);
                $msg = "Course updated successfully!";
            } else {
                // Insert new course
                $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name) VALUES (?, ?)");
                $stmt->execute([$code, $name]);
                $msg = "Course added successfully!";
            }
        } catch (PDOException $e) {
            $err = "Course code already exists.";
        }
    } else {
        $err = "All fields are required.";
    }
}

// Fetch all courses with subject counts
$courses = $pdo->query("
    SELECT c.*, COUNT(s.id) AS total_subjects 
    FROM courses c 
    LEFT JOIN subjects s ON s.course_id = c.id 
    GROUP BY c.id 
    ORDER BY c.course_name ASC
")->fetchAll();

$page_title = "Manage Courses";
include "../partials/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Degree Courses</h2>
    <a href="dashboard.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
</div>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show"><?= e($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="row g-4">
    <!-- Add Course Form -->
    <div class="col-md-4">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3">Add Course</h5>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-bold">Course Code</label>
                    <input type="text" name="course_code" class="form-control" placeholder="e.g., BIT" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Course Name</label>
                    <input type="text" name="course_name" class="form-control" placeholder="e.g., Bachelor in Information Technology" required>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i> Add Course</button>
            </form>
        </div>
    </div>

    <!-- Course List -->
    <div class="col-md-8">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3">All Courses</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Course Name</th>
                            <th class="text-center">Subjects Linked</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr><td colspan="4" class="text-center text-muted">No courses registered.</td></tr>
                        <?php else: ?>
                            <?php foreach ($courses as $c): ?>
                                <tr>
                                    <td><span class="badge bg-primary fs-6"><?= e($c['course_code']) ?></span></td>
                                    <td><strong><?= e($c['course_name']) ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary fs-6"><?= $c['total_subjects'] ?> Subjects</span>
                                    </td>
                                    <td class="text-end">
                                        <!-- Edit Button (Triggers Modal) -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-warning me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?= $c['id'] ?>">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>

                                        <!-- Delete Button -->
                                        <a href="courses.php?action=delete&id=<?= $c['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this course?');">
                                           <i class="bi bi-trash"></i> Delete
                                        </a>

                                        <!-- Edit Modal -->
                                        <div class="modal fade text-start" id="editModal<?= $c['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold">Edit Course</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Course Code</label>
                                                                <input type="text" name="course_code" class="form-control" value="<?= e($c['course_code']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Course Name</label>
                                                                <input type="text" name="course_name" class="form-control" value="<?= e($c['course_name']) ?>" required>
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