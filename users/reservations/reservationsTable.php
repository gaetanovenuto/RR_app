<?php
include __DIR__ . '/../../config.php';
if ($_GET['reservation_create'] ?? false) {
    if ($_GET['reservation_create'] == 'success') {
        $successMessage = "Prenotazione creata correttamente";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['joined_events'] ?? false)) {
    $gaps = json_decode($_POST['gaps'], true);
    $events = json_decode($_POST['events'], true);

    $guests = explode(",", $_POST['guestsEmail']);
    $allGuests = explode(",", $_POST['allGuests']);

    $excludedGuests = array_diff($allGuests, $guests);

    $joinable = Event::check_if_joinable($gaps);
    if ($joinable) {
        foreach ($events as $event) {
            $events_to_delete[] = $event['id']; 
        }

        if ($excludedGuests ?? false) {
            foreach ($events as $event) {
                $event_guests = explode(",", $event['guestsEmail']);
                foreach ($excludedGuests as $excludedGuest) {
                    if (in_array($excludedGuest, $event_guests)) {
                        $excludedGuestsWithEvents[] = [
                            "email" => $excludedGuest,
                            "event_id" => $event['id']
                        ];
                    }
                }
            }
        }

        if ($events_to_delete ?? false) {
            
            unset($_POST['events']);
            unset($_POST['gaps']);
            unset($_POST['allGuests']);
            unset($_POST['joined_events']);
            if ($excludedGuests ?? false) {
                Event::joinEvents($_POST, $excludedGuestsWithEvents);
            } else {
                Event::joinEvents($_POST);
            }
            Participant::delete(sprintf("event_id IN (%s)", implode(",", $events_to_delete)));
            Event::delete(sprintf("id IN (%s)", implode(",", $events_to_delete)));

            $evs = Event::select([
                "where" => sprintf("starting_time = '%s' AND ending_time = '%s'", $_POST['starting_time'], $_POST['ending_time'])
            ]);
            if ($evs ?? false) {
                $ev = $evs[0];
                echo json_encode(["success" => 1, "message" => "Prenotazione creata con successo", "data" => $ev]);
            } else {
                echo json_encode(["success" => 0, "message" => "Errore nella creazione dell'evento."]);
            }
            exit();
        } else {
            echo json_encode(["success" => 0, "message" => $errors]);
            exit();

        }
    }

    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['joining_events'] ?? false)) {
    $events = Event::select([
        "columns" => "events.id, events.user_id as user_id, events.name, events.guestsEmail, events.starting_time, events.ending_time, events.alert_time, events.access_key, COUNT(participants.id) as participants, rooms.id as room_id, rooms.seats, DATE(events.starting_time) as event_date",
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
        "where" => sprintf("events.id IN (%s)", $_POST['events_id']),
    ]);

    $is_same_date = false;
    $day_events = [];
    for ($i = 0; $i < count($events); $i++) {
        for ($j = $i + 1; $j < count($events); $j++) {
            if ($events[$i]['event_date'] != $events[$j]['event_date']) {
                break 2;
            } else {
                $is_same_date = true;
                $day_events[] = [$events[$i], $events[$j]];
            }
        }
    }

    if ($is_same_date) {
        if ($day_events ?? false) {
            $joinable = Event::check_if_joinable($day_events);
            
            if ($joinable) {
                $starting_times = array_column($events, 'starting_time');
                array_multisort($starting_times, SORT_ASC, $events);
                echo json_encode([
                    "success" => 1,
                    "message" => "È possibile unire gli eventi selezionati",
                    "events" => $events,
                    "gaps" => $day_events
                ]);
            } else {
                echo json_encode([
                    "success" => 0,
                    "message" => "Si è verificato un errore nell'unione degli eventi"
                ]);
            }
        } 
    } else {
        echo json_encode([
            "success" => 0,
            "message" => "Impossibile unire eventi in date diverse o se ci sono altri eventi o non disponibilità in mezzo"
        ]);
    }
    exit();
}

$guestsEmailArray = [$user['email']] ?? [];

$rooms = Room::select();
$roomArray = [];
foreach ($rooms as $key => $room) {
    $roomArray[$room['id']] = $room['opening_range'];
}

$pageTitle = 'LE TUE PRENOTAZIONI';
include_once __DIR__ . '/../../template/header.php';
?>
<div class="row w-100 mx-2">
    <h3 class="my-1 text-center fw-bold">
        <?= $pageTitle ?>
    </h3>
    <div id="tableContent" class="position-relative px-0">
        <?php include_once 'reservationsTableContent.php' ?>
        <div id="modal_ranges" style="visibility: hidden;">
            <div class="modal_ranges_body">
                <h5 class="text-center">
                    
                </h5>
            </div>
        </div>
    </div>
    <div class="modal fade" id="join_modal" tabindex="-1" aria-labelledby="join_modal_label" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-fullscreen modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content overflow-y-auto">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="join_modal_label">Unisci eventi</h1>
                </div>
                <form method="POST" id="join_events_form" onkeydown="return event.key != 'Enter';">
                    <div class="modal-body join_modal_body">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-sm btn-success">Unisci</button>
                        <button type="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal" onclick="window.reloadPage()">Annulla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="toast position-absolute align-items-center text-white border-0 rounded-0 opacity-9" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex justify-content-center align-items-center">
            <div class="toast-body text-white fw-bold">
            </div>
        </div>
    </div>
</div>

<?php 
    include_once __DIR__ . '/../../template/footer.php';
?>
<script>
// GENERIC FUNCTIONS
let key = 'id';
let orderDirection = 'ASC';
let page = 1;
let perPage = 10;

const toast = document.querySelector('.toast');
const toastBody = document.querySelector('.toast-body');
const modal = document.getElementById('modal_ranges');
const modal_body = document.querySelector('.modal_ranges_body');
const join_modal = document.getElementById('join_modal');
const join_modal_body = document.querySelector('.join_modal_body');
let guestsEmailArray = <?php echo json_encode($guestsEmailArray); ?>;

let excludedGuests = [];


function validateEmail(email) {
    var re = /\S+@\S+\.\S+/;
    return re.test(email);
}

function render() {
    list.innerHTML = '';
    guestsEmailArray.forEach((item, index) => {
        if (item != '<?= $user['email'] ?>') {
            list.innerHTML += `<li class="btn btn-sm btn-outline-dark d-flex justify-content-between align-items-center m-1"><span>${item}</span><button type="button" class="btn btn-close btn-sm text-danger" onclick="remove(${index})"></button></li>`;
        } else {
            list.innerHTML += `<li class="btn btn-sm btn-secondary d-flex justify-content-between align-items-center m-1"><span>${item}</span></li>`;
        }
    });
    counter.innerText = guestsEmailArray.length;
}

function remove(index, remove = false) {
    const removedEmail = guestsEmailArray[index];
    guestsEmailArray.splice(index, 1);
    let input_box = document.getElementById('input_box_' + removedEmail);
    let checkbox = document.getElementById('checkbox_' + removedEmail);
    input_box.classList.remove('d-none');
    input_box.classList.add('d-flex');
    checkbox.checked = false;
    render();
}

function setInnerHtml(elm, html) {
    elm.innerHTML = html;
    Array.from(elm.querySelectorAll("script")).forEach(oldScript => {
        const newScript = document.createElement("script");
        Array.from(oldScript.attributes)
            .forEach(attr => newScript.setAttribute(attr.name, attr.value));
        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
        oldScript.parentNode.replaceChild(newScript, oldScript);
    });
}

async function reloadTable(response = null) {
    const data = new FormData();
    data.append("orderTable", true);
    data.append('orderBy', key);
    data.append('orderDirection', orderDirection);
    data.append('page', parseInt(page)); 
    data.append('perPage', parseInt(perPage));
    data.append('response', JSON.stringify(response));

    fetch('/users/reservations/reservationsTableContent.php', {
        method: 'POST',
        body: data
    }).then(async (res) => {
        if (!res.ok) {
            errorBox.innerHTML = 'Errore nel caricamento dei dati';
            return;
        }
        const tableData = await res.text();
        let tableContent = document.getElementById('tableContent');
        tableContent.innerHTML = tableData;
        
        if (response ?? false) {
            toast.classList.add('show');
            if (response.success) {
                toast.classList.add('bg-success');
            } else {
                toast.classList.add('bg-danger');
            }
            toastBody.innerHTML = response.message;
    
            setTimeout(() => {
                toast.classList.remove('show', 'bg-success', 'bg-danger');
            }, 3000);
        }
    })
}

function addParticipant(id = null, email = null, remove = false) {
    const participant = document.getElementById('input_mails');
    const seats = document.getElementById('room_seats').value;
    if (remove) {
        if (guestsEmailArray.length < seats) {
            guestsEmailArray.push(email);
            let checkbox_box = document.getElementById('input_box_' + email)
            checkbox_box.classList.remove('d-flex');
            checkbox_box.classList.add('d-none');
            render();
        } else {
            modal_toast.classList.add('show');
            modal_toast.classList.add('bg-danger');
            modal_toastBody.innerHTML = "Raggiunta capacità massima";

            setTimeout(() => {
                toast.classList.remove('show', 'bg-success', 'bg-danger');
            }, 3000);
        }
    }

    let val = participant.value.trim();
    val = val.split(/[\s,;–-]+/);
    if (val != '' || remove) {
        if (!remove) {
            if (guestsEmailArray.includes(val)) {
            modal_toast.classList.add('show');
            modal_toast.classList.add('bg-danger');
            modal_toastBody.innerHTML = "Indirizzo email già inserito.";
        } else {
            if (Array.isArray(val)) {
                val.forEach((item, key) => {
                    if (guestsEmailArray.includes(item)) {
                        modal_toast.classList.add('show');
                        modal_toast.classList.add('bg-danger');
                        modal_toastBody.innerHTML = "Indirizzo email già inserito.";
                        return;
                    }

                    if (guestsEmailArray.length == seats) {
                        excludedGuests.push(item);
                        modal_toastBody.innerHTML = `
                        <div class="toast reservation_toast position-absolute align-items-center text-white border-0 rounded-0 opacity-9 show bg-danger" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="d-flex justify-content-center align-items-center">
                                <div class="toast-body text-white fw-bold">
                                    <div class="bg-danger text-white p-2">
                                        <small>Attenzione: Raggiunta capacità massima. Indirizzi email non inseriti:</small>
                                        <ul class="list-unstyled toast_list mt-2 mb-0">
                                            
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                        let toast_list = document.querySelector('.toast_list');
                        excludedGuests.forEach((excludedGuest) => {
                            toast_list.innerHTML += `
                            <li><small>${excludedGuest}</small></li>
                            `
                        });
                        setTimeout(() => {
                            modal_toast.classList.remove('show', 'bg-danger');
                            modal_toastBody.innerHTML = '';
                        }, 8000);
                    } else if (validateEmail(item)) {
                        guestsEmailArray.push(item);
                        render();
                        participant.value = '';
                        participant.focus();
                    } else {
                        modal_toast.classList.add('show');
                        modal_toast.classList.add('bg-danger');
                        modal_toastBody.innerHTML = item + " non è un indirizzo email valido.";
                        setTimeout(() => {
                            modal_toast.classList.remove('show', 'bg-danger');
                            modal_toastBody.innerHTML = '';
                        }, 8000);
                    }
                })
            }
        }
        }
        
    } else {
        modal_toast.classList.add('show');
        modal_toast.classList.add('bg-danger');
        modal_toastBody.innerHTML = "Inserisci un indirizzo email";

        setTimeout(() => {
            modal_toast.classList.remove('show', 'bg-danger');
            modal_toastBody.innerHTML = '';
        }, 3000);
    }
}

function reloadPage() {
    location.reload();
}

</script>



