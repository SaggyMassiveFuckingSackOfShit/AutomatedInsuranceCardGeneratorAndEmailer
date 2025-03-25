<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;
use PhpOffice\PhpSpreadsheet\IOFactory;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

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

function extractCardNumber($filename) {
    $parts = explode('_', $filename);
    if (count($parts) > 1) {
        return str_replace('.pdf', '', $parts[1]);
    }
    return null;
}

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

function findEmailByCardNumber($data, $cardNumber) {
    foreach ($data as $row) {
        if (!empty($row[1]) && $row[1] == $cardNumber) {
            return $row[12] ?? null;
        }
    }
    return null;
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

function processFiles() {
    $excelFile = getLatestUploadedFile('uploads/');
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
    $successMessages = [];
    foreach ($fileEmailDict as $filename => $email) {
        echo "<script>updateStatus('Sending email to $email...');</script>";
        flush();
        ob_flush();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Invalid email for $filename: $email <br>";
            continue;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('eddrind17@gmail.com', 'Bewiser Philippines');
            $mail->addAddress($email);

            $filePath = "outputs/pdf/" . $filename;
            if (file_exists($filePath)) {
                $mail->addAttachment($filePath);
            } else {
                echo "File not found: $filePath <br>";
                continue;
            }

            $mail->isHTML(true);
            $mail->Subject = "Your Digital Card";
            $mail->Body = "Dear user,<br><br>Please find your attached digital card.<br><br>Best regards,<br>Your Team";

            $mail->send();
            $successMessages[] = "Email sent to $email with file $filename";
            echo "<script>showAlert('Email sent to $email');</script>";
        } catch (Exception $e) {
            echo "Error sending email to $email: " . $mail->ErrorInfo . "<br>";
        }
    }
    return $successMessages;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEND EMAILLLLLL</title>
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        /* Body Styling */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(90deg, #c25b18, #2a5298);
        }

        /* Card Container */
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 380px;
            animation: fadeIn 0.8s ease-in-out;
        }

        /* Fade-In Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Title Styling */
        h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            font-weight: 700;
        }

        /* Button Styling */
        button {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            color: white;
            border-radius: 8px;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        button:hover {
            background: linear-gradient(135deg, #0056b3, #003f8a);
            transform: scale(1.05);
        }

        /* Status Text */
        #status {
            margin-top: 15px;
            font-size: 14px;
            color: #007BFF;
            font-weight: 500;
        }

        /* Alert Box */
        .alert {
            position: relative;
            margin-top: 20px;
            width: 100%;
            background: #28a745;
            color: white;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.5s ease-in-out, transform 0.3s ease-in-out;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .alert.show {
            opacity: 1;
            transform: scale(1.05);
        }

        /* Progress Bar Container */
        .progress-container {
            width: 100%;
            background: #ddd;
            border-radius: 8px;
            margin-top: 20px;
            overflow: hidden;
            height: 12px;
        }

        /* Progress Bar */
        .progress-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            transition: width 0.5s ease-in-out;
            border-radius: 8px;
        }

    </style>
</head>
<body>
    <div class="container">
        <h2>Process & Send Emails</h2>
        <form method="POST">
            <button type="submit" name="process">Start Process</button>
        </form>
        <p id="status">Waiting for action...</p>
        <div class="progress-container">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
        <div id="alert" class="alert"></div>
    </div>

    <script>
        function showAlert(message) {
            const alertBox = document.getElementById('alert');
            alertBox.textContent = message;
            alertBox.classList.add('show');
            setTimeout(() => {
                alertBox.classList.remove('show');
            }, 3000);
        }

        function updateStatus(message) {
            document.getElementById('status').textContent = message;
        }

        function updateProgress(percent) {
            document.getElementById('progress-bar').style.width = percent + '%';
        }
    </script>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process'])) {
        echo "<script>updateStatus('Processing files...'); updateProgress(10);</script>";
        flush();
        ob_flush();
        $result = processFiles();
        echo "<script>updateStatus('Sending emails...'); updateProgress(50);</script>";
        $successMessages = sendEmails($result);
        echo "<script>updateStatus('Process complete.'); updateProgress(100); showAlert('Process completed successfully!');</script>";
    }
    ?>
</body>
</html>


