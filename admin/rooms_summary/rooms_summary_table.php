<?php
include __DIR__ . '/../../config.php';
$logTitle = "Rooms summary table";
if (User::isAdmin()) {
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

$today = date('Y-m-d');
$roomExists = false;
$error_message = "Stanza non trovata, inserisci un id numerico esistente.";

if (($_GET['id'] ?? false) && is_numeric($_GET['id'])) {
    $rooms = Room::select([
        "where" => sprintf("id = %s", $_GET['id'])
    ]);
    if ($rooms) {
        $roomExists = true;
        $room = $rooms[0];
        $pageTitle = $room['name'];
    }
}
$pageTitle = "Sommario prenotazioni";
include_once __DIR__ . '/../../template/header.php';
?>
<?php if (!$deniedAccess): ?>
    <?php if ($roomExists ?? false): ?>
        <div class="row flex-column w-75">
            <h5 class="text-center mt-2">
                <?= $pageTitle ?>
            </h5>
            <div class="row justify-content-between align-items-center mt-2">
                <!-- Pagina precedente -->
                <a href="/rooms_table" class="btn btn-sm btn-secondary rounded-50 col-auto">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div class="col-6">
                    <label for="input_date" class="fw-bold text-center">Inserisci una data</label>
                    <input type="hidden" id="room_id" name="room_id" value="<?= $_GET['id'] ?>">
                    <input type="date" id="input_date" class="form-control" value="<?= $today ?>">
                </div>
                <button type="button" class="col-auto btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">Esporta</button>

                <!-- Modal -->
                 <div class="modal fade" id="exportModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
                     <div class="modal-dialog">
                         <div class="modal-content">
                             <div class="modal-header">
                                <h5 class="modal-title" id="exportModalLabel">Esporta riepilogo prenotazioni</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                 <div class="toast position-absolute align-items-center text-white border-0 rounded-0 opacity-9" role="alert" aria-live="assertive" aria-atomic="true">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <div class="toast-body text-white fw-bold">
                                        </div>
                                    </div>
                                </div>   
                                <form method="POST" id="export_form" action="">
                                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                    <input type="hidden" name="export_form" value="true">
                                    <div class="row justify-content-between">
                                        <div class="col-12 col-md-6">
                                            <label for="starting_date" class="form-label fw-bold">
                                                Inizio
                                            </label>
                                            <input type="date" name="starting_date" id="starting_date" class="form-control form-control-sm" onchange="checkDates()">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="ending_date" class="form-label fw-bold">
                                                Fine
                                            </label>
                                            <input type="date" name="ending_date" id="ending_date" class="form-control form-control-sm" onchange="checkDates()">
                                        </div>
                                        <div class="col-12 text-danger">
                                            <small id="error_dates"></small>
                                        </div>
                                       
                                    </div>
                                    <div class="row buttons justify-content-end gap-2 m-2">
                                        <button type="button" class="btn btn-secondary btn-sm col-auto" data-bs-dismiss="modal">Chiudi</button>
                                        <button type="submit" class="btn btn-primary btn-sm col-auto" id="export_btn">Esporta</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <h5 class="text-center text-danger mt-2" id="error_message"></h5>
            <div id="timeGridDayCalendar"></div>
        </div>
        
    <?php else: ?>
            <?php include_once __DIR__ . '/../../users/404.php' ?>
    <?php endif; ?>
<?php else: ?>
    <?php include_once __DIR__ . '/../../users/access_denied.php' ?>
<?php endif; ?>

<?php include_once __DIR__ . '/../../template/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/@event-calendar/build@4.3.0/dist/event-calendar.min.js"></script>
<script>
const input_date = document.getElementById('input_date');
const calendarEl = document.getElementById('timeGridDayCalendar');
const room_id = document.getElementById('room_id').value;
const toast = document.querySelector('.toast');
const toastBody = document.querySelector('.toast-body');


function fetchData() {
    const data = new FormData();
    data.append("date", input_date.value);
    data.append("room_id", room_id);
    data.append("time_changed", true);

    fetch('/admin/rooms_summary/rooms_summary_table_content.php', {
        method: "POST",
        body: data
    }).then(async (res) => {
        const response = await res.json();
        calendarEl.innerHTML = '';
        document.getElementById('error_message').innerHTML = '';
        if (response.success) {
            const events = [];
            if (response.events && Array.isArray(response.events)) {
                for (let i = 0; i < response.events.length; i++) {
                    const event = response.events[i];
                    events.push({
                        id: event.id,
                        title: event.event_name,
                        start: new Date(event.starting_time),
                        end: new Date(event.ending_time),
                        resourceId: event.room_id,
                        backgroundColor: '#43a520',
                        borderColor: '#43a520',
                        textColor: '#FFFFFF',
                    });
                    
                }
            }
            const calendar = EventCalendar.create(calendarEl, {
                view: 'resourceTimeGridDay',
                timeZone: 'local',         
                date: response.date,
                headerToolbar: { start: '', center: '', end: '' },
                editable: false,
                eventStartEditable: false,
                eventDurationEditable: false,
                eventResizableFromStart: false,
                events: events,
                height: '75%',
                allDaySlot: false,
                slotDuration: '00:15:00',
                slotLabelInterval: '01:00:00',
                resources: [
                    {
                        id: response.room_id,
                        title: response.room_name
                    }
                ],
                eventClick: function(info) {
                    window.location.href = "<?= $_ENV['APP_URL'] ?>" + '/reservation_details?id=' + info.event.id;
                }
            });
        } else {
            document.getElementById('error_message').innerHTML = response.message;
        }
        
                
    })
}

fetchData();

input_date.addEventListener('change', () => {
    fetchData()
});

function checkDates() {
    const starting_date = document.getElementById('starting_date').value;
    const ending_date = document.getElementById('ending_date').value;
    const button = document.getElementById('export_btn');
    const error_dates = document.getElementById('error_dates');
    error_dates.innerHTML = '';
    button.disabled = false;

    if (ending_date) {
        if (starting_date > ending_date) {
            error_dates.innerHTML = "Attenzione: la data finale inserita è precedente a quella iniziale.";
            button.disabled = true;
        }
    }
}

document.getElementById('export_form').addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const form = e.target;
    const data = new FormData(form);

    fetch('/admin/rooms_summary/export_excel.php', {
        method: "POST",
        body: data
    })
    .then(async res => {
        if (!res.ok) {
            const error = await res.json().catch(() => ({ error: 'Errore sconosciuto' }));
            toast.classList.add('show', 'bg-danger');
            toastBody.innerHTML = error.error;
            setTimeout(() => {
                toast.classList.remove('show', 'bg-success', 'bg-danger');
            }, 3000);
            return;
        }

        return res.blob();
    })
    .then(blob => {
        if (!blob) return;
        const a = document.createElement('a');
        const url = window.URL.createObjectURL(blob);
        a.href = url;
        a.download = 'riepilogo_prenotazioni.xlsx';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    })
    .catch(err => {
        toast.classList.add('show', 'bg-danger');
        toastBody.innerHTML = "Attenzione: si è verificato un errore nell'esportazione";
        setTimeout(() => {
            toast.classList.remove('show', 'bg-success', 'bg-danger');
        }, 3000);
    });
});

</script>