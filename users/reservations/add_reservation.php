<?php
include __DIR__ . '/../../config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['event_created'] ?? false) {
    unset($_POST['event_created']);
    $rooms = Room::select([
        "where" => sprintf("id = %d", $_POST['room_id'])
    ]);
    $room = $rooms[0];
    $users = User::select([
        "where" => sprintf("id = %d", $_POST['user_id'])
    ]);
    $user = $users[0];
    
    $isAvailable = Event::checkRanges($_POST['starting_time'], $_POST['ending_time'], $_POST['room_id'], $gap = true);
    if ($_POST['guestsEmail'] ?? false) {
        $partecipantsArr = explode(",", $_POST['guestsEmail']);
        if (count($partecipantsArr) > $room['seats']) {
            $guestsEmailArray = [$user['email']];
            $errors['guestsEmail'] = "Inseriti più indirizzi email di quelli consentiti nella stanza. Riprova.";
        }
    }

    if (empty($errors)) {
        Event::createAndSendEmail($_POST);
        $events = Event::select([
            "where" => sprintf("starting_time = '%s' AND ending_time = '%s'", $_POST['starting_time'], $_POST['ending_time'])
        ]);
        if ($events ?? false) {
            $event = $events[0];
            echo json_encode(["success" => 1, "message" => "Prenotazione creata con successo", "data" => $event]);
        } else {
            echo json_encode(["success" => 0, "message" => "Errore nella creazione dell'evento."]);
        }
        exit();
    } else {
        echo json_encode(["success" => 0, "message" => $errors]);
        exit();
    }
    
}
$today = date('Y-m-d');
$now = date('H:i');
$guestsEmailArray = [$user['email']] ?? [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['time_changed'] ?? false) {
    include_once __DIR__ . '/reservation_range.php';
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['new_event'] ?? false) {
    $rooms = Room::select([
        "where" => sprintf("id = %s", $_POST['room_id'])
    ]);
    $room = $rooms[0];
}

$pageTitle = "Aggiungi prenotazione";
include_once __DIR__ . '/../../template/header.php';

?>

<div id="reserve_form">
    <h3 class="text-center mt-2">
        <?= $pageTitle ?>
    </h3>
    <div class="row justify-content-center position-relative">
        <div class="col-4 col-sm-6">
            <label for="input_date" class="fw-bold text-center">Inserisci una data</label>
            <input type="date" id="input_date" class="form-control" value="<?= $today ?>">
        </div>
        <div id="error_message" class="text-center mt-3 fw-bold"></div>
        <div id="calendar" class="w-100 h-100 ec-dark"></div>
        <div class="toast reservation_toast position-absolute align-items-center text-white border-0 rounded-0 opacity-9" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex justify-content-center align-items-center">
                <div class="toast-body text-white fw-bold">
                </div>
            </div>
        </div>
    </div>
</div>

<div id="event_create_modal" class="d-none">
    <div class="modal fade show d-block mx-auto" id="reservationModal" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" id="modal_content">
                
            </div>
        </div>
    </div>
</div>



<?php include_once __DIR__ . '/../../template/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/@event-calendar/build@4.3.0/dist/event-calendar.min.js"></script>
<script>
    let guestsEmailArray = <?php echo json_encode($guestsEmailArray); ?>;
    let excludedGuests = [];
    const reservation_toast = document.querySelector('.reservation_toast');
    const reservation_toastBody = document.querySelector('.toast-body');
    let toast_html = '';


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

    function remove(index) {
        guestsEmailArray.splice(index, 1);
        render();
    }
    const input_date = document.getElementById('input_date');
    const calendarEl = document.getElementById('calendar');

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

    function fetchData() {
        const data = new FormData();
        data.append("date", input_date.value);
        data.append("time_changed", true);
        
        fetch('/users/reservations/reservation_range.php', {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();
            console.log(response);
            calendarEl.innerHTML = '';

            if (response.success) {
                const rooms = [];
                for (let i = 0; i < response.rooms.length; i++) {
                    rooms.push({
                        id: response.rooms[i].id,
                        title: response.rooms[i].name,
                        time_frame: response.rooms[i].time_frame
                    });
                }
                
                const unavailabilities = [];
                if (response.unavailabilities && Array.isArray(response.unavailabilities)) {
                    for (let i = 0; i < response.unavailabilities.length; i++) {
                        const unavailability = response.unavailabilities[i];
                        unavailabilities.push({
                            title: 'Non disponibile',
                            start: new Date(unavailability.start),
                            end: new Date(unavailability.end),
                            resourceId: unavailability.room_id,
                            backgroundColor: '#acacac',
                            borderColor: '#acacac',
                            textColor: '#000000'
                        });
                    }
                }
                
                const calendar = EventCalendar.create(calendarEl, {
                    view: 'resourceTimelineDay',
                    timeZone: 'local',         
                    date: response.date,
                    scrollTime: '<?= $now ?>',
                    editable: false,
                    eventStartEditable: false,
                    eventDurationEditable: false,
                    eventResizableFromStart: false,
                    eventContent: {html: '&nbsp;'},
                    views: {
                        resourceTimelineDay: {
                            slotDuration: '00:15:00',
                            slotLabelInterval: '01:00:00',
                            slotMinTime: '00:00:00',
                            slotMaxTime: '24:00:00',
                            slotWidth: 16,
                            resources: rooms,
                        }
                    },
                    headerToolbar: { start: '', center: '', end: '' },
                    height: '50%',
                    dayMaxEvents: true,
                    selectable: true,
                    events: unavailabilities,
                    select: function(info) {
                        
                        isOverlapped = false;
                        isPassed = false;
                        isRangeSmaller = false;
                        for (let i = 0; i < unavailabilities.length; i++) {
                            if (info.resource.id != unavailabilities[i].resourceId) continue;
                            if ((info.start > unavailabilities[i].start && info.start < unavailabilities[i].end) ||
                                (info.end > unavailabilities[i].start && info.end < unavailabilities[i].end) ||
                                (info.start <= unavailabilities[i].start && info.end >= unavailabilities[i].end))
                                {   
                                    isOverlapped = true;
                                    reservation_toast.classList.add('show');
                                    reservation_toast.classList.add('bg-danger');
                                    reservation_toastBody.innerHTML = 'Impossibile creare l\'evento nella stanza selezionata: fascia non disponibile. Seleziona un\'altra fascia.';
                                    setTimeout(() => {
                                        reservation_toast.classList.remove('show', 'bg-danger');
                                        reservation_toastBody.innerHTML = '';
                                    }, 5000);
                                    break;
                            } else if (info.start < new Date()) {
                                isPassed = true;
                                reservation_toast.classList.add('show');
                                reservation_toast.classList.add('bg-danger');
                                reservation_toastBody.innerHTML = 'Impossibile creare un evento nel passato.';
                                setTimeout(() => {
                                    reservation_toast.classList.remove('show', 'bg-danger');
                                    reservation_toastBody.innerHTML = '';
                                }, 5000);
                                break;
                            }
                        }
                        
                        for (let i = 0; i < rooms.length; i++) {
                            if ((rooms[i].id == info.resource.id) && ((info.end - info.start) < (rooms[i].time_frame * 60000))) {
                                isRangeSmaller = true;
                                reservation_toast.classList.add('show');
                                reservation_toast.classList.add('bg-danger');
                                reservation_toastBody.innerHTML = 'Impossibile creare un evento più piccolo di un singolo blocco per la stanza. Il blocco per questa stanza è di ' + rooms[i].time_frame + ' minuti.';
                                setTimeout(() => {
                                    reservation_toast.classList.remove('show', 'bg-danger');
                                    reservation_toastBody.innerHTML = '';
                                }, 5000);
                                break;
                            }
                        }
                        if (!isPassed && !isOverlapped && !isRangeSmaller) {
                            const data = new FormData();
                            data.append('start', info.startStr);
                            data.append('end', info.endStr);
                            data.append('room_id', info.resource.id);
                            data.append('new_event', true);
                            fetch('/users/reservations/modal.php', {
                                method: "POST",
                                body: data
                            }).then(async (res) => {
                                let modalResponse = await res.text();
                                const modal = document.getElementById('event_create_modal');
                                modal.classList.remove('d-none');
                                const modal_content = document.getElementById('modal_content');
                                setInnerHtml(modal_content, modalResponse);

                                const txt = document.getElementById('input_mails');
                                const list = document.getElementById('list');
                                const counter = document.getElementById('counter');
                                counter.innerHTML = guestsEmailArray.length;
                                
                                
                                render();

                                txt.addEventListener('keyup', function(e) {
                                    e.preventDefault();
                                    if (e.key === 'Enter') {
                                        addParticipant();
                                    }
                                });

                                window.onload = function() {
                                    render();
                                    txt.focus();
                                };

                            })
                        }
                    }
                });
            } else {
                document.getElementById('error_message').innerHTML = response.message;
            }
            
            


            
        }).catch(error => {
            console.error('Errore nel caricamento dei dati:', error);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        fetchData();
    });
    
    input_date.addEventListener('change', fetchData);

    function closeModal() {
        const modal = document.getElementById('event_create_modal');
        modal.classList.add('d-none');
        const modal_content = document.getElementById('modal_content');
        modal_content.innerHTML = '';
        guestsEmailArray = <?php echo json_encode($guestsEmailArray); ?>;
    }

    function addParticipant() {
        const participant = document.getElementById('input_mails');
        const seats = document.getElementById('room_seats').value;

        let val = participant.value.trim();
        val = val.split(/[\s,;–-]+/);
        if (val != '') {
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
                    console.log(excludedGuests);
                }
            }
        } else {
            modal_toast.classList.add('show');
            modal_toast.classList.add('bg-danger');
            modal_toastBody.innerHTML = "Inserisci un indirizzo email";
        }
    }
</script>