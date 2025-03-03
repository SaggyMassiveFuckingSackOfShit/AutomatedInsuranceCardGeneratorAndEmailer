<?php
header('Content-Type: application/json');

$progressFile = "outputs/progress.txt";

if (!file_exists($progressFile)) {
    file_put_contents($progressFile, "0");
}

$progress = file_get_contents($progressFile);

// If progress is stuck at 100% before a new request, reset it
if ((int)$progress > 100) {
    $progress = 0;
    file_put_contents($progressFile, "0");
}

echo json_encode(["progress" => $progress]);
?>
