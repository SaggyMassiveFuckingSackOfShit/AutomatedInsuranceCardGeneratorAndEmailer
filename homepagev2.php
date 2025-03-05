<?php
session_start();

// One-time first access code (fixed)
$first_access_code = "asdasd23323212845";

// Set access timeout (8 hours)
$access_timeout = 8 * 60 * 60;

// Default code to use after session timeout
$default_code = "bewiser123";

// Function to generate a random 6-digit access code
function generateAccessCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// File to store the generated codes
$codeFile = 'access_codes.txt';

// Function to load the codes from the file
function loadCodes($codeFile) {
    if (file_exists($codeFile)) {
        $data = file_get_contents($codeFile);
        return json_decode($data, true);
    }
    return null;
}

// Function to save the codes to the file
function saveCodes($codeFile, $codes) {
    file_put_contents($codeFile, json_encode($codes));
}

// Load the existing codes from the file (if any)
$codes = loadCodes($codeFile);

// If there are no codes in the file, initialize them
if (!$codes) {
    $codes = [
        'current_code' => $first_access_code,
        'next_code' => generateAccessCode(),
        'access_time' => time(),
    ];
    saveCodes($codeFile, $codes);
}

// Handle form submission for access code
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['access_code'])) {
    if ($_POST['access_code'] === $codes['current_code']) {
        $_SESSION['access_granted'] = true;
        $codes['access_time'] = time();
        
        // Update access codes for next session
        $codes['current_code'] = $codes['next_code']; // Use next code
        $codes['next_code'] = generateAccessCode(); // Generate new next code
        
        // Save updated codes to file
        saveCodes($codeFile, $codes);
    } else {
        $error = "Invalid access code.";
    }
}

// Check if session has expired
if (isset($codes['access_time']) && (time() - $codes['access_time']) > $access_timeout) {
    // Session expired, reset session and save the default code
    session_unset();
    session_destroy();

    // Save default code to the file after timeout
    $codes['current_code'] = $default_code;
    $codes['next_code'] = generateAccessCode(); // Generate new next code
    $codes['access_time'] = time(); // Reset the access time
    
    saveCodes($codeFile, $codes);
    
    header("Location: index.php"); // Redirect user to re-enter code
    exit();
}

$access_granted = $_SESSION['access_granted'] ?? false;
?>

<!-- HTML code continues here... -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/css/adminlte.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>

    <script>
$(document).ready(function() {
    $("#generateForm").submit(function(event) {
        event.preventDefault(); // Prevent full page refresh

        let formData = new FormData(this);

        $("#progress").show(); // Show progress bar
        $("#progress-bar-fill").css("width", "0%"); // Reset progress bar
        $("#progress-text").text("0%");

        $.ajax({
            url: "generator.php", // Update with the correct backend script
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                let xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        let percentComplete = (evt.loaded / evt.total) * 100;
                        $("#progress-bar-fill").css("width", percentComplete + "%");
                        $("#progress-text").text(Math.round(percentComplete) + "%");
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                $("#progress-text").text("Completed!");
                alert("Generation Successful!");
            },
            error: function() {
                alert("Error generating card.");
            }
        });
    });
});

        function updateTimer() {
            let timeout = <?php echo ($_SESSION['access_time'] + $access_timeout) - time(); ?>;
            setInterval(() => {
                let hours = Math.floor(timeout / 3600);
                let minutes = Math.floor((timeout % 3600) / 60);
                let seconds = timeout % 60;
                document.getElementById("timer").innerText = `${hours}h ${minutes}m ${seconds}s`;
                timeout--;
                if (timeout < 0) {
                    location.reload();
                }
            }, 1000);
        }
        window.onload = updateTimer;

        function loadPage(page, event) {
    event.preventDefault(); // Prevent page reload
    $(".nav-link").removeClass("active"); // Remove active class sa ibang items
    event.target.closest("a").classList.add("active"); // Highlight selected menu

    $("#content-area").fadeOut(200, function() { // Smooth fade effect
        $(this).load(page, function() {
            $(this).fadeIn(200);
        });
    });
}

    </script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            text-align: center;
            background: linear-gradient(135deg, #c25b18, #1d2b46);
            color: white;
            margin: 0;
            overflow-x: hidden;
            height: 100vh;
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
<body class="hold-transition sidebar-mini">
    <?php if (!$access_granted): ?>
        <div class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-75 z-50">
            <form method="post" class="p-6 bg-gray-800 text-black rounded-lg shadow-lg text-center">
                <h2 class="text-2xl font-bold mb-4">Enter Access Code</h2>
                <?php if (isset($error)) echo "<p class='text-red-500'>$error</p>"; ?>
                <input type="password" name="access_code" class="px-4 py-2 border rounded mb-4 w-full text-center bg-gray-700 text-black" required>
                <button type="submit" class="px-6 py-2 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition">Submit</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="wrapper <?php echo $access_granted ? '' : 'blurred'; ?>">

        <nav class="main-header navbar navbar-expand navbar-dark navbar-light bg-dark">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="index.php" class="nav-link">Home</a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <span class="nav-link">Session Expires In: <span id="timer"></span></span>
                </li>
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="homepagev2.php" class="brand-link">
                <span class="brand-text font-weight-light">Admin Dashboard</span>
            </a>
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="homepagev2.php" class="nav-link" onclick="loadPage('dashboard.php', event)">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="generator.php" class="nav-link" target="_blank">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Card Generator</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="emailsender.php" class="nav-link" onclick="loadPage('emailsender.php', event)">
                                <i class="nav-icon fas fa-envelope"></i>
                                <p>Email Sender</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <section class="content pt-3">
                <div class="container-fluid">
                    <div id="content-area" class="p-4">
                        <h3>Welcome to the Admin Dashboard</h3>
                        <p>Select an option from the sidebar to get started.</p>
                    </div>
                </div>
            </section>
        </div>

        <footer class="text-center p-3">
            <p>© 2025 Admin Dashboard | Created by STI</p>
        </footer>
    </div>
</body>
</html>
