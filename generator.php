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




if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset"])) {
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$updatedWordFile = '';
$updatedPdfFile = '';

$file = 'xlsx/sample.xlsx';
$outputDir = 'outputs/img/';

$data = [];

if (file_exists($file)) {
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    
    foreach ($sheet->getRowIterator() as $row) {
        $rowData = [];
        foreach ($row->getCellIterator() as $cell) {
            $rowData[] = $cell->getValue();
        }
        $data[] = $rowData;
    }
} else {
    die("Error: File not found.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {
    $progressFile = "outputs/progress.txt";
    
    // Reset progress at the beginning of the process
    file_put_contents($progressFile, "0");

    $totalRecords = count($data) - 1; // Exclude the header row
    $processed = 0;
    file_put_contents('outputs/debug_totalRecords.log', "Total Records: " . $totalRecords . "\n", FILE_APPEND);


    foreach ($data as $index => $rowData) {
        if ($index === 0 || strtoupper($rowData[3] ?? '') === 'PHYSICAL' || empty($rowData[1] ?? '')) {
            continue;
            file_put_contents('outputs/debug_foreach.log', "Processing row $index\n", FILE_APPEND);

        }
        if ($index === 0 || strtoupper($rowData[3] ?? '') === 'PHYSICAL' || empty($rowData[1] ?? '')) {
            file_put_contents('outputs/debug_skipped.log', "Skipped row $index: " . json_encode($rowData) . "\n", FILE_APPEND);
            continue;
        }
        file_put_contents('outputs/debug_foreach.log', "Processing row $index\n", FILE_APPEND);
        

        // Process each record
        $full_name = ($rowData[6] ?? '') . ' ' . ($rowData[5] ?? '');
        $beneficiary_name = ($rowData[13] ?? '');
        $relation_name = ($rowData[14] ?? '');
        $cardNumber = $rowData[1] ?? '';

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
                let progressBar = document.getElementById('progress-bar-fill');
                let progressText = document.getElementById('progress-text');

                progressBar.style.width = progress + '%';
                progressText.innerText = progress + '%';

                if (progress < 100) {
                    setTimeout(fetchProgress, 1000);
                } else {
                    setTimeout(() => {
                        progressBar.style.width = '100%';
                        progressText.innerText = 'Completed!';
                    }, 500);  // Small delay to ensure the UI updates
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
            <div class="drop-zone" onclick="triggerFileInput()" ondrop="handleFileSelect(event)" ondragover="event.preventDefault()">
                <p>Drag & Drop Excel file here or click to upload</p>
                <input type="file" name="excelFile" id="excelFile" class="file-input" accept=".xls,.xlsx" onchange="handleFileSelect(event)">
                <p id="fileName">No file selected</p>
            </div>
            <button type="submit" name="generate" class="btn btn-primary">
                <i class="fa fa-id-card"></i> Generate Card
            </button>
            <button type="reset" class="btn btn-danger" onclick="document.getElementById('fileName').innerText='No file selected'; document.getElementById('progress').style.display='none';">
                <i class="fa fa-redo"></i> Reset
            </button>
        </form>
        
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
