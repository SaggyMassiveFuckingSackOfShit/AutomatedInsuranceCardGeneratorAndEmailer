<?php
header('Content-Type: application/json');

$progressFile = "outputs/progress.txt";

if (!file_exists($progressFile)) {
    file_put_contents($progressFile, "0");
}

$progress = file_get_contents($progressFile);

if ((int)$progress > 100) {
    $progress = 100;
    file_put_contents($progressFile, "100");
}

echo json_encode(["progress" => $progress]);
?>
