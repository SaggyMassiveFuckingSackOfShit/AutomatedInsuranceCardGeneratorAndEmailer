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
    foreach ($data as $index => $rowData) {
        if ($index === 0) continue;
        
        $full_name = ($rowData[6] ?? '') . ' ' . ($rowData[5] ?? '');
        $cardNumber = $rowData[1] ?? '';
        
        $nameParts = explode(" ", trim($full_name));
        $lastName = strtoupper(end($nameParts));
        
        $frontImage = "$outputDir{$lastName}_{$cardNumber}front.png";
        $backImage = "$outputDir{$lastName}_{$cardNumber}back.png";
        $featuresImage = 'templates/features_template.png';
        
        $command = "python generateCard.py " . escapeshellarg($full_name) . " " . escapeshellarg($cardNumber);
        shell_exec($command);
        
        if (!file_exists($frontImage) || !file_exists($backImage) || !file_exists($featuresImage)) {
            die("Error: Generated images not found.");
        }

        $templateFile = 'template.docx';
        if (!file_exists($templateFile)) {
            die("Error: Template file not found.");
        }

        $updatedFile = "{$lastName}_{$cardNumber}.docx";
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

        $outputDoc = 'final_card.docx';
        $templateProcessor->saveAs($outputDoc);
        $updatedWordFile = $outputDoc;
        
        convertDocxToPdf($updatedWordFile, __DIR__);
        $updatedPdfFile = str_replace('.docx', '.pdf', $updatedWordFile);

        if (!empty($updatedPdfFile) && file_exists($updatedPdfFile)) {
            $_SESSION['updatedPdfFile'] = $updatedPdfFile;
        } else {
            die("Error: Failed to convert Word to PDF.");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Card</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        .card {
            display: inline-block;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <form method="post">
        <button type="submit" name="generate">Generate Card</button>
    </form>
    
    <div class="card-container">
        <?php if (!empty($updatedWordFile)): ?>
            <div class="card">
                <h3>Generated Card:</h3>
                <img src="<?php echo htmlspecialchars($frontImage); ?>" alt="Generated Card">
                <h3 class="mt-4">Back of Card:</h3>
                <img src="<?php echo htmlspecialchars($backImage); ?>" alt="Back of Card">
            </div>

            <div class="text-center mt-4">
                <a href="<?php echo htmlspecialchars($updatedWordFile); ?>" class="btn btn-primary" download>Download Word File</a>
                <a href="<?php echo htmlspecialchars($updatedPdfFile); ?>" class="btn btn-secondary" download>Download PDF</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
