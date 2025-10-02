<?php
include __DIR__ . '/../../config.php';

$logTitle = "Barcode scanner logic";

if (User::isModerator()) {
    $deniedAccess = false;
    Log::create([
        [
            "user_id" => $_SESSION['id'],
            "action_type" => "Secure route access successful",
            "ip_address" => $_SERVER['REMOTE_ADDR'],
            "details" => "The user has access to: $logTitle",
            "level" => 0
            ]
        ]);
    } else {
        $deniedAccess = true;
        Log::create([
        [
            "user_id" => $_SESSION['id'],
            "action_type" => "Attempted route protected access",
            "ip_address" => $_SERVER['REMOTE_ADDR'],
            "details" => "The user has attempted to access a protected page without having the necessary permissions: $logTitle",
            "level" => 3
            ]
        ]);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $participants = Participant::select([
        "where" => sprintf("SUBSTRING(MD5(CONCAT(participants.email, participants.event_id, participants.id)), 1, 10) = '%s'", $_POST['barcode'])
    ]);
    if (!$participants) {
        echo json_encode([
            "success" => 0,
            "message" => "Codice a barre non valido"
        ]);
    } else {
        $participant = $participants[0];
        $now = date('Y-m-d H:i:s');
        $records = Event_participants::select([
            "where" => sprintf("participant_id = %d AND event_id = %d", $participant['id'], $participant['event_id'])
        ]);
        if ($records) {
            $record = $records[0];
            if ($record['check_out']) {
                echo json_encode([
                    "success" => 0,
                    "message" => "Check-out già effettuato."
                ]);
                exit;
            }

            Event_participants::update([
                "check_out" => $now
            ], sprintf("id = %d", $record['id']));

            echo json_encode([
                "success" => 1,
                "message" => "Check-out effettuato correttamente! Ci vediamo, {$participant['email']}!"
            ]);
            exit;
        } else {
            Event_participants::create([[
                "participant_id" => $participant['id'],
                "event_id" => $participant['event_id'],
                "check_in" => $now,
            ]]);
            echo json_encode([
                "success" => 1,
                "message" => "Check-in effettuato correttamente! Benvenuto, {$participant['email']}"
            ]);
            exit;
        }
    }
}
?>