<?php
include __DIR__ . '/../../config.php';
User::requireAdmin();
$now = date('Y-m-d H:i:s');

function dateString($date) {
    $string = date('d/m/Y', strtotime($date)) . ' alle ' . date('H:i:s', strtotime($date));
    return $string;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['time_changed'] ?? false) {
   $events = Event::select([
       "columns" => "rooms.name as room_name, events.room_id, events.id, events.name as event_name, events.starting_time, events.ending_time, events.alert_time, events.notes, events.access_key, events.created_at, COUNT(participants.id) as participants, events.guestsEmail",
       "joins" => [
        [
            "type" => "LEFT",
            "sql" => "participants ON events.id = participants.event_id"
        ],
        [
            "type" => "LEFT",
            "sql" => "rooms ON events.room_id = rooms.id"
        ],
    ],
    "groupBy" => "events.id",
    "where" => "events.room_id = {$_POST['room_id']} AND events.starting_time LIKE '%{$_POST['date']}%'"
    ]);
}

if ($events ?? false) {
    $event = $events[0];
    $guests = explode(",", $event['guestsEmail']);

    echo json_encode([
        "success" => 1,
        "date" => $_POST['date'],
        "events" => $events,
        "room_name" => $event['room_name'],
        "room_id" => $event['room_id']
    ]);
    exit();
} else {
    echo json_encode([
        "success" => 0,
        "date" => $_POST['date'],
        "message" => "Nessun evento trovato per la stanza selezionata"
    ]);
    exit();
}

?>




