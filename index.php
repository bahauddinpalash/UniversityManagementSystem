<?php
require_once "config/auth.php";
if (isset($_SESSION['user'])) redirect_dashboard();
$page_title = "UniManage - Excellence in Education & Innovation";
include "partials/header.php";
?>

<!-- Announcement Ticker -->
<div class="bg-primary text-white py-2 px-3 shadow-sm">
    <div class="container d-flex align-items-center">
        <span class="badge bg-warning text-dark me-2 fw-bold text-uppercase">Notice</span>
        <marquee class="small mb-0" scrollamount="6">
            Welcome to the UniManage Academic Portal. Fall Semester Course Registration is now open! Please log in to view your enrolled modules and timetable.
        </marquee>
    </div>
</div>

<!-- Hero Banner Section -->
<div class="position-relative text-white text-center py-5 mb-5 rounded-3 shadow-lg overflow-hidden" 
     style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.92), rgba(33, 37, 41, 0.95)), url('https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;">
    <div class="container py-5 my-3 position-relative z-1">
        <span class="badge bg-light text-primary px-3 py-2 rounded-pill fw-semibold mb-3 text-uppercase tracking-wide">
            Official University Campus Portal
        </span>
        <h1 class="display-3 fw-bold mb-3">Empowering Knowledge & Innovation</h1>
        <p class="col-lg-8 mx-auto fs-5 fw-light text-light mb-4">
            Welcome to UniManage. Your gateway to seamless academic enrollment, course schedules, interactive attendance tracking, and real-time student services.
        </p>
        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
            <a href="login.php" class="btn btn-warning btn-lg px-5 py-3 fw-bold shadow-sm">
                <i class="bi bi-box-arrow-in-right me-2"></i>Access Portal
            </a>
            <a href="#features" class="btn btn-outline-light btn-lg px-4 py-3 fw-medium">
                Explore Features <i class="bi bi-arrow-down ms-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Quick Statistics Section -->
<div class="container mb-5">
    <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
            <div class="p-3 border rounded-3 bg-white shadow-sm">
                <h2 class="fw-bold text-primary mb-0">15,000+</h2>
                <small class="text-muted text-uppercase fw-semibold">Active Students</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 border rounded-3 bg-white shadow-sm">
                <h2 class="fw-bold text-success mb-0">120+</h2>
                <small class="text-muted text-uppercase fw-semibold">Academic Programs</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 border rounded-3 bg-white shadow-sm">
                <h2 class="fw-bold text-info mb-0">850+</h2>
                <small class="text-muted text-uppercase fw-semibold">Faculty Members</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 border rounded-3 bg-white shadow-sm">
                <h2 class="fw-bold text-warning mb-0">98%</h2>
                <small class="text-muted text-uppercase fw-semibold">Attendance Rate</small>
            </div>
        </div>
    </div>
</div>

<!-- Campus Features & Services -->
<div id="features" class="container py-4 mb-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-dark">University Digital Services</h2>
        <p class="text-muted mx-auto" style="max-width: 600px;">Integrated tools built for administrators, lecturers, and students to streamline university management.</p>
    </div>

    <div class="row g-4">
        <!-- Student & Staff Hub -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-top transition">
                <div class="card-body p-4 text-center">
                    <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-people-fill display-6"></i>
                    </div>
                    <h5 class="fw-bold">Student & Staff Portal</h5>
                    <p class="text-muted small">Centralized academic profiles, digital student IDs, role-based controls, and administrative management.</p>
                </div>
            </div>
        </div>

        <!-- Course Management -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-top transition">
                <div class="card-body p-4 text-center">
                    <div class="bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-journal-bookmark-fill display-6"></i>
                    </div>
                    <h5 class="fw-bold">Subject Enrollment</h5>
                    <p class="text-muted small">Real-time degree program enrollment, subject registration, and academic progression tracking.</p>
                </div>
            </div>
        </div>

        <!-- Smart QR Attendance -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-top transition">
                <div class="card-body p-4 text-center">
                    <div class="bg-warning-subtle text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-qr-code-scan display-6"></i>
                    </div>
                    <h5 class="fw-bold">Smart QR Check-In</h5>
                    <p class="text-muted small">Instant classroom attendance scanning with automated logs and live percentage statistics.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Campus News & Information Banner -->
<div class="bg-light p-5 rounded-3 border shadow-sm mb-5">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h3 class="fw-bold text-dark mb-2"><i class="bi bi-mortarboard-fill text-primary me-2"></i>Need Portal Assistance?</h3>
            <p class="text-muted mb-lg-0">If you are a newly enrolled student or faculty member experiencing login or enrollment issues, please contact our IT Helpdesk or visit your faculty department.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <a href="login.php" class="btn btn-outline-primary btn-lg fw-semibold">Login Support</a>
        </div>
    </div>
</div>

<?php include "partials/footer.php"; ?>