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
        $beneficiary_name = ($rowData[13] ?? '');
        $relation_name = ($rowData[14] ?? '');
        $cardNumber = $rowData[1] ?? '';
        
        $nameParts = explode(" ", trim($full_name));
        $lastName = strtoupper(end($nameParts));
        
        $frontImage = "$outputDir{$lastName}_{$cardNumber}front.png";
        $backImage = "$outputDir{$lastName}_{$cardNumber}back.png";
        $featuresImage = 'templates/features_template.png';
        
        $command = "python generateCard.py " . escapeshellarg($full_name) . " " . escapeshellarg($beneficiary_name) . " " . escapeshellarg($relation_name) . " " . escapeshellarg($cardNumber);
        shell_exec($command);
        
        if (!file_exists($frontImage) || !file_exists($backImage) || !file_exists($featuresImage)) {
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
        $updatedWordFile = $outputDoc;
        
        convertDocxToPdf($updatedWordFile, "outputs/pdf");
        $updatedPdfFile = str_replace('.docx', '.pdf', $updatedWordFile);

        if (!empty($updatedPdfFile) && file_exists($updatedPdfFile)) {
            $_SESSION['updatedPdfFile'] = $updatedPdfFile;
        } else {
            die("Error: Failed to convert Word to PDF.");
        }
        unlink($outputDoc);
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
            margin: 139px;
            overflow-x: hidden;
        }
        .container {
            max-width: 5000px;
            margin: auto;
            background: rgba(255, 255, 255, 0.15);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            animation: fadeIn 1s ease-in-out;
            position: relative;
        }
        .logo {
            max-width: 550px;
            display: block;
            margin: 0 auto 0px;
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
            box-shadow: 0 4px 10px rgba(255, 126, 179, 0.3);
        }
        .btn-danger {
            background: #1d2b46;
            color: white;
            box-shadow: 0 4px 10px rgba(255, 75, 92, 0.3);
        }
        .btn:hover {
            transform: scale(1.1);
        }
        .card-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            text-align: center;
        }
        .card:hover {
            transform: translateY(-10px);
        }
        img {
            max-width: 100%;
            border-radius: 15px;
            transition: transform 0.3s;
        }
        img:hover {
            transform: scale(1.05);
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
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
    <script>
        function showLoading() {
            document.getElementById('progress').style.display = 'block';
        }
    </script>
</head>
<body>
<img src="logo.png" alt="Company Logo" class="logo">
    <div class="container">
        <h1>Insurance Card Generator</h1>
        <p>Click the button below to generate insurance card.</p>
        <form method="post" onsubmit="showLoading()">
            <button type="submit" name="generate" class="btn btn-primary">
                <i class="fa fa-id-card"></i> Generate Card
            </button>
            <button type="submit" name="reset" class="btn btn-danger">
                <i class="fa fa-redo"></i> Reset
            </button>
        </form>

        <div id="progress" class="progress">
            <p class="loading"><i class="fa fa-spinner fa-spin"></i> Generating card, please wait...</p>
        </div>

        <div class="card-container" style="justify-content: center; align-items: center; display: flex; flex-direction: column;">
            <?php if (!empty($updatedWordFile)): ?>
                <div class="card">
                    <h3>Generated Card:</h3>
                    <img src="<?php echo htmlspecialchars($frontImage); ?>" alt="Generated Card">
                    <h3 class="mt-4">Back of Card:</h3>
                    <img src="<?php echo htmlspecialchars($backImage); ?>" alt="Back of Card">
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

