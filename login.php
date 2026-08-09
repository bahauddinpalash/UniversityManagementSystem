<?php
require_once "config/auth.php";
require_once "config/database.php";

if (isset($_SESSION['user'])) redirect_dashboard();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && password_verify($password, $u['password'])) {
        $_SESSION['user'] = [
            'id'    => $u['id'],
            'name'  => $u['name'],
            'email' => $u['email'],
            'role'  => $u['role']
        ];
        redirect_dashboard();
    } else {
        $error = "Invalid email or password.";
    }
}

$page_title = "Portal Login - UniManage";
include "partials/header.php";
?>

<div class="row g-0 justify-content-center my-4">
    <div class="col-lg-10">
        <!-- Top Navigation Bar Button -->
        <div class="mb-3">
            <a href="index.php" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-house-door-fill me-1"></i> Return to Homepage
            </a>
        </div>

        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="row g-0">
                <!-- Left Decorative Branding Column -->
                <div class="col-md-6 bg-primary text-white p-5 d-none d-md-flex flex-column justify-content-between position-relative" 
                     style="background: linear-gradient(135deg, rgba(13,110,253,0.95), rgba(33,37,41,0.9)), url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1000&q=80') center/cover;">
                    <div class="position-relative z-1">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-building-fill display-6 me-2"></i>
                            <span class="fs-4 fw-bold tracking-wide">UniManage</span>
                        </div>
                        <h2 class="fw-bold display-6">Academic & Attendance Portal</h2>
                        <p class="text-light opacity-75 mt-2">
                            Access student records, dynamic subject enrollment, and live QR code attendance scanning.
                        </p>
                    </div>

                    <div class="position-relative z-1 pt-4 border-top border-white-50">
                        <small class="d-block text-light opacity-75 mb-2">Supported Portals:</small>
                        <div class="d-flex gap-2">
                            <span class="badge bg-light text-primary">Admin</span>
                            <span class="badge bg-light text-primary">Lecturer</span>
                            <span class="badge bg-light text-primary">Student</span>
                        </div>
                    </div>
                </div>

                <!-- Right Login Form Column -->
                <div class="col-md-6 bg-white p-4 p-sm-5 d-flex flex-column justify-content-center">
                    <div class="mb-4 text-center text-md-start">
                        <i class="bi bi-shield-lock-fill text-primary display-5 d-md-none mb-2"></i>
                        <h3 class="fw-bold text-dark mb-1">Welcome Back</h3>
                        <p class="text-muted small">Enter your credentials to access your portal</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div><?= e($error) ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off">
                        <!-- Email Input -->
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@university.edu" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                            <label for="email"><i class="bi bi-envelope me-1"></i> Email Address</label>
                        </div>

                        <!-- Password Input -->
                        <div class="form-floating mb-3 position-relative">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password"><i class="bi bi-key me-1"></i> Password</label>
                            <button type="button" class="btn btn-link text-muted position-absolute end-0 top-50 translate-middle-y text-decoration-none me-2" id="togglePassword">
                                <i class="bi bi-eye-slash" id="toggleIcon"></i>
                            </button>
                        </div>

                        <!-- Remember Me / Quick Options -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember">
                                <label class="form-check-label small text-muted" for="remember">Remember me</label>
                            </div>
                            <a href="index.php" class="small text-decoration-none"><i class="bi bi-house-door me-1"></i>Home</a>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm py-2">
                            Sign In <i class="bi bi-box-arrow-in-right ms-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle Password Visibility
document.getElementById('togglePassword')?.addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    }
});
</script>

<?php include "partials/footer.php"; ?>