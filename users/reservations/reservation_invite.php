<?php
require_once '../../config.php';


$token = $_GET['token'] ?? '';
$message = '';
$confirmedParticipationTemplate = 'confirmed_reservation';
$validInvite = false;

function dateString($date) {
    $string = date('d/m/Y', strtotime($date)) . ' alle ' . date('H:i:s', strtotime($date));
    return $string;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['add_participation'] ?? false)) {
    $total_participants = $_POST['total_participants'];
    $room['seats'] = $_POST['room_seats'];
    $isRegistered = Participant::select([
        "columns" => "COUNT(*) as count",
        "where" => sprintf("event_id = %s AND email = '%s'", $_POST['event_id'], $_POST['email'])
    ]);
    if ($total_participants >= $room['seats']) {
        echo json_encode([
            "success" => 0, "message" => "L'evento ha raggiunto la capacità massima di partecipanti."
        ]);
    } else {
        if ($isRegistered[0]['count'] > 0) {
            echo json_encode([
                "success" => 0, "message" => "Utente già registrato per l'evento"
            ]);
        } else {
            $participant = Participant::create([[
                "event_id" => $_POST['event_id'],
                "email" => $_POST['email'],
                "notes" => $_POST['notes'],
                "confirmed" => 1,
                "access_way" => 1
            ]]);

            if ($participant) {
                $events = Event::select([
                    "where" => sprintf("id = %d", $_POST['event_id'])
                ]);
                $event = $events[0];
                $mail = Event::prepareAndSendEmail($_POST['email'], $confirmedParticipationTemplate, $event, true);

                if ($mail ?? false) {
                    echo json_encode([
                        "success" => 1, "message" => "Partecipazione confermata con successo, ti è stata inviata una email di riepilogo."
                    ]);
                } else {
                    echo json_encode([
                        "success" => 0, "message" => "Si è verificato un errore nell'invio della mail di riepilogo."
                    ]);
                }
            } else {
                echo json_encode([
                    "success" => 0, "message" => "Errore nella conferma della partecipazione"
                ]);
            }
        }
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['confirm_participation'] ?? false) {
    
    $where = sprintf("email = '%s' AND event_id = %s", $_POST['utem'], $_POST['event_id']);
    
    $isConfirmed = Participant::select([
        "where" => sprintf("event_id = %s AND email = '%s' AND confirmed = 1", $_POST['event_id'], $_POST['utem'])
    ]);
    
    if ($isConfirmed[0] ?? false) {
        echo json_encode([
            "success" => 0, "message" => "Utente già confermato per l'evento"
        ]);
    } else {
        $updatedParticipant = Participant::update([
            "confirmed" => 1,
            "notes" => $_POST['notes']
        ], $where);


        if ($updatedParticipant) {
            $events = Event::select([
                "where" => sprintf("id = %d", $_POST['event_id'])
            ]);
            $event = $events[0];
            $mail = Event::prepareAndSendEmail($_POST['utem'], $confirmedParticipationTemplate, $event);

            echo json_encode([
                "success" => 1, "message" => "Partecipazione confermata con successo, ti è stata inviata una email di riepilogo."
            ]);
        } else {
            echo json_encode([
                "success" => 0, "message" => "Errore nella conferma della partecipazione"
            ]);
        }
    }
    exit();
}

if (empty($token)) {
    $message = 'Token di conferma mancante.';
} else {
    $now = date('Y-m-d H:i:s');
    $events = Event::select([
        "where" => sprintf("access_key = '%s' AND starting_time > '%s'", $_GET['token'], $now)
    ]);
    if (empty($events)) {
        $message = 'L\'evento è già iniziato oppure il token di conferma non è più valido o è scaduto.';
    } else {
        $validInvite = true;
        $event = $events[0];
        $rooms = Room::select([
            "where" => sprintf("id = %s", $event['room_id'])
        ]);
        $room = $rooms[0];

        $creators = User::select([
            "where" => sprintf("id = %s", $event['user_id'])
        ]);
        $creator = $creators[0];

        $participants = Participant::select([
            "columns" => "COUNT(*) as count",
            "where" => sprintf("event_id = %s", $event['id'])
        ]);

        $total_participants = $participants[0]['count'];


        if ($_GET['utem'] ?? false) {
            $users = Participant::select([
                "where" => sprintf("sha1(email) = '%s'", $_GET['utem'])
            ]);

            if ($users ?? false) {
                $user = $users[0];
            }
        }
    }
}






$pageTitle = sprintf("Partecipa all'evento: %s", $event['name'] ?? 'Invito non valido');
include_once '../../template/header.php';
?>
    <div class="container min-vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="card shadow-lg border-0 rounded-4 p-4 w-100" style="max-width: 600px;">
            <?php if ($validInvite): ?>
            <h2 class="text-center mb-2 text-primary"><?= $pageTitle ?></h2>
            <h5 class="text-center mb-4">
                <?= sprintf("%s %s ti ha invitato", $creator['firstname'], $creator['lastname']) ?>
            </h5>
            <div class="row justify-content-between">
                <div class="col-auto">
                    <strong>Inizio: </strong> <?= dateString($event['starting_time']) ?>
                </div>
                <div class="col-auto">
                    <strong>Fine: </strong> <?= dateString($event['ending_time']) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($message ?? false): ?>
                <h4 class="text-center text-danger">
                    <?= $message ?>
                </h4>
            <?php else: ?>
                <?php if ($user ?? false): ?>
                    <form method="POST" id="confirm_participation" class="d-flex justify-content-center align-items-center flex-column">
                        <input type="hidden" name="utem" value="<?= $user['email'] ?>">
                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">

                        <label for="notes" class="fw-bold mt-2">
                            Aggiungi note (opzionale):
                        </label>
                        <textarea name="notes" id="notes_box" rows="3" class="form-control w-100"></textarea>

                        <button class="btn btn-success mt-2">
                            Conferma partecipazione
                        </button>
                    </form>
                    <div class="text-center mt-3" id="errorBox">
                        <div id="error_message" class="text-danger mb-2"></div>
                        <div id="success_message" class="text-success mb-2"></div>
                    </div>
                    <?php else: ?>
                        <form method="POST" id="participate_form" class="d-flex justify-content-center align-items-center flex-column">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                            <input type="hidden" name="room_seats" value="<?= $room['seats'] ?>">
                            <input type="hidden" name="total_participants" value="<?= $total_participants ?>">
                            
                            <label for="email">
                                Email*:
                            </label>
                            <input type="email" name="email" class="form-control">

                            <label for="notes" class="fw-bold mt-2">
                                Aggiungi note (opzionale):
                            </label>
                            <textarea name="notes" id="notes_box" rows="3" class="form-control w-100"></textarea>
                            
                            <button class="btn btn-success mt-2">
                                Partecipa all'evento
                            </button>
                            <small class="mt-2">I campi contrassegnati da * sono obbligatori.</small>
                            
                        </form>
                        <div class="text-center mt-3">
                            <div id="error_message" class="text-danger mb-2"></div>
                            <div id="success_message" class="text-success mb-2"></div>
                        </div>
                    <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>


<?php 
include_once '../../template/footer.php';
?>
<script>
    const participate_form = document.getElementById('participate_form') ?? false;

    if (participate_form !== false) {
        participate_form.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const form = e.target;
        const data = new FormData(form);
        data.append('add_participation', true);

        fetch('/users/reservations/reservation_invite.php', {
            method: 'POST',
            body: data
        }).then(async (res) => {
            const response = await res.json();
            const errorMessage = document.getElementById('error_message');
            const successMessage = document.getElementById('success_message');
            participate_form.classList.add('d-none');
            participate_form.classList.remove('d-flex');

            if (response.success) {
                errorMessage.innerHTML = '';
                successMessage.innerHTML = response.message;
            } else {
                errorMessage.innerHTML = response.message;
            }
            
        })
    })
    }   
    

    const confirm_participation = document.getElementById('confirm_participation') ?? false;

    if (confirm_participation !== false) {
        confirm_participation.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const form = e.target;
        const data = new FormData(form);
        data.append('confirm_participation', true);
        console.log(form);

        fetch('/users/reservations/reservation_invite.php', {
            method: 'POST',
            body: data
        }).then(async (res) => {
            const response = await res.json();
            const errorMessage = document.getElementById('error_message');
            const successMessage = document.getElementById('success_message');
            confirm_participation.classList.remove('d-flex');
            confirm_participation.classList.add('d-none');

            if (response.success) {
                errorMessage.innerHTML = '';
                successMessage.innerHTML = response.message;
            } else {
                errorMessage.innerHTML = response.message;
            }
            
        })
    })
    }
    
</script>