<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'vendor/autoload.php';
require 'DatabaseManager.php';

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

    $files = glob($uploadDir . '*');
    if (!$files) {
        return "Error: No files found in directory.";
    }

    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

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
        $i = 0;
        foreach ($row->getCellIterator() as $cell) {
            if($i>=32){
                break;
            }else{
                $i++;
            }
            $rowData[] = $cell->getValue();
            file_put_contents("debug/debug_data.log", $cell->getValue()."\n", FILE_APPEND);
        }
        $data[] = $rowData;
        file_put_contents("debug/debug_data.log", "////////////////////////////////////////////////////\n", FILE_APPEND);
    }
    file_put_contents("debug/debug_data.log", $cell->getValue()."\n", FILE_APPEND);
    
    return $data;
}

function generateCards($data, $outputDir) {
    if (!file_exists('outputs')) {
        mkdir('outputs', 0777, true);
    }

    foreach ($data as $index => $rowData) {
        if ($index === 0 || is_null($rowData[0])) continue;
        $full_name = ($rowData[23] ?? '') . ' ' . ($rowData[6] ?? '');
        $beneficiary_name = ($rowData[19] ?? '');
        $relation_name = ($rowData[20] ?? '');
        $dbManager = new DatabaseManager('localhost', 'root', '', 'TESTING', 'ENTRIES');
        
        $lastInsertedId = str_split(str_pad($dbManager->getLastInsertedId(), 8 , '0', STR_PAD_LEFT), 4);
        $rowData[8] = ($rowData[8] ?? str_replace(' ', '_', $rowData[8] ?? "DC 0000 0325 " . $lastInsertedId[0] . " " . $lastInsertedId[1])); 
        $cardNumber = $rowData[8];
        
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

        
        $templateDir = "templates/";

        $frontImage = "$outputDir{$lastName}_{$cardNumber}front.png";
        $backImage = "$outputDir{$lastName}_{$cardNumber}back.png";
        if (strcasecmp($rowData[1], "Gold Plan") == 0) {
            $featuresImage = $templateDir . "goldFeatured.png";
        } elseif (strcasecmp($rowData[1], "Prime Plan") == 0) {
            $featuresImage = $templateDir . "primeFeatured.png";
        } elseif (strcasecmp($rowData[1], "Student Plan") == 0) {
            $featuresImage = $templateDir . "studentFeatured.png";
        } else {
            $featuresImage = $templateDir . "standardFeatured.png";
        }
            
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