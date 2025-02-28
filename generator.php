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

    <?php
    require 'vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\IOFactory;

    // Only run when the 'generate' button is clicked
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {
        $file = 'xlsx/sample.xlsx';

        if (file_exists($file)) {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $firstRow = true;

            foreach ($sheet->getRowIterator() as $row) {
                if ($firstRow) {
                    $firstRow = false;
                    continue;
                }
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }

                // Extract values
                $full_name = (isset($rowData[6]) ? $rowData[6] : '') . ' ' . (isset($rowData[5]) ? $rowData[5] : '');
                $beneficiaryName = isset($rowData[13]) ? $rowData[13] : '';
                $relationName = isset($rowData[14]) ? $rowData[14] : '';
                $cardNumber = isset($rowData[1]) ? $rowData[1] : '';

                // Run Python script
                $command = escapeshellcmd("python generateCard.py") . " " . 
                           escapeshellarg($full_name) . " " . 
                           escapeshellarg($beneficiaryName) . " " . 
                           escapeshellarg($relationName) . " " . 
                           escapeshellarg($cardNumber);
                
                $output = shell_exec($command);

                // Validate output
                if ($output === null) {
                    die("Error: Failed to execute Python script.");
                }
            }

            // Display all images from outputs/img directory
            $imgDir = 'outputs/img/';
            $images = glob($imgDir . '*.{jpg,png,gif,jpeg}', GLOB_BRACE);

            if (!empty($images)) {
                foreach ($images as $image) {
                    echo '<div class="card">';
                    echo '<img src="' . htmlspecialchars($image) . '" alt="Generated Card">';
                    echo '</div>';
                }
            } else {
                echo "<p>No images found in <strong>outputs/img</strong>.</p>";
            }
        } else {
            echo "<p>Error: File not found.</p>";
        }
    }
    ?>
</body>
</html>
