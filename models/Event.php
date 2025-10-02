<?php
use Spatie\CalendarLinks\Link;
use chillerlan\QRCode\{QRCode, QROptions};

class Event extends Model {

    // Campi tabella
    private $id;
    private $user_id;
    private $room_id;
    private $name;
    private $starting_time;
    private $ending_time;
    private $access_key;
    private $alert_time;
    private $guestsEmail;
    private $sent_email;
    private $notes;
    private $created_at;
    private $updated_at;

    // Nome tabella (Query)
    protected static $table = 'events'; 

    // Campi obbligatori
    protected static $requiredFields = ['name', 'user_id', 'room_id', 'starting_time', 'ending_time', 'alert_time'];

    // Minuti di preavviso possibili
    public static $possibleAlertTimes = [
        15, 30, 60
    ];

    // Campi unici
    public static $uniqueFields = [];

    // Nomi tabella generale
    public static $indexLabels = [
        'name' => 'Nome prenotazione',
        'starting_time' => 'Data d\'inizio',
        'ending_time' => 'Data di fine',
        'alert_time' => 'Preavviso',
        'access_key' => 'Link d\'invito',
        'participants' => 'Partecipanti',
        'seats' => 'Posti disponibili',
    ];

    // Nomi tabella dettaglio evento
    public static $detailsLabels = [
        'username' => 'Username',
        'room_name' => 'Stanza',
        'starting_time' => 'Orario d\'inizio',
        'ending_time' => 'Orario di fine',
        'alert_time' => 'Tempo di preavviso',
        'created_at' => 'Data di creazione'
    ];

    
    public static function validateData($data) {
        $errors = static::checkRequiredFields($data);

        if ($data['starting_time'] ?? false) {
            if ($data['starting_time'] < date('Y-m-d H:i:s')) {
                $errors['starting_time'] = "La data d'inizio dell'evento deve essere superiore a quella attuale.";
            }
        }

        if ($data['ending_time'] ?? false) {
            if ($data['ending_time'] < date('Y-m-d H:i:s')) {
                $errors['ending_time'] = "La data di fine dell'evento deve essere superiore a quella attuale.";
            }
        }

        if ($data['alert_time'] ?? false) {
            if (!in_array($data['alert_time'], static::$possibleAlertTimes)) {
                $errors['alert_time'] = "Impossibile impostare un orario di preavviso diverso da quelli consentiti.";
            }
        }

        return $errors ?: static::checkDataUniqueness($data);
    }

    public static function prepareData($data, $new = false) {
        if ($data['guestsEmail'] ?? false) $data['guestsEmail'] = strtolower($data['guestsEmail']);
        $now = date('Y-m-d H:i:s');

        $data['updated_at'] = "$now";
        if ($new) {
            $data['created_at'] = "$now";
            if (!isset($data['notes'])) $data['notes'] = "Nessuna nota per questa prenotazione.";
            $data['access_key'] = bin2hex(random_bytes(32));
        }

        return $data;
    }

    public static function prepareAndSendEmail($userEmail, $templateType, $reservation, $attachment = false) {
        $creators = User::select([
            "where" => sprintf("id = '%s'", $reservation['user_id'])
        ]);
        $reservation_creator = $creators[0];
        $participants = Participant::select([
            "where" => sprintf("event_id = %s AND email = '%s'", $reservation['id'], $userEmail)
        ]);
        $participant = $participants[0];
        $attachment = Helper::generatePdf([
            "email" => $userEmail, 
            "event_id" => $reservation['id']
        ], 'participation_confirmed');
        $tagData = [];
        foreach (Mail_templates::$possibleTags[$templateType] as $tag => $info) {
            switch ($tag) {
                case 'reservation_name':
                    $tagData[$tag] = $reservation['name'];
                    break;
                case 'alert_time':
                    $timeDiff = ceil((strtotime($reservation['starting_time']) - strtotime(date("Y-m-d H:i:s"))) / 60);
                    
                    if ($timeDiff > 1) {
                        $tagData[$tag] = $timeDiff . ' minuti';
                    } else {
                        $tagData[$tag] = 'meno di un minuto';
                    }
                    break;
                case 'reservation_creator':
                    $tagData[$tag] = $reservation_creator['username'];
                    break;
                case 'reservation_start':
                    $tagData[$tag] = $reservation['starting_time'];
                    break;
                case 'reservation_end':
                    $tagData[$tag] = $reservation['ending_time'];
                    break;
                case 'reservation_link':
                    $tagData[$tag] = sprintf('%s/users/reservations/%s.php?token=%s&utem=%s', $_ENV['APP_URL'], $templateType, $reservation['access_key'], sha1($participant['email']));
                    break;
                case 'footer_disclaimer':
                    $tagData[$tag] = 'Orario di invio della mail: ' . date("Y-m-d H:i:s");
                    break;
            }
        }
        [$subject, $body] = Mail_templates::prepareEmailData($userEmail, $templateType, $tagData);
       
        if ($subject && $body) {
            if ($attachment) {
                $sentEmail = Mail::send($userEmail, $subject, $body, $attachment);
            } else {
                $sentEmail = Mail::send($userEmail, $subject, $body);
            }
            return $sentEmail;
        }
    }

    public static function createAndSendEmail($data) {
        $errors = static::validateData($data);
        if ($errors ?? false) {
            return ["success" => 0, "message" => $errors];
        }

        $preparedData = static::prepareData($data, true);
        $guestsEmail = explode(",", $preparedData['guestsEmail']);
        $created = static::create([$data]);
        
        if ($created) {
            Log::create([
                [
                    "user_id" => $_SESSION['id'],
                    "action_type" => "Created event",
                    "ip_address" => $_SERVER['REMOTE_ADDR'],
                    "details" => "User has created an event."
                ]
            ]);
            $reservations = static::select([
                "where" => sprintf("user_id = %s AND starting_time = '%s' AND ending_time = '%s' AND room_id = %s", $preparedData['user_id'], $preparedData['starting_time'], $preparedData['ending_time'], $preparedData['room_id'])
            ]);
            $reservation = $reservations[0];
            foreach ($guestsEmail as $guest) {
                $participants = Participant::create([[
                    "event_id" => $reservation['id'],
                    "email" => $guest,
                    "access_way" => 0
                ]]);
                $emailSent = static::prepareAndSendEmail($guest, 'reservation_invite', $reservation);
                
            }
            return $emailSent;
        }
    }

    public static function joinEvents($data, $excludedGuests = null) {
        $errors = static::validateData($data);
        if ($errors ?? false) {
            return ["success" => 0, "message" => $errors];
        }

        $preparedData = static::prepareData($data, true);
        $guestsEmail = explode(",", $preparedData['guestsEmail']);
        $created = static::create([$data]);

        if ($created) {
            Log::create([
                [
                    "user_id" => $_SESSION['id'],
                    "action_type" => "Joined events",
                    "ip_address" => $_SERVER['REMOTE_ADDR'],
                    "details" => "User joined two or more events."
                ]
            ]);
            $reservations = static::select([
                "where" => sprintf("user_id = %s AND starting_time = '%s' AND ending_time = '%s' AND room_id = %s", $preparedData['user_id'], $preparedData['starting_time'], $preparedData['ending_time'], $preparedData['room_id'])
            ]);
            $reservation = $reservations[0];
            foreach ($guestsEmail as $guest) {
                $participants = Participant::create([[
                    "event_id" => $reservation['id'],
                    "email" => $guest,
                    "access_way" => 0
                ]]);
                $emailSent = static::prepareAndSendEmail($guest, 'reservation_update', $reservation);
            }

            if ($excludedGuests ?? false) {
                foreach ($excludedGuests as $excludedGuest) {
                    $events = Event::select([
                        "where" => sprintf("id = %d", $excludedGuest['event_id'])
                    ]);
                    $event = $events[0];
                    $emailSent = static::prepareAndSendEmail($excludedGuest['email'], 'reservation_canceled', $event);
                }
            }

            return $emailSent;
        }
    }

    public static function calculateRanges($date, $gap = false, $room_id = null, $event_id = null) {
        $day = date('Y-m-d', strtotime($date));
        $baseWeekDay = date('w', strtotime($date));

        if ($room_id ?? false) {
            $rooms = Room::select([
                "where" => sprintf("id = %s", $room_id)
            ]);
        } else {
            $rooms = Room::select();
        }

        $unavailableRanges = [];

        foreach ($rooms as $room) {
            if ($gap) $gap = $room['reservation_gap'] * 60;
            $opening_ranges = json_decode($room['opening_range'], true);
            $dayStart = strtotime($day . ' 00:00:00');
            $dayEnd = strtotime($day . ' 23:59:59');

            foreach ($opening_ranges as $index => $range) {
                if (in_array($baseWeekDay, $range['days'])) {
                    $day_ranges[] = $opening_ranges[$index];
                }
            }

            if ($day_ranges ?? false) {
                foreach ($day_ranges as $range) {
                    if (in_array($baseWeekDay, $range['days'])) {
                        $completed_ranges[] = [
                            "start" => strtotime("$day {$range['start']}:00"),
                            "end" => strtotime("$day {$range['end']}:00"),
                            "day" => $baseWeekDay
                        ];
                    }
                }

                $completed_ranges = array_filter($completed_ranges, function ($item) {
                    return $item['start'] < $item['end'];
                });
                    
                $completed_ranges = array_values($completed_ranges);
                    
                for ($i = 0; $i < count($completed_ranges); $i++) {
                    for ($j = $i + 1; $j < count($completed_ranges); $j++) {
                        if ($completed_ranges[$i]['end'] == $completed_ranges[$j]['start']) {
                            $completed_ranges[$i]['end'] = $completed_ranges[$j]['end'];
                            unset($completed_ranges[$j]);
                            $completed_ranges = array_values($completed_ranges);
                            $j--;
                        }
                    }
                }

                $events = Event::select([
                    "where" => "room_id = {$room['id']} AND starting_time LIKE '%$day%' AND id != " . ($event_id ?? 0),
                    "limit" => -1
                ]);

                if ($events ?? false) {
                    $events = array_map(function ($event) {
                        $event['starting_time'] = strtotime($event['starting_time']);
                        $event['ending_time'] = strtotime($event['ending_time']);
                        return $event;
                    }, $events);

                    foreach ($events as $event) {
                        if ($gap) {
                            $event['starting_time'] -= $gap;
                            $event['ending_time'] += $gap;
                        }
                        $new_ranges = [];
                        foreach ($completed_ranges as $range) {
                            if ($range['start'] <= $event['ending_time'] && $range['end'] >= $event['starting_time']) {
                                if ($range['start'] < $event['starting_time']) {
                                    $new_ranges[] = [
                                        'start' => $range['start'],
                                        'end' => $event['starting_time']
                                    ];
                                }
                                if ($range['end'] > $event['ending_time']) {
                                    $new_ranges[] = [
                                        'start' => $event['ending_time'],
                                        'end' => $range['end']
                                    ];
                                }
                            } else {
                                $new_ranges[] = $range;
                            }
                        }
                        $completed_ranges = $new_ranges;
                    }
                }
                usort($completed_ranges, fn($a, $b) => $a['start'] <=> $b['start']);
            }

            if ($completed_ranges ?? false) {
                if ($completed_ranges[0]['start'] > $dayStart) {
                    $unavailableRanges[] = [
                        "start" => $dayStart,
                        "end" => $completed_ranges[0]['start'],
                        "room_id" => $room['id']
                    ];
                }

                for ($i = 0; $i < count($completed_ranges); $i++) {
                    if ($i == count($completed_ranges) - 1) {
                        $unavailableRanges[] = [
                            "start" => $completed_ranges[$i]['end'],
                            "end" => $dayEnd,
                            "room_id" => $room['id']
                        ];
                    } else {
                        $unavailableRanges[] = [
                            "start" => $completed_ranges[$i]['end'],
                            "end" => $completed_ranges[$i + 1]['start'],
                            "room_id" => $room['id']
                        ];
                    }
                }
            } else {
                $unavailableRanges[] = [
                    "start" => $dayStart,
                    "end" => $dayEnd,
                    "room_id" => $room['id']
                ];
            }
        }
        return $unavailableRanges;  
    }

    public static function checkRanges($starting_time, $ending_time, $room_id, $gap, $event_id = null) {
        $starting_time = strtotime($starting_time);
        $ending_time = strtotime($ending_time);
        $day = date('Y-m-d', $starting_time);
        $now = strtotime(date('Y-m-d H:i:s'));
        $unavailable_ranges = static::calculateRanges($day, $gap, $room_id, $event_id);
        $rooms = Room::select([
            "where" => sprintf("id = %d", $room_id)
        ]);
        $room = $rooms[0];
        foreach ($unavailable_ranges as $range) {
            $isOverlapped = false;
            $isPassed = false;
            $isRangeSmaller = false;
            $isNotMultiple = false;
            if (
                ($starting_time > $range['start'] && $starting_time < $range['end']) ||
                ($ending_time > $range['start'] && $ending_time < $range['end']) ||
                ($starting_time <= $range['start'] && $ending_time >= $range['end'])
                ) {
                $isOverlapped = true;
                Log::create([
                    [
                        "user_id" => $_SESSION['id'],
                        "action_type" => "Failed event creation",
                        "ip_address" => $_SERVER['REMOTE_ADDR'],
                        "details" => "Attempted event creation failed because the event is overlapped on one or more events.",
                        "level" => 0
                    ]
                ]);
                return [
                    "success" => 0,
                    "message" => "Impossibile creare un evento sovrapposto ad un altro."
                ];
                break;
            } else if ($starting_time < $now) {
                $isPassed = true;
                Log::create([
                    [
                        "user_id" => $_SESSION['id'],
                        "action_type" => "Failed event creation",
                        "ip_address" => $_SERVER['REMOTE_ADDR'],
                        "details" => "Attempted event creation failed as the event is in the past.",
                        "level" => 0
                    ]
                ]);
                return [
                    "success" => 0,
                    "message" => "Impossibile creare un evento nel passato"
                ];
                break;
            }
        }

        if ($gap) {
            if (
                ($ending_time - $starting_time) < ($room['time_frame'] * 60)) {
                $isRangeSmaller = true;
                Log::create([
                    [
                        "user_id" => $_SESSION['id'],
                        "action_type" => "Failed event creation",
                        "ip_address" => $_SERVER['REMOTE_ADDR'],
                        "details" => "Attempted event creation failed because the event is shorter than the single block allowed by the room.",
                        "level" => 0
                    ]
                ]);
                return [
                    "success" => 0,
                    "message" => "Impossibile creare un evento più piccolo del singolo blocco. Il blocco per questa stanza è: {$room['time_frame']} minuti"
                ];
            } elseif ((($ending_time - $starting_time) % ($room['time_frame'] * 60)) != 0) {
                $isNotMultiple = true;
                Log::create([
                    [
                        "user_id" => $_SESSION['id'],
                        "action_type" => "Failed event creation",
                        "ip_address" => $_SERVER['REMOTE_ADDR'],
                        "details" => "Attempted event creation failed because the event does not respect room time frames.",
                        "level" => 0
                    ]
                ]);
                return [
                    "success" => 0,
                    "message" => "Impossibile creare un evento che non rispetti i blocchi. Il blocco per questa stanza è: {$room['time_frame']} minuti"
                ];
            }
        }

        if (!$isOverlapped && !$isPassed && !$isRangeSmaller && !$isNotMultiple) {
            Log::create([
                [
                    "user_id" => $_SESSION['id'],
                    "action_type" => "Event created",
                    "ip_address" => $_SERVER['REMOTE_ADDR'],
                    "details" => "User has created an event",
                    "level" => 0
                ]
            ]);
            return [
                "success" => 1,
                "message" => "Evento aggiornato correttamente"
            ];
        }
    }

    public static function check_if_joinable($events_array = []) {
        
        $now = date('Y-m-d H:i:s');
        $ranges_to_check = [];
        foreach ($events_array as $key => $event_array) {
            $starting_times = array_column($event_array, 'starting_time');
            array_multisort($starting_times, SORT_ASC, $event_array);
            
            for ($i = 0; $i <= count($event_array); $i++) {
                for ($j = $i + 1; $j < count($event_array); $j++) {
                    if ($event_array[$i]['starting_time'] > $now) {
                        $ranges_to_check[] = [
                            "starting_time" => $event_array[$i]['ending_time'],
                            "ending_time" => $event_array[$j]['starting_time'],
                            "room_id" => $event_array[$i]['room_id']
                        ];
                    }
                }
            }
        }
        foreach ($ranges_to_check as $range_to_check) {
            $is_joinable = static::checkRanges($range_to_check['starting_time'], $range_to_check['ending_time'], $range_to_check['room_id'], $gap = false);
            if ($is_joinable['success'] ?? false) {
                Log::create([
                    [
                        "user_id" => $_SESSION['id'],
                        "action_type" => "Attempted combination of events",
                        "ip_address" => $_SERVER['REMOTE_ADDR'],
                        "details" => "The user has successfully attempted to merge two or more events.",
                        "level" => 0
                    ]
                ]);
                return true;
            } else {
                Log::create([
                    [
                        "user_id" => $_SESSION['id'],
                        "action_type" => "Attempted combination of events",
                        "ip_address" => $_SERVER['REMOTE_ADDR'],
                        "details" => "The user failed to merge two or more events.",
                        "level" => 0
                    ]
                ]);
                return false;
            }
        }
    }

    public static function fetch_pdf_data($params, $templateType) {
        switch ($templateType) {
            case 'participation_confirmed':
                $events = static::select([
                    "where" => sprintf("id = %d", $params['event_id'])
                ]);
                $event = $events[0];
                $participants = Participant::select([
                    "where" => sprintf("email = '%s' AND event_id = %d", $params['email'], $params['event_id'])
                ]);
                $participant = $participants[0];
        
                $rooms = Room::select([
                    "where" => sprintf("id = %d", $event['room_id'])
                ]);
                $room = $rooms[0]; 
        
                return [$event, $participant, $room];
                break;
            case 'print_tickets':

                $events = Event::select([
                    "where" => sprintf("id = %d", $params['event_id'])
                ]);
                $event = $events[0];
            
                $participants = Participant::select([
                    "where" => sprintf("email = '%s' AND event_id = %d", $params['email'], $params['event_id'])
                ]);

                $participant = $participants[0];
                
                return [$event, $participant];
                break;
            case 'summary_tickets':
                $events = Event::select([
                    "where" => sprintf("id = %d", $params['event_id'])
                ]);
                $event = $events[0];
            
                $participants = Participant::select([
                    "where" => sprintf("email = '%s' AND event_id = %d", $params['email'], $params['event_id'])
                ]);

                $participant = $participants[0];
                
                return [$event, $participant];
                break;
        }

    }

    public static function writePdf($params, $templateType) {

        switch ($templateType) {
            case 'participation_confirmed':
                [$event, $participant, $room] = static::fetch_pdf_data([
                    "email" => $params['email'], 
                    "event_id" => $params['event_id']
                ], 'participation_confirmed');
                $url = sprintf('%s/users/ics.php?event_id=%d&token=%s', $_ENV['APP_URL'], $event['id'], $event['access_key']);
                $qrOptions = new QROptions([
                    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                    'imageBase64' => false, 
                    'scale' => 5,
                    'imageTransparent' => false
                ]);
                
                $qrCode = new QRCode($qrOptions);
                $qrPngData = $qrCode->render($url);
                $barcodeData = static::createBarcode($params['email']);
        
        
                ob_start();
        
                include __DIR__ . '/../template/pdf_templates/reservation_confirm_summary_pdf_content.php';
                include __DIR__ . '/../template/pdf_templates/ticket_pdf_content.php';
                $html = ob_get_contents();
                
        
                ob_end_clean();
                
                return [$html, $qrPngData, $barcodeData];
                break;
            case 'print_tickets':
                [$event, $participants] = static::fetch_pdf_data(['email' => $params['email'], "event_id" => $params['event_id']], 'print_tickets');
                $participants_arr = Participant::select([
                    "where" => sprintf("event_id = %d AND email = '%s'", $event['id'], $params['email'])
                ]);
                $participant = $participants_arr[0];
                $barcodeData = static::createBarcode($params['email'], $event['id'], $participant['id']);

                ob_start();
                include __DIR__ . '/../template/pdf_templates/ticket_pdf_content.php';
                $html = ob_get_contents();
                ob_end_clean();
                
                return [$html, $barcodeData]; 
                break;
            case 'summary_tickets':
                [$event, $participants] = static::fetch_pdf_data(['email' => $params['email'], "event_id" => $params['event_id']], 'summary_tickets');
                $participants_arr = Participant::select([
                    "where" => sprintf("event_id = %d AND email = '%s'", $event['id'], $params['email'])
                ]);
                $participant = $participants_arr[0];

                $barcodeData = static::createBarcode($params['email'], $event['id'], $participant['id']);
                ob_start();

                include __DIR__ . '/../template/pdf_templates/summary_tickets_pdf_content.php';
                $html = ob_get_contents();
                ob_end_clean();
                
                return [$html, $barcodeData]; 
                break;
        }
    }

    public static function createIcsContent($event, $room) {
        $event_title = $event['name'];
        $event_start_date = date('Ymd\THis\Z', strtotime($event['starting_time']));
        $event_end_date = date('Ymd\THis\Z', strtotime($event['ending_time']));
        $event_location = $room['name'];
        $event_description = $event['notes'];

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//YourApp//EN\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . uniqid() . "\r\n";
        $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . $event_start_date . "\r\n";
        $ical .= "DTEND:" . $event_end_date . "\r\n";
        $ical .= "LOCATION:" . $event_location . "\r\n";
        $ical .= "SUMMARY:" . $event_title . "\r\n";
        $ical .= "DESCRIPTION:" . $event_description . "\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    public static function createBarcode($email, $event_id, $participant_id) {
        $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
        $string = md5($email . $event_id . $participant_id);
        $string = substr($string, 0, 10);
        $barcodeData = $generator->getBarcode($string, $generator::TYPE_CODE_128);
        return $barcodeData;
    }
}