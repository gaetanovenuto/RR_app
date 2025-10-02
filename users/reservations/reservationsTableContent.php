<?php
include __DIR__ . '/../../config.php';

// Variabili di ordinamento
$page = ($_POST['page'] ?? false) ? (int)$_POST['page'] : 1;
$perPage = isset($_POST['perPage']) ? (int)$_POST['perPage'] : 10;
$orderBy = $_POST['orderBy'] ?? 'id';
$orderDirection = $_POST['orderDirection'] ?? 'ASC';

// Variabili colonne
$headers = Event::$indexLabels;
$possibleColumns = array_keys(Event::$indexLabels);
if (isset($_POST['orderBy'])) {
    if (!in_array($_POST['orderBy'], $possibleColumns)) {
        $orderBy = 'id';
    }
}
// Variabili direzione d'ordine colonne
$possibleOrderDirections = ['ASC', 'DESC'];
if (isset($_POST['orderDirection'])) {
    if (!in_array($_POST['orderDirection'], $possibleOrderDirections)) {
        $orderDirection = 'ASC';
    }
}

// Variabili paginazione
$getQuery = Event::select([
    "columns" => "COUNT(*) as count",
    "where" => sprintf("user_id = %d", $_SESSION['id'])
]);
$totalItems = $getQuery[0]['count'];
$totalPages = (int)ceil($totalItems / $perPage);
if ($page > $totalPages) $page = $totalPages;

// Variabili elementi possibili per pagina
$possiblePerPage = [5, 10, 15];

// Variabili eliminazione
$deletingRoomsArray = [];
$roomsIdArray = [];

function dateDifference($time) {
    $now = date('Y-m-d H:i:s');
    return (strtotime($time) > strtotime($now)) ? true : false;
}

$eventsIdArray = [];

// Query per recuperare i dati.
$allEvents = Event::select([
    "columns" => "events.id, events.name, events.starting_time, events.ending_time, events.alert_time, events.access_key, COUNT(participants.id) as participants, rooms.id as room_id, rooms.seats, DATE(events.starting_time) as event_date",
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
    "where" => sprintf("events.user_id = %s", $_SESSION['id']),
    "orderBy" => $orderBy,
    "orderDirection" => $orderDirection,
    "limit" => (int)$perPage,
    "offset" => $page >= 1 ? (int)(($page - 1) * $perPage) : null
]);

if ($_POST['response'] ?? false) {
    $response = $_POST['response'];
}

?>

<!-- Pulsanti sopra la tabella -->
<div class="d-flex justify-content-between align-items-center mb-1">
    <!-- Pagina precedente -->
    <a href="/" class="btn btn-sm btn-secondary rounded-50 col-auto" >
        <i class="fa-solid fa-arrow-left"></i>
    </a>
    
    <!-- ** SE ADMIN ** Pulsanti per creare una nuova stanza o eliminare più dati -->
    <div class="buttons col-auto">
            <button onclick="joinEvents(events)" type="button" class="join_btn btn btn-sm btn-warning d-none d-lg-inline-block col-auto">
                Unisci
            </button>
            <a href="../add_reservation" class="btn btn-sm btn-success col-auto fw-bold">
                + Prenota
            </a>
    </div>
</div>

<?php if ($allEvents ?? false): ?>
<!-- Tabella sopra 768px -->
<table id="roomsTable" class="table table-striped">
    <!-- INTESTAZIONI -->
    <thead>
        <tr>
        <?php foreach($headers as $key => $header): ?>

            <?php if ($key == 'starting_time' || $key == 'ending_time'): ?>
                <th role="button" class="border-1 px-1 py-2 d-none" onclick="key = '<?= $key ?>'; orderDirection = '<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>'; reloadTable()">
                    <div class="d-flex align-items-center">
                        <span>
                            <?= $header ?> 
                        </span>

                        <!-- ** Freccia direzionale ordine tabella ** -->
                        <?php if ($orderBy == $key): ?>
                            <i class="fas fa-angle-<?= $orderDirection == 'ASC' ? 'down' : 'up' ?> fa-sm ms-1"></i>
                        <?php endif; ?>
                    </div>
                </th>
            <?php elseif ($key == 'access_key'): ?>
                <th role="button" class="border-1 px-1 py-2 d-none d-sm-table-cell" onclick="key = '<?= $key ?>'; orderDirection = '<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>'; reloadTable()">
                    <div class="d-flex align-items-center">
                        <span>
                            <?= $header ?> 
                        </span>

                        <!-- ** Freccia direzionale ordine tabella ** -->
                        <?php if ($orderBy == $key): ?>
                            <i class="fas fa-angle-<?= $orderDirection == 'ASC' ? 'down' : 'up' ?> fa-sm ms-1"></i>
                        <?php endif; ?>
                    </div>
                </th>
            <?php elseif ($key == 'alert_time'): ?>
                <th role="button" class="border-1 px-1 py-2 d-none d-md-table-cell" onclick="key = '<?= $key ?>'; orderDirection = '<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>'; reloadTable()">
                    <div class="d-flex align-items-center">
                        <span>
                            <?= $header ?> 
                        </span>

                        <!-- ** Freccia direzionale ordine tabella ** -->
                        <?php if ($orderBy == $key): ?>
                            <i class="fas fa-angle-<?= $orderDirection == 'ASC' ? 'down' : 'up' ?> fa-sm ms-1"></i>
                        <?php endif; ?>
                    </div>
                </th>
            <?php else: ?>

                <!-- ***** Resto degli elementi visibili ***** -->
                <th role="button" class="border-1 px-1 py-2" onclick="key = '<?= $key ?>'; orderDirection = '<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>'; reloadTable()">
                    <div class="d-flex align-items-center">
                        <span>
                            <?= $header ?> 
                        </span>
                        
                        <!-- ** Freccia direzionale ordine tabella ** -->
                        <?php if ($orderBy == $key): ?>
                            <i class="fas fa-angle-<?= $orderDirection == 'ASC' ? 'down' : 'up' ?> fa-sm"></i>
                        <?php endif; ?>
                    </div>
                </th>            
            <?php endif; ?>
        <?php endforeach; ?>
        
        <th class="col-2 border-1 px-1 py-2">Actions</th>
        <th class="col-auto border-1 px-1 py-2 d-none d-lg-table-cell">Unisci</th>
        </tr>
    </thead>

    <!-- *** CORPO DELLA TABELLA *** -->
    <tbody>
        <?php foreach($allEvents as $eventData): ?>
            <tr class="border-1" id="room-row-<?= $eventData['id'] ?>">
                <?php foreach($eventData as $key => $singleEventData):
                   if ($key == 'starting_time' || $key == 'ending_time' || $key == 'id' || $key == 'room_id' || $key == 'event_date' ): ?>
                        <td class="text-center border-1 d-none"></td>

                        <?php elseif ($key == 'alert_time'): ?>
                            <td class="border-1 d-none d-md-table-cell">
                                <?= $singleEventData . ' minuti' ?>
                            </td>
                        <?php elseif ($key == 'opening_range'): ?>
                            <td class="border-1 text-center w-25">
                                <button class="btn fw-bold" type="button" onclick="showModal(<?= $eventData['id'] ?>)">
                                    <i class="fa-solid fa-circle-info"></i> Info
                                </button>
                            </td>
                    <?php elseif($key == 'access_key'): ?>
                            <td class="border-1 text-center d-none d-sm-table-cell">
                                <input type="hidden" class="hidden_link" value="<?= sprintf("%s/users/reservations/reservation_invite.php?token=%s", $_ENV['APP_URL'], $eventData['access_key']) ?>">
                                <button class="btn btn-sm btn-success copy_button">Copia il link</button>
                            </td>
                        <!-- Per tutti gli altri campi, stampa il dato normalmente. -->
                    <?php else: ?>
                        <td class="border-1"><?= $singleEventData ?? 'Non disponibile' ?></td>
                    <?php endif;
                endforeach; ?>
                <td>
                    <div class="d-flex justify-content-between">
                        <a href="/reservation_details?id=<?= $eventData['id'] ?>" class="btn btn-sm btn-outline-dark rounded-0 col-auto">Dettagli</a>
                    </div>
                </td>
                <td class="border-1 px-2 text-center d-none d-lg-table-cell">
                    <input type="checkbox" class="toggle_checkbox" id="checkbox-id-<?= $eventData['id'] ?>" onclick="toggleIdInJoinArray(<?= $eventData['id'] ?>, event)">
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="pagination_buttons d-flex justify-content-between align-items-center"> 

    <nav class="col-auto d-flex align-items-center">
        <ul class="list-unstyled d-flex justify-content-center align-items-center m-0">
            <li>
                <button onclick="page = 1; reloadTable()" class="btn btn-sm border-0 text-dark btn-outline-dark <?= $totalPages <= 1 ? 'disabled' : '' ?>">
                    <i class="fa-solid fa-angles-left"></i>
                </button>
            </li>
            <li>
                <button onclick="page = page - 1; reloadTable()" class="btn btn-sm border-0 text-dark btn-outline-dark <?= $page <= 1 ? 'disabled' : '' ?> <?= $totalPages <= 1 ? 'disabled' : '' ?>">
                    <i class="fa-solid fa-angle-left"></i>
                </button>
            </li>
            <li>
                <button onclick="page = page + 1; reloadTable()" class="btn btn-sm border-0 text-dark btn-outline-dark <?= $page >= $totalPages ? 'disabled' : '' ?> <?= $totalPages <= 1 ? 'disabled' : '' ?>">
                    <i class="fa-solid fa-angle-right"></i>
                </button>
            </li>
            <li>
                <button onclick="page = <?= $totalPages ?>; reloadTable()" class="btn btn-sm border-0 text-dark btn-outline-dark <?= $totalPages <= 1 ? 'disabled' : '' ?>">
                    <i class="fa-solid fa-angles-right"></i>
                </button>
            </li>
        </ul>
    </nav> 

    <div class="text-center fw-bold d-sm-none"><?= sprintf("%s - %s", $page, $totalPages) ?></div>
    <div class="text-center fw-bold d-none d-sm-block"><?= sprintf("Pagina %s di %s", $page, $totalPages) ?></div>


    <!-- Elementi visibili per pagina -->
    <div class="btn-group h-25 ms-5" role="group">
        <?php foreach($possiblePerPage as $perPageButton): ?>
            <button type="button" onclick="perPage = <?= (int)$perPageButton ?>; reloadTable()" class="btn btn-outline-dark btn-sm rounded-0 <?= $perPage == $perPageButton ? 'btn-dark text-white' : '' ?>" title="<?= $perPageButton ?> elementi visibili per pagina">
                <?= $perPageButton ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>


<?php else: ?>
    <?php $error_message = "Nessuna prenotazione esistente"; include_once __DIR__ . '/../../admin/dataNotFound.php' ?>
<?php endif;?>
<script>
    key = '<?= $orderBy ?>';
    orderDirection = '<?= $orderDirection ?>';
    page = parseInt(<?= $page ?>);
    perPage = parseInt(<?= $perPage ?>);

    const events = [];
    const eventsIdArray = <?= json_encode($eventsIdArray) ?>;
    const join_btn = document.querySelector('.join_btn');
    join_btn.disabled = true;



    document.querySelectorAll('.copy_button').forEach(button => {
        button.addEventListener('click', function () {
            const input = this.closest('td').querySelector('.hidden_link');
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
        });
    });

    function toggleIdInJoinArray(eventId) {
        if (event) event.stopPropagation();
        const row_checkbox = document.getElementById('checkbox-id-' + eventId);
        if (events.includes(eventId)) {
            let index = events.indexOf(eventId);
            events.splice(index, 1);
            row_checkbox.checked = false;
        } else {
            events.push(eventId);
            row_checkbox.checked = true;
        }

        if (events.length >= 2) {
            join_btn.disabled = false;
        } else {
            join_btn.disabled = true;
        }
        console.log(events);
    }

    function joinEvents(events) {
        if (confirm('Sei sicuro di voler unire gli eventi selezionati?')) {
            if (events.length == 1) {
                toast.classList.add('show', 'bg-danger');
                toastBody.innerHTML = "Impossibile unire un solo evento";

                setTimeout(() => {
                    toast.classList.remove('show', 'bg-success', 'bg-danger');
                }, 3000);
            } else {
                const data = new FormData();
                data.append('events_id', events);
                data.append('joining_events', true);

                fetch('/users/reservations/reservationsTable.php', {
                    method: "POST",
                    body: data
                }).then(async(res) => {
                    const response = await res.json();
                    
                     if (response ?? false) {
                        if (response.success) {
                            new bootstrap.Modal(join_modal).show();
                            const modal_data = new FormData();
                            modal_data.append("events", JSON.stringify(response.events));
                            modal_data.append("gaps", JSON.stringify(response.gaps));
                            modal_data.append("modal_open", true);

                            fetch('/users/reservations/join_modal.php', {
                                method: "POST",
                                body: modal_data
                            }).then(async(res) => {
                                const html = await res.text();
                                setInnerHtml(join_modal_body, html);
                            })
                            
                        } else {
                            toast.classList.add('show');
                            toast.classList.add('bg-danger');
                            toastBody.innerHTML = response.message;
                        }
                
                        setTimeout(() => {
                            toast.classList.remove('show', 'bg-success', 'bg-danger');
                        }, 3000);
                    }
                })
            }
        }
    }
</script>