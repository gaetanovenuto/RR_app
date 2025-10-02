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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['new_event'] == true) {
    $rooms = Room::select([
        "where" => sprintf("id = %s", $_POST['room_id'])
    ]);
    
    $room = $rooms[0];
}
$pageTitle = "Riepilogo prenotazione";
include_once __DIR__ . '/../../template/header.php';
?>


<h2 class="text-center mt-2"><?= $pageTitle ?></h2>
<div class="modal-body position-relative">
    <div class="toast modal_toast position-absolute align-items-center text-white border-0 rounded-0 opacity-9" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex justify-content-center align-items-center">
            <div class="modal_toast_body toast-body text-white fw-bold">
            </div>
        </div>
    </div>
    <form method="POST" id="reservation_form" class="w-100" onkeydown="return event.key != 'Enter';">
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
                <?= date('d/m/Y H:i', strtotime($_POST['start'])) ?>
                <input type="hidden" value="<?= date('Y/m/d H:i:s', strtotime($_POST['start'])) ?>" name="starting_time">
            </div>
            <div class="mb-3 col-md-6">
                <div class="fw-bold">
                    Orario di fine:
                </div>
                <?= date('d/m/Y H:i', strtotime($_POST['end'])) ?>
                <input type="hidden" value="<?= date('Y/m/d H:i:s', strtotime($_POST['end'])) ?>" name="ending_time">
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
        <div class="d-flex justify-content-center gap-3 w-100">
            <button type="submit" class="btn btn-primary" id="confirm_reservation">
                <i class="fas fa-check me-2"></i>
                Conferma Prenotazione
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeModal()">
                <i class="fas fa-times me-2"></i>
                Annulla
            </button>
        </div>
    </form>
</div>


<?php include_once __DIR__ . '/../../template/footer.php'; ?>

<script>
const modal_toast = document.querySelector('.modal_toast');
const modal_toastBody = document.querySelector('.modal_toast_body');

document.getElementById('reservation_form').addEventListener('submit', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const form = e.target;
    const data = new FormData(form);
    data.append("event_created", true);
    data.append("guestsEmail", guestsEmailArray);
    
    if (confirm('Sicuro di voler creare la prenotazione?')) {
    
        fetch('/users/reservations/add_reservation.php', {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();
            console.log(response);
            const successBox = document.getElementById('success_message');
            const errorBox = document.getElementById('error_message');
            if (response.success) {
                errorBox.innerHTML = '';
                successBox.innerHTML = response.message;
                window.location.href = "<?= $_ENV['APP_URL'] ?>/reservations?reservation_create=success";
            } else {
                successBox.innerHTML = '';
                errorBox.innerHTML = response.message;
            }
        }).catch((err) => {
            console.error(err);
        })
    }
})

</script>

