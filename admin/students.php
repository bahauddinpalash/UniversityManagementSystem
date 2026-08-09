<?php
$asset_prefix = "../";
require_once "../config/auth.php";
require_role("admin");
require_once "../config/database.php";

$msg = "";
$err = "";

// Handle Delete Student
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        $pdo->beginTransaction();

        // Get user_id linked to student
        $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmt->execute([$delete_id]);
        $student = $stmt->fetch();

        if ($student) {
            $user_id = $student['user_id'];
            
            // Delete enrollments, student record, and user account
            $pdo->prepare("DELETE FROM enrollments WHERE student_id = ?")->execute([$delete_id]);
            $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$delete_id]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        }

        $pdo->commit();
        $msg = "Student deleted successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $err = "Error deleting student.";
    }
}

// Handle Add or Edit Student
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_db_id'] ?? 0);
    $student_no = trim($_POST['student_id'] ?? '');
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $course_id  = (int)($_POST['course_id'] ?? 0);
    $password   = $_POST['password'] ?? '';

    if ($student_no && $name && $email && $course_id) {
        try {
            if ($student_id > 0) {
                // Update Student
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
                $stmt->execute([$student_id]);
                $user_id = $stmt->fetch()['user_id'];

                // Update users table
                if (!empty($password)) {
                    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $hashed_pass, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $user_id]);
                }

                // Update students table
                $stmt = $pdo->prepare("UPDATE students SET student_id = ?, phone = ?, course_id = ? WHERE id = ?");
                $stmt->execute([$student_no, $phone, $course_id, $student_id]);

                $pdo->commit();
                $msg = "Student updated successfully!";
            } else {
                // Add New Student
                if (empty($password)) {
                    $err = "Password is required for new students.";
                } else {
                    $pdo->beginTransaction();

                    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
                    $stmt->execute([$name, $email, $hashed_pass]);
                    $user_id = $pdo->lastInsertId();

                    $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, phone, course_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $student_no, $phone, $course_id]);

                    $pdo->commit();
                    $msg = "Student registered successfully!";
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $err = "Student ID or Email already exists.";
        }
    } else {
        $err = "Please fill in all required fields including Course.";
    }
}

// Fetch all Courses for dropdown
$courses = $pdo->query("SELECT * FROM courses ORDER BY course_name ASC")->fetchAll();

// Fetch Students with User and Course info
$students = $pdo->query("
    SELECT s.id AS student_db_id, s.student_id, s.phone, s.course_id, 
           u.name, u.email, c.course_code, c.course_name 
    FROM students s 
    JOIN users u ON u.id = s.user_id 
    LEFT JOIN courses c ON c.id = s.course_id 
    ORDER BY s.id DESC
")->fetchAll();

$page_title = "Manage Students";
include "../partials/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Students</h2>
    <a href="dashboard.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
</div>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show"><?= e($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Add Student Card -->
<div class="card p-4 shadow-sm mb-4">
    <h5 class="fw-bold mb-3">Add Student</h5>
    <form method="post">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Student ID</label>
                <input type="text" name="student_id" class="form-control" placeholder="e.g. 202402010057" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Phone</label>
                <input type="text" name="phone" class="form-control" placeholder="Phone">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Course Enrolled</label>
                <select name="course_id" class="form-select" required>
                    <option value="">-- Select Degree Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['course_code'] . " - " . $c['course_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-person-plus me-1"></i> Add Student</button>
            </div>
        </div>
    </form>
</div>

<!-- Students List Card -->
<div class="card p-4 shadow-sm">
    <h5 class="fw-bold mb-3">All Registered Students</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Enrolled Course</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No students registered yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $st): ?>
                        <tr>
                            <td><strong><?= e($st['student_id']) ?></strong></td>
                            <td><?= e($st['name']) ?></td>
                            <td><?= e($st['email']) ?></td>
                            <td><?= e($st['phone'] ?: '-') ?></td>
                            <td>
                                <?php if ($st['course_code']): ?>
                                    <span class="badge bg-primary"><?= e($st['course_code']) ?></span>
                                    <small class="text-muted d-block"><?= e($st['course_name']) ?></small>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <!-- Edit Button -->
                                <button type="button" class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#editStudentModal<?= $st['student_db_id'] ?>">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>

                                <!-- Delete Button -->
                                <a href="students.php?action=delete&id=<?= $st['student_db_id'] ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to delete this student account?');">
                                   <i class="bi bi-trash"></i> Delete
                                </a>

                                <!-- Edit Modal -->
                                <div class="modal fade text-start" id="editStudentModal<?= $st['student_db_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="post">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Edit Student Information</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="student_db_id" value="<?= $st['student_db_id'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Student ID</label>
                                                        <input type="text" name="student_id" class="form-control" value="<?= e($st['student_id']) ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Full Name</label>
                                                        <input type="text" name="name" class="form-control" value="<?= e($st['name']) ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Email</label>
                                                        <input type="email" name="email" class="form-control" value="<?= e($st['email']) ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Phone</label>
                                                        <input type="text" name="phone" class="form-control" value="<?= e($st['phone']) ?>">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Enrolled Course</label>
                                                        <select name="course_id" class="form-select" required>
                                                            <?php foreach ($courses as $c): ?>
                                                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $st['course_id'] ? 'selected' : '' ?>>
                                                                    <?= e($c['course_code'] . " - " . $c['course_name']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Password</label>
                                                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep unchanged">
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

<?php include "../partials/footer.php"; ?>