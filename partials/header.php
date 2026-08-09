<?php
require_once __DIR__ . '/../config/auth.php';
if (!isset($page_title)) $page_title = "University Management System";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>assets/css/style.css" rel="stylesheet">

<script>
  // Detect if page was loaded from Back/Forward cache and force reload
  window.addEventListener('pageshow', function (event) {
    if (
      event.persisted ||
      (performance &&
        performance.getEntriesByType('navigation')[0]?.type === 'back_forward')
    ) {
      window.location.reload(true);
    }
  });
</script>

</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
<div class="container">
<!-- Logo links directly to index.php -->
<a class="navbar-brand fw-bold" href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>index.php">
    <i class="bi bi-mortarboard-fill me-2"></i>UniManage
</a>
<div class="ms-auto text-white">
<?php if(isset($_SESSION['user'])): ?>
<span class="me-3"><?= e($_SESSION['user']['name']) ?> (<?= e($_SESSION['user']['role']) ?>)</span>
<a class="btn btn-light btn-sm" href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>logout.php">Logout</a>
<?php endif; ?>
</div>
</div>
</nav>
<div class="container py-4">