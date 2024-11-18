<?php
// Start the session
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Traffic Anomaly Detection</title>
    <style>
        /* Reset styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        /* Navbar styling */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #005f73;
            color: white;
        }
        .navbar .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .navbar nav a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
        }
        .navbar nav a:hover {
            text-decoration: underline;
        }
        /* Hero section */
        .hero {
            background-color: #94d2bd;
            color: #005f73;
            padding: 60px 20px;
            text-align: center;
        }
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .hero p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: auto;
            margin-bottom: 20px;
        }
        .hero button {
            background-color: #005f73;
            color: white;
            padding: 10px 20px;
            font-size: 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .hero button:hover {
            background-color: #0a9396;
        }
        /* About section */
        .about {
            padding: 40px 20px;
            text-align: center;
            background-color: #e9ecef;
        }
        .about h2 {
            font-size: 2rem;
            margin-bottom: 20px;
        }
        .about p {
            max-width: 600px;
            margin: auto;
        }
        /* Responsive grid */
        .features {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 40px 20px;
            justify-content: center;
        }
        .feature-item {
            flex: 1 1 300px;
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
        }
        .feature-item h3 {
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        /* Footer */
        .footer {
            padding: 20px;
            text-align: center;
            background-color: #005f73;
            color: white;
        }
        .footer p {
            margin: 0;
        }
        /* Responsive styles */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            .about h2, .feature-item h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <header class="navbar">
        <div class="logo">AnomalyNet</div>
        <nav>
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#features">Features</a>
            <a href="login.php">Login</a>
            <a href="signup.php">Signup</a>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <h1>Welcome to AnomalyNet</h1>
        <p>Detect and analyze network traffic anomalies to secure your digital environment. Stay protected with our advanced detection algorithms.</p>
        <button onclick="window.location.href='index.php'">Upload Files for Detection</button> <!-- Redirect button -->
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <h2>About Our Project</h2>
        <p>AnomalyNet is designed to detect and classify anomalies in network traffic in real-time. Our goal is to provide a robust solution for network administrators to identify and mitigate potential threats to ensure data security and network reliability.</p>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="feature-item">
            <h3>Real-time Analysis</h3>
            <p>Our system provides real-time traffic monitoring and anomaly detection to ensure proactive threat management.</p>
        </div>
        <div class="feature-item">
            <h3>Comprehensive Reporting</h3>
            <p>Get detailed reports on network activities and identified threats for informed decision-making.</p>
        </div>
        <div class="feature-item">
            <h3>User-Friendly Interface</h3>
            <p>Our intuitive design ensures that you can monitor and respond to network activities efficiently and effectively.</p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2024 AnomalyNet. All rights reserved.</p>
    </footer>

</body>
</html>
