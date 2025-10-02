<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail extends Model 
{
    private $id;
    private $recipient;
    private $subject;
    private $state;
    private $error;
    private $created_at;
    private $updated_at;
    
    protected static $table = "mails";
    protected static $requiredFields = ['recipient', 'subject'];
    protected static $uniqueFields = [];

    public static $indexLabels = [
        'id' => '#',
        'recipient' => 'Destinatario',
        'subject' => 'Oggetto',
        'state' => 'Stato',
        'error' => 'Errore'
    ];

    public static function send($recipient, $subject, $body, $attachment = null) 
    {
        $state = 0;
        $error = null;
        
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_SERVER'];
            $mail->Port = $_ENV['MAIL_PORT'];
            
            if ($attachment) {
                $mail->addAttachment($attachment);
            }
            $mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME'] ?? $_ENV['MAIL_FROM']);
            $mail->addAddress($recipient);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $result = $mail->send();
            
            $state = 1;
            $error = '-';
            
        } catch (Exception $e) {
            $state = 0;
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $state = 0;
            $error = 'Errore generico: ' . $e->getMessage();
        }

        $logMailData = [
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'state' => $state,
            'error' => $error
        ];
        Mail::create([$logMailData]);
        if ($logMailData['state'] <= 0) {
            return [
                'success' => 0,
                'state' => $logMailData['state'],
                'message' => 'Attenzione: si è verificato un errore, riprova tra qualche minuto. <br>Se il problema persiste, contatta il supporto.'
            ];
        } else {
            return [
                'success' => 1,
                'state' => $logMailData['state'],
                'message' => 'Email inviata correttamente'
            ];
        }
    }

    public static function validateData($data) 
    {
        $errors = static::checkRequiredFields($data);

        if ($data['recipient'] ?? false) {
            if (strlen($data['recipient']) < 3) {
                $errors['recipient'] = "Il destinatario deve essere lungo almeno 3 caratteri";
            }
            
            if (!filter_var($data['recipient'], FILTER_VALIDATE_EMAIL)) {
                $errors['recipient'] = "Il destinatario deve essere un'email valida";
            }
        }

        if ($data['subject'] ?? false) {
            if (strlen(trim($data['subject'])) < 3) {
                $errors['subject'] = "L'oggetto deve essere lungo almeno 3 caratteri.";
            }
        }

        return $errors ?: static::checkDataUniqueness($data);
    }

    public static function prepareData($data, $new = false) 
    {
        if ($new) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $data;
    }
}