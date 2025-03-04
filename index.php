<?php require 'generator.php'; ?>
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
            margin: 25px;
            overflow-x: hidden;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: rgba(255, 255, 255, 0.15);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            animation: fadeIn 1s ease-in-out;
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
        }
        .btn-danger {
            background: #1d2b46;
            color: white;
        }
        .btn:hover {
            transform: scale(1.1);
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
        .progress-bar {
            width: 100%;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-bar-fill {
            width: 0%;
            height: 10px;
            background: #c25b18;
            transition: width 1s ease-in-out;
        }   
        .progress-text {
            margin-top: 5px;
            font-weight: bold;
            color: white;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .drop-zone {
            border: 2px dashed #fff;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            border-radius: 10px;
            margin: 20px 0;
            transition: background 0.3s;
        }
        .drop-zone:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .file-input {
            display: none;
        }
    </style>
<script>
    function startProgressBar() {
        document.getElementById('progress').style.display = 'block';
        let progressBar = document.getElementById('progress-bar-fill');
        let progressText = document.getElementById('progress-text');

        // Reset UI before starting
        progressBar.style.width = '0%';
        progressText.innerText = '0%';

        function fetchProgress() {
            fetch('progress.php')
                .then(response => response.json())
                .then(data => {
                    let progress = data.progress;
                    let progressBar = document.getElementById('progress-bar-fill');
                    let progressText = document.getElementById('progress-text');

                    progressBar.style.width = progress + '%';
                    progressText.innerText = progress + '%';

                    if (progress < 100) {
                        setTimeout(fetchProgress, 1000);
                    } else {
                    setTimeout(() => {
                        progressBar.style.width = '100%';
                        progressText.innerText = 'Completed!';
                    }, 1000);  // Small delay to ensure the UI updates
                    }
                })
                .catch(error => console.error('Error fetching progress:', error));
        }

        fetchProgress();
    }

    function handleFileSelect(event) {
            event.preventDefault();
            document.getElementById("drop-area").classList.remove("hover");

            let files = event.dataTransfer.files; // Get dropped files

            if (files.length === 0) {
                alert("No valid file detected.");
                return;
            }

            let file = files[0]; // Handle the first file only
            console.log("Dropped file:", file.name, file.type);

            // Ensure it's a valid file (not a web URL or shortcut)
            if (!file.type || file.size === 0) {
                alert("Invalid file. Please drag a real file, not a shortcut or web link.");
                return;
            }

            let formData = new FormData();
            formData.append("file", file);

            fetch("upload.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(data => alert(data)) // Display server response
            .catch(error => console.error("Error:", error));
        }

        document.addEventListener("DOMContentLoaded", function() {
            const uploadButton = document.getElementById('drop-area');
            const fileInput = document.getElementById('fileInput');
            const fileNameDisplay = document.getElementById('fileName');

            uploadButton.addEventListener('click', function() {
                fileInput.click();
            });

            fileInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    fileNameDisplay.textContent = 'Selected file: ' + file.name;
                }
            });
        });

    
</script>

</head>
    <body>
        <img src="img/logo.png" alt="Company Logo" class="logo">
        <div class="container">
            <h1>Insurance Card Generator</h1>
            <p>Upload an Excel file and generate insurance cards.</p>
            
            <input type="file" id="fileInput" style="display: none;">

            <form method="post" enctype="multipart/form-data" onsubmit="startProgressBar(); return validateFile(document.getElementById('excelFile').files[0])">
                <div class="drop-zone" id = "drop-area" ondrop="handleFileSelect(event)" ondragover="event.preventDefault()">
                    <p>Drag & Drop Excel file here or click to upload</p>
                    <input type="file" name="excelFile" id="excelFile" class="file-input" accept=".xls,.xlsx" onchange="handleFileSelect(event)">
                    <p id="fileName">No file selected</p>
                </div>
                <button type="submit" name="generate" class="btn btn-primary">
                    <i class="fa fa-id-card"></i> Generate Card
                </button>
                <button type="reset" class="btn btn-danger" onclick="document.getElementById('fileName').innerText='No file selected'; document.getElementById('progress').style.display='none';">
                    <i class="fa fa-redo"></i> Reset
                </button>
            </form>
            
            <div id="progress" class="progress">
                <p class="loading"><i class="fa fa-spinner fa-spin"></i> Generating card, please wait...</p>
                <div class="progress-bar">
                    <div id="progress-bar-fill" class="progress-bar-fill"></div>
                </div>
                <p id="progress-text" class="progress-text">0%</p>
            </div>
        </div>
    </body>
</html>