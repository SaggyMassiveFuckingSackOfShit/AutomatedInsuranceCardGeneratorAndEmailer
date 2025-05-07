<?php
require 'vendor/autoload.php';
require 'DatabaseManager.php';
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

function processFiles() {
    $excelFile = getLatestUploadedFile('uploads/');
    $files = getFiles();
    $data = readExcelData($excelFile);
    $result = [];
    foreach ($files as $file) {
        $cardNumber = extractCardNumber($file);
        $db = new DatabaseManager('localhost', 'root', '', 'TESTING', 'ENTRIES');
        if ($cardNumber) {
            $email = $db->findEmailByCardNumber( str_replace("_", " ", $cardNumber));
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
            $mail->setFrom('bewiserph2021@gmail.com', 'Bewiser Philippines');
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
            copy($filePath, "outputs/backup/".basename($filePath));
            unlink($filePath);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/css/adminlte.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>
    <style>
        * {
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            height: 1080px;
            font-family: 'Poppins', sans-serif;
            text-align: center;
            background: linear-gradient(135deg, #c25b18, #1d2b46);
            color: white;
            overflow-x: hidden;
        }
        .container {
            max-width: 600px;
            margin-top: 33%;
            margin-bottom: 33%;
            margin: auto;
            background: rgba(255, 255, 255, 0.15);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            font-weight: 700;
        }
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
        #status {
            margin-top: 15px;
            font-size: 14px;
            color: #007BFF;
            font-weight: 500;
        }
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
        .progress-container {
            width: 100%;
            background: #ddd;
            border-radius: 8px;
            margin-top: 20px;
            overflow: hidden;
            height: 12px;
        }
        .progress-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            transition: width 0.5s ease-in-out;
            border-radius: 8px;
        }
        .wrapper, .content-wrapper, .main-header, .main-sidebar {
          background: linear-gradient(135deg, #c25b18, #1d2b46) !important;
        }
        .card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
            backdrop-filter: blur(5px);
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .navbar, .sidebar {
            background: rgba(0, 0, 0, 0.3) !important;
        }
        .nav-link:hover {
            color: #ffcc00 !important;
        }
        .fixed {
            background: rgba(0, 0, 0, 0.75) !important;
        }
        .blurred {
            filter: blur(5px);
            pointer-events: none;
            user-select: none;
        }
        #content-area {
            padding: 20px;
            min-height: 80vh;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body>
<nav class="main-header navbar navbar-expand navbar-dark navbar-light bg-dark">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
    </nav>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <span class="brand-text font-weight-light brand-link">Admin Dashboard</span>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="generator.html" class="nav-link" onclick="loadPage('generator.html', event)">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>Card Generator</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="mailer.php" class="nav-link" onclick="loadPage('mailer.php', event)">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>Email Sender</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>    <div class="container">
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


