<?php

declare(strict_types=1);

namespace App;

use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public function __construct(private string $host, private int $port, private string $username, private string $password) {}

    public function getMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->SMTPAuth = true;

        $mail->Host = $this->host;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $this->port;
        $mail->Username = $this->username;
        $mail->Password = $this->password;

        $mail->isHTML(true);

        return $mail;
    }
}
