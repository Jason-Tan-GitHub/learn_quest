<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

//Server settings
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'capitaledgeV@gmail.com';  // Your Gmail address
$mail->Password   = 'yupb oxyp logy sbrp';     // Your App Password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Use TLS
$mail->Port       = 587;  // Port for TLS

$mail->isHTML(true);

return $mail;
