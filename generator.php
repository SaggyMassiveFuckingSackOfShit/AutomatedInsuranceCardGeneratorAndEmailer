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