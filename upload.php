<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["file"])) {
    $uploadDir = "xlsx/";  
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);  // Create directory if not exists
    }

    $fileTmpPath = $_FILES["file"]["tmp_name"];
    $fileName = basename($_FILES["file"]["name"]);
    $destination = $uploadDir . $fileName;

    if (move_uploaded_file($fileTmpPath, $destination)) {
        echo "File uploaded successfully: $destination";
    } else {
        echo "Error moving the file.";
    }
} else {
    echo "No file uploaded.";
}
?>