<?php
include __DIR__ . '/../../config.php';
if (User::isAdmin()) {
    $deniedAccess = false;
} else {
    $deniedAccess = true;
}
// Variabili di ordinamento
$page = ($_POST['page'] ?? false) ? (int)$_POST['page'] : 1;
$perPage = isset($_POST['perPage']) ? (int)$_POST['perPage'] : 10;
$orderBy = $_POST['orderBy'] ?? 'id';
$orderDirection = $_POST['orderDirection'] ?? 'DESC';

// Variabili colonne
$headers = Lang::$indexLabels;
$possibleColumns = array_keys(Lang::$indexLabels);
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
$getQuery = Lang::select([
    "columns" => "COUNT(*) as count"
]);
$totalItems = $getQuery[0]['count'];
$totalPages = (int)ceil($totalItems / $perPage);
if ($page > $totalPages) $page = $totalPages;

// Variabili elementi possibili per pagina
$possiblePerPage = [5, 8, 12];

$langs = Lang::getFullData([
    "orderBy" => $orderBy,
    "orderDirection" => $orderDirection,
    "limit" => (int)$perPage,
    "offset" => $page >= 1 ? (int)(($page - 1) * $perPage) : null
]);

?>
<?php if (!$deniedAccess): ?>

    <!-- Pulsanti sopra la tabella -->
    <div class="d-flex justify-content-between align-items-center mb-1">
        <!-- Pagina precedente -->
        <a href="/" class="btn btn-sm btn-secondary rounded-50 col-auto" >
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        
        <!-- ** SE ADMIN ** Pulsanti per creare una nuova stanza o eliminare più dati -->
        <div class="buttons col-auto d-flex justify-content-between align-items-center">
            <?php if ($_SESSION['role'] >= 2): ?>
                <form id="import_form" method="POST" enctype="multipart/form-data" class="d-flex justify-content-center mx-2 align-items-center">
                    <input type="file" name="file" accept=".csv" class="form-control">
                    <button type="submit" class="btn btn-sm btn-outline-dark ms-1 me-3" name="import">Importa</button>
                </form>
                <a href="../langs_update" class="btn btn-sm btn-success col-auto fw-bold">
                    + Crea
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($langs['values'] ?? false): ?>

    <!-- Tabella sopra 768px -->
    <table id="langsTable" class="table table-striped table-bordered">
        <!-- INTESTAZIONI -->
        <thead>
            <tr>
            <?php foreach($headers as $key => $header): ?>
                <!-- ***** Elementi visibili solo da 1200px a salire ***** -->
                <?php if ($key == 'id'): ?>
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
            
            <!-- *** Solo admins+: intestazioni per azioni e checkbox per eliminare tutti i dati visibili *** -->
            <?php if ($_SESSION['role'] >= 2): ?>
                <th class="col-2  px-1 py-2">Actions</th>
            <?php endif; ?>

            </tr>
        </thead>

        <!-- *** CORPO DELLA TABELLA *** -->
        <tbody>
            <?php foreach($langs['values'] as $langData): ?>
                
                <tr class="" id="lang-row-<?= $langData['id'] ?>">
                    <?php foreach($langData as $key => $singleLangData):

                        if (!$singleLangData && !in_array($key, array_keys(Lang::$languages))): ?>
                        <!-- Se il dato è null/0 e la chiave non è "STATO", inserisce cella vuota. -->
                            <td></td>
                        <?php continue;
                        endif; ?>

                        <?php if ($key == 'id'): ?>
                            <!-- Se la chiave è "ID", nasconde il campo fino a 1200px -->
                            <?php $langsIdArray[] = $singleLangData; ?>
                            <td class="text-center  d-none d-xl-table-cell"><?= $singleLangData ?></td>

                        <?php elseif (in_array($key, array_keys(Lang::$languages))): ?>
                            <td class="text-center">
                                <i class="<?= $singleLangData ? 'fa-solid fa-check' : 'fa-solid fa-xmark' ?>"></i>
                            </td>
                            <!-- Per tutti gli altri campi, stampa il dato normalmente. -->
                        <?php else: ?>
                            <td><?= $singleLangData ?></td>
                        <?php endif;
                    endforeach; ?>

                        <!-- Se si è admin+, stampa le azioni possibili per la riga -->
                        <?php if ($_SESSION['role'] >= 2): ?>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <a href="/langs_update?id=<?=$langData['id'] ?>" class="btn btn-sm btn-outline-dark rounded-0 col-auto">Modifica</a>
                                </div>
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

