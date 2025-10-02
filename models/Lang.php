<?php

class Lang extends Model 
{
    private $id;
    private $key;
    private $it;
    private $en;
    private $es;
    private $fr;
    private $de;

    protected static $table = 'langs';
    protected static $requiredFields = ['key_lang'];
    protected static $uniqueFields = ['key_lang'];

    public static $indexLabels = [
        'id' => '#',
        'key_lang' => 'Chiave',
        'it' => 'IT',
        'en' => 'EN',
        'es' => 'ES',
        'fr' => 'FR',
        'de' => 'DE'
    ];

    public static $languages = [
        "it" => [
            "label" => "Italiano",
            "flag" => "/public/langs/it.png",
            "enabled" => 1
        ],
        "en" => [
            "label" => "English",
            "flag" => "/public/langs/gb.png",
            "enabled" => 1,
        ],
        "es" => [
            "label" => "Español",
            "flag" => "/public/langs/es.png",
            "enabled" => 1
        ],
        "fr" => [
            "label" => "Francés",
            "flag" => "/public/langs/fr.png",
            "enabled" => 0
        ],
        "de" => [
            "label" => "Alemán",
            "flag" => "/public/langs/de.png",
            "enabled" => 0
        ],
    ];

    public static function validateData($data)
    {
        $errors = static::checkRequiredFields($data);
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

    public static function getText($text) 
    {
        $results = static::select([
            "columns" => "{$_SESSION['lang']}, it",
            "where" => sprintf("key_lang = '%s'", $text)
        ]);
        if (!$results) {
            static::create([
                [
                    'key_lang' => $text
                ]
            ]);
            return $text;
        }
        $result = $results[0];
        if (!$result[$_SESSION['lang']]) return $result['it'] ?: $text;
        return $result[$_SESSION['lang']];
        
    }

    public static function importCSV($filename) 
    {
        $file = fopen($filename, "r");
        
        $row = 1;
        $translations = static::select();
        
        while(($data = fgetcsv($file, 10000, ",")) !== false) {
            $values = array_map("trim", $data);
            
            if ($row == 1) {
                $headers = $values;
                
                foreach ($headers as $header) {
                    if (!in_array($header, array_keys(static::$indexLabels))) {
                        fclose($file);
                        return false;
                    }
                }
            } else {
                $createData = []; 
                
                foreach ($headers as $index => $header) {
                    if (isset($values[$index]) && !empty(trim($values[$index]))) {
                        $createData[$header] = $values[$index];
                    }
                }
                
                if (!isset($createData['key_lang']) || empty($createData['key_lang'])) {
                    continue;
                }
                
                $keyExists = false;
                foreach ($translations as $translation) {
                    if ($createData['key_lang'] === $translation['key_lang']) {
                        $keyExists = true;
                        break;
                    }
                }
                
                if ($keyExists) {
                    fclose($file);
                    return false;
                }
                
                $created = static::create([$createData]);
                if (!$created) {
                    fclose($file);
                    return false;
                }
            }
            $row++;
        }
        
        fclose($file);
        return true;
    }
}