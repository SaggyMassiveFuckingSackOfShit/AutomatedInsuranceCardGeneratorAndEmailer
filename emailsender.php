<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["pdfFile"]) && isset($_POST["email"])) {
    $uploadDir = "uploads/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $file = $_FILES["pdfFile"];
    $email = $_POST["email"];
    $filePath = $uploadDir . basename($file["name"]);

    if (mime_content_type($file["tmp_name"]) !== "application/pdf") {
        echo "error: Only PDF files are allowed.";
        exit;
    }

    if (move_uploaded_file($file["tmp_name"], $filePath)) {
        if (sendEmail($email, $filePath)) {
            echo "success";
        } else {
            echo "error: Email sending failed.";
        }
    } else {
        echo "error: File upload failed.";
    }
    exit;
}

function sendEmail($recipientEmail, $filePath) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('eddrind17@gmail.com', 'Bewiser Philippines');
        $mail->addAddress($recipientEmail);
        $mail->Subject = "Your Digital Card";
        $mail->Body    = "Hello,<br><br>Please find the attached PDF file.<br><br>Best Regards,<br>Your Company";
        $mail->isHTML(true);
        $mail->addAttachment($filePath);

        return $mail->send();
    } catch (Exception $e) {
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $errorMessage = date('Y-m-d H:i:s') . " - Email failed: " . $mail->ErrorInfo . "\n";
        file_put_contents($logDir . '/email_error.log', $errorMessage, FILE_APPEND);

        echo "error: " . $mail->ErrorInfo;
        return false;
    }
}
?>

<div class="content-area">
    <h3 class="text-center text-white">Upload File and Send via Email</h3>
    <div class="card p-4">
        <form id="uploadForm" onsubmit="uploadFile(event)">
            <button type="submit" class="btn btn-danger w-100">Submit</button>
        </form>

        <div id="progressContainer" class="hidden mt-3">
            <div class="progress">
                <div id="progressBar" class="progress-bar progress-bar-striped bg-warning" style="width: 0%;"></div>
            </div>
            <p id="progressText" class="text-white">0%</p>
        </div>

        <div id="status" class="hidden alert mt-3"></div>
    </div>
</div>

<script>

function uploadFile(event) {
    event.preventDefault();
    let formData = new FormData(document.getElementById("uploadForm"));
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "emailsender.php", true);

    xhr.onloadstart = function () {
        document.getElementById("progressContainer").classList.remove("hidden");
        document.getElementById("progressBar").style.width = "0%";
        document.getElementById("progressText").textContent = "0%";
    };

    xhr.upload.onprogress = function (event) {
        if (event.lengthComputable) {
            let percentComplete = Math.round((event.loaded / event.total) * 100);
            document.getElementById("progressBar").style.width = percentComplete + "%";
            document.getElementById("progressText").textContent = percentComplete + "%";
        }
    };

    xhr.onload = function () {
        let response = xhr.responseText.trim();
        let statusDiv = document.getElementById("status");

        statusDiv.style.display = "block";
        statusDiv.innerHTML = response === "success" 
            ? "✅ <strong>File sent successfully!</strong>" 
            : "❌ " + response;

        statusDiv.className = response === "success" ? "alert alert-success" : "alert alert-danger";

        setTimeout(() => document.getElementById("progressContainer").classList.add("hidden"), 2000);
    };

    xhr.onerror = function () {
        let statusDiv = document.getElementById("status");
        statusDiv.textContent = "❌ Network error.";
        statusDiv.className = "alert alert-danger";
    };

    xhr.send(formData);
}
</script>
