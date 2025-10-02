<?php
include __DIR__ . '/../../config.php';
Room::setIndexLabels();

if (User::isModerator()) {
    $deniedAccess = false;
} else {
    $deniedAccess = true;
}

// Variabili di ordinamento
$page = ($_POST['page'] ?? false) ? (int)$_POST['page'] : 1;
$perPage = isset($_POST['perPage']) ? (int)$_POST['perPage'] : 10;
$orderBy = $_POST['orderBy'] ?? 'id';
$orderDirection = $_POST['orderDirection'] ?? 'ASC';

// Variabili colonne
$headers = Room::$indexLabels;
$possibleColumns = array_keys(Room::$indexLabels);
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
$getQuery = Room::select([
    "columns" => "COUNT(*) as count"
]);
$totalItems = $getQuery[0]['count'];
$totalPages = (int)ceil($totalItems / $perPage);
if ($page > $totalPages) $page = $totalPages;

// Variabili elementi possibili per pagina
$possiblePerPage = [5, 10, 15];

// Variabili eliminazione
$deletingRoomsArray = [];
$roomsIdArray = [];

// Query per recuperare i dati.
$rooms = Room::getFullData([
    "orderBy" => $orderBy,
    "orderDirection" => $orderDirection,
    "limit" => (int)$perPage,
    "offset" => $page >= 1 ? (int)(($page - 1) * $perPage) : null
]);

if (!$rooms['values'] ?? false) {
    $error_message = 'Attenzione: nessuna stanza presente';
}

if ($_POST['response'] ?? false) {
    $response = $_POST['response'];
}
?>
<?php if (!$deniedAccess): ?>
    <?php if ($rooms['values'] ?? false): ?>
    <!-- Pulsanti sopra la tabella -->
    <div class="d-flex justify-content-between align-items-center mb-1">
        <!-- Pagina precedente -->
        <a href="/" class="btn btn-sm btn-secondary rounded-50 col-auto" >
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        
        <!-- ** SE ADMIN ** Pulsanti per creare una nuova stanza o eliminare più dati -->
        <div class="buttons col-auto">
            <?php if ($_SESSION['role'] >= 2): ?>
                <a href="../rooms_update" class="btn btn-sm btn-success col-auto fw-bold">
                    + Crea
                </a>
                <button onclick="deleteMultipleRooms(rooms)" type="button" class="btn btn-sm btn-danger d-none d-lg-inline-block fw-bold">
                    Elimina selezionati
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabella sopra 768px -->
    <table id="roomsTable" class="table table-striped">
        <!-- INTESTAZIONI -->
        <thead>
            <tr>
            <?php foreach($headers as $key => $header): ?>
                <!-- ***** Elementi visibili solo da 1200px a salire ***** -->

                <?php if ($key == 'id'): ?>
                    <th role="button" class="border-1 px-1 py-2 d-none d-xl-table-cell" onclick="key = '<?= $key ?>'; orderDirection = '<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>'; reloadTable()">
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
                <?php elseif ($key == 'time_frame' || $key == 'reservation_gap'): ?>
                    <th role="button" class="border-1 px-1 py-2 d-none d-lg-table-cell" onclick="key = '<?= $key ?>'; orderDirection = '<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>'; reloadTable()">
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
                <?php elseif ($key == 'availability'): ?>
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
            
            <!-- *** Solo admins+: intestazioni per azioni e checkbox per eliminare tutti i dati visibili *** -->
            <?php if ($_SESSION['role'] >= 2): ?>
                <th class="col-2 border-1 px-1 py-2">Actions</th>
                <th class="col-auto border-1 px-1 py-2 d-none d-lg-table-cell text-center">
                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllRoomsInDeletingArray()">
                </th>
            <?php endif; ?>

            </tr>
        </thead>

        <!-- *** CORPO DELLA TABELLA *** -->
        <tbody>
            <?php foreach($rooms['values'] as $roomData): ?>
                <tr class="border-1" id="room-row-<?= $roomData['id'] ?>">
                    <?php foreach($roomData as $key => $singleRoomData):
                        // Se il dato è null/0 e la chiave non è "ABILITATO", inserisce cella vuota.
                        if (!$singleRoomData && $key != 'availability'): ?>
                            <td></td>
                        <?php continue;
                        endif; ?>

                        <!-- Se la chiave è "ID", nasconde il campo fino a 1200px -->
                        <?php if ($key == 'id'): ?>
                            <?php $roomsIdArray[] = $singleRoomData; ?>
                            <td class="text-center border-1 d-none d-xl-table-cell"><?= $singleRoomData ?></td>
                        
                            <!-- Se la chiave è "DISPONIBILITÀ", nasconde il campo fino a 1200px (Visibile solo ad admin+) -->
                        <?php elseif ($key == 'availability'): ?>
                            <?php if ($_SESSION['role'] > 1): ?>
                            <td class="text-center border-1 d-none d-sm-table-cell">
                                <div class="form-check form-switch d-flex justify-content-center align-items-center">
                                    <input class="form-check-input" type="checkbox" role="switch" onclick="toggleRoomAbilitation(<?= $roomData['id'] ?>, '<?= $roomData['name']?>', '<?= $roomData['availability'] ?>')" <?= $roomData['availability'] ? 'checked' : '' ?>></button>
                                </div>
                            </td>
                            <?php endif; ?>

                            <?php elseif ($key == 'time_frame' || $key == 'reservation_gap'): ?>
                                <td class="border-1 d-none d-lg-table-cell">
                                    <?= $singleRoomData . ' minuti' ?>
                                </td>
                            <?php elseif ($key == 'opening_range'): ?>
                                <td class="border-1 text-center w-25">
                                    <button class="btn fw-bold" type="button" onclick="showModal(<?= $roomData['id'] ?>)">
                                        <i class="fa-solid fa-circle-info"></i> Info
                                    </button>
                                </td>
                            <!-- Per tutti gli altri campi, stampa il dato normalmente. -->
                        <?php else: ?>
                            <td class="border-1"><?= $singleRoomData ?></td>
                        <?php endif;
                    endforeach; ?>

                        <!-- Se si è admin+, stampa le azioni possibili per la riga -->
                        <?php if ($_SESSION['role'] >= 2): ?>
                            <td>
                                <div class="d-flex justify-content-between gap-2">
                                    <a href="/rooms_update?id=<?=$roomData['id'] ?>" class="btn btn-sm btn-outline-dark rounded-0 col-auto">Modifica</a>
                                    <a href="/rooms_summary?id=<?=$roomData['id'] ?>" class="btn btn-sm btn-outline-warning rounded-0 col-auto">Eventi</a>
                                    <button onclick="deleteRoom(<?= $roomData['id'] ?>)" class="btn border-danger text-danger rounded-0 btn-sm col-auto">Elimina</button>
                                </div>
                            </td>
                            <td class="border-1 px-2 text-center d-none d-lg-table-cell">
                                <input type="checkbox" class="toggle_checkbox" onclick="toggleIdInDeletingArray(<?= $roomData['id'] ?>)">
                            </td>
                        <?php endif; ?>
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
        <div class="d-flex justify-content-between align-items-center mb-1">
            <!-- Pagina precedente -->
            <a href="/" class="btn btn-sm btn-secondary rounded-50 col-auto" >
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            
            <!-- ** SE ADMIN ** Pulsanti per creare una nuova stanza o eliminare più dati -->
            <div class="buttons col-auto">
                <?php if ($_SESSION['role'] >= 2): ?>
                    <a href="../rooms_update" class="btn btn-sm btn-success col-auto fw-bold">
                        + Crea
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php include_once __DIR__ . '/../dataNotFound.php' ?>
    <?php endif;?>
<?php else: ?>
    <?php include_once __DIR__ . '/../../users/access_denied.php' ?>
<?php endif; ?>
<script>
    key = '<?= $orderBy ?>';
    orderDirection = '<?= $orderDirection ?>';
    page = parseInt(<?= $page ?>);
    perPage = parseInt(<?= $perPage ?>);
    const rooms = [];
    const roomsIdArray = <?= json_encode($roomsIdArray) ?>;

    function toggleIdInDeletingArray(roomId) {
        if (rooms.includes(roomId)) {
            let index = rooms.indexOf(roomId);
            rooms.splice(index, 1);
        } else {
            rooms.push(roomId);
        }
        updateMainCheckboxState();
    }
    
    function toggleAllRoomsInDeletingArray() {
        const mainCheckbox = document.getElementById('selectAllCheckbox');
        const checkboxes = document.querySelectorAll('tbody input.toggle_checkbox');
        
        if (mainCheckbox.checked) {
            rooms.length = 0;
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
                const roomId = parseInt(checkbox.closest('tr').id.replace('room-row-', ''));
                if (!rooms.includes(roomId)) {
                    rooms.push(roomId);
                }
            });
        } else {
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            rooms.length = 0;
        }
    }
    
    function updateMainCheckboxState() {
        const mainCheckbox = document.getElementById('selectAllCheckbox');
        const checkboxes = document.querySelectorAll('tbody input.toggle_checkbox');
        const checkedBoxes = document.querySelectorAll('tbody input.toggle_checkbox:checked');
        
        if (checkboxes.length === checkedBoxes.length && checkboxes.length > 0) {
            mainCheckbox.checked = true;
            mainCheckbox.indeterminate = false;
        } else if (checkedBoxes.length === 0) {
            mainCheckbox.checked = false;
            mainCheckbox.indeterminate = false;
        } else {
            mainCheckbox.indeterminate = true;
        }
    }

    

    

</script>