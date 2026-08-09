<?php
$asset_prefix = "../";
require_once "../config/auth.php";
require_role("lecturer");
require_once "../config/database.php";

$user_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0;

// Fetch lecturer DB ID
$stmt = $pdo->prepare("SELECT id FROM lecturers WHERE user_id = ?");
$stmt->execute([$user_id]);
$lecturer_id = $stmt->fetchColumn();

// Get lecturer's assigned subjects along with their course_id
$stmt = $pdo->prepare("
    SELECT s.id, s.subject_code, s.subject_name, s.course_id, c.course_code 
    FROM subjects s 
    LEFT JOIN courses c ON c.id = s.course_id 
    WHERE s.lecturer_id = ? 
    ORDER BY s.subject_code ASC
");
$stmt->execute([$lecturer_id]);
$assigned_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_subject_id = (int)($_GET['subject_id'] ?? ($_POST['subject_id'] ?? 0));

// Handle AJAX QR Code Scanning
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    header('Content-Type: application/json');
    $raw_data   = trim($_POST['qr_data'] ?? '');
    $subject_id = (int)($_POST['subject_id'] ?? 0);

    if (empty($raw_data)) {
        echo json_encode(['status' => 'error', 'message' => 'Empty QR code scanned.']);
        exit;
    }

    if ($subject_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a subject before scanning.']);
        exit;
    }

    $student_code = '';

    // Attempt to parse QR content (Handles JSON vs Plain Text)
    $json_data = json_decode($raw_data, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
        $student_code = $json_data['student_id'] ?? $json_data['student_code'] ?? $json_data['id'] ?? '';
    } else {
        $student_code = $raw_data; // Fallback to raw text if not JSON
    }

    $student_code = trim($student_code);

    if (empty($student_code)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid QR data. Student ID could not be identified.']);
        exit;
    }

    try {
        // Get the course_id for the selected subject
        $subj_stmt = $pdo->prepare("SELECT course_id FROM subjects WHERE id = ?");
        $subj_stmt->execute([$subject_id]);
        $course_id = $subj_stmt->fetchColumn();

        if (!$course_id) {
            echo json_encode(['status' => 'error', 'message' => 'Course record not found for this subject.']);
            exit;
        }

        // Fetch student details
        $stmt = $pdo->prepare("
            SELECT s.id AS student_pk, s.student_id, u.name 
            FROM students s 
            JOIN users u ON u.id = s.user_id 
            WHERE s.student_id = ?
        ");
        $stmt->execute([$student_code]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            echo json_encode(['status' => 'error', 'message' => "Student ID '{$student_code}' not found in system."]);
            exit;
        }

        // Verify Student Enrollment in Subject
        $chk = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND subject_id = ?");
        $chk->execute([$student['student_pk'], $subject_id]);

        if ($chk->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => "Student {$student['name']} ({$student['student_id']}) is not enrolled in this subject."]);
            exit;
        }

        // Mark Attendance (Including course_id)
        $today = date('Y-m-d');
        $time  = date('H:i:s');

        $stmt = $pdo->prepare("
            INSERT INTO attendance (student_id, subject_id, course_id, attendance_date, attendance_time, status) 
            VALUES (?, ?, ?, ?, ?, 'Present')
            ON DUPLICATE KEY UPDATE status = 'Present', attendance_time = VALUES(attendance_time)
        ");
        $stmt->execute([$student['student_pk'], $subject_id, $course_id, $today, $time]);

        echo json_encode([
            'status'  => 'success',
            'message' => "Attendance recorded for {$student['name']} ({$student['student_id']}).",
            'time'    => date('h:i:s A')
        ]);
        exit;

    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

$page_title = "QR Code Scanner";
include "../partials/header.php";
?>

<script src="https://unpkg.com/html5-qrcode"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-qr-code-scan me-2 text-primary"></i>Scan Attendance QR</h2>
    <div>
        <a href="attendance.php<?= $selected_subject_id ? '?subject_id='.$selected_subject_id : '' ?>" class="btn btn-outline-primary me-2">
            <i class="bi bi-journal-check me-1"></i> View Logs
        </a>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>
</div>

<div class="row">
    <!-- Left Column: Controls & Scanner -->
    <div class="col-md-6 mb-4">
        <div class="card p-4 shadow-sm border-0">
            <div class="mb-3">
                <label class="form-label fw-bold">Select Subject for Attendance:</label>
                <select id="subject_id" class="form-select form-select-lg">
                    <option value="">-- Choose Subject --</option>
                    <?php foreach ($assigned_subjects as $sb): ?>
                        <option value="<?= $sb['id'] ?>" <?= $selected_subject_id == $sb['id'] ? 'selected' : '' ?>>
                            <?= e(($sb['course_code'] ? "[" . $sb['course_code'] . "] " : "") . $sb['subject_code'] . " - " . $sb['subject_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="scanner-wrapper" class="mt-3">
                <div id="reader" style="width: 100%;"></div>
            </div>

            <div id="scan-alert" class="mt-3"></div>
        </div>
    </div>

    <!-- Right Column: Real-time Scan Logs -->
    <div class="col-md-6">
        <div class="card p-4 shadow-sm border-0">
            <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Live Scan Logs</h5>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle" id="logs-table">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Message</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="logs-body">
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No scans registered in this session yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let html5QrcodeScanner = null;
let lastScannedCode = '';
let scanTimeout = null;

function onScanSuccess(decodedText, decodedResult) {
    if (decodedText === lastScannedCode) return;
    lastScannedCode = decodedText;

    const subjectId = document.getElementById('subject_id').value;
    const alertBox = document.getElementById('scan-alert');

    if (!subjectId) {
        alertBox.innerHTML = '<div class="alert alert-warning">Please select a subject first!</div>';
        addLog(new Date().toLocaleTimeString(), 'Subject not selected', 'Failed');
        return;
    }

    const formData = new FormData();
    formData.append('qr_data', decodedText);
    formData.append('subject_id', subjectId);

    fetch('scan-attendance.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alertBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            addLog(data.time, data.message, 'Success');
        } else {
            alertBox.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            addLog(new Date().toLocaleTimeString(), data.message, 'Failed');
        }
    })
    .catch(error => {
        alertBox.innerHTML = '<div class="alert alert-danger">Error processing request.</div>';
        addLog(new Date().toLocaleTimeString(), 'Server/Network Error', 'Failed');
    });

    clearTimeout(scanTimeout);
    scanTimeout = setTimeout(() => { lastScannedCode = ''; }, 3000);
}

function addLog(time, message, status) {
    const tbody = document.getElementById('logs-body');
    if (tbody.children.length === 1 && tbody.children[0].cells.length === 1) {
        tbody.innerHTML = '';
    }

    const badgeClass = status === 'Success' ? 'bg-success' : 'bg-danger';
    const row = `<tr>
        <td><strong>${time}</strong></td>
        <td>${message}</td>
        <td><span class="badge ${badgeClass}">${status}</span></td>
    </tr>`;
    tbody.insertAdjacentHTML('afterbegin', row);
}

document.addEventListener("DOMContentLoaded", function() {
    html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: { width: 250, height: 250 } }, false);
    html5QrcodeScanner.render(onScanSuccess);
});
</script>

<?php include "../partials/footer.php"; ?>