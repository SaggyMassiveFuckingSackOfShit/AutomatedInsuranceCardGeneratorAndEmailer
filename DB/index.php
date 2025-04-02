<?php
require_once 'ExcelReader.php';
require_once 'DatabaseManager.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["fileToUpload"])) {
    $target_dir = "uploads/";
    $upload_message = "";
    
    if (!file_exists($target_dir) && !mkdir($target_dir, 0777, true)) {
        $upload_message = "Error: Could not create upload directory.";
    } elseif (!is_writable($target_dir)) {
        $upload_message = "Error: Upload directory is not writable.";
    } else {
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            try {
                $excelReader = new ExcelReader($target_file);
                $data = $excelReader->readFile();
                
                $dbManager = new DatabaseManager('localhost', 'root', '', 'TESTING', 'ENTRIES');
                $dbManager->insertExcelData($data);
                $dbManager->close();
                
                $upload_message = "File uploaded and data inserted successfully. Processed " . count($data) . " rows.";
            } catch (Exception $e) {
                $upload_message = "Error processing file: " . $e->getMessage();
            }
        } else {
            $upload_message = "Error moving uploaded file.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel File Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .error {
            color: red;
            margin-top: 10px;
        }
        #responseMessage {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>File Upload</h1>
        <form id="uploadForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="excelFile">Upload Excel File:</label>
                <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".xlsx,.xls">
                <small class="form-text text-muted">Selected file: <span id="fileName">No file selected</span></small>
            </div>
            <div id="responseMessage"></div>
            <button type="submit" class="btn btn-primary">Generate Cards</button>
        </form>
        <?php if (isset($upload_message)): ?>
            <p class="<?php echo strpos($upload_message, 'Error') !== false ? 'error' : ''; ?>"><?php echo $upload_message; ?></p>
        <?php endif; ?>
    </div>
    <script src="script.js"></script>
</body>
</html> 