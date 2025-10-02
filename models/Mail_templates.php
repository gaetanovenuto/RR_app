<?php
include __DIR__ . '/../vendor/autoload.php';

class Mail_templates extends Model {
    private $id;
    private $type;
    private $subject;
    private $body;
    private $enabled;
        
    protected static $table = 'mail_templates';
    protected static $requiredFields = ['type', 'subject', 'body'];
    protected static $uniqueFields = ['type'];
        
    public static $indexLabels = [
        'id' => '#',
        'type' => 'Nome',
        'subject' => 'Oggetto Email',
        'enabled' => 'Abilitato'
    ];

    public static $possibleTypes = [
        'confirmation_account' => 'Conferma registrazione',
        'reset_password' => 'Reset password',
        'password_changed' => 'Password cambiata correttamente',
        'reservation_invite' => 'Invito di partecipazione all\'evento',
        'reservation_start_alert' => 'Avviso inizio riunione',
        'reservation_changed' => 'Avviso modifica riunione',
        'confirmed_reservation' => 'Partecipazione all\'evento confermata',
        'reservation_update' => 'Evento aggiornato',
        'reservation_canceled' => 'Evento cancellato'
    ];

    public static $possibleTags = [
        'confirmation_account' => [
            'id' => 'ID dell\'utente',
            'username' => 'Username dell\'utente',
            'firstname' => 'Nome dell\'utente',
            'lastname' => 'Cognome dell\'utente',
            'email' => 'Email dell\'utente',
            'confirmation_url' => 'Link di conferma autogenerato per la conferma dell\'email e attivazione account.',
            'footer_disclaimer' => 'Orario di invio della email: orario',
        ], 
        'reset_password' => [
            'id' => 'ID dell\'utente',
            'username' => 'Username dell\'utente',
            'firstname' => 'Nome dell\'utente',
            'lastname' => 'Cognome dell\'utente',
            'email' => 'Email dell\'utente',
            'reset_password_url' => 'Link di reset password autogenerato per il reset della password',
            'footer_disclaimer' => 'Orario di invio della email: orario'
        ],
        'password_changed' => [
            'firstname' => 'Nome dell\'utente',
            'footer_disclaimer' => 'Orario di invio della email: orario'
        ],
        'reservation_invite' => [
            'reservation_name' => 'Nome della prenotazione',
            'reservation_creator' => 'Nome del creatore della prenotazione',
            'reservation_link' => 'Link di conferma autogenerato alla prenotazione',
            'reservation_start' => 'Orario di inizio della prenotazione',
            'reservation_end' => 'Orario di fine della prenotazione',
            'footer_disclaimer' => 'Orario di invio della email: orario',
        ], 
        'reservation_start_alert' => [
            'reservation_name' => 'Nome della prenotazione',
            'reservation_start' => 'Orario di inizio della prenotazione',
            'alert_time' => 'Tempo mancante all\'inizio della riunione',
            'footer_disclaimer' => 'Orario di invio della email: orario',
        ],
        'reservation_changed' => [
            'reservation_name' => 'Nome della prenotazione',
            'reservation_start' => 'Orario di inizio della prenotazione',
            'alert_time' => 'Tempo mancante all\'inizio della riunione',
            'footer_disclaimer' => 'Orario di invio della email: orario'
        ],
        'confirmed_reservation' => [
            'reservation_name' => 'Nome della prenotazione',
            'reservation_start' => 'Orario di inizio della prenotazione',
            'footer_disclaimer' => 'Orario di invio della email: orario'
        ],
        'reservation_update' => [
            'reservation_name' => 'Nome della prenotazione',
            'reservation_creator' => 'Nome del creatore della prenotazione',
            'reservation_link' => 'Link di conferma autogenerato alla prenotazione',
            'reservation_start' => 'Orario di inizio della prenotazione',
            'reservation_end' => 'Orario di fine della prenotazione',
            'footer_disclaimer' => 'Orario di invio della email: orario',
        ],
        'reservation_canceled' => [
            'reservation_name' => 'Nome della prenotazione',
            'footer_disclaimer' => 'Orario di invio della email: orario'
        ]
    ];
    

    public static function validateData($data) {
        $errors = static::checkRequiredFields($data);

        if ($data['subject'] ?? false) {
            if (strlen(trim($data['subject'])) < 3) {
                $errors['subject'] = "L'oggetto deve essere lungo almeno 3 caratteri.";
            }
        }

        if ($data['body'] ?? false) {
            if (strlen(trim($data['body'])) < 10) {
                $errors['body'] = "Il corpo del messaggio deve essere lungo almeno 10 caratteri.";
            }
        }

        return $errors ?: static::checkDataUniqueness($data);
    }

    public static function prepareData($data, $new = false) {
        if ($new) {
            $data['enabled'] = 1;
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $data;
    }

    private static function replacePlaceholders($body, $data, $text) {
        if (preg_match_all('/\{(\w+)\}/', $body, $matches)) {
            foreach ($matches[1] as $placeholder) {
                if (array_key_exists($placeholder, $text)) {
                    $value = $text[$placeholder];
                    $body = str_replace("{" . $placeholder . "}", $value, $body);
                }
            }
        }
        return $body;
    }

    public static function prepareEmailData($data, $type, $text) {
        $templates = static::select([
            "where" => "type = '" . $type . "' AND enabled = 1",
            "limit" => 1
        ]);
        if (empty($templates)) {
            error_log("Template email non trovato per questo tipo: " . $type);
            return false;
        }
        
        $template = $templates[0];
        $subject = $template['subject'];
        $body = $template['body'];
        $subject = static::replacePlaceholders($subject, $data, $text);
        $body = static::replacePlaceholders($body, $data, $text);
        return [$subject, $body];
    } 
}