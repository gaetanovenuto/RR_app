<?php
include __DIR__ . '/../../config.php';
$logTitle = "Languages table";

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['imported_file'] ?? false) {
    $file_name = $_FILES['file']['tmp_name'];
    
    if ($file_name ?? false) {
        if (pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION) != "csv") {
            echo json_encode([
                "success" => 0,
                "message" => "Importa un file con formato .csv"
            ]);
            exit();
        }
        
        $imported = Lang::importCSV($file_name);
        
        if ($imported) {
            echo json_encode([
                "success" => 1,
                "message" => "File caricato correttamente"
            ]);
        } else {
            echo json_encode([
                "success" => 0,
                "message" => "Si è verificato un errore nell'importazione del file"
            ]);
        }
    } else {
        echo json_encode([
            "success" => 0,
            "message" => "Carica un file"
        ]);
    }
    exit();
}

$pageTitle = 'LANGUAGES';
include_once __DIR__ . '/../../template/header.php';
?>
<?php if (!$deniedAccess): ?>
    <div class="row w-100 mx-2">
        <h3 class="my-1 text-center fw-bold">
            <?= $pageTitle ?>
        </h3>
        <div id="tableContent" class="position-relative px-0">
            <?php include_once 'langsTableContent.php' ?>

            <div class="modal fade" id="modal-errors" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-errors-label"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="modal-errors-body">
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    const data = new FormData();
    data.append("orderTable", true);
    data.append('orderBy', key);
    data.append('orderDirection', orderDirection);
    data.append('page', parseInt(page)); 
    data.append('perPage', parseInt(perPage));
    data.append('response', JSON.stringify(response));

    fetch('/admin/lang/langsTableContent.php', {
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

document.getElementById('import_form').addEventListener('submit', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const form = e.target;
    const data = new FormData(form);
    data.append("imported_file", true);

    fetch('/admin/lang/langsTable.php', {
        method: "POST",
        body: data
    }).then(async (res) => {
        const response = await res.json();
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
            }, 5000);
        }
    })
})

</script>




