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
        .card-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
        }
        img {
            max-width: 300px;
            border-radius: 10px;
            margin-top: 10px;
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

    // Only execute when the 'Generate Card' button is clicked
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {
        $file = 'xlsx/sample.xlsx';
        $outputDir = 'outputs/img/';

        if (file_exists($file)) {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $firstRow = true;

            echo '<div class="card-container">';

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
                $cardNumber = isset($rowData[1]) ? $rowData[1] : '';

                // Extract last name in uppercase
                $nameParts = explode(" ", trim($full_name));
                $lastName = strtoupper(end($nameParts)); // Get the last element in the name

                // Expected filenames
                $frontImage = $outputDir . $lastName . "_" . $cardNumber . "front.png";
                $backImage = $outputDir . $lastName . "_" . $cardNumber . "back.png";

                // Run Python script
                $command = escapeshellcmd("python generateCard.py") . " " . 
                           escapeshellarg($full_name) . " " . 
                           escapeshellarg($cardNumber);
                shell_exec($command);

                // Display images if they exist
                echo '<div class="card">';
                if (file_exists($frontImage)) {
                    echo '<img src="' . htmlspecialchars($frontImage) . '" alt="Front of ' . htmlspecialchars($full_name) . '">';
                } else {
                    echo '<p>Front image not found</p>';
                }

                if (file_exists($backImage)) {
                    echo '<img src="' . htmlspecialchars($backImage) . '" alt="Back of ' . htmlspecialchars($full_name) . '">';
                } else {
                    echo '<p>Back image not found</p>';
                }
                echo '</div>';
            }

            echo '</div>';
        } else {
            echo "<p>Error: File not found.</p>";
        }
    }
    ?>
</body>
</html>
