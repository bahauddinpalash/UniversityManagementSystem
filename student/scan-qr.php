<?php
$asset_prefix = "../";
require_once "../config/auth.php";
require_role("student");
require_once "../config/database.php";

$stmt = $pdo->prepare("SELECT s.id, s.student_id FROM students s WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$student = $stmt->fetch();

$page_title = "Scan Attendance QR";
include "../partials/header.php";
?>

<h2>Scan Attendance QR Code</h2>
<div class="card p-4">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <p class="text-muted">Point your scanner at the course QR code displayed by your lecturer.</p>
            <div id="reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>
            <div id="result" class="alert alert-secondary mt-3">Ready to scan...</div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
const result = document.getElementById('result');
let busy = false;

function onScanSuccess(courseId) {
    if(busy) return;
    busy = true;
    result.className = 'alert alert-info';
    result.textContent = 'Recording attendance...';

    fetch('../lecturer/attendance-api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            student_id: <?= json_encode($student['student_id']) ?>,
            course_id: courseId
        })
    })
    .then(r => r.json())
    .then(data => {
        result.className = data.success ? 'alert alert-success' : 'alert alert-danger';
        result.textContent = data.message;
        setTimeout(() => busy = false, 2000);
    })
    .catch(() => {
        result.className = 'alert alert-danger';
        result.textContent = 'Server connection error.';
        setTimeout(() => busy = false, 2000);
    });
}

const scanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: { width: 250, height: 250 } }, false);
scanner.render(onScanSuccess);
</script>

<?php include "../partials/footer.php"; ?>