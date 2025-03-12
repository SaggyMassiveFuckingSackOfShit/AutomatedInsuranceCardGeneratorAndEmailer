<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

// Function to convert DOCX to PDF using LibreOffice
function convertDocxToPdf($inputFile, $outputDir) {
    $libreOfficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';

    // Escape paths properly
    $escapedInputFile = escapeshellarg($inputFile);
    $escapedOutputDir = escapeshellarg($outputDir);
    $escapedLibreOfficePath = escapeshellarg($libreOfficePath);

    // Construct command
    $command = "$escapedLibreOfficePath --headless --convert-to pdf --outdir $escapedOutputDir $escapedInputFile";

    // Run command and capture full output
    $output = shell_exec($command . " 2>&1");

    $pdfFile = str_replace('.docx', '.pdf', $inputFile);

    if (!file_exists(str_replace("'", "", $pdfFile))) {
        die("Error: PDF file was not created. LibreOffice output: <pre>$output</pre>");
    }

    return $output;
}


function getLatestUploadedFile($uploadDir) {
    if (!is_dir($uploadDir)) {
        return "Error: Directory does not exist.";
    }

    $files = glob($uploadDir . '*'); // Get all files in the directory
    if (!$files) {
        return "Error: No files found in directory.";
    }

    // Sort files by modification time (newest first)
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    // Get the most recent file
    $latestFile = $files[0];
    
    return $latestFile;
}

function loadExcelData($file) {
    if (!file_exists($file)) {
        die("Error: File not found.");
    }
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $data = [];
    foreach ($sheet->getRowIterator() as $row) {
        $rowData = [];
        foreach ($row->getCellIterator() as $cell) {
            $rowData[] = $cell->getValue();
        }
        $data[] = $rowData;
    }
    return $data;
}

function generateCards($data, $outputDir) {
    $progressFile = "outputs/progress.txt";
    file_put_contents($progressFile, "0");
    $totalRecords = count($data) - 1;
    $processed = 0;

    foreach ($data as $index => $rowData) {
        if ($index === 0 || strtoupper($rowData[3] ?? '') === 'PHYSICAL' || empty($rowData[1] ?? '')) {
            continue;
        }
        // Process each record
        $full_name = ($rowData[6] ?? '') . ' ' . ($rowData[5] ?? '');
        $beneficiary_name = ($rowData[13] ?? '');
        $relation_name = ($rowData[14] ?? '');
        $cardNumber = $rowData[1] ?? '';
        $email = $rowData[12] ?? '';

        $nameParts = explode(" ", trim($full_name));
        $lastName = strtoupper(end($nameParts));

        $frontImage = "$outputDir{$lastName}_{$cardNumber}front.png";
        $backImage = "$outputDir{$lastName}_{$cardNumber}back.png";
        $featuresImage = 'templates/features_template.png';

        $command = "python generateCard.py " . escapeshellarg($full_name) . " " . escapeshellarg($beneficiary_name) . " " . escapeshellarg($relation_name) . " " . escapeshellarg($cardNumber);
        file_put_contents('outputs/debug_python.log', "Running: $command\n", FILE_APPEND);
        $output = shell_exec($command . " 2>&1");
        file_put_contents('outputs/debug_python.log', "Output: $output\n", FILE_APPEND);
        
        if ($processed >= $totalRecords) {
            file_put_contents('outputs/debug_progress.log', "Force setting progress to 100%\n", FILE_APPEND);
            file_put_contents($progressFile, "100");
        }
        

        if (!file_exists($frontImage) || !file_exists($backImage) || !file_exists($featuresImage)) {
            file_put_contents('outputs/debug_missing_images.log', "Missing images: $frontImage | $backImage | $featuresImage\n", FILE_APPEND);
            die("Error: Generated images not found.");
        }
        
        $templateFile = 'templates/template.docx';
        if (!file_exists($templateFile)) {
            die("Error: Template file not found.");
        }

        $updatedFile = "outputs/pdf/{$lastName}_{$cardNumber}.docx";
        copy($templateFile, $updatedFile);

        $templateProcessor = new TemplateProcessor($updatedFile);

        $templateProcessor->setImageValue('image', [
            'path' => $frontImage,
            'width' => 600,
            'height' => 375,
            'ratio' => false
        ]);

        $templateProcessor->setImageValue('image2', [
            'path' => $backImage,
            'width' => 600,
            'height' => 375,
            'ratio' => false
        ]);

        $templateProcessor->setImageValue('image3', [
            'path' => $featuresImage,
            'width' => 600,
            'height' => 375,
            'ratio' => false
        ]);

        $outputDoc = "outputs/pdf/{$lastName}_{$cardNumber}.docx";
        $templateProcessor->saveAs($outputDoc);
        convertDocxToPdf($outputDoc, "outputs/pdf");
        unlink($outputDoc);
        unlink($frontImage);
        unlink($backImage);
        


        // Update progress
        $processed++;
        $progress = ceil(($processed / ($totalRecords-1)) * 100);
        
        file_put_contents('outputs/debug_progress.log', "Processed: $processed / $totalRecords => $progress%\n", FILE_APPEND);
        file_put_contents($progressFile, $progress);
        
        
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {
    if (isset($_FILES["excelFile"]) && $_FILES["excelFile"]["error"] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $uploadedFile = $uploadDir . basename($_FILES["excelFile"]["name"]);

        if (move_uploaded_file($_FILES["excelFile"]["tmp_name"], $uploadedFile)) {
            $data = loadExcelData($uploadedFile);
            generateCards($data, 'outputs/img/');
        } else {
            die("Error moving uploaded file.");
        }
    } else {
        echo "Upload error code: " . ($_FILES["excelFile"]["error"] ?? "No file uploaded");
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Card</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            text-align: center;
            background: linear-gradient(135deg, #c25b18, #1d2b46);
            color: white;
            margin: 25px;
            overflow-x: hidden;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: rgba(255, 255, 255, 0.15);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            animation: fadeIn 1s ease-in-out;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            margin: 5px;
        }
        .btn-primary {
            background: #c25b18;
            color: white;
        }
        .btn-danger {
            background: #1d2b46;
            color: white;
        }
        .btn:hover {
            transform: scale(1.1);
        }
        .progress {
            display: none;
            margin-top: 20px;
        }
        .loading {
            font-size: 18px;
            font-weight: bold;
            color: #c25b18;
        }
        .progress-bar {
            width: 100%;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-bar-fill {
            width: 0%;
            height: 10px;
            background: #c25b18;
            transition: width 1s ease-in-out;
        }   
        .progress-text {
            margin-top: 5px;
            font-weight: bold;
            color: white;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .drop-zone {
            border: 2px dashed #fff;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            border-radius: 10px;
            margin: 20px 0;
            transition: background 0.3s;
        }
        .drop-zone:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .file-input {
            display: none;
        }
    </style>
<script>
    function openFileExplorer() {
        document.getElementById('fileInput').click();
    }
    function displayFileName() {
        const fileInput = document.getElementById('fileInput');
        const fileNameDisplay = document.getElementById('fileName');

        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const fileType = file.name.split('.').pop().toLowerCase();

            if (fileType === "xlsx" || fileType === "xls") {
                fileNameDisplay.textContent = "Selected file: " + file.name;
                uploadFile(file);
            } else {
                fileNameDisplay.textContent = "Invalid file type. Please select an Excel file.";
                fileInput.value = "";
            }
        } else {
            fileNameDisplay.textContent = "No file selected";
        }
    }
    function uploadFile(file) {
        let formData = new FormData();
        formData.append("excelFile", file);

        fetch("upload.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            console.log(result);
        })
        .catch(error => console.error("Error uploading file:", error));
    }
    function startProgressBar() {
        document.getElementById('progress').style.display = 'block';
        let progressBar = document.getElementById('progress-bar-fill');
        let progressText = document.getElementById('progress-text');

        // Reset UI before starting
        progressBar.style.width = '0%';
        progressText.innerText = '0%';

        function fetchProgress() {
            fetch('progress.php')
                .then(response => response.json())
                .then(data => {
                    let progress = data.progress;
                    progressBar.style.width = progress + '%';
                    progressText.innerText = progress + '%';

                    if (progress < 100) {
                        setTimeout(fetchProgress, 1000);
                    } else {
                        progressText.innerText = 'Completed!';
                    }
                })
                .catch(error => console.error('Error fetching progress:', error));
        }

        fetchProgress();
    }
</script>


</head>
<body>
    <img src="img/logo.png" alt="Company Logo" class="logo">
    <div class="container">
        <h1>Insurance Card Generator</h1>
        <p>Upload an Excel file and generate insurance cards.</p>
        
        <form method="post" enctype="multipart/form-data" onsubmit="startProgressBar(); return validateFile(document.getElementById('excelFile').files[0])">
            <div class="drop-zone" onclick="openFileExplorer()" ondrop="handleFileSelect(event)" ondragover="event.preventDefault()">
                <p>Click to upload</p>

                <input type="file" id="excelFile" name="excelFile" accept=".xlsx,.xls" style="display: none;" onchange="displayFileName()">
                <p id="fileName">No file selected</p>
            </div>
            <button type="submit" name="generate" class="btn btn-primary">
                <i class="fa fa-id-card"></i> Generate Card
            </button>
            <button type="reset" class="btn btn-danger" onclick="resetForm()">
                <i class="fa fa-redo"></i> Reset
            </button>
        </form>

<script>
function openFileExplorer() {
    document.getElementById("excelFile").click();
}

function displayFileName() {
    let fileInput = document.getElementById("excelFile");
    let fileName = document.getElementById("fileName");
    fileName.innerText = fileInput.files.length > 0 ? fileInput.files[0].name : "No file selected";
}

function resetForm() {
    document.getElementById("fileName").innerText = "No file selected";
    document.getElementById("progress").style.display = "none";
}
</script>

        
        <div id="progress" class="progress">
            <p class="loading"><i class="fa fa-spinner fa-spin"></i> Generating card, please wait...</p>
            <div class="progress-bar">
                <div id="progress-bar-fill" class="progress-bar-fill"></div>
            </div>
            <p id="progress-text" class="progress-text">0%</p>
        </div>
    </div>
</body>
</html>