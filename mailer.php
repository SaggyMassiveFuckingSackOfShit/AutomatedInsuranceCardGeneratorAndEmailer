<?php

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;
use PhpOffice\PhpSpreadsheet\IOFactory;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();




//tama
function getFiles() {
    $dir = 'outputs/pdf/';
    $files = [];
    if (is_dir($dir)) {
        $iterator = new DirectoryIterator($dir);
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getFilename();
            }
        }
    }
    return $files;
}

//TAMA
function extractCardNumber($filename) {
    $parts = explode('_', $filename);
    if (count($parts) > 1) {
        return str_replace('.pdf', '', $parts[1]);
    }
    return null;
}

//MAY TAMA
function readExcelData($file) {
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
    return $data;
}

//TAMA
function findEmailByCardNumber($data, $cardNumber) {
    foreach ($data as $row) {
        if (!empty($row[1]) && $row[1] == $cardNumber) {
            return $row[12] ?? null;
        }
    }
    return null;
}

//TAMA
function processFiles() {
    $excelFile = "xlsx/sample.xlsx"; // Always using xlsx/sample.xlsx
    $files = getFiles();
    $data = readExcelData($excelFile);
    $result = [];

    foreach ($files as $file) {
        $cardNumber = extractCardNumber($file);
        if ($cardNumber) {
            $email = findEmailByCardNumber($data, $cardNumber);
            if ($email) {
                $result[$file] = $email;
            }
        }
    }
    
    return $result;
}

function sendEmails($fileEmailDict) {
    foreach ($fileEmailDict as $filename => $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Invalid email for $filename: $email <br>";
            continue;
        }

        $mail = new PHPMailer(true);
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Change this to your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Sender & Recipient
            $mail->setFrom('eddrind17@gmail.com', 'Bewiser Philippines');
            $mail->addAddress($email); // Recipient's email

            // Attach file
            $filePath = "outputs/pdf/" . $filename;
            if (file_exists($filePath)) {
                $mail->addAttachment($filePath);
            } else {
                echo "File not foundSEND EMAIL: $filePath <br>";
                continue;
            }

            // Email content
            $mail->isHTML(true);
            $mail->Subject = "Your Digital Card";
            $mail->Body = "Dear user,<br><br>Please find your attached digital card.<br><br>Best regards,<br>Your Team";

            // Send email
            $mail->send();
            echo "Email sent to $email with file $filename <br>";
        } catch (Exception $e) {
            echo "Error sending email to $email: " . $mail->ErrorInfo . "<br>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Process Files</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }
        button {
            padding: 15px 30px;
            font-size: 18px;
            cursor: pointer;
            border: none;
            background-color: #007BFF;
            color: white;
            border-radius: 5px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
            margin-top: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
        pre {
            margin-top: 20px;
            background: white;
            padding: 10px;
            border: 1px solid black;
            width: 80%;
            overflow: auto;
        }
    </style>
</head>
<body>
    <form method="POST">
        <button type="submit" name="process">Process Files</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process'])) {
        $result = processFiles(); // Always processes xlsx/sample.xlsx
        sendEmails(processFiles());
        // Display the raw output
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }
    ?>
</body>
</html>
