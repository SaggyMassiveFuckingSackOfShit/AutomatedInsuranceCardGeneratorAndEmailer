<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'vendor/autoload.php';
require 'DatabaseManager.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

function convertDocxToPdf($inputFile, $outputDir) {
    $libreOfficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
    $command = escapeshellarg($libreOfficePath) . " --headless --convert-to pdf --outdir " . escapeshellarg($outputDir) . " " . escapeshellarg($inputFile);
    $output = shell_exec($command . " 2>&1");
    
    if (!file_exists(str_replace('.docx', '.pdf', $inputFile))) {
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
    if (!file_exists($file)) die("Error: File not found.");
    
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $data = [];
    
    foreach ($sheet->getRowIterator() as $row) {
        $rowData = [];
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $count = 0;
        foreach ($cellIterator as $cell) {
            if ($count >= 32) break;
            $rowData[] = $cell->getValue();
            $count++;
        }
        $data[] = array_pad($rowData, 32, null);
    }
    return $data;
}

function generateCards($data, $outputDir) {
    // Ensure outputs directory exists
    if (!file_exists('outputs')) {
        mkdir('outputs', 0777, true);
    }

    foreach ($data as $index => $rowData) {
        if ($index === 0) continue;

        $full_name = ($rowData[23] ?? '') . ' ' . ($rowData[6] ?? '');
        $beneficiary_name = ($rowData[19] ?? '');
        $relation_name = ($rowData[20] ?? '');
        $cardNumber = str_replace('-',' ',$rowData[8] ?? 'DC 0000 0325 0000 ' . rand(1111,9999));

        $dbManager = new DatabaseManager('localhost', 'root', '', 'TESTING', 'ENTRIES');
        try {
            if (!$dbManager->cardNumberExists($cardNumber)) {
                $dbManager->insertExcelData([$rowData]);
            }
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
        }
        $dbManager->close();
        $nameParts = explode(" ", trim($full_name));
        $lastName = strtoupper(end($nameParts));
        $cardNumber = str_replace(' ', '_', $cardNumber);

        $frontImage = "$outputDir{$lastName}_{$cardNumber}front.png";
        $backImage = "$outputDir{$lastName}_{$cardNumber}back.png";
        $featuresImage = 'templates/features_template.png';

        $command = "python generateCard.py " . escapeshellarg($full_name) . " " . escapeshellarg($beneficiary_name) . " " . escapeshellarg($relation_name) . " " . escapeshellarg($cardNumber);
        $output = shell_exec($command . " 2>&1");
        
        if (!file_exists($frontImage) || !file_exists($backImage) || !file_exists($featuresImage)) {
            error_log("Error: Generated images not found. Command output: " . $output);
            continue;
        }
        
        $templateFile = 'templates/template.docx';
        if (!file_exists($templateFile)) {
            error_log("Error: Template file not found.");
            continue;
        }

        $outputDoc = "outputs/pdf/{$lastName}_{$cardNumber}.docx";
        copy($templateFile, $outputDoc);

        $templateProcessor = new TemplateProcessor($outputDoc);
        $imageSettings = ['width' => 600, 'height' => 375, 'ratio' => false];
        
        $templateProcessor->setImageValue('image', ['path' => $frontImage] + $imageSettings);
        $templateProcessor->setImageValue('image2', ['path' => $backImage] + $imageSettings);
        $templateProcessor->setImageValue('image3', ['path' => $featuresImage] + $imageSettings);
        
        $templateProcessor->saveAs($outputDoc);
        convertDocxToPdf($outputDoc, "outputs/pdf");
        
        unlink($outputDoc);
        unlink($frontImage);
        unlink($backImage);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {
    if (isset($_FILES["excelFile"]) && $_FILES["excelFile"]["error"] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES["excelFile"]["tmp_name"];
        if (file_exists($uploadedFile)) {
            $data = loadExcelData($uploadedFile);
            generateCards($data, 'outputs/img/');
            echo "Processing complete!";
        } else {
            echo "Error: File not found at " . $uploadedFile;
        }
    } else {
        echo "Error: Upload error code: " . ($_FILES["excelFile"]["error"] ?? "No file uploaded");
    }
}
?>