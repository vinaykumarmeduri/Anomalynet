<?php
session_start(); // Start the session
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Database connection
$conn = new mysqli('localhost', 'root', '', 'traffic_anomaly_detection');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
// Inside your existing if block where you check for file uploads
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['pcap_file'])) {
        $filename = $_FILES['pcap_file']['name'];
        $target_file = 'uploads/' . basename($filename);
        
        if (move_uploaded_file($_FILES['pcap_file']['tmp_name'], $target_file)) {
            // Save the file info in the database
            $stmt = $conn->prepare("INSERT INTO uploads (filename) VALUES (?)");
            $stmt->bind_param("s", $filename);
            $stmt->execute();
            $stmt->close();
            
            // Analyze the PCAP file
            $analysis_result = analyze_pcap($target_file);
            
            // Save the analysis result in the database
            $stmt = $conn->prepare("UPDATE uploads SET result=? WHERE filename=?");
            $stmt->bind_param("ss", $analysis_result['result'], $filename);
            $stmt->execute();
            $stmt->close();
            
            // Save the analysis report as a PDF
            $report_filename = 'uploads/' . pathinfo($filename, PATHINFO_FILENAME) . '_report.pdf'; // Save as PDF
            generate_pdf_report($analysis_result, $report_filename);
            
            // Generate the graph from the analysis results
            $graph_filename = 'uploads/graph.png';
            generate_graph($analysis_result['packet_counts'], $graph_filename);
            
            // Send an email if anomalies are detected
            $email_status = ""; // Initialize an empty variable
            if (!empty($analysis_result['anomalies'])) {
                $email_status = send_email_alert($analysis_result, $report_filename);
            }

            // Store the result in the session to display after redirect
            $_SESSION['upload_result'] = [
                'message' => "File uploaded and processed successfully!",
                'result' => nl2br(htmlspecialchars($analysis_result['result'])),
                'graph' => $graph_filename,
                'report' => $report_filename,
                'email_status' => $email_status // Add email status to session
            ];

            // Redirect to the same page to avoid resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['upload_result'] = ['message' => "Error uploading file."];
        }
    }
}


// Display any results if available
$upload_result = isset($_SESSION['upload_result']) ? $_SESSION['upload_result'] : null;
unset($_SESSION['upload_result']); // Clear the session variable after use

// Function to analyze the PCAP file and generate the result and report
function analyze_pcap($file_path) {
    // Command to analyze the PCAP file using tshark
    $tshark_path = '"C:\\Program Files\\Wireshark\\tshark.exe"'; // Properly quote the path
    $command = "$tshark_path -r " . escapeshellarg($file_path) . " -Y \"http or tcp or udp or icmp\" -T fields -e ip.src -e ip.dst -e frame.len 2>&1"; // Redirect stderr to stdout

    // Execute the command and capture the output
    $output = shell_exec($command);
    
    if ($output === null) {
        return ["result" => "Error analyzing the file.", "report" => "No data available.", "packet_counts" => [], "anomalies" => []];
    }

    // Check if the output is empty or contains errors
    if (empty(trim($output))) {
        return ["result" => "No output returned from TShark.", "report" => "No data available.", "packet_counts" => [], "anomalies" => []];
    }

    // Split the output into lines (each representing a packet)
    $lines = explode("\n", trim($output));
    $packet_count_per_ip = []; // To count packets from each IP
    $anomalies = []; // Store anomalies and their mitigations

    foreach ($lines as $line) {
        if (!empty($line)) {
            $parts = explode("\t", $line);

            if (count($parts) >= 3) {
                $src_ip = $parts[0];
                $dst_ip = $parts[1];
                $frame_len = $parts[2];

                // Count the number of packets per IP
                if (!isset($packet_count_per_ip[$src_ip])) {
                    $packet_count_per_ip[$src_ip] = 0;
                }
                $packet_count_per_ip[$src_ip]++;

                // Example anomaly detection logic
                if ((int)$frame_len > 1500) {
                    $anomalies[] = [
                        "message" => "Large packet detected: $frame_len bytes from $src_ip to $dst_ip",
                        "mitigation" => "Investigate the source of large packets. Consider implementing packet filtering."
                    ];
                }
            }
        }
    }

    // Check for any IP sending an unusually high number of packets
    foreach ($packet_count_per_ip as $ip => $count) {
        if ($count > 100) { // Example threshold for too many packets from one IP
            $anomalies[] = [
                "message" => "Potential DoS attack: $ip sent $count packets",
                "mitigation" => "Implement rate limiting and investigate the source IP."
            ];
        }
    }

    // Generate the analysis report
    $report = "Analysis Report:\n";
    $report .= "Total packets analyzed: " . count($lines) . "\n";
    $report .= "Packet counts per IP:\n" . print_r($packet_count_per_ip, true) . "\n";
    
    // Determine if anomalies were detected
    if (!empty($anomalies)) {
        $report .= "Anomalies Detected: " . count($anomalies) . "\n\n"; // Include the count of anomalies
        foreach ($anomalies as $anomaly) {
            $report .= $anomaly['message'] . "\n";
            $report .= "Mitigation: " . $anomaly['mitigation'] . "\n\n";
        }
    } else {
        $report .= "No anomalies detected.\n";
    }
    
    // Consolidate the result message
    $result_message = !empty($anomalies) ? "Anomalies detected: " . count($anomalies) : "No anomalies detected.";
    
    // Return the structured data
    return [
        "result" => "Analysis Result:\n" . $result_message,
        "report" => $report,
        "packet_counts" => $packet_count_per_ip,
        "anomalies" => $anomalies
        
    ];
    
}    

// Function to generate the PDF report
function generate_pdf_report($report_data, $report_filename) {
    require('libs/fpdf.php'); // Include FPDF library

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Traffic Analysis Report', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(0, 10, $report_data['report']); // Add the report content

    // Save the PDF to file
    $pdf->Output('F', $report_filename); // Save PDF file
}

// Function to generate a graph based on the packet counts
function generate_graph($packet_counts, $graph_filename) {
    // Generate a CSV data for the graph
    $csv_data = "IP Address,Packet Count\n";
    foreach ($packet_counts as $ip => $count) {
        $csv_data .= "$ip,$count\n";
    }
    $csv_temp_file = 'uploads/packet_counts.csv';
    file_put_contents($csv_temp_file, $csv_data);
    
    // Python script to generate the graph
    $python_script = "C:\\xampp\\htdocs\\traffic_anomaly_detection\\generate_graph.py"; // Update this path
    $command = "python $python_script " . escapeshellarg($csv_temp_file) . " " . escapeshellarg($graph_filename);
    shell_exec($command);
}

// Function to send email alert if anomalies are detected
function send_email_alert($analysis_result, $report_filename) {
    // Load PHPMailer classes
   

    // Create a new PHPMailer instance
    $mail = new PHPMailer();

    // Server settings
    $mail->isSMTP();                                          // Set mailer to use SMTP
    $mail->Host = 'smtp.gmail.com';                          // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                                   // Enable SMTP authentication
    $mail->Username = 'anomalynet19@gmail.com';              // Your Gmail address
    $mail->Password = 'ttgw cszg fhqx kaki';                 // Your App Password
    $mail->SMTPSecure = 'tls';                               // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587;                                       // TCP port to connect to

    // Recipients
    $mail->setFrom('anomalynet19@gmail.com', 'Anomalynet'); // Sender's email and name
    $mail->addAddress($_SESSION['email']);  // Add a recipient

    // Email subject and message
    $mail->isHTML(true);                                     // Set email format to HTML
    $mail->Subject = "Anomalies Detected in Uploaded PCAP File";
    $mail->Body    = "Dear User,<br><br>Anomalies were detected in your uploaded PCAP file.<br><br>Please find the detailed report attached.<br><br>Regards,<br>Traffic Anomaly Detection Team";

    // Attach the report
    $mail->addAttachment($report_filename); // Attach the PDF report

    // Attempt to send the email
    if ($mail->send()) {
        return "Email sent successfully.";
    } else {
        return "Email could not be sent. Mailer Error: " . $mail->ErrorInfo;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Traffic Anomaly Detection</title>
    <style>
        body{
        font-family: Arial, sans-serif;
        background-color: #e3f2fd;
        margin: 0;
        padding: 0;
    }
    header {
        background-color: #1976d2;
        color: white;
        padding: 20px 0;
        text-align: center;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }
    header h1 {
        font-size: 28px;
        font-weight: bold;
        margin: 0;
    }
    .container {
        width: 60%;
        margin: 30px auto 0;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
    }
    .container {
        width: 60%;
        margin: 0 auto;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
        margin-top: 70px;
    }
    h2 {
        color: #1976d2;
        text-align: center;
        font-weight: bold;
        font-size: 24px;
    }
    form {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 30px;
    }
    input[type="file"] {
        padding: 10px;
        font-size: 16px;
        color: #1976d2;
        border: 2px dashed #90caf9;
        border-radius: 8px;
        margin-bottom: 15px;
        background-color: #f1f8e9;
        cursor: pointer;
    }
    input[type="file"]:hover {
        border-color: #1976d2;
    }
    button {
        background-color: #1976d2;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }
    button:hover {
        background-color: #1565c0;
    }
    p {
        text-align: center;
        font-size: 18px;
        color: #424242;
    }
    .result-message {
        text-align: center;
        font-size: 20px;
        color: #2e7d32;
        font-weight: bold;
        margin: 20px 0;
    }
    img {
        display: block;
        margin: 20px auto;
        max-width: 100%;
        border: 2px solid #90caf9;
        border-radius: 8px;
    }
    .download-link {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    .download-link a {
        font-size: 18px;
        color: #1976d2;
        text-decoration: none;
        background-color: #e3f2fd;
        padding: 10px 20px;
        border-radius: 8px;
        transition: color 0.3s ease;
    }
    .download-link a:hover {
        color: #1565c0;
        text-decoration: underline;
    }
</style>
</head>
<body>
    <header>
        <h1>Network Traffic Anomaly Detection</h1>
    </header>

    <div class="container">
        <h2>Upload PCAP File for Anomaly Detection</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="file" name="pcap_file" accept=".pcap">
            <button type="submit">Upload and Analyze</button>
        </form>

        <?php if ($upload_result): ?>
            <div class="result-message"><?php echo $upload_result['message']; ?></div>
            <p><?php echo $upload_result['result']; ?></p>
           
           
            <?php if (isset($upload_result['graph'])): ?>
                <img src="<?php echo $upload_result['graph']; ?>" alt="Packet Count Graph">
            <?php endif; ?>
            <?php if (isset($upload_result['report'])): ?>
                <div class="download-link">
                    <a href="<?php echo $upload_result['report']; ?>" download="analysis_report.pdf">Download Analysis Report</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>


