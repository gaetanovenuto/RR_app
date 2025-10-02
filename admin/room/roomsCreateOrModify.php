<?php
include __DIR__ . '/../../config.php';
Room::setWeekDays();

$logTitle = "Rooms create/update";
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
$room = null;
$successMessage = '';
$errorMessage = '';
$errors = [];

$roomExists = false;
$new = false;
$error_message = "Stanza non trovata, inserisci un id numerico esistente.";

if (($_GET['id'] ?? false)) {
    if (is_numeric($_GET['id'])) {
        $room = Room::getSingleData($_GET['id']);
        if ($room) {
            $roomExists = true;
            if (isset($room['opening_range'])) {
                if (is_string($room['opening_range'])) {
                    $room['opening_range'] = json_decode($room['opening_range'], true);
                }
                if (!is_array($room['opening_range'])) {
                    $room['opening_range'] = [];
                }
            } else {
                if ($room) {
                    $room['opening_range'] = [];
                }
            }
        }
    }

    
} else {
    $new = true;
}

// Array vuota dove pusherò gli index
$indexes = [];

if (($_SERVER['REQUEST_METHOD'] == 'POST') && ($_POST['submitting'] ?? false)) {
    $updatedData = [
        "name" => $_POST['name'],
        "seats" => $_POST['seats'],
        "opening_range" => $_POST['opening_range'],
        "time_frame" => $_POST['time_frame'],
        "availability" => isset($_POST['availability']) ? 1 : 0,
        "reservation_gap" => $_POST['reservation_gap'],
    ];

    if ($_POST['id'] ?? false) {
        $updatedData['id'] = $_POST['id'];
        $room = Room::getSingleData($_POST['id']);
    }

    if (empty($errors)) {
        if ($_POST['id'] ?? false) {
            $responseMessage = "Stanza correttamente modificata";
            $response = Room::update($updatedData, "id = " . $_POST['id']);
            Log::create([
                [
                    "user_id" => $_SESSION['id'],
                    "action_type" => "Stanza modificata",
                    "ip_address" => $_SERVER['REMOTE_ADDR'],
                    "details" => "L'utente ha modificato la stanza con id: {$_POST['id']}",
                    "level" => 1
                ]
            ]);
        } else {
            $responseMessage = "Stanza creata correttamente";
            $response = Room::create([$updatedData]);
            Log::create([
                [
                    "user_id" => $_SESSION['id'],
                    "action_type" => "Stanza creata",
                    "ip_address" => $_SERVER['REMOTE_ADDR'],
                    "details" => "L'utente ha creato una nuova stanza",
                    "level" => 1
                ]
            ]);
        }
        if (!is_array($response)) {
            echo json_encode(["success" => 1, "message" => $responseMessage]);
        } else {
            $resErrors = array_values($response);
            echo json_encode(["success" => 0, "errors" => $resErrors]);
        }
    } else {
        echo json_encode(["success" => 0, "errors" => $errors]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['time_changed'] ?? false) {
    // Prendo tutte le stanze con l'id interessato
    $rooms = Room::select([
        "where" => sprintf("id = %d", $_POST['room_id'])
    ]);
    if ($rooms ?? false) {
        // Prendo la prima stanza
        $room = $rooms[0];
        // Costruisco il range che mi interessa controllare
        $range_to_check = [
            "start" => $_POST['time_start'],
            "end" => $_POST['time_end'],
            "days" => explode(",", $_POST['days'])
        ];
        // Trasformo in array di array il json del db
        $room_ranges = json_decode($room['opening_range'], true);

        // Per ogni range da controllare prendo un range alla volta
        foreach ($room_ranges as $key => $range) {
            // Se l'index del range che ho cambiato è diverso dall'index del range che sto controllando
            if ($key != $_POST['range_index']) {
                // Per ogni giorno del range che vado a controllare
                foreach ($range['days'] as $day) {

                    // Se il giorno è tra i giorni del range che ho cambiato
                    if (in_array($day, $range_to_check['days'])) {
                        if (
                            // Se l'inizio del range che ho cambiato è incluso tra l'inizio e la fine del range da controllare
                            ($range_to_check['start'] >= $range['start'] && $range_to_check['start'] <= $range['end']) ||

                            // Oppure il range che ho cambiato inizia prima e finisce dopo il range da controllare
                            ($range_to_check['start'] < $range['start'] && $range_to_check['end'] > $range['end']) ||

                            // Oppure la fine del range che ho cambiato è incluso tra l'inizio e la fine del range da controllare
                            ($range_to_check['end'] >= $range['start'] && $range_to_check['end'] <= $range['end'])
                        ) {
                            if (!in_array($day, $indexes)) {
                                $indexes[] = intval($day);
                            }
    
                            
                        }
                    }
                }
            }
        }
        if ($indexes ?? false) {
            echo json_encode([
                "success" => 0,
                "days" => $indexes,
                "message" => "Attenzione: sono stati rilevati giorni con orari sovrapposti"
            ]);
        } else {
            echo json_encode([
                "success" => 1,
                "message" => "Nessun orario sovrapposto è stato rilevato"
            ]);
        }
    }
    exit();
}


$pageTitle = $room ? 'Modifica Stanza' : 'Crea Nuova Stanza';
include_once __DIR__ . '/../../template/header.php';
?>
<?php if (!$deniedAccess): ?>
    <?php if ($roomExists || $new == true): ?>
    <div class="container mt-4">
        <div class="toast position-absolute align-items-center text-white border-0 rounded-0 opacity-9" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex justify-content-center align-items-center">
                <div class="toast-body text-white fw-bold">
                </div>
            </div>
        </div>

        <h3 class="text-center mb-4"><?= $pageTitle ?></h3>

        <form method="POST" id="update_form">
            <input type="hidden" name="id" value="<?= htmlspecialchars($room['id'] ?? '') ?>" id="room_id">

            <div class="row align-items-center px-2 justify-content-between">
                <div class="mb-3 col-12 col-sm-3 col-md-4">
                    <label for="input-name" class="form-label">Nome</label>
                    <input type="text" name="name" id="input-name"
                        class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($room['name'] ?? '') ?>" required>
                    <div class="invalid-feedback" id="name_errors"><?= $errors['name'] ?? '' ?></div>
                </div>
                <div class="mb-3 col-12 col-sm-2 col-md-3">
                    <label for="input-seats" class="form-label">Posti</label>
                    <input type="number" name="seats" id="input-seats"
                        class="form-control <?= isset($errors['seats']) ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($room['seats'] ?? '') ?>" required>
                    <div class="invalid-feedback" id="seats_errors"><?= $errors['seats'] ?? '' ?></div>
                </div>
                <div class="mb-3 col-12 col-sm-auto">
                    <label for="input-time_frame" class="form-label">Fascia oraria</label>
                    <select name="time_frame" id="input-time_frame" class="form-select">
                        <?php foreach (Room::$possibleFrames as $frame): ?>
                            <option value="<?= $frame ?>" <?= ($room && $room['time_frame'] == $frame) ? 'selected' : '' ?>>
                                <?= $frame ?> min
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback" id="time_frame_errors"><?= $errors['time_frame'] ?? '' ?></div>
                </div>
                <div class="form-check col-12 col-md-2 pt-3 pe-3">
                    <input type="checkbox" name="availability" class="form-check-input" id="input-availability"
                        <?= $room && $room['availability'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="input-availability">Abilitato</label>
                </div>
                <div class="mb-3 col-12 col-sm-auto">
                    <label for="input-reservation_gap" class="form-label">Distacco tra prenotazioni</label>
                    <select name="reservation_gap" id="input-reservation_gap" class="form-select">
                        <?php foreach (Room::$resGap as $gap): ?>
                            <option value="<?= $gap ?>" <?= ($room && $room['reservation_gap'] == $gap) ? 'selected' : '' ?>>
                                <?= $gap ?> min
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback" id="reservation_gap_errors"><?= $errors['reservation_gap'] ?? '' ?></div>
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-12">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <h5 class="text-center">Orari di Apertura</h5>
                    </div>
                    <div id="time-forms-container" class="row">
                        <div class="mb-3 col-12 d-flex justify-content-end">
                            <button type="button" class="btn btn-success btn-sm" onclick="addTimeForm()">
                                Aggiungi Orario
                            </button>
                        </div>
                        
                        <?php if ($room && !empty($room['opening_range'])): ?>
                            <?php foreach ($room['opening_range'] as $index => $timeRange): ?>
                                <div class="time-form-group border p-3 mb-3 col-6" data-index="<?= $index ?>">
                                    <div class="row justify-content-center">
                                        <?php foreach (Room::$weekDays as $val => $weekDay): ?>
                                            <div class="col-auto d-flex align-items-center gap-1">
                                                <input type="checkbox" 
                                                    value="<?= $val ?>"
                                                    id="input-opening-range-<?= $index ?>-day-<?= $val ?>" 
                                                    name="opening_range[<?= $index ?>][days][]"
                                                    onchange="toggleDays(<?= $val ?>, <?= $index ?>)"
                                                    <?= (isset($timeRange['days']) && in_array($val, $timeRange['days'])) ? 'checked' : '' ?>> 
                                                <span class="<?= ($val == 6 || $val == 7) ? 'text-danger' : '' ?>"><?= $weekDay ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="row align-items-center justify-content-center">
                                        <div class="col-md-4">
                                            <label for="input-opening_range-<?= $index ?>-start" class="form-label">Orario di inizio</label>
                                            <input type="text" 
                                                class="form-control flatpickr-time h-100" 
                                                name="opening_range[<?= $index ?>][start]" 
                                                id="input-opening_range-<?= $index ?>-start"
                                                value="<?= $timeRange['start'] ?? '' ?>"
                                                placeholder="Seleziona orario di inizio"
                                                onchange="checkAvailability(<?= $index ?>)"
                                                required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="input-opening_range-<?= $index ?>-end" class="form-label">Orario di fine</label>
                                            <input type="text" 
                                                class="form-control flatpickr-time h-100" 
                                                name="opening_range[<?= $index ?>][end]" 
                                                id="input-opening_range-<?= $index ?>-end"
                                                value="<?= $timeRange['end'] ?? '' ?>"
                                                placeholder="Seleziona orario di fine"
                                                onchange="checkAvailability(<?= $index ?>)"
                                                required>
                                        </div>
                                        <div class="d-flex align-items-end justify-content-center mt-2">
                                            <button type="button" 
                                                class="btn btn-danger btn-sm remove-time-form" 
                                                onclick="removeTimeForm(<?= $index ?>)">
                                                Rimuovi
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>                            
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="text-danger text-center my-2" id="opening_range_errors"></div>
            <div class="d-flex justify-content-center gap-3">
                <button type="submit" class="btn btn-primary"><?= $room ? 'Modifica' : 'Crea' ?></button>
                <a href="../rooms_table" class="btn btn-danger">Chiudi</a>
            </div>

            <div class="text-center mt-3">
                <span class="text-success" id="success_message"></span>
                <span class="text-danger" id="error_message"></span>
            </div>
        </form>
    </div>
    
    <?php else: ?>
        <?php include_once __DIR__ . '/../../users/404.php' ?>
    <?php endif; ?>
<?php else: ?>
    <?php include_once __DIR__ . '/../../users/access_denied.php' ?>
<?php endif; ?>

<?php include_once __DIR__ . '/../../template/header.php'; ?>

<script>
    const toast = document.querySelector('.toast');
    const toastBody = document.querySelector('.toast-body');

    let timeFormIndex = <?php 
        if ($room && !empty($room['opening_range'])) {
            echo count($room['opening_range']);
        } else {
            echo 0;
        }
    ?>;
    
    function getTimeFormHTML(index, timeframe) {
        const weekDays = <?php echo json_encode(Room::$weekDays); ?>;
        
        let checkboxesHTML = '';
        Object.entries(weekDays).forEach(([val, weekDay]) => {
            checkboxesHTML += `
                <div class="col-auto">
                    <input type="checkbox" 
                        value="${val}" 
                        name="opening_range[${index}][days][]"
                        onchange="toggleDays(${val}, ${index})"> ${weekDay}
                </div>
            `;
        });
        
        return `
            <div class="time-form-group border p-3 mb-3 col-6" data-index="${index}">
                <div class="row justify-content-center">
                    ${checkboxesHTML}
                </div>
                <div class="row align-items-center justify-content-center">
                    <div class="col-md-4">
                        <label for="input-opening_range-${index}-start" class="form-label">Orario di inizio</label>
                        <input type="text" 
                            class="form-control flatpickr-time h-100" 
                            name="opening_range[${index}][start]" 
                            id="input-opening_range-${index}-start"
                            data-time-frame="${timeframe}"
                            placeholder="Seleziona orario di inizio"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label for="input-opening_range-${index}-end" class="form-label">Orario di fine</label>
                        <input type="text" 
                            class="form-control flatpickr-time h-100" 
                            name="opening_range[${index}][end]" 
                            id="input-opening_range-${index}-end"
                            data-time-frame="${timeframe}"
                            placeholder="Seleziona orario di fine"
                            required>
                    </div>
                    <div class="d-flex align-items-end justify-content-center mt-2">
                        <button type="button" 
                                class="btn btn-danger btn-sm remove-time-form" 
                                onclick="removeTimeForm(${index})">
                            Rimuovi
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    function initFlatpickr() {
        const timeframeSelect = document.getElementById('input-time_frame');
        const timeframe = parseInt(timeframeSelect.value);
        
        document.querySelectorAll('.flatpickr-time').forEach(element => {
            const oldInstance = element._flatpickr;
            if (oldInstance) {
                oldInstance.destroy();
            }
            
            flatpickr(element, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                minuteIncrement: timeframe
            });
        });
    }

    function addTimeForm() {
        const container = document.getElementById('time-forms-container');
        const timeframeSelect = document.getElementById('input-time_frame');
        const timeframe = parseInt(timeframeSelect.value);
        
        container.insertAdjacentHTML('beforeend', getTimeFormHTML(timeFormIndex, timeframe));
        timeFormIndex++;
        
        initFlatpickr();
        toggleRemoveButtons();
    }

    function removeTimeForm(index) {
        const formGroup = document.querySelector(`.time-form-group[data-index="${index}"]`);
        if (formGroup) {
            formGroup.remove();
            toggleRemoveButtons();
        }
    }

    function toggleRemoveButtons() {
        const timeFormGroups = document.querySelectorAll('.time-form-group');
        const removeButtons = document.querySelectorAll('.remove-time-form');
        
        if (timeFormGroups.length <= 1) {
            removeButtons.forEach(button => {
                button.style.display = 'none';
            });
        } else {
            removeButtons.forEach(button => {
                button.style.display = 'block';
            });
        }
    }

    document.getElementById('input-time_frame').addEventListener('change', function() {
        initFlatpickr();
    });

    document.addEventListener('DOMContentLoaded', function() {
        initializeSelectedDays();
        
        if (timeFormIndex === 0) {
            addTimeForm();
        } else {
            initFlatpickr();
            toggleRemoveButtons();
        }
    });

    const selectedDays = {};

    function toggleDays(day, timeRangeIndex) {
        if (!selectedDays[timeRangeIndex]) {
            selectedDays[timeRangeIndex] = [];
        }
        
        const dayIndex = selectedDays[timeRangeIndex].indexOf(day);
        
        if (dayIndex > -1) {
            selectedDays[timeRangeIndex].splice(dayIndex, 1);
        } else {
            selectedDays[timeRangeIndex].push(day);
        }
        
        console.log('Giorni selezionati:', selectedDays);
    }

    function initializeSelectedDays() {
    <?php if ($room && !empty($room['opening_range'])): ?>
        <?php foreach ($room['opening_range'] as $index => $timeRange): ?>
            <?php if (isset($timeRange['days']) && is_array($timeRange['days'])): ?>
                selectedDays[<?= $index ?>] = <?= json_encode($timeRange['days']) ?>;
                
                <?php foreach ($timeRange['days'] as $day): ?>
                    const checkbox_<?= $index ?>_<?= $day ?> = document.getElementById('input-opening-range-<?= $index ?>-day-<?= $day ?>');
                    
                    if (checkbox_<?= $index ?>_<?= $day ?>) {
                        checkbox_<?= $index ?>_<?= $day ?>.checked = true;
                    }
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
}

    document.getElementById('update_form').addEventListener('submit', function (e) {
        toast.classList.remove('show', 'bg-danger', 'bg-success');
        e.preventDefault();
        e.stopPropagation();

        const form = e.target;
        const data = new FormData(form);
        
        data.append('submitting', true);

        fetch('/admin/room/roomsCreateOrModify.php', {
            method: 'POST',
            body: data
        }).then(async (res) => {
            const response = await res.json();

            if (response ?? false) {
                toast.classList.add('show');
                if (response.success) {
                    toast.classList.add('bg-success');
                    toastBody.innerHTML = response.message;
                } else {
                    let errors = response.errors;
                    toast.classList.add('bg-danger');
                    html = `<ul>`;
                    errors.forEach((error) => {
                        html += `<li>${error}</li>`;
                    })
                    html += `</ul>`;
                    toastBody.innerHTML = html;
                }
                
                setTimeout(() => {
                    toast.classList.remove('show', 'bg-success', 'bg-danger');
                }, 5000);
            }
        
        })
    });

    let timerId;
    function checkAvailability(index) {
        clearTimeout(timerId);
        timerId = setTimeout(() => {
            const data = new FormData();
            const time_start = document.getElementById('input-opening_range-' + index + '-start').value;
            const time_end = document.getElementById('input-opening_range-' + index + '-end').value;
            const room_id = document.getElementById('room_id').value;
            data.append('time_changed', true);
            data.append('time_start', time_start);
            data.append('days', selectedDays[index]);
            data.append('time_end', time_end);
            data.append('room_id', room_id);
            data.append('range_index', index);
            
            fetch('/admin/room/roomsCreateOrModify.php', {
                method: "POST",
                body: data
            }).then(async (res) => {
                const response = await res.json();

                if (!response.success) {
                    toast.classList.add('show');
                    toast.classList.add('bg-danger');
                    toastBody.innerHTML = response.message;

                    setTimeout(() => {
                        toast.classList.remove('show', 'bg-success', 'bg-danger');
                    }, 5000);
                }

            })
        }, 1000);
        
    }
</script>