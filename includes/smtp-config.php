<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getMailer()
{
    $mail = new PHPMailer(true);

    // SMTP CONFIG
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'vermaabhishek79326@gmail.com'; // 👈 apni email
    $mail->Password   = 'hpix jshy orab juet'; // 👈 Gmail App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Optional (better delivery)
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ];

    $mail->CharSet = 'UTF-8';

    return $mail;
}