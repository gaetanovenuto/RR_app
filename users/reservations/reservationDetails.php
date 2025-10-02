<?php
include __DIR__ . '/../../config.php';
$event = null;
$errors = [];

function dateString($date) {
    $string = date('d/m/Y', strtotime($date)) . ' alle ' . date('H:i:s', strtotime($date));
    return $string;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['update_reservation'] ?? false) {
        $updatedData = [
            "name" => $_POST['event_name'],
            "alert_time" => $_POST['alert_time'],
            "notes" => $_POST['event_notes']
        ];
        $starting_time = sprintf('%s %s:00', $_POST['date'], $_POST['starting_time']);
        $ending_time = sprintf('%s %s:00', $_POST['date'], $_POST['ending_time']);
    
        $isAvailable = Event::checkRanges($starting_time, $ending_time, $_POST['room_id'], $gap = true, $_POST['event_id']);
        
        if ($isAvailable['success'] ?? false) {
            $updatedData['starting_time'] = $starting_time;
            $updatedData['ending_time'] = $ending_time;
            $update = Event::update($updatedData, sprintf("id = %d", $_POST['event_id']));
    
            $participants = Participant::select([
                "columns" => "email",
                "where" => sprintf("event_id = %s", $_POST['event_id']),
                "orderBy" => "email",
                "limit" => -1
            ]);
            
            $participantsEmails = [];
            foreach ($participants as $participant) {
                $participantsEmails[] = $participant['email'];
            }
    
            if ($update ?? false) {
                $events = Event::select([
                    "where" => sprintf("id = %d", $_POST['event_id'])
                ]);
                $event = $events[0];
                foreach ($participantsEmails as $participantEmail) {
                    $emailSent = Event::prepareAndSendEmail($participantEmail, 'reservation_changed', $event);
                }
                echo json_encode([
                    "success" => 1,
                    "message" => $isAvailable['message']
                ]);
            } else {
                echo json_encode([
                    "success" => 0,
                    "message" => "Errore nell'aggiornamento dell'evento"
                ]);
            }
        } else {
            echo json_encode([
                "success" => 0,
                "message" => $isAvailable['message']
            ]);
        }
        exit();
    }

    if ($_POST['printing_tickets'] ?? false) {
        $emails_to_check = array_map('trim', explode(",", $_POST['emails']));

        $validParticipants = Participant::select([
            "columns" => "email",
            "where" => sprintf("event_id = %d", $_POST['event_id']),
            "limit" => -1
        ]);

        $validEmails = array_column($validParticipants, 'email');

        $invalidEmails = array_filter($emails_to_check, function ($email) use ($validEmails) {
            return !in_array($email, $validEmails);
        });

        if (!empty($invalidEmails)) {
            echo json_encode([
                "success" => 0,
                "message" => "Le seguenti email non sono partecipanti dell'evento: " . implode(', ', $invalidEmails)
            ]);
            exit();
        }

        $pdf = Helper::generatePdf([
            "emails" => $emails_to_check,
            "event_id" => $_POST['event_id']
        ], 'print_tickets');

        exit();
    }
}


if (isset($_GET['id'])) {
    $events = Event::select([
        "columns" => "users.username, rooms.name as room_name, rooms.seats as room_seats, rooms.id as room_id, events.id as event_id, events.name as event_name, events.starting_time, events.ending_time, events.alert_time, events.notes, events.access_key, events.created_at, events.updated_at, events.guestsEmail",
        "joins" => [
        [
            "type" => "LEFT",
            "sql" => "participants ON events.id = participants.event_id"
        ],
        [
            "type" => "LEFT",
            "sql" => "rooms ON events.room_id = rooms.id"
        ],
        [
            "type" => "LEFT",
            "sql" => "users ON events.user_id = users.id"
        ]
    ],
    "groupBy" => "events.id",
    "where" => sprintf("events.id = %s", $_GET['id']),
    ]);
    $event = $events[0];
    $participants = Participant::select([
        "where" => sprintf("event_id = %s", $_GET['id']),
        "orderBy" => "email",
        "limit" => -1
    ]);
    $countParticipants = Participant::select([
        "columns" => "COUNT(*) as COUNT",
        "where" => sprintf("event_id = %s", $_GET['id'])
    ]);
    $totalParticipants = $countParticipants[0]['COUNT'];
    $countConfirmedParticipants = Participant::select([
        "columns" => "COUNT(*) as COUNT",
        "where" => sprintf("event_id = %s AND confirmed = 1", $_GET['id'])
    ]);
    $confirmedParticipants = $countConfirmedParticipants[0]['COUNT'];
    $notShownArray = ['id', 'guestsEmail', 'notes', 'access_key', 'unavailability', 'updated_at', 'event_name'];
    $dateArray = ['starting_time', 'ending_time', 'created_at'];
    if (!$event) {
        $errors['Event'] = "Evento non esistente";
        header("Location: eventsTable.php");
        exit;
    }
}

$pageTitle = "{$event['event_name']}";

include_once __DIR__ . '/../../template/header.php';
?>

<div class="w-100 mx-3">
    <h3 class="text-center mt-1">
        <?= $pageTitle ?>
    </h3>
    <div class="row m-2 justify-content-between">
        <a href="/reservations" class="btn btn-secondary rounded-50 col-auto"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="d-flex justify-content-between col-auto">
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-sm btn-info text-white me-2" data-bs-toggle="modal" data-bs-target="#print_tickets_modal">
                <?= Lang::getText("print_ticket") ?>
            </button>
            <input type="hidden" class="hidden_link" value="<?= sprintf("%s/users/reservations/reservation_invite.php?token=%s", $_ENV['APP_URL'], $event['access_key']) ?>">
            <button class="btn btn-sm btn-success copy_button col-auto">Copia il link</button>
        </div>
    </div>
    <form method="POST" id="reservation_details_form">
        <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['event_id'] ?? '') ?>">
        <input type="hidden" name="room_id" value="<?= htmlspecialchars($event['room_id'] ?? '') ?>">
        <input type="hidden" name="update_reservation" value="true">

        <div class="row align-items-center">
            <div class="mb-3 col-12 px-1">
                <label for="input-event_name" class="form-label">Nome prenotazione</label>
                <input type="text" name="event_name" id="input-event_name"
                       class="form-control <?= isset($errors['event_name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($event['event_name'] ?? '') ?>" required>
                <div class="invalid-feedback" id="name_errors"><?= $errors['event_name'] ?? '' ?></div>
            </div>
        </div>
        <div class="row align-items-center">
            <div class="mb-3 col-12 col-sm-6 px-1">
                <label for="input-room" class="form-label">Nome stanza</label>
                <input class="form-control" disabled value="<?= $event['room_name'] ?>"></input>
            </div>
            <div class="mb-3 col-12 col-sm-6 px-1">
                <label for="input-alert_time" class="form-label">Tempo di preavviso</label>
                <select name="alert_time" id="input-alert_time" class="form-control">
                    <?php foreach (Event::$possibleAlertTimes as $alertTime): ?>
                        <option value="<?= $alertTime ?>" <?= ($event && $event['alert_time'] == $alertTime) ? 'selected' : '' ?>>
                            <?= $alertTime ?> min
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
            
        <div class="row">
            <div class="mb-3 col-12 px-0">
                <div class="d-flex justify-content-center align-items-center mb-3">
                    <h5 class="text-center">Orario prenotazione</h5>
                </div>
                <div id="time-forms-container">
                    <div class="mb-3 row justify-content-center mx-1">
                        <input type="date" class="form-control mb-2" name="date" value="<?= date('Y-m-d', strtotime($event['starting_time'])) ?>">
                        <div class="col-12 col-sm-6 ps-2">
                            <label for="starting_time">Inizio evento</label>
                            <input type="time" class="form-control mb-2" name="starting_time" value="<?= date('H:i', strtotime($event['starting_time'])) ?>">
                        </div>
                        <div class="col-12 col-sm-6 pe-2">
                            <label for="ending_time">Fine evento</label>
                            <input type="time" class="form-control" name="ending_time" value="<?= date('H:i', strtotime($event['ending_time'])) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-danger text-center my-2" id="opening_range_errors"></div>
        <div class="row my-2 justify-content-center">
            <label class="fw-bold form-label">Note evento:</label>
            <textarea name="event_notes" class="col-12" id="event_notes" placeholder="<?= $event['notes'] ? '' : 'Nessuna nota disponibile, inserisci una nota' ?>"><?= $event['notes'] ?? '' ?></textarea>
        </div>
        <div class="d-flex justify-content-center gap-3">
            <button type="submit" class="btn btn-primary">Modifica</button>
            <a href="../reservations" class="btn btn-danger">Chiudi</a>
        </div>

        <div class="text-center mt-3">
            <span class="text-success" id="success_message"></span>
            <span class="text-danger" id="error_message"></span>
        </div>
    </form>

    <?php if ($participants ?? false): ?>
    <h5 class="text-center mt-4">
        
    </h5>
    <div class="text-end">
        <?= sprintf("%d/%d, confermati: %d", $totalParticipants, $event['room_seats'], $confirmedParticipants) ?>
    </div>
    <div class="card users_container">
        <div class="accordion" id="participantsAccordion">
            <?php foreach ($participants as $i => $participant): ?>
                <?php $collapseId = 'participant-collapse-' . $i; ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-<?= $i ?>">
                        <button class="accordion-button collapsed py-1 <?= $participant['confirmed'] ? 'bg-success text-white' : ''?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
                            <?= $participant['email'] ?> <span class="ms-2"><?= $participant['notes'] ? "❗" : "" ?></span>
                        </button>
                    </h2>
                    <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= $i ?>" data-bs-parent="#participantsAccordion">
                        <div class="accordion-body">
                            <div class="row">
                                <div class="col-auto">
                                    <strong>Registrato il: </strong> <?= dateString($participant['created_at']) ?>
                                </div>
                                <div class="col-auto">
                                    <strong>Metodo di accesso: </strong><?= Participant::$accessMethod[$participant['access_way']] ?>
                                </div>
                                <div class="col-auto">
                                    <strong>Confermato: </strong> <?= $participant['confirmed'] ? 'il ' . dateString($participant['updated_at']) : 'No' ?>
                                </div>
                            </div>
                            <div class="row">
                                <strong class="d-block">Note:</strong>
                                <p><?= $participant['notes'] ? $participant['notes'] : 'Nessuna nota disponibile' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    
    <!-- Modal -->
    <div class="modal fade" id="print_tickets_modal" tabindex="-1" aria-labelledby="print_tickets_modal_label" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title text-center" id="print_tickets_modal_label"><?= Lang::getText("print_tickets") ?></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                        <div class="d-flex justify-content-between col-12">
                            <label for="input_checkbox_all" class="me-2 fw-bold">SELEZIONA TUTTI</label>
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllEmailsInPrintArray()">
                        </div>
                    <?php foreach ($participants as $i => $participant): ?>
                        <div class="d-flex justify-content-between col-12" id="container_checkbox_<?= $participant['email'] ?>">
                            <label for="input_checkbox_<?= $i ?>" class="me-2"><?= $participant['email'] ?></label>
                            <input type="checkbox" id="row_checkbox_<?= $participant['email'] ?>" class="toggle_checkbox" onclick="toggleEmailInPrintArray('<?= $participant['email'] ?>')">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= Lang::getText("general:close") ?></button>
                    <button type="button" class="btn btn-primary" id="print_btn"><?= Lang::getText("print_tickets") ?></button>
                </div>
                <div class="toast position-absolute align-items-center text-white border-0 rounded-0 opacity-9" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="toast-body text-white fw-bold">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <h5 class="fw-bold text-center">Nessun partecipante disponibile</h5>
    <?php endif; ?>
    
</div>

<?php include_once __DIR__ . '/../../template/footer.php'; ?>

<script>
    const emails = [];
    const event_id = <?= $event['event_id'] ?>;
    const toast = document.querySelector('.toast');
    const toastBody = document.querySelector('.toast-body');
    let toast_html = '';

    const button = document.querySelector('.copy_button');
    button.addEventListener('click', function (e) {
        const input = document.querySelector('.hidden_link');
        const link = input.value;
        button.classList.remove("btn-success");
        button.classList.add("btn-secondary");
        button.innerHTML = "Link Copiato";

        navigator.clipboard.writeText(link)
            .then(() => {
                setInterval(() => { 
                    button.classList.remove("btn-secondary");
                    button.classList.add("btn-success");
                    button.innerHTML = "Copia il link";
                }, 5000);
            })
            .catch(err => {
                console.error('Errore nella copia: ', err);
            });
    })

    document.getElementById('reservation_details_form').addEventListener('submit', function (e) {
        if (confirm('Confermi di voler modificare la tua prenotazione?')) {
            e.preventDefault();
            e.stopPropagation();
            const form = e.target;
            const data = new FormData(form);
    
            fetch('/users/reservations/reservationDetails.php', {
                method: "POST",
                body: data
            }).then(async (res) => {
                const response = await res.json();
                const successBox = document.getElementById('success_message');
                const errorBox = document.getElementById('error_message');

                if (response.success) {
                    errorBox.innerHTML = '';
                    successBox.innerHTML = response.message;
                } else {
                    errorBox.innerHTML = response.message;
                    successBox.innerHTML = '';
                }
            })
        }
    })

    function toggleAllEmailsInPrintArray() {
        const mainCheckbox = document.getElementById('selectAllCheckbox');
        const checkboxes = document.querySelectorAll('.modal input.toggle_checkbox');
        
        if (mainCheckbox.checked) {
            emails.length = 0;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
                
                const email = checkbox.id.replace('row_checkbox_', '');
                
                if (!emails.includes(email)) {
                    emails.push(email);
                }
            });
        } else {
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            emails.length = 0;
        }
        
    }

    const mainCheckbox = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.modal input.toggle_checkbox');
    const checkedBoxes = document.querySelectorAll('.modal input.toggle_checkbox:checked');

    function updateMainCheckboxState() {
        
        if (checkboxes.length === checkedBoxes.length && checkboxes.length > 0) {
            mainCheckbox.checked = true;
            mainCheckbox.indeterminate = false;
        } else if (checkedBoxes.length === 0) {
            mainCheckbox.checked = false;
            mainCheckbox.indeterminate = false;
        } else {
            mainCheckbox.checked = false;
            mainCheckbox.indeterminate = true;
        }
    }

    function toggleEmailInPrintArray(email) {
        if (event) event.stopPropagation();
        
        const row_checkbox = document.getElementById('row_checkbox_' + email);
        
        if (emails.includes(email)) {
            let index = emails.indexOf(email);
            emails.splice(index, 1);
            row_checkbox.checked = false;
        } else {
            emails.push(email);
            row_checkbox.checked = true;
        }
        
        updateMainCheckboxState();
    }

    const print_btn = document.getElementById('print_btn');
    
    print_btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const data = new FormData();
        data.append('emails', emails);
        data.append('event_id', event_id)
        data.append('printing_tickets', true);

        if (emails.length == 0) {
            toast.classList.add('show', 'bg-danger');
            toastBody.innerHTML = 'Nessuna email selezionata';

            setTimeout(() => {
                toast.classList.remove('show', 'bg-danger');
                toastBody.innerHTML = '';
            }, 3000);
            return;
        }
        fetch('/users/reservations/reservationDetails.php', {
            method: "POST",
            body: data
        }).then(async (res) => {
            if (!res.ok) throw new Error('Errore nel generare il PDF');

            const blob = await res.blob();

            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'event_tickets.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            toast.classList.add('show', 'bg-success');
            toastBody.innerHTML = 'Biglietti scaricati correttamente';

            setTimeout(() => {
                toast.classList.remove('show', 'bg-success');
                toastBody.innerHTML = '';
            }, 3000);
        }).catch(err => {
            console.error(err);
            toast.classList.add('show', 'bg-danger');
            toastBody.innerHTML = 'Errore nel download dei biglietti';

            setTimeout(() => {
                toast.classList.remove('show', 'bg-danger');
                toastBody.innerHTML = '';
            }, 3000);
        });
    })
</script>

