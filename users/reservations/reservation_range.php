<?php
include __DIR__ . '/../../config.php';
function tsToDate($date) {
    return date('H:i:s', $date);
}

function tsToISO($date) {
    return date('Y-m-d H:i:s', $date);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['time_changed'] ?? false) {
    $roomExists = false;
    $rooms = Room::select();
    
    if ($rooms ?? false) {
        $roomExists = true;
        foreach ($rooms as $room) {
            $unavailable_ranges = Event::calculateRanges($_POST['date']);

            $unavailable_ranges = array_map(function ($item) use ($room) {
                return [
                    "start" => sprintf("%s %s", $_POST['date'], tsToDate($item['start'])),
                    "end" => sprintf("%s %s", $_POST['date'], tsToDate($item['end'])),
                    "room_id" => $room['id']
                ];
            }, $unavailable_ranges);
            
        }
        
        echo json_encode([
            "success" => 1,
            "date" => $_POST['date'],
            "rooms" => $rooms,
            "unavailabilities" => $unavailable_ranges
        ]);
    } else {
        echo json_encode([
            "success" => 0,
            "date" => $_POST['date'],
            "message" => "Nessuna stanza presente"
        ]);
    }
    exit();
}


// $rooms = Room::select(); // 1. Ricavo tutte le stanze.

// $unavailableRanges = [];

// if ($rooms ?? false) {
//     foreach ($rooms as $room) { // 2. Di tutte le stanze, considero singolarmente una stanza.
//         $gap = $room['reservation_gap'] * 60; // 3. Converto orari di apertura e distacco in timestamp.
//         $opening_ranges = json_decode($room['opening_range'], true); // 3. Converto orari di apertura e distacco in timestamp.

//         $opening_ranges = array_map(function($item){return [ // 3. Converto orari di apertura e distacco in timestamp.
//                 'start' => strtotime($_POST['date'] . ' ' . $item['start'] . ':00'),
//                 'end' => strtotime($_POST['date'] . ' ' . $item['end'] . ':00')
//             ];
//         }, $opening_ranges);
        
//         $events = Event::select([ // 4. Ricavo tutti gli eventi che riguardano la stanza interessata in timestamp.
//             "where" => "room_id = {$room['id']} AND starting_time LIKE '%{$_POST['date']}%'",
//             "limit" => -1
//         ]);
        
//         $events = array_map(function($item) { // 4. Ricavo tutti gli eventi che riguardano la stanza interessata in timestamp.
//             $item['starting_time'] = strtotime($item['starting_time']);
//             $item['ending_time'] = strtotime($item['ending_time']);
//             return $item;
//         }, $events);
        
        
//         foreach ($opening_ranges as $index => $range) { // 5.	Controllo tutti i range, se l’inizio del range è maggiore o uguale alla fine del range, elimino il range.
//             if ($range['start'] >= $range['end']) {
//                 unset($opening_ranges[$index]);
//             }
//         }
//         $opening_ranges = array_values($opening_ranges);
        
//         for ($i = 0; $i < count($opening_ranges); $i++) { // 6.	Paragono ogni range con tutti gli altri, se la fine di uno coincide con l’inizio di un altro, unisco i range.
//             for ($j = $i + 1; $j < count($opening_ranges); $j++) {
//                 if ($opening_ranges[$i]['end'] == $opening_ranges[$j]['start']) {
//                     $opening_ranges[$i]['end'] = $opening_ranges[$j]['end'];
//                     unset($opening_ranges[$j]);
//                     $opening_ranges = array_values($opening_ranges);
//                     $j--; 
//                 }
//             }
//         }
//         if ($events) {
//             foreach ($events as $key => $event) { // 7. Di tutti gli eventi, considero singolarmente un evento.
                
//                 $event['starting_time'] -= $gap; // 8.	Aggiungo il distacco tra le prenotazioni sia all’inizio che alla fine dell’evento.
//                 $event['ending_time'] += $gap; // 8.	Aggiungo il distacco tra le prenotazioni sia all’inizio che alla fine dell’evento.
                
//                 $new_opening_ranges = [];
                
//                 foreach ($opening_ranges as $index => $range) { 
//                     // 9.	Cerco un range che abbia l’inizio minore o uguale alla fine dell’evento e la fine maggiore o uguale all'inizio dell’evento.
//                     if (($range['start'] <= $event['ending_time'] && $range['end'] >= $event['starting_time'])) { 
                        
//                         // 11.	Se l’inizio del range è minore dell’inizio dell’evento, mi salvo un nuovo range che va dall’inizio del range all’inizio dell’evento.
//                         if ($range['start'] < $event['starting_time']) {
//                             $new_opening_ranges[] = [
//                                 "start" => $range['start'],
//                                 "end" => $event['starting_time']
//                             ];
//                         }
                        
//                         // 12.	Se la fine del range è maggiore alla fine dell’evento, mi salvo un nuovo range che va dalla fine dell’evento alla fine del range.
//                         if ($range['end'] > $event['ending_time']) {
//                             $new_opening_ranges[] = [
//                                 "start" => $event['ending_time'],
//                                 "end" => $range['end']
//                             ];
//                         }
//                         // 13.  Altrimenti salvo l'intero range.
//                     } else {
//                         $new_opening_ranges[] = $range;
//                     }
//                 }
//                 // 14. Sostituisco i range iniziali con i nuovi range.
//                 $opening_ranges = $new_opening_ranges;
//             }
//         }
//         $opening_ranges = array_map(function ($item) {return [
//             "start" => tsToDate($item['start']),
//             "end" => tsToDate($item['end'])
//         ];}, $opening_ranges);

//         array_multisort($opening_ranges, SORT_ASC);
//         $room['opening_range'] = $opening_ranges;
//         $dayStart = '00:00:00';
//         $dayEnd = '23:59:59';

//         $unavailableRanges[] = [
//             "start" => sprintf('%s %s', $_POST['date'], $dayStart),
//             "end" => sprintf('%s %s', $_POST['date'], $opening_ranges[0]['start']),
//             "room_id" => $room['id']
//         ];


//         for ($i = 0; $i < count($opening_ranges); $i++) {
//             if ($i == count($opening_ranges) - 1) {
//                 $unavailableRanges[] = [
//                     "start" => sprintf('%s %s', $_POST['date'], $opening_ranges[$i]['end']),
//                     "end" => sprintf('%s %s', $_POST['date'], $dayEnd),
//                     "room_id" => $room['id']
//                 ];
//             } else {
//                 $unavailableRanges[] = [
//                     "start" => sprintf('%s %s', $_POST['date'], $opening_ranges[$i]['end']),
//                     "end" => sprintf('%s %s', $_POST['date'], $opening_ranges[$i + 1]['start']),
//                     "room_id" => $room['id']
//                 ];
//             }
//         }    
//     }
//     echo json_encode([
//         "success" => 1,
//         "date" => $_POST['date'],
//         "rooms" => $rooms,
//         "unavailabilities" => $unavailableRanges
//     ]);
// } else {
//     echo json_encode([
//         "success" => 0,
//         "date" => $_POST['date'],
//         "message" => "Nessuna stanza disponibile"
//     ]);
// }

// exit();
?>