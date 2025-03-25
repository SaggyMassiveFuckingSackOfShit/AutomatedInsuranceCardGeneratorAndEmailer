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

    // Ensure the debug folder exists
    $debugDir = "debug/";
    if (!is_dir($debugDir)) {
        mkdir($debugDir, 0777, true);
    }

    file_put_contents($progressFile, "0");
    $totalRecords = count($data) - 1;
    $processed = 0;

    foreach ($data as $index => $rowData) {
        if ($index === 0) {
            continue;
        }

        $full_name = ($rowData[23] ?? '') . ' ' . ($rowData[6] ?? '');
        $beneficiary_name = ($rowData[19] ?? '');
        $relation_name = ($rowData[20] ?? '');
        $cardNumber = str_replace('-',' ',$rowData[8] ?? 'DC 0000 0325 0000 ' . rand(1111,9999));

        $nameParts = explode(" ", trim($full_name));
        $lastName = strtoupper(end($nameParts));

        $frontImage = "$outputDir{$lastName}_{$cardNumber}front.png";
        $backImage = "$outputDir{$lastName}_{$cardNumber}back.png";
        $featuresImage = 'templates/features_template.png';

        $command = "python generateCard.py " . escapeshellarg($full_name) . " " . escapeshellarg($beneficiary_name) . " " . escapeshellarg($relation_name) . " " . escapeshellarg($cardNumber);
        file_put_contents('debug/debug_python.log', "Running: $command\n", FILE_APPEND);
        $output = shell_exec($command . " 2>&1");
        file_put_contents('debug/debug_python.log', "Output: $output\n", FILE_APPEND);
        
        if ($processed >= $totalRecords) {
            file_put_contents('debug/debug_progress.log', "Force setting progress to 100%\n", FILE_APPEND);
            file_put_contents($progressFile, "100");
        }
        
        if (!file_exists($frontImage) || !file_exists($backImage) || !file_exists($featuresImage)) {
            file_put_contents('debug/debug_missing_images.log', "Missing images: $frontImage | $backImage | $featuresImage\n", FILE_APPEND);
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
        $progress = ceil(($processed / ($totalRecords)) * 100);
        
        file_put_contents('debug/debug_progress.log', "Processed: $processed / $totalRecords => $progress%\n", FILE_APPEND);
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