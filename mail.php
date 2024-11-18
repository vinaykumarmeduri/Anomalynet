<?php
// Load PHPMailer classes
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Create a new PHPMailer instance
$mail = new PHPMailer();

// Server settings
$mail->isSMTP();                                          // Set mailer to use SMTP
$mail->Host = 'smtp.gmail.com';                          // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                                   // Enable SMTP authentication
$mail->Username = 'anomalynet19@gmail.com ';             // Your Gmail address
$mail->Password = 'ttgw cszg fhqx kaki';                   // Your App Password
$mail->SMTPSecure = 'tls';                               // Enable TLS encryption, `ssl` also accepted
$mail->Port = 587;                                       // TCP port to connect to

// Recipients
$mail->setFrom('anomalynet19@gmail.com', 'Anomalynet');    // Sender's email and name
$mail->addAddress('vinaykumarmeduri150@gmail.com', 'Vinay'); // Add a recipient

// Content
$mail->isHTML(true);                                     // Set email format to HTML
$mail->Subject = 'Test Email from PHPMailer';
$mail->Body    = 'This is a test email sent using PHPMailer with Gmail App Password.';
$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

// Send the email
if ($mail->send()) {
    echo 'Email sent successfully.';
} else {
    echo 'Email could not be sent. Mailer Error: ' . $mail->ErrorInfo;
}
?>
