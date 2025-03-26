<?php
require_once 'ExcelReader.php';
require_once 'DatabaseManager.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES["fileToUpload"])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            try {
                // Read Excel file
                $excelReader = new ExcelReader($target_file);
                $data = $excelReader->readFile();
                
                // Connect to database and insert data
                $dbManager = new DatabaseManager(
                    'localhost',  // host
                    'root',      // username
                    '',          // password
                    'TESTING',   // database name
                    'ENTRIES'    // table name
                );
                
                $dbManager->insertExcelData($data);
                $dbManager->close();
                
                $upload_message = "File uploaded and data inserted successfully. Processed " . count($data) . " rows.";
            } catch (Exception $e) {
                $upload_message = "Error processing file: " . $e->getMessage();
            }
        } else {
            $upload_message = "Error uploading file.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>File Upload</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            max-width: 800px;
            overflow-x: auto;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>File Upload</h1>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
        <input type="file" name="fileToUpload" accept=".xlsx,.xls">
        <input type="submit" value="Generate">
    </form>
    <?php if (isset($upload_message)): ?>
        <p><?php echo $upload_message; ?></p>
    <?php endif; ?>
    <?php if (isset($data)): ?>
        <pre><?php print_r($data); ?></pre>
    <?php endif; ?>
</body>
</html> 