<?php
$asset_prefix = "../";
require_once "../config/auth.php";
require_role("admin");
require_once "../config/database.php";

// Fetch counts for all entities
$courses     = (int)($pdo->query("SELECT COUNT(*) c FROM courses")->fetch()['c'] ?? 0);
$subjects    = (int)($pdo->query("SELECT COUNT(*) c FROM subjects")->fetch()['c'] ?? 0);
$students    = (int)($pdo->query("SELECT COUNT(*) c FROM students")->fetch()['c'] ?? 0);
$lecturers   = (int)($pdo->query("SELECT COUNT(*) c FROM lecturers")->fetch()['c'] ?? 0);
$enrollments = (int)($pdo->query("SELECT COUNT(*) c FROM enrollments")->fetch()['c'] ?? 0);

$page_title = "Admin Dashboard";
include "../partials/header.php";
?>

<style>
    .stat-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
    }
    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .action-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        transition: all 0.2s ease;
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem;
        background: #ffffff;
    }
    .action-card:hover {
        border-color: #0d6efd;
        background-color: #f8f9fa;
        color: #0d6efd;
        transform: translateY(-2px);
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Admin Dashboard</h2>
        <p class="text-muted mb-0">Overview and system management portal</p>
    </div>
</div>

<!-- Stat Cards Grid -->
<div class="row g-4 mb-4">
    <!-- Degree Courses -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card shadow-sm h-100 p-3">
            <div class="card-body p-2 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-semibold text-uppercase small">Courses</span>
                    <h2 class="fw-bold my-1"><?= $courses ?></h2>
                    <a href="courses.php" class="text-primary text-decoration-none small fw-semibold">
                        Manage <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-award-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Subjects -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card shadow-sm h-100 p-3">
            <div class="card-body p-2 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-semibold text-uppercase small">Subjects</span>
                    <h2 class="fw-bold my-1"><?= $subjects ?></h2>
                    <a href="subjects.php" class="text-success text-decoration-none small fw-semibold">
                        Manage <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Students -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card shadow-sm h-100 p-3">
            <div class="card-body p-2 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-semibold text-uppercase small">Students</span>
                    <h2 class="fw-bold my-1"><?= $students ?></h2>
                    <a href="students.php" class="text-info text-decoration-none small fw-semibold">
                        Manage <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Lecturers -->
    <div class="col-sm-6 col-xl-6">
        <div class="card stat-card shadow-sm h-100 p-3">
            <div class="card-body p-2 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-semibold text-uppercase small">Lecturers</span>
                    <h2 class="fw-bold my-1"><?= $lecturers ?></h2>
                    <a href="lecturers.php" class="text-warning text-decoration-none small fw-semibold">
                        Manage <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrollments -->
    <div class="col-sm-6 col-xl-6">
        <div class="card stat-card shadow-sm h-100 p-3">
            <div class="card-body p-2 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-semibold text-uppercase small">Enrollments</span>
                    <h2 class="fw-bold my-1"><?= $enrollments ?></h2>
                    <a href="enrollments.php" class="text-secondary text-decoration-none small fw-semibold">
                        Manage <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                    <i class="bi bi-card-checklist"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Section -->
<div class="card border-0 shadow-sm p-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions</h5>
    <div class="row g-3">
        <div class="col-md-3">
            <a href="courses.php" class="action-card shadow-sm">
                <div class="stat-icon bg-primary text-white">
                    <i class="bi bi-award"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Add Course</h6>
                    <small class="text-muted">Degree Program</small>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="subjects.php" class="action-card shadow-sm">
                <div class="stat-icon bg-success text-white">
                    <i class="bi bi-journal-plus"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Add Subject</h6>
                    <small class="text-muted">Module / Unit</small>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="students.php" class="action-card shadow-sm">
                <div class="stat-icon bg-info text-white">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Add Student</h6>
                    <small class="text-muted">Register student</small>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="enrollments.php" class="action-card shadow-sm">
                <div class="stat-icon bg-secondary text-white">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Enroll Student</h6>
                    <small class="text-muted">Assign subjects</small>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include "../partials/footer.php"; ?>