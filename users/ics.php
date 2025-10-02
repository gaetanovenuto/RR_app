<?php
require_once '../config.php';

$event_id = $_GET['event_id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$event_id || !$token) {
    http_response_code(400);
    exit("Id o token non presenti.");
}

$events = Event::select(["where" => "id = $event_id AND access_key = '$token'"]);
if (!$events) {
    http_response_code(404);
    exit("Evento non trovato o token non valido");
}

$event = $events[0];
$rooms = Room::select(["where" => "id = " . $event['room_id']]);
$room = $rooms[0];

$icalContent = Event::createIcsContent($event, $room);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="evento_' . $event_id . '.ics"');
header('Content-Length: ' . strlen($icalContent));

echo $icalContent;
exit;
