<?php
include __DIR__ . '/../../config.php';
if (User::isModerator()) {
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
$headers = Mail::$indexLabels;
$possibleColumns = array_keys(Mail::$indexLabels);
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
$getQuery = Mail::select([
    "columns" => "COUNT(*) as count"
]);
$totalItems = $getQuery[0]['count'];
$totalPages = (int)ceil($totalItems / $perPage);
if ($page > $totalPages) $page = $totalPages;

// Variabili elementi possibili per pagina
$possiblePerPage = [5, 8, 12];

// Array invio massivo email
$sendingEmailsArray = [];
$logMailsIdArray = [];

$mails = Mail::getFullData([
    "orderBy" => $orderBy,
    "orderDirection" => $orderDirection,
    "limit" => (int)$perPage,
    "offset" => $page >= 1 ? (int)(($page - 1) * $perPage) : null
]);

?>
<?php if (!$deniedAccess): ?>

    <?php if ($mails['values'] ?? false): ?>
    <!-- Pulsanti sopra la tabella -->
    <div class="d-flex justify-content-between align-items-center mb-1">
        <!-- Pagina precedente -->
        <a href="/" class="btn btn-sm btn-secondary rounded-50 col-auto" >
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        
        <!-- ** SE ADMIN ** Pulsanti per creare una nuova stanza o eliminare più dati -->
        <div class="buttons col-auto">
            <?php if ($_SESSION['role'] >= 2): ?>
                <button onclick="sendMultipleMails(logMails)" type="button" class="btn btn-warning d-none d-lg-inline-block col-auto">
                    Reinvia
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabella sopra 768px -->
    <table id="mailsTable" class="table table-striped table-bordered">
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
                
                <?php elseif ($key == 'state' || $key == 'subject'): ?>
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
            
            <!-- *** Solo admins+: intestazioni per azioni e checkbox per eliminare tutti i dati visibili *** -->
            <?php if ($_SESSION['role'] >= 2): ?>
                <th class="col-2  px-1 py-2">Actions</th>
                <th class="col-auto  px-1 py-2 d-none d-lg-table-cell text-center">
                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllMailsInSendingArray()">
                </th>
            <?php endif; ?>

            </tr>
        </thead>

        <!-- *** CORPO DELLA TABELLA *** -->
        <tbody>
            <?php foreach($mails['values'] as $mailData): ?>
                
                <tr class="" id="mail-row-<?= $mailData['id'] ?>">
                    <?php foreach($mailData as $key => $singleMailData):

                        if (!$singleMailData && $key != 'state'): ?>
                        <!-- Se il dato è null/0 e la chiave non è "STATO", inserisce cella vuota. -->
                            <td></td>
                        <?php continue;
                        endif; ?>

                        <?php if ($key == 'id'): ?>
                            <!-- Se la chiave è "ID", nasconde il campo fino a 1200px -->
                            <?php $mailsIdArray[] = $singleMailData; ?>
                            <td class="text-center  d-none d-xl-table-cell"><?= $singleMailData ?></td>
                        

                            <!-- Se la chiave è "DISPONIBILITÀ", nasconde il campo fino a 1200px (Visibile solo ad admin+) -->
                        <?php elseif ($key == 'state'): ?>
                            <?php if ($_SESSION['role'] > 1): ?>
                                <td class="text-center  d-none d-sm-table-cell">
                                    <i class="fa-solid fa-circle" style="color: <?= $singleMailData ? '#118609' : '#eb0017'?>;"></i>
                                </td>
                            <?php endif; ?>
                        <?php elseif ($key == 'subject'): ?>
                            <td class="d-none d-sm-table-cell">
                                <?= $singleMailData ?>
                            </td>
                        <?php elseif ($key == 'recipient'): ?>
                            <td style="max-width: 100px;">
                                <p class="text-truncate" >
                                    <?= $singleMailData ?>
                                </p>
                            </td>
                        <?php elseif ($key == 'error'): ?>
                            <?php if ($mailData['state'] == 0): ?>
                                <td class="text-center w-25">
                                    <button class="btn fw-bold" type="button" onclick="showModal(<?= $mailData['id'] ?>)">
                                        <i class="fa-solid fa-circle-info"></i> Info
                                    </button>
                                </td>
                            <?php else: ?>
                                <td></td>
                            <?php endif; ?>
                            <!-- Per tutti gli altri campi, stampa il dato normalmente. -->
                        <?php else: ?>
                            <td><?= $singleMailData ?></td>
                        <?php endif;
                    endforeach; ?>

                        <!-- Se si è admin+, stampa le azioni possibili per la riga -->
                        <?php if ($_SESSION['role'] >= 2): ?>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <a href="/mails_detail?id=<?=$mailData['id'] ?>" class="btn btn-sm btn-outline-dark rounded-0 col-auto">Dettaglio</a>
                                </div>
                            </td>
                            <td class=" px-2 text-center d-none d-lg-table-cell">
                                <input type="checkbox" class="toggle_checkbox" onclick="toggleIdInSendingArray(<?= $mailData['id'] ?>)">
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
    
    const logMails = [];
    const logMailsIdArray = <?= json_encode($logMailsIdArray) ?>;
    
    function toggleIdInSendingArray(logMailId) {
        if (logMails.includes(logMailId)) {
            let index = logMails.indexOf(logMailId);
            logMails.splice(index, 1);
        } else {
            logMails.push(logMailId);
        }
        updateMainCheckboxState();
    }

    function toggleAllMailsInSendingArray() {
        const mainCheckbox = document.getElementById('selectAllCheckbox');
        const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
        
        if (mainCheckbox.checked) {
            logMails.length = 0;
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
                const logMailId = parseInt(checkbox.closest('tr').id.replace('mail-row-', ''));
                if (!logMails.includes(logMailId)) {
                    logMails.push(logMailId);
                }
            });
        } else {
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            logMails.length = 0;
        }
    }

    function updateMainCheckboxState() {
        const mainCheckbox = document.getElementById('selectAllCheckbox');
        const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
        const checkedBoxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');
        console.log(logMails);
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

    function showError(id) {
        const errorBox = document.getElementById('modalError-'+ id);
        errorBox.style.visibility = 'visible';
        console.log(errorBox);

    }

    function hideError(id) {
        const errorBox = document.getElementById('modalError-'+ id);
        console.log(id);
        errorBox.style.visibility = 'hidden';
    }
</script>

