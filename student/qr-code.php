<?php
$asset_prefix="../";require_once "../config/auth.php";require_role("student");require_once "../config/database.php";
$stmt=$pdo->prepare("SELECT student_id FROM students WHERE user_id=?");$stmt->execute([$_SESSION['user']['id']]);$student=$stmt->fetch();
$page_title="My QR Code";include "../partials/header.php";?>
<div class="row justify-content-center"><div class="col-md-6"><div class="card p-4 text-center"><h3>My Attendance QR Code</h3><p>Student ID: <strong><?=e($student['student_id'])?></strong></p><div id="qrcode" class="qr-box"></div><button onclick="window.print()" class="btn btn-outline-primary">Print QR</button></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><script>new QRCode(document.getElementById("qrcode"),{text:<?=json_encode($student['student_id'])?>,width:220,height:220});</script>
<?php include "../partials/footer.php"; ?>