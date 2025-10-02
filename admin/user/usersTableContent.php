<?php
include __DIR__ . '/../../config.php';
User::setIndexLabels();
User::setRoles();

if (User::isModerator()) {
    $deniedAccess = false;
} else {
    $deniedAccess = true;
}

$quickSearch = $_POST['search_filter'] ?? '';

// Variabili di ordinamento
$page = ($_POST['page'] ?? false) ? (int)$_POST['page'] : 1;
$perPage = isset($_POST['perPage']) ? (int)$_POST['perPage'] : 10;
$orderBy = $_POST['orderBy'] ?? 'id';
$orderDirection = $_POST['orderDirection'] ?? 'ASC';

// Variabili colonne
$headers = User::$indexLabels;
$possibleColumns = array_keys(User::$indexLabels);
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
$where = '';
if ($quickSearch) {
    $where .= "username LIKE '%$quickSearch%' OR firstname LIKE '%$quickSearch%' OR lastname LIKE '%$quickSearch%' OR email LIKE '%$quickSearch%'";
}

$getQuery = User::select([
    "columns" => "COUNT(*) as count",
    "where" => $where
]);
$totalItems = $getQuery[0]['count'];
$totalPages = (int)ceil($totalItems / $perPage);
if ($page > $totalPages) $page = $totalPages;

// Variabili elementi possibili per pagina
$possiblePerPage = [5, 10, 15];

// Variabili ruolo
$possibleRoles = User::$roles;

// Variabili eliminazione
$deletingUsersArray = [];
$usersIdArray = [];
// Query per recuperare i dati.
$users = User::getFullData([
    "orderBy" => $orderBy,
    "orderDirection" => $orderDirection,
    "limit" => (int)$perPage,
    "offset" => $page >= 1 ? (int)(($page - 1) * $perPage) : null,
    "where" => $where
]);

if (!$users['values'] ?? false) {
    $error_message = Lang::getText("user:no_data");
}

if ($_POST['response'] ?? false) {
    $response = $_POST['response'];
}
?>

<?php if (!$deniedAccess): ?>
    <?php if ($users['values'] ?? false): ?>
    <!-- Pulsanti sopra la tabella -->
    <div class="d-flex justify-content-between align-items-center mb-1">
        <!-- Pagina precedente -->
        <a href="/" class="btn btn-sm btn-secondary rounded-50 col-auto" >
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        
        <!-- ** SE ADMIN ** Pulsanti per creare un nuovo utente o eliminare più dati -->
        <div class="buttons col-auto">
            <?php if ($_SESSION['role'] >= 2): ?>
                <a href="../users_update" class="btn btn-sm btn-success col-auto fw-bold">
                    <?= Lang::getText("general:create") ?>
                </a>
                <button onclick="deleteMultipleUsers(users)" type="button" class="btn btn-sm btn-danger d-none d-lg-inline-block fw-bold">
                    <?= Lang::getText("general:delete_selected") ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="filters col-12 d-flex justify-content-between align-items-center flex-column">
        <form class="d-flex justify-content-between align-items-center flex-wrap gap-2" id="filter_search_form" method="POST" onkeydown="return event.key != 'Enter';">
            <div class="d-flex justify-content-between align-items-center me-1 col-12 col-md-auto">
                <label for="search_filter" class="fw-semibold text-muted me-2">Ricerca:</label>
                <input type="text" class="form-control form-control-sm me-4" name="search_filter" placeholder="Cosa cerchi?" id="search_text">
            </div>
            <button type="button" class="btn-secondary btn btn-sm mx-2" onclick="reloadTable()">Cerca</button>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="resetFilters()">Reset filtri</button>
        </form>
<div class="tags mt-2"></div>
    </div>

    <!-- Tabella sopra 768px -->
    <table id="usersTable" class="table d-none d-md-table table-striped">
        <!-- INTESTAZIONI -->
        <thead>
            <tr>
            <?php foreach($headers as $key => $header): ?>
                <!-- ***** Elementi visibili solo da 1200px a salire ***** -->

                <?php if ($key == 'username' || $key == 'id'): ?>
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
                <th class="col-2 border-1 px-1 py-2"><?= Lang::getText("general:actions") ?></th>
                <th class="col-auto border-1 px-1 py-2 d-none d-lg-table-cell text-center">
                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllUsersInDeletingArray()">
                </th>
            <?php endif; ?>

            </tr>
        </thead>

        <!-- *** CORPO DELLA TABELLA *** -->
        <tbody>
            <?php foreach($users['values'] as $userData): ?>
                
                <tr class="border-1" id="user-row-<?= $userData['id'] ?>" <?= $_SESSION["role"] > 1 ? "onclick=\"handleRowClick({$userData['id']}, event)\" role=\"button\"" : '' ?>>
                    <?php foreach($userData as $key => $singleUserData):

                        // Campo "RUOLO": traduce il numero con il nome del ruolo.
                        if ($key == 'role') {
                            foreach ($possibleRoles as $key => $value) {
                                if ($singleUserData == $key) {
                                    $singleUserData = $value;
                                }
                            }
                        }

                        // Se il dato è null/0 e la chiave non è "ABILITATO", inserisce cella vuota.
                        if (!$singleUserData && $key != 'enabled'): ?>
                            <td></td>
                        <?php continue;
                        endif;

                        // Se la chiave è "CREATO IL" riporta la data in formato GG-MM-YYYY HH:ii.
                        if ($key == 'created_at'):
                            $date = new DateTime($singleUserData); ?>
                            <td class="border-1"><?= $date->format('d/m/Y H:i'); ?></td>

                        <!-- Se la chiave è "ID", nasconde il campo fino a 1200px -->
                        <?php elseif ($key == 'id'): ?>
                            <?php $usersIdArray[] = $singleUserData; ?>
                            <td class="text-center border-1 d-none d-xl-table-cell"><?= $singleUserData ?></td>
                        
                        <!-- Se la chiave è "USERNAME", nasconde il campo fino a 1200px -->
                        <?php elseif ($key == 'username'): ?>
                            <td class="border-1 d-none d-xl-table-cell">
                                <?= htmlspecialchars((string) $singleUserData) ?>
                            </td>

                            <!-- Se la chiave è "ABILITATO", nasconde il campo fino a 1200px (Visibile solo ad admin+) -->
                        <?php elseif ($key == 'enabled'): ?>
                            <?php if ($_SESSION['role'] > 1): ?>
                            <td class="text-center border-1 d-none d-md-table-cell">
                                <div class="form-check form-switch d-flex justify-content-center align-items-center">
                                    <input class="form-check-input" type="checkbox" role="switch" onclick="toggleUserAbilitation(<?= $userData['id'] ?>, '<?= $userData['firstname']?>', '<?= $userData['lastname']?>', <?= $userData['enabled']?>)" <?= $userData['enabled'] ? 'checked' : '' ?>></button>
                                </div>
                            </td>

                            <!-- Se si è moderatori, anziché l'input, viene visualizzato solo "SI" o "NO" -->
                            <?php elseif ($_SESSION['role'] == 1): ?>
                                <td class="text-center border-1 d-none d-md-table-cell">
                                    <i class="fa-solid fa-circle" style="color: <?= $singleUserData ? '#118609' : '#eb0017'?>;"></i>
                                </td>
                            <?php endif; ?>

                            <!-- Per tutti gli altri campi, stampa il dato normalmente. -->
                        <?php else: ?>
                            <td class="border-1"><?= htmlspecialchars((string) $singleUserData) ?></td>
                        <?php endif;
                    endforeach; ?>

                        <!-- Se si è admin+, stampa le azioni possibili per la riga -->
                        <?php if ($_SESSION['role'] >= 2): ?>
                            <td>
                                <div class="d-flex justify-content-between">
                                    <a href="/users_update?id=<?=$userData['id'] ?>" class="btn btn-sm btn-outline-dark rounded-0 col-auto"><?= Lang::getText("general:update") ?></a>
                                    <button onclick="deleteUser(<?= $userData['id'] ?>)" class="btn border-danger text-danger rounded-0 btn-sm col-auto"><?= Lang::getText("general:delete") ?></button>
                                </div>
                            </td>
                            <td class="border-1 px-2 text-center d-none d-lg-table-cell">
                                <input type="checkbox" class="toggle_checkbox" id="checkbox-id-<?= $userData['id'] ?>" onclick="toggleIdInDeletingArray(<?= $userData['id'] ?>, event)">
                            </td>
                        <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Tabella fino a 768px -->
    <table class="table table-striped d-md-none">
        <thead>
            <tr class="text-white text-center">
            <?php foreach($headers as $key => $header): ?>
                <?php if ($key == 'firstname' || $key == 'lastname'): ?>
                    <th role="button" class="border-1 px-1 py-2" onclick="key = '<?= $key ?>'; orderDirection = '<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>'; reloadTable()">
                        <div class="d-flex align-items-center">
                            <span class="mx-2">
                                <?= $header ?> 
                            </span>
                            
                            <?php if ($orderBy == $key): ?>
                                <i class="fas fa-angle-<?= $orderDirection == 'ASC' ? 'down' : 'up' ?> fa-sm"></i>
                            <?php endif; ?>
                        </div>
                    </th>
                <?php elseif ($key == 'email'): ?>
                    <th role="button" class="border-1 px-1 py-2 d-none d-sm-table-cell" onclick="key = '<?= $key ?>'; orderDirection = '<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>'; reloadTable()">
                        <div class="d-flex align-items-center">
                            <span class="mx-2">
                                <?= $header ?> 
                            </span>
                            
                            <?php if ($orderBy == $key): ?>
                                <i class="fas fa-angle-<?= $orderDirection == 'ASC' ? 'down' : 'up' ?> fa-sm"></i>
                            <?php endif; ?>
                        </div>
                    </th>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($_SESSION['role'] >= 2): ?>
                <th class="border-1 px-1 py-2"><?= Lang::getText("general:actions") ?></th>
            <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!$users['values']): ?>
                <tr>
                    <td colspan="<?= count($users['keys']) + 2 ?>">
                        <p class="fw-bold text-center"><?= Lang::getText("general:no_data_available") ?></p>
                    </td>
                </tr>
            <?php else: ?>
            <?php foreach($users['values'] as $userData): ?>
                
                <tr class="border-1 px-1 py-2" id="user-row-<?= $userData['id'] ?>">
                    <?php foreach ($userData as $key => $user): ?>
                        <?php if ($key == 'firstname' || $key == 'lastname'): ?>
                            <td class="border-1">
                                <?= htmlspecialchars((string) $user) ?>
                            </td>
                        <?php elseif ($key == 'email'): ?>
                            <td class="border-1 d-none d-sm-table-cell">
                                <?= htmlspecialchars(($user)) ?>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                        <?php if ($_SESSION['role'] >= 2): ?>
                            <td class="justify-content-between col-auto">
                                <div class="d-flex justify-content-between">
                                    <button onclick="deleteUser(<?= $userData['id'] ?>)" class="btn btn-outline-danger text-danger rounded-0 btn-sm"><?= Lang::getText("general:delete") ?></button>
                                    <a href="/users_update?id=<?=$userData['id'] ?>" class="btn btn-outline-dark text-dark rounded-0 btn-sm"><?= Lang::getText("general:update") ?></a>
                                </div>
                            </td>
                            <?php endif; ?>
                            <td class="border-1 px-2 text-center d-none d-lg-table-cell">
                                <input type="checkbox" onclick="toggleIdInDeletingArray(<?= $userData['id'] ?>)">
                            </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
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
        <div class="text-center fw-bold d-none d-sm-block"><?= sprintf(Lang::getText("general:paginator"), $page, $totalPages) ?></div>

        <!-- Elementi visibili per pagina -->
        <div class="btn-group h-25 ms-5" role="group">
            <?php foreach($possiblePerPage as $perPageButton): ?>
                <button type="button" onclick="perPage = <?= (int)$perPageButton ?>; reloadTable()" class="btn btn-outline-dark btn-sm rounded-0 <?= $perPage == $perPageButton ? 'btn-dark text-white' : '' ?>" title="<?= $perPageButton ?> <?= Lang::getText("general:visible_elements_per_page") ?>">
                    <?= $perPageButton ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>


    <?php else: ?>
        <!-- Pulsanti sopra la tabella -->
        <div class="d-flex justify-content-between align-items-center mb-1">
            <!-- Pagina precedente -->
            <a href="/" class="btn btn-sm btn-secondary rounded-50 col-auto" >
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            
            <!-- ** SE ADMIN ** Pulsanti per creare un nuovo utente o eliminare più dati -->
            <div class="buttons col-auto">
                <?php if ($_SESSION['role'] >= 2): ?>
                    <a href="../users_update" class="btn btn-sm btn-success col-auto fw-bold">
                        + <?= Lang::getText("general:create") ?>
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
    const users = [];
    const usersIdArray = <?= json_encode($usersIdArray) ?>;

    function toggleIdInDeletingArray(userId) {
        if (event) event.stopPropagation();
        const row_checkbox = document.getElementById('checkbox-id-' + userId);
        if (users.includes(userId)) {
            let index = users.indexOf(userId);
            users.splice(index, 1);
            row_checkbox.checked = false;
        } else {
            users.push(userId);
            row_checkbox.checked = true;
        }
        updateMainCheckboxState();
    }

    function handleRowClick(userId, event) {

        // Cliccando su un punto vuoto della riga, controlla se la tipologia è uno dei valori sotto, se true non fa nulla, quindi seleziona la riga.
        if (event.target.type === 'checkbox' || 
            event.target.tagName === 'INPUT' || 
            event.target.tagName === 'BUTTON' || 
            event.target.tagName === 'A' ||
            event.target.closest('button') ||
            event.target.closest('a')) {
            return;
        }
        
        toggleIdInDeletingArray(userId);
    }
    
    function toggleAllUsersInDeletingArray() {
        const mainCheckbox = document.getElementById('selectAllCheckbox');
        const checkboxes = document.querySelectorAll('tbody input.toggle_checkbox');
        
        if (mainCheckbox.checked) {
            users.length = 0;
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
                const userId = parseInt(checkbox.closest('tr').id.replace('user-row-', ''));
                if (!users.includes(userId)) {
                    users.push(userId);
                }
            });
        } else {
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            users.length = 0;
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