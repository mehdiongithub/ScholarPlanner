<?php
/**
 * Employee Portal Redirect
 * Legacy /employee view now routes to the unified and permission-aware Admin Control Center.
 */
if (!headers_sent()) {
    header("Location: " . url('/admin'), true, 301);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=<?= url('/admin') ?>">
    <title>Redirecting to Employee Portal...</title>
    <script>
        window.location.replace("<?= url('/admin') ?>");
    </script>
</head>
<body>
    <p>Redirecting to <a href="<?= url('/admin') ?>">Employee Staff Portal</a>...</p>
</body>
</html>
