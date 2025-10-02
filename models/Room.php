<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Room extends Model {
    // Attributes
    private $id;
    private $name;
    private $seats;
    private $opening_range;
    private $time_frame;
    private $availability;
    private $reservation_gap;
    private $created_at;
    private $updated_at;

    protected static $table = 'rooms';
    protected static $requiredFields = ['name', 'seats', 'opening_range', 'time_frame', 'reservation_gap'];
    protected static $uniqueFields = ['name'];
    public static $availabilityLabels = [
        0 => 'Non disponibile',
        1 => 'Disponibile',
    ];
    public static $possibleFrames = [
        15, 30, 60
    ];
    public static $resGap = [
        15, 30, 60
    ];

    public static $indexLabels = [];

    public static function setIndexLabels() {
        static::$indexLabels = [
            'id' => '#',
            'name' => Lang::getText("room:name"),
            'seats' => Lang::getText("room:seats"),
            'opening_range' => Lang::getText("room:opening_range"),
            'time_frame' => Lang::getText("room:time_frame"),
            'availability' => Lang::getText("room:available"),
            'reservation_gap' => Lang::getText("room:reservation_gap")
        ];
    }

    public static $weekDays = [
    ];

    public static function setWeekDays() {
        static::$weekDays = [
            "0" => Lang::getText("general:sunday"),
            "1" => Lang::getText("general:monday"),
            "2" => Lang::getText("general:tuesday"),
            "3" => Lang::getText("general:wednesday"),
            "4" => Lang::getText("general:thursday"),
            "5" => Lang::getText("general:friday"),
            "6" => Lang::getText("general:saturday")
        ];
    }

    public static function validateData($data) {
        $errors = static::checkRequiredFields($data);
        if ($data['name'] ?? false) {
            if (strlen($data['name']) < 3) {
                $errors['name'] = "Il nome della stanza deve essere lungo almeno 3 caratteri.";
            } else if (!preg_match('/^[a-zA-Z0-9\s]+$/', $data['name'])) {
                $errors['name'] = "Il nome può contenere solo lettere minuscole, maiuscole e numeri.";
            }
        }

        if ($data['seats'] ?? false) {
            if (!is_numeric($data['seats'])) {
                $errors['seats'] = "Inserisci un numero di posti disponibili.";
            }
        }
        

        if ($data['opening_range'] ?? false) {
            foreach ($data['opening_range'] as $singleRange) {
                if (!$singleRange['start'] || !$singleRange['end']) {
                    $errors['opening_range'] = "Inserisci un orario di apertura e un orario di chiusura.";
                    break;
                }
                
                $startTime = strtotime('1970-01-01 ' . $singleRange['start']);
                $endTime = strtotime('1970-01-01 ' . $singleRange['end']);
                
                if ($endTime <= $startTime) {
                    $errors['opening_range'] = "L'orario di chiusura deve essere successivo all'orario di apertura.";
                    break;
                }

                if ($data['time_frame'] ?? false) {
                    $resMS = (int)$data['time_frame'] * 60;
                    if (($endTime - $startTime) % ($resMS) !== 0) {
                        $errors['opening_range'] = "L'orario di apertura selezionato non è conforme le fasce orarie selezionate";
                    }
                }

                $timeRanges[] = [
                    'start' => $startTime,
                    'end' => $endTime,
                    'days' => $singleRange['days']
                ];
            }

            for ($i = 0; $i < count($timeRanges); $i++) {
                for ($j = $i + 1; $j < count($timeRanges); $j++) {
                    
                    foreach ($timeRanges[$i]['days'] as $day) {
                        if (in_array($day, $timeRanges[$j]['days'])) {
                            if (
                                ($timeRanges[$i]['start'] <= $timeRanges[$j]['end'] && $timeRanges[$i]['end'] >= $timeRanges[$j]['start'])
                            ) {
                                $errors['opening_range'] = "Gli orari inseriti si sovrappongono. Verifica che non ci siano sovrapposizioni.";
                                break 3;
                            }
                        }
                    }
                    
                }
            }

        }

        if ($data['time_frame'] ?? false) {
            if (!in_array($data['time_frame'], static::$possibleFrames)) {
                $errors['time_frame'] = "Impossibile impostare la fascia oraria selezionata.";
            }
        }
        
        if ($data['availability'] ?? false) {
            if (!in_array($data['availability'], array_keys(static::$availabilityLabels))) {
                $errors['availability'] = "Impossibile impostare la disponibilità selezionata.";
            }
        }
        
        if ($data['reservation_gap'] ?? false) {
            if (!in_array($data['reservation_gap'], static::$resGap)) {
                $errors['reservation_gap'] = "Impossibile impostare il distacco tra le prenotazioni selezionato.";
            }
        }
        
        return $errors;
    }

    public static function prepareData($data, $new = false) {
        if ($data['name'] ?? false) $data['name'] = ucfirst(strtolower($data['name']));
        if ($data['seats'] ?? false) $data['seats'] = (int)$data['seats'];            
        if ($data['opening_range'] ?? false) $data['opening_range'] = json_encode($data['opening_range']);
        if ($data['time_frame'] ?? false) $data['time_frame'] = (int)$data['time_frame'];
        if ($data['availability'] ?? false) $data['availability'] = (int)$data['availability'];
        if ($data['reservation_gap'] ?? false) $data['reservation_gap'] = (int)$data['reservation_gap'];
        
        $now = date('Y-m-d H:i:s');
        $data['updated_at'] = "$now";
        
        if ($new) {
            $data['created_at'] = "$now";
        }
        return $data;
    }
}