<?php

class Participant extends Model {
    private $id;
    private $event_id;
    private $email;
    private $notes;
    private $confirmed;
    private $access_way;
    private $created_at;
    private $updated_at;

    // ACCESS WAY:
    //  - 0: Invitato tramite creazione evento.
    //  - 1: Accesso autonomo tramite link.

    public static $accessMethod = [
        'Invito tramite creazione',
        'Accesso tramite link pubblico'
    ];

    protected static $table = 'participants';
    protected static $requiredFields = ['event_id', 'email'];
    protected static $uniqueFields = [];

    public static function validateData($data) {
        $errors = static::checkRequiredFields($data);
              
        
        return $errors ?: static::checkDataUniqueness($data);
    }

    public static function prepareData($data, $new = false) {
        if ($data['email'] ?? false) $data['email'] = strtolower($data['email']);

        $now = date('Y-m-d H:i:s');
        $data['updated_at'] = "$now";

        if ($new) {
            $data['created_at'] = "$now";
            $data['confirmed'] = $data['confirmed'] ?? 0;
        }
        return $data;
    }
}