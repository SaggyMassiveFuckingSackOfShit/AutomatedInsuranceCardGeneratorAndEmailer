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
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <form method="post">
        <button type="submit" name="generate">Generate Card</button>
        <button type="submit" name="reset">Reset</button>
    </form>

    <?php
    require 'vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\IOFactory;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['generate'])) {
            $file = 'xlsx/sample.xlsx';
            
            if (file_exists($file)) {
                $spreadsheet = IOFactory::load($file);
                $sheet = $spreadsheet->getActiveSheet();
                $firstRow = true;
                
                echo '<div class="card">';
                
                foreach ($sheet->getRowIterator() as $row) {
                    if ($firstRow) {
                        $firstRow = false;
                        continue;
                    }
                    $rowData = [];
                    foreach ($row->getCellIterator() as $cell) {
                        $rowData[] = $cell->getValue();
                    }
                    
                    // Get values from specified indexes
                    $full_name = (isset($rowData[6]) ? $rowData[6] : '') . ' ' . (isset($rowData[5]) ? $rowData[5] : '');
                    $beneficiaryName = isset($rowData[13]) ? $rowData[13] : '';
                    $relationName = isset($rowData[14]) ? $rowData[14] : '';
                    $cardNumber = isset($rowData[1]) ? $rowData[1] : '';
                    
                    // Construct shell command
                    $command = escapeshellcmd("python generateCard.py") . " " . 
                               escapeshellarg($full_name) . " " . 
                               escapeshellarg($beneficiaryName) . " " . 
                               escapeshellarg($relationName) . " " . 
                               escapeshellarg($cardNumber);
                    
                    // Execute Python script
                    $output = shell_exec($command);
                    
                    // Check for execution failure
                    if ($output === null) {
                        die("Error: Failed to execute Python script.");
                    }
                    
                    // Process Python output
                    $output = trim($output);
                    $outputLines = explode("\n", $output);
                    
                    // Validate Python output
                    if (count($outputLines) < 2) {
                        die("Error: Python script did not return expected output.");
                    }
                    
                    echo '<p><strong>Full Name:</strong> ' . htmlspecialchars(trim($full_name)) . '</p>';
                    echo '<p><strong>Beneficiary Name:</strong> ' . htmlspecialchars(trim($beneficiaryName)) . '</p>';
                    echo '<p><strong>Relation Name:</strong> ' . htmlspecialchars(trim($relationName)) . '</p>';
                    echo '<p><strong>Card Number:</strong> ' . htmlspecialchars(trim($cardNumber)) . '</p>';
                }
                
                echo '</div>';
            }
        }
    }
    ?>
</body>
</html>
