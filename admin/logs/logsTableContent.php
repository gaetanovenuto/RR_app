<?php
include __DIR__ . '/../../config.php';
if (User::isAdmin()) {
    $deniedAccess = false;
} else {
    $deniedAccess = true;
}

if ($_POST['reset_form'] ?? false) {
    $where = '';
}
$quickSearch = $_POST['search_filter'] ?? '';
$starting_date_search = $_POST['starting_date'] ?? '';
$ending_date_search = $_POST['ending_date'] ?? '';


// Variabili di ordinamento
$page = ($_POST['page'] ?? false) ? (int)$_POST['page'] : 1;
$perPage = isset($_POST['perPage']) ? (int)$_POST['perPage'] : 10;
$orderBy = $_POST['orderBy'] ?? 'id';
$orderDirection = $_POST['orderDirection'] ?? 'DESC';

// Variabili colonne
$headers = Log::$indexLabels;
$possibleColumns = array_keys(Log::$indexLabels);
if (isset($_POST['orderBy'])) {
    if (!in_array($_POST['orderBy'], $possibleColumns)) {
        $orderBy = 'id';
    }
}
// Variabili direzione d'ordine colonne
$possibleOrderDirections = ['ASC', 'DESC'];
if (isset($_POST['orderDirection'])) {
    if (!in_array($_POST['orderDirection'], $possibleOrderDirections)) {
        $orderDirection = 'DESC';
    }
}

// Variabili paginazione
$where = '';
if ($quickSearch) {
    $where .= "logs.action_type LIKE '%{$quickSearch}%'";
}
if ($starting_date_search && $ending_date_search) {
    if ($where != '') {
        $where .= ' AND ';
    }
    if ($ending_date_search <= $starting_date_search) {
        $where .= "logs.created_at LIKE '%$starting_date_search%'";
    } else {
        $where .= "logs.created_at >= '$starting_date_search' AND logs.created_at <= '$ending_date_search 23:59:59'";
    }
} elseif ($starting_date_search) {
    $where .= "logs.created_at LIKE '%$starting_date_search%'";
} elseif ($ending_date_search) {
    $where .= "logs.created_at LIKE '%$ending_date_search%'";
}

$getQuery = Log::select([
    "columns" => "COUNT(*) as count",
    "where" => $where
]);
$totalItems = $getQuery[0]['count'];
$totalPages = (int)ceil($totalItems / $perPage);
if ($page > $totalPages) $page = $totalPages;

// Variabili elementi possibili per pagina
$possiblePerPage = [5, 8, 12];

// Array invio massivo email
$sendingEmailsArray = [];
$logLogsIdArray = [];

$logs = Log::select([
    "columns" => "logs.id, logs.user_id, users.firstname, users.lastname, logs.action_type, logs.ip_address, logs.created_at, logs.details, logs.level",
    "joins" => [
        [
            "type" => "LEFT",
            "sql" => "users ON logs.user_id = users.id"
        ]
    ],
    "where" => $where,
    "orderBy" => $orderBy,
    "orderDirection" => $orderDirection,
    "limit" => (int)$perPage,
    "offset" => $page >= 1 ? (int)(($page - 1) * $perPage) : null
]);

?>
<?php if (!$deniedAccess): ?>

    <?php if ($logs ?? false): ?>
    <!-- Pulsanti sopra la tabella -->
    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap">
        <!-- Pagina precedente -->
        <a href="/" class="btn btn-sm btn-secondary rounded-50 col-auto" >
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        
        <div class="filters col-12 col-md-9 d-flex justify-content-between align-items-center flex-column">
            <form class="d-flex justify-content-between align-items-center flex-wrap gap-2" id="filter_search_form" method="POST" onkeydown="return event.key != 'Enter';">
                <div class="d-flex justify-content-between align-items-center me-1 col-12 col-md-auto">
                    <label for="search_filter" class="fw-semibold text-muted me-2">Ricerca:</label>
                    <input type="text" class="form-control form-control-sm me-4" name="search_filter" placeholder="Cosa cerchi?" id="search_text">
                </div>
                <div class="input_start d-flex justify-content-between align-items-center me-1 col-12 col-md-auto">
                    <label for="starting_date" class="fw-semibold me-1">Da:</label>
                    <input type="date" name="starting_date" class="col-auto form-control-style text-muted" id="starting_date">
                </div>
                <div class="input_end d-flex justify-content-between align-items-center me-1 col-12 col-md-auto">
                    <label for="ending_date" class="fw-semibold me-1">A:</label>
                    <input type="date" name="ending_date" class="form-control-style text-muted" id="ending_date">
                </div>
                <button type="button" class="btn-secondary btn btn-sm mx-2" onclick="reloadTable()">Cerca</button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="resetFilters()">Reset filtri</button>
            </form>
<div class="tags mt-2"></div>
        </div>
    </div>

    <!-- Tabella sopra 768px -->
    <table id="logsTable" class="table table-striped table-bordered">
        <!-- INTESTAZIONI -->
        <thead>
            <tr>
            <?php foreach($headers as $key => $header): ?>
                <!-- ***** Elementi visibili solo da 1200px a salire ***** -->

                <?php if ($key == 'id' || $key == 'firstname' || $key == 'lastname'): ?>
                    <th role="button" class=" px-1 py-2 d-none d-xl-table-cell" onclick="key = '<?= $key ?>'; orderDirection = '<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>'; reloadTable()">
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
                
                <?php elseif ($key == 'user_id' || $key == 'level'): ?>
                    <th role="button" class=" px-1 py-2 d-none d-sm-table-cell" onclick="key = '<?= $key ?>'; orderDirection = '<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>'; reloadTable()">
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
                    <th role="button" class=" px-1 py-2" onclick="key = '<?= $key ?>'; orderDirection = '<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>'; reloadTable()">
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
            
            

            </tr>
        </thead>

        <!-- *** CORPO DELLA TABELLA *** -->
        <tbody>
            <?php foreach($logs as $logData): ?>
                <tr id="mail-row-<?= $logData['id'] ?>">
                    <?php foreach($logData as $key => $singleLogData):
                        if (!$singleLogData && $key != 'details' && $key != 'level' && $key != 'firstname' && $key != 'lastname' && $key != 'user_id'): ?>
                        <!-- Se il dato è null/0 e la chiave non è "STATO", inserisce cella vuota. -->
                            <td></td>
                        <?php continue;
                        endif; ?>

                        <?php if ($key == 'id' || $key == 'firstname' || $key == 'lastname'): ?>
                            <!-- Se la chiave è "ID", nasconde il campo fino a 1200px -->
                            <td class="text-center d-none d-xl-table-cell"><?= $singleLogData ?></td>
                        <?php elseif ($key == 'details'): ?>
                                <td class="text-center w-25">
                                    <button class="btn fw-bold" type="button" onclick="showModal(<?= $logData['id'] ?>)">
                                        <i class="fa-solid fa-circle-info"></i> Info
                                    </button>
                                </td>
                        <?php elseif ($key == 'level'): ?>
                            <td class="text-center d-none d-sm-table-cell">
                                <i class="fa-solid fa-circle" style="color: <?php
                                    if ($logData['level'] == 3) {
                                        echo 'red';
                                    } elseif ($logData['level'] == 2) {
                                        echo 'orange';
                                    } elseif ($logData['level'] == 1) {
                                        echo 'yellow';
                                    } else {
                                        echo 'green';
                                    }
                                ?>; border: 2px solid black; border-radius:50%;"></i>
                            </td>
                        <?php elseif ($key == 'user_id'): ?>
                            <td class="text-center d-none d-sm-table-cell"><?= $singleLogData ?></td>

                            <!-- Per tutti gli altri campi, stampa il dato normalmente. -->
                        <?php else: ?>
                            <td><?= $singleLogData ?></td>
                        <?php endif;
                    endforeach; ?>
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
            <a href="/logs" class="btn btn-sm btn-secondary rounded-50 col-auto" >
                <i class="fa-solid fa-arrow-left"></i>
            </a>
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
    
</script>

