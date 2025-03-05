<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Homepage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            text-align: center;
            background: linear-gradient(135deg, #c25b18, #1d2b46);
            color: white;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        .hero {
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            padding: 20px;
        }
        .logo {
            width: 550px;
            height: auto;
            margin-bottom: 20px;
        }
        .title {
            font-size: 3rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .subtitle {
            font-size: 1.5rem;
            margin-top: 10px;
            max-width: 80%;
        }
        .access-section {
            margin-top: 30px;
            text-align: center;
        }
        .access-btn {
            background: #ff6b6b;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .access-btn:hover {
            background: #ff4757;
        }
        .credits {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            font-size: 1rem;
            opacity: 0.8;
        }
        .credits a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        .credits a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header class="hero">
        <img src="img/logo.png" alt="Logo" class="logo">
        <h1 class="title">Welcome to Our System</h1>
        <p class="subtitle">Revolutionizing the way you manage insurance cards</p>
        <section class="access-section">
            <p>Access the admin page with an access code to continue.</p>
            <button class="access-btn" onclick="window.location.href='homepagev2.php'">Enter Access Code</button>
        </section>
    </header>
    
    <footer class="credits">
        <p>Developed by <a href="#">Team STI</a></p>
    </footer>
    
    <script>
        gsap.from(".logo", { duration: 1.5, y: -50, opacity: 0, ease: "bounce" });
        gsap.from(".title", { duration: 1.5, y: -50, opacity: 0, ease: "bounce" });
        gsap.from(".subtitle", { duration: 1.5, delay: 0.5, y: 50, opacity: 0 });
        gsap.from(".access-section", { duration: 1, opacity: 0, y: 30 });
    </script>
</body>
</html>
