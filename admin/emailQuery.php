<?php
include __DIR__ . '/../config.php';
// SELECT * FROM events WHERE (UNIX_TIMESTAMP(starting_time) - (alert_time * 60)) <= UNIX_TIMESTAMP(NOW()) AND sent_email = null;
$date = date('Y-m-d H:i:s');
$events = Event::select([
    "where" => sprintf("(UNIX_TIMESTAMP(starting_time) - (alert_time * 60)) <= UNIX_TIMESTAMP('$date') AND sent_email IS NULL"),
    "limit" => -1
]);
if ($events ?? false) {
    foreach ($events as $event) {
        if ($event['starting_time'] > $date) {
            $participants = Participant::select([
                "where" => sprintf("event_id = %s AND confirmed = 1", $event['id']),
                "limit" => -1
            ]);

            foreach ($participants as $participant) {
                $sentEmail = Event::prepareAndSendEmail($participant['email'], 'reservation_start_alert', $event);

                // if ($sentEmail ?? false) {
                //     $where = sprintf("id = %s", $event['id']);
                //     Event::update([
                //         "sent_email" => $date,
                        
                //     ], $where);
                // }
            }
        }
        
    }
}

