<?php
include __DIR__ . '/../../config.php';
if (User::isAuthenticated()) {
    $users = User::select([
        "where" => "id = " . $_SESSION['id']
    ]);
    $user = $users[0];
} else {
    header("Location: /users/login.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['modal_open'] ?? false) {
    $gaps = json_decode($_POST['gaps'], true);
    $joined_events = json_decode($_POST['events'], true);
    $first_event = $joined_events[0];
    $last_event = $joined_events[count($joined_events) - 1];
    $users = User::select([
        "where" => sprintf("id = %d", $joined_events[0]['user_id'])
    ]);
    if ($users ?? false) {
        $user = $users[0];
    }

    $rooms = Room::select([
        "where" => sprintf("id = %d", $joined_events[0]['room_id'])
    ]);
    if ($rooms ?? false) {
        $room = $rooms[0];
    }

    $totalParticipants = 0;
    $newGuestsArray = [];
    foreach ($joined_events as $event) {
        $guestsArray = explode(",", $event['guestsEmail']);

        foreach ($guestsArray as $guest) {
            if (!in_array($guest, $newGuestsArray) && $guest != $user['email']) {
                $newGuestsArray[] = $guest;
            }
        }
    }
    $excededGuests = false;
    if (count($newGuestsArray) > $room['seats']) {
        $excededGuests = true;
        $guestsEmailArray = [$user['email']];
    } else {
        $guestsEmailArray = array_merge([$user['email']], $newGuestsArray);
    }
}
?>
<div class="toast modal_toast position-absolute align-items-center text-white border-0 rounded-0 opacity-9" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex justify-content-center align-items-center">
        <div class="modal_toast_body toast-body text-white fw-bold">
        </div>
    </div>
</div>
<input type="hidden" name="user_id" value="<?= $user['id'] ?>">
<input type="hidden" name="room_id" value="<?= $room['id'] ?>">
<input type="hidden" id="room_seats" value="<?= $room['seats'] ?>">

<div class="row">
    <div class="mb-3 col-md-12">
        <label for="name" class="fw-bold">
            Nome della prenotazione*:
        </label>
        <input type="text" name="name" class="form-control" required>
        
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <div class="fw-bold">
            Orario di inizio:
        </div>
        <?= date('d/m/Y H:i', strtotime($first_event['starting_time'])) ?>
        <input type="hidden" value="<?= date('Y/m/d H:i:s', strtotime($first_event['starting_time'])) ?>" name="starting_time">
    </div>
    <div class="mb-3 col-md-6">
        <div class="fw-bold">
            Orario di fine:
        </div>
        <?= date('d/m/Y H:i', strtotime($last_event['ending_time'])) ?>
        <input type="hidden" value="<?= date('Y/m/d H:i:s', strtotime($last_event['ending_time'])) ?>" name="ending_time">
    </div>
</div>

<div class="row">
    <div class="mb-3 col-md-6">
        <div class="fw-bold">
            Utente prenotante:
        </div>
        <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?>
    </div>
    <div class="mb-3 col-md-3">
        <div class="fw-bold">
            Stanza:
        </div>
        <?= htmlspecialchars($room['name']) ?>
    </div>
    <div class="mb-3 col-md-3 d-flex align-items-center flex-column">
        <label for="alert_time" class="fw-bold">
            Tempo di preavviso*:
        </label>
        <select name="alert_time" id="alert_time" class="form-control w-50">
            <?php foreach (Event::$possibleAlertTimes as $alert): ?>
                <option value="<?= $alert ?>"><?= $alert ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="row">
    <div class="mb-3 col-12">
        <label for="notes" class="form-label fw-bold">
            Note aggiuntive (opzionale):
        </label>
        <textarea 
            class="form-control" 
            id="notes" 
            name="notes" 
            rows="3" 
            placeholder="Inserisci eventuali note per la prenotazione..."></textarea>
    </div>
</div>

<div class="row">
    <div class="mb-3 col-12">
        <label for="guestsEmail" class="form-label fw-bold">
            Inserisci le email dei partecipanti alla riunione (opzionale)
        </label>
        <div class="d-flex justify-content-between form-control-style">
            <input type="text" id="input_mails" placeholder="Inserisci gli indirizzi email dei partecipanti..." class="col border-0">
            <span role="button" onclick="addParticipant()" class="col-auto" id="add_participant_btn">
                <i class="fas fa-plus p-1 border border-secondary text-secondary rounded-1"></i>
            </span>
        </div>
        <small class="d-none d-xl-block">Inserisci uno o più indirizzi email, separandoli con una virgola e premi invio per aggiungerli.</small>

        <ul id="list" class="list-unstyled d-flex flex-wrap mt-2"></ul>
        
        <div class="text-end w-100"><span id="counter"></span><?= sprintf("/%s", $room['seats'])?></div>
        <?php if ($excededGuests): ?>
            <div class="card overflow-y-auto p-2" id="excededGuestsBox" style="height: 150px;">
                <?php foreach ($newGuestsArray as $key => $newGuest): ?>
                    <div class="guest d-flex align-items-center <?= ($key != count($newGuestsArray) - 1) ? 'border-bottom' : '' ?>" id="input_box_<?= $newGuest ?>">
                        <input type="checkbox" id="checkbox_<?= $newGuest ?>" class="me-2" value="<?= $newGuest ?>" onclick="addParticipant(<?= $key ?>, '<?= $newGuest ?>', remove)"> <span><?= $newGuest ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="row">
    <ul class="list-unstyled">
        <li>
            <small>I campi contrassegnati da * sono obbligatori.</small>
        </li>
    </ul>
</div>
<div class="text-center mt-3">
    <div id="success_message" class="text-success mb-2"></div>
    <div id="error_message" class="text-danger mb-2"></div>
</div>

<script>
const modal_toast = document.querySelector('.modal_toast');
const modal_toastBody = document.querySelector('.modal_toast_body');
let newGuestsArray = <?php echo json_encode($newGuestsArray); ?>;
const txt = document.getElementById('input_mails');
const list = document.getElementById('list');
const counter = document.getElementById('counter');
guestsEmailArray = <?php echo json_encode($guestsEmailArray); ?>;

counter.innerHTML = guestsEmailArray.length;
render();
txt.addEventListener('keyup', function(e) {
    e.preventDefault();
    if (e.key === 'Enter') {
        addParticipant();
    }
});

document.getElementById('join_events_form').addEventListener('submit', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const form = e.target;
    const data = new FormData(form);
    data.append("guestsEmail", guestsEmailArray);
    data.append("allGuests", newGuestsArray);
    data.append("events", '<?= json_encode($joined_events) ?>');
    data.append("gaps", '<?= json_encode($gaps) ?>');
    data.append('joined_events', true);

    if (confirm('Sei sicuro di voler unire gli eventi?')) {

        fetch('/users/reservations/reservationsTable.php', {
            method: "POST",
            body: data
        }).then(async(res) => {
            const response = await res.json();

            if (response.success) {
                window.location.href = "<?= $_ENV['APP_URL'] ?>/reservations?reservation_create=success";
            } else {
                modal_toast.classList.add('show', 'bg-danger');
                modal_toastBody.innerHTML = response.message;
            }
        })
    }
})
</script>