<?php
include __DIR__ . '/../../config.php';

$logTitle = "Email logs table";
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

if ($_POST['sendMultipleMails'] ?? false) {
    $logMailsArray = json_decode($_POST['sendingMailsArray'], true);
    if ($logMailsArray ?? false) {
        $mails = Mail::select([
            "columns" => "recipient, subject, body, id, state",
            "where" => sprintf("id in (%s)", implode(", ", $logMailsArray)),
            "limit" => -1
        ]);
        $logs = [];
        foreach ($mails as $mail) {
            $sentEmail = Mail::send($mail['recipient'], $mail['subject'], $mail['body']);
            Log::create([
                [
                    "user_id" => $_SESSION['id'],
                    "action_type" => "Email reinviata",
                    "ip_address" => $_SERVER['REMOTE_ADDR'],
                    "details" => "L'utente ha reinviato la mail con id: {$mail['id']} a: {$mail['recipient']}",
                    "level" => 2
                    ]
            ]);
            $logs[] = $sentEmail;
            $errorLogs = [];
            foreach ($logs as $i => $log) {
                if ($log['state'] == 0) {
                    $errorLogs[] = $logs[$i];
                }
            }
            
        }
        if (empty($errorLogs)) {
            echo json_encode([
                "success" => 1,
                "message" => "Email inviate correttamente"
            ]);
        } else {
            Log::create([
                [
                    "user_id" => $_SESSION['id'],
                    "action_type" => "Fallito reinvio mail",
                    "ip_address" => $_SERVER['REMOTE_ADDR'],
                    "details" => "L'utente ha fallito nel reinvio della mail con id: {$mail['id']} a: {$mail['recipient']}",
                    "level" => 2
                ]
            ]);
            echo json_encode([
                "success" => 0,
                "message" => "Attenzione: alcune email non sono state inviate."
            ]);
        }
    }
    exit();
}

if ($_POST['fetch_error'] ?? false) {
    $mails = Mail::select([
        "where" => sprintf("id = %s", $_POST['id'])
    ]);

    if ($mails ?? false) {
        $mail = $mails[0];
        
        echo json_encode([
            "success" => 1,
            "mail" => $mail
        ]);
    } else {
        echo json_encode([
            "success" => 0,
            "message" => "Si è verificato un errore nella visualizzazione dell'errore"
        ]);
    }
    exit();
}

$pageTitle = Lang::getText("log:log_mails");
include_once __DIR__ . '/../../template/header.php';
?>
<?php if (!$deniedAccess): ?>
    <div class="row w-100 mx-2">
        <h3 class="my-1 text-center fw-bold">
            <?= $pageTitle ?>
        </h3>
        <div id="tableContent" class="position-relative px-0">
            <?php include_once 'emailLogsTableContent.php' ?>

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

async function sendMultipleMails(mailsArray) {
    if (mailsArray.length === 0) {
        alert("Nessuna email selezionata");
        return;
    }
    
    if (confirm('Sei sicuro di voler inviare queste email?')) {
        const data = new FormData();
        data.append("sendingMailsArray", JSON.stringify(mailsArray));
        data.append("sendMultipleMails", true);

        fetch("/admin/emailLogs/emailLogsTable.php", {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();
            logMails.length = 0;
            reloadTable(response);
        })
    }
}

async function reloadTable(response = null) {
    const data = new FormData();
    data.append("orderTable", true);
    data.append('orderBy', key);
    data.append('orderDirection', orderDirection);
    data.append('page', parseInt(page)); 
    data.append('perPage', parseInt(perPage));
    data.append('response', JSON.stringify(response));

    fetch('/admin/emailLogs/emailLogsTableContent.php', {
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

function showModal(id) {
    
    const data = new FormData();
    data.append("id", id);
    data.append("fetch_error", true);

    fetch('/admin/emailLogs/emailLogsTable.php', {
        method: "POST",
        body: data
    }).then(async (res) => {
        const response = await res.json();
        const mail = response.mail;
        
        modal_title.innerHTML = "Errore per l'email #" + mail.id;
        modal_body.innerHTML = mail.error;
        new bootstrap.Modal(modal).show();
        
    })
}

</script>




