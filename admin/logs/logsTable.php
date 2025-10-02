<?php
include __DIR__ . '/../../config.php';

$logTitle = "Logs table";
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

if ($_POST['fetch_details'] ?? false) {
    $logs = Log::select([
        "where" => sprintf("id = %s", $_POST['id'])
    ]);
    
    if ($logs ?? false) {
        $log = $logs[0];
        
        echo json_encode([
            "success" => 1,
            "log" => $log
        ]);
    } else {
        echo json_encode([
            "success" => 0,
            "message" => "Si è verificato un errore nella visualizzazione dell'errore"
        ]);
    }
    exit();
}



$pageTitle = 'LOGS';
include_once __DIR__ . '/../../template/header.php';
?>
<?php if (!$deniedAccess): ?>
    <div class="row w-100 mx-2">
        <h3 class="my-1 text-center fw-bold">
            <?= $pageTitle ?>
        </h3>
        <div id="tableContent" class="position-relative px-0">
            <?php include_once 'logsTableContent.php' ?>

            <div class="modal fade" id="modal-errors" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-errors-label"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="modal-errors-body">
                        ...
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
                    </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="toast position-absolute align-items-center text-white border-0 rounded-0 opacity-9" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex justify-content-center align-items-center">
                <div class="toast-body text-white fw-bold">
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php include_once __DIR__ . '/../../users/access_denied.php' ?>
<?php endif; ?>

<?php 
    include_once __DIR__ . '/../../template/footer.php';
?>
<script>
// GENERIC FUNCTIONS
let key = 'id';
let orderDirection = 'DESC';
let page = 1;
let perPage = 10;

const toast = document.querySelector('.toast');
const toastBody = document.querySelector('.toast-body');
const modal = document.getElementById('modal-errors');
const modal_title = document.querySelector('.modal-title');
const modal_body = document.querySelector('.modal-body');

async function reloadTable(response = null) {
    const search_text = document.getElementById('search_text').value;
    const starting_date = document.getElementById('starting_date').value;
    const ending_date = document.getElementById('ending_date').value;

    const data = new FormData();
    data.append("orderTable", true);
    data.append('orderBy', key);
    data.append('orderDirection', orderDirection);
    data.append('page', parseInt(page)); 
    data.append('perPage', parseInt(perPage));
    data.append('response', JSON.stringify(response));
    data.append('search_filter', search_text);
    data.append('starting_date', starting_date);
    data.append('ending_date', ending_date);

    fetch('/admin/logs/logsTableContent.php', {
        method: 'POST',
        body: data
    }).then(async (res) => {
        if (!res.ok) {
            errorBox.innerHTML = 'Errore nel caricamento dei dati';
            return;
        }
        const tableData = await res.text();
        let tableContent = document.getElementById('tableContent');
        tableContent.innerHTML = tableData;

        restoreInputValues(search_text, starting_date, ending_date);
        updateFilterBadges(search_text, starting_date, ending_date);

        if (response ?? false) {
            toast.classList.add('show');
            if (response.success) {
                toast.classList.add('bg-success');
            } else {
                toast.classList.add('bg-danger');
            }
            toastBody.innerHTML = response.message;
    
            setTimeout(() => {
                toast.classList.remove('show', 'bg-success', 'bg-danger');
            }, 3000);
        }
    })
}

function updateFilterBadges(search_text, starting_date, ending_date) {
    const badgeContainer = document.querySelector('.tags');
    if (!badgeContainer) return;
    
    badgeContainer.innerHTML = '';
    
    if (search_text.trim() !== '') {
        badgeContainer.innerHTML += `<span class="badge dark_blue_bg me-1 p-2 text-white">${search_text}</span>`;
    }
    
    if (starting_date && ending_date) {
        if (ending_date <= starting_date) {
            badgeContainer.innerHTML += `<span class="badge dark_blue_bg me-1 p-2 text-white">${starting_date}</span>`;
        } else {
            badgeContainer.innerHTML += `<span class="badge dark_blue_bg me-1 p-2 text-white">Dal ${starting_date} al ${ending_date}</span>`;
        }
    } else if (starting_date) {
        badgeContainer.innerHTML += `<span class="badge dark_blue_bg me-1 p-2 text-white">Da: ${starting_date}</span>`;
    } else if (ending_date) {
        badgeContainer.innerHTML += `<span class="badge dark_blue_bg me-1 p-2 text-white">Fino: ${ending_date}</span>`;
    }
}

function restoreInputValues(search_text, starting_date, ending_date) {
    const searchInput = document.getElementById('search_text');
    const startingDateInput = document.getElementById('starting_date');
    const endingDateInput = document.getElementById('ending_date');
    
    if (searchInput) {
        searchInput.value = search_text;
    }
    
    if (startingDateInput) {
        startingDateInput.value = starting_date;
    }
    
    if (endingDateInput) {
        endingDateInput.value = ending_date;
    }
}

function showModal(id) {  
    const data = new FormData();
    data.append("id", id);
    data.append("fetch_details", true);

    fetch('/admin/logs/logsTable.php', {
        method: "POST",
        body: data
    }).then(async (res) => {
        const response = await res.json();
        const log = response.log;
        
        modal_title.innerHTML = "Dettagli del log #" + log.id;
        if (log.details) {
            modal_body.innerHTML = log.details;
        } else {
            modal_body.innerHTML = 'Nessun dettaglio presente';
        }
        new bootstrap.Modal(modal).show();
        
    })
}

    

    function resetFilters() {
        document.getElementById('search_text').value = '';
        document.getElementById('starting_date').value = '';
        document.getElementById('ending_date').value = '';
        
        page = 1;
        
        reloadTable();
    }

</script>




