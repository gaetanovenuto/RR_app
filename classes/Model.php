<?php 
require_once 'Database.php';
class Model {

    // DEBUG: settando questa variabile a true, verrà stampata la query SQL nell'HTML e nella risposta server. 
    // N.B. Le query falliscono se si aspettano un JSON perché si ritrovano la query davanti.
    public static $echo_query = false;
    
    // ******************  CREATE ******************

    public static function create($data) {
        // FUNZIONE CREATE GENERICA: 
        // $data si aspetta un ARRAY DI ARRAY: Viene settata $preparedData come array vuota e successivamente si cicla PER OGNI SET DI OGGETTO CREATO.
        // Esempio: $data = [[Alessio, Vietri, 35], [Luca, Bianchi, 46]], $singleData = [Alessio, Vietri, 35]. 
        // Se viene passato un singolo utente come singolo array (Es. $data = [Alessio, Vietri, 35]), i controlli validateData e prepareData falliscono, in quanto si aspettano un array di array!
        // $validationErrors riporta gli errori se falliscono gli errori di validazione. In seguito viene eseguita la query.
        $preparedData = [];
        foreach ($data as $singleData) {
            $validationErrors = static::validateData($singleData);
            $preparedData[] = static::prepareData($singleData, true);
        }
        if (!empty($validationErrors)) return $validationErrors;
        if (self::$echo_query)
            Database::$echo_query = true;
        
        return Database::insert(static::$table, $preparedData);
    }

    // ****************** READ ****************** 

    public static function select($params = []) {
        // FUNZIONE SELECT GENERICA:
        // $params DEVE essere un ARRAY di parametri -> vedi classes/Database.php per i parametri accettati.
        if (!is_array($params)) $params = []; 

        if (self::$echo_query)
            Database::$echo_query = true;
        return Database::select(static::$table, $params);
    }

    // ****************** UPDATE ****************** 

    public static function update($data, $where = "") {
        // FUNZIONE UPDATE GENERICA:
        // Si aspetta due parametri: $data è l'ARRAY DI VALORI DA AGGIORNARE, $where è LA CONDIZIONE (Solitamente id =).
        
        $validationErrors = static::validateData($data);
        $preparedData = static::prepareData($data, false);
        if (!empty($validationErrors)) return $validationErrors;
        if (self::$echo_query)
            Database::$echo_query = true;
        return Database::update(static::$table, 
            $preparedData, 
            [
                "where" => $where
            ]
        );
    }

    // ****************** DELETE ****************** 

    public static function delete($where) {
        // FUNZIONE DELETE GENERICA:
        // Si aspetta solo la condizione dove eliminare la riga (Solitamente id =).
        if (!$where) false;
        if (self::$echo_query)
            Database::$echo_query = true;
        return Database::delete(static::$table, $where);
    }

    // ****************** FUNCTIONS ****************** 

    protected static function checkRequiredFields($data) {
        // FUNZIONE DI CONTROLLO CAMPO OBBLIGATORIO:
        // $data è l'array di campi obbligatori (Da settare nella classe es. User, Room). Se non è array si rompe. Riporta gli errori.
        if (!is_array($data)) $data = [$data];
        $errors = [];
        foreach ($data as $key => $value) {
            if (in_array($key, static::$requiredFields) && !$value) {
                $errors[$key] = 'Il campo è obbligatorio.';
            }
        }

        return $errors;
    }

    protected static function checkDataUniqueness($data) {
        // FUNZIONE DI CONTROLLO VALORE UNICO:
        // $data è l'array di campi per il quale il valore deve essere UNICO per UTENTE. Da settare i campi unici nella classe (Es. User, Room).
        if (!static::$uniqueFields || !isset($data['id']))
            return [];

        $where = '';
        foreach (static::$uniqueFields as $field) {
            
            if (!($data[$field] ?? false)) continue;
            $where .= $where ? " OR " : "";
            $where .= sprintf("%s = '%s'", $field, $data[$field]);
        }
        

        $where = $where ? "($where) AND " : ''; 
        
        $where .= "id != " . $data['id'] . " ";
        
        if (self::$echo_query)
        Database::$echo_query = true;
        $dbData = Database::select(
            static::$table,
            [
                "columns" => implode(', ', static::$uniqueFields),
                "where" => $where,
                "limit" => 1,
                ]
            );

        
    
        if (!$dbData || !is_array($dbData)) return [];
    
        $errors = [];
        $row = $dbData[0];
    
        foreach (static::$uniqueFields as $key) {
            if (!($data[$key] ?? false)) continue;
            if (isset($row[$key]) && $row[$key] == $data[$key]) {
                $errors[$key] = 'Valore già presente a sistema.';
            }
        }
        return $errors;
    }
    
    
    public static function getFullData($params = []) {
        // Funzione generica per riportare tutti i dati DELL'INDEX, accetta parametri per riportare tutti i dati che rispettano quei parametri.
        // Richiede una indexLabels nella classe (Es. User, Room) per indicare gli headers della tabella e riportare di conseguenza i valori corrispondenti.
        if (self::$echo_query)
            Database::$echo_query = true;
            $dbData = Database::select(
            static::$table,
            array_merge($params, [
                "columns" => implode(', ', array_keys(static::$indexLabels))
            ])
        );
        return [
            'keys' => array_values(static::$indexLabels),
            'values' => $dbData
        ];        
    }

    public static function getSingleData($id) {
        // Funzione generica per prendere tutti i dati a partire dall'id.
        if (self::$echo_query)
            Database::$echo_query = true;
            $dbData = Database::select(
            static::$table,
            [
                "where" => 'id = ' . $id
            ]
        );

        if (!$dbData) return null;
        return $dbData[0];
    }

}