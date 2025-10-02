<?php

class Log extends Model {
    private $id;
    private $user_id;
    private $action_type;
    private $ip_address;
    private $details;
    private $created_at;

    protected static $table = 'logs';

    protected static $requiredFields = ['action_type', 'created_at', 'ip_address'];

    public static $uniqueFields = [];

    public static $indexLabels = [
        "id" => "#",
        "user_id" => "ID Utente",
        "firstname" => "Nome",
        "lastname" => "Cognome",
        "action_type" => "Azione",
        "ip_address" => "Indirizzo IP",
        "created_at" => "Data",
        "details" => "Dettagli",
        "level" => "Grado"
    ];

    public static function validateData($data) {
        $errors = static::checkRequiredFields($data);
        
        return $errors ?: static::checkDataUniqueness($data);
    }

    public static function prepareData($data, $new = false) {
        
        if ($new) $data['created_at'] = date('Y-m-d H:i:s');
        return $data;
    }
}