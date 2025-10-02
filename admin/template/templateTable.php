<?php
include __DIR__ . '/../../config.php';

$logTitle = "Template table";
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

if ($_POST['deleteTemplateData'] ?? false) {
    $result = Mail_templates::delete('id = ' . $_POST['id']);
    if (!$result) {
        echo json_encode([
            "success" => 0,
            "message" => "Errore nell'eliminazione del template"
        ]);
        exit();
    }
    echo json_encode([
        "success" => 1,
        "message" => "Template eliminato correttamente"
    ]);
    exit();
}

if ($_POST['updateState'] ?? false) {
    $result = Mail_templates::update(["enabled" => $_POST['updatedTemplateState']], "id = " . $_POST['id']);
    
    if (!$result) {
        echo json_encode([
            "success" => 0,
            "message" => sprintf("Errore %s del template", $_POST['updatedTemplateState'] ? 'nell\'abilitazione' : 'nella disabilitazione')
        ]);
        exit();
    }
    echo json_encode([
        "success" => 1,
        "message" => sprintf("Template %s correttamente", $_POST['updatedTemplateState'] ? 'abilitato' : 'disabilitato')
    ]);

    exit();
}

if ($_POST['deleteMultipleTemplates'] ?? false) {
    $deletingTemplatesArray = json_decode($_POST['deletingTemplatesArray'], true);
    if ($deletingTemplatesArray ?? false) {
        $result = Mail_templates::delete('id in (' . implode(", ", $deletingTemplatesArray) . ")");
        
        if (!$result) {
            echo json_encode(["success" => 0, "message" => "Errore nell'eliminazione dei template"]);
            exit();
        }
        echo json_encode(["success" => 1]);
        exit();
    }
}

$pageTitle = 'TEMPLATE EMAIL';
include_once __DIR__ . '/../../template/header.php';
?>
<?php if (!$deniedAccess): ?>
    <div class="row w-100 mx-2">
        <h3 class="my-1 text-center fw-bold">
            <?= $pageTitle ?>
        </h3>
        <div id="tableContent">
            <?php include_once 'templateTableContent.php' ?>
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
let orderDirection = 'ASC';
let page = 1;
let perPage = 10;

const toast = document.querySelector('.toast');
const toastBody = document.querySelector('.toast-body');

async function deleteTemplate(templateId) {
    if (confirm('Sei sicuro di voler eliminare questo template?')) {
        const data = new FormData();
        data.append("id", templateId);
        data.append("deleteTemplateData", true);

        fetch("/admin/template/templateTable.php", {
            method: 'POST',
            body: data
        }).then(async (res) => {
            const response = await res.json();
            reloadTable(response);
        });
    }
}

async function deleteMultipleTemplates(templatesArray) {
    if (templatesArray.length === 0) {
        alert("Nessun template selezionato");
        return;
    }
    
    if (confirm('Sei sicuro di voler eliminare questi template?')) {
        const data = new FormData();
        data.append("deletingTemplatesArray", JSON.stringify(templatesArray));
        data.append("deleteMultipleTemplates", true);

        fetch("/admin/template/templateTable.php", {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();
            reloadTable(response);
        });
    }
}

async function toggleTemplateAbilitation(templateId, templateType, templateState) {
    let updatedTemplateState;

    if (confirm("Sei sicuro di voler " + (templateState ? "disabilitare" : "abilitare") + " " + templateType + "?")) {
        updatedTemplateState = (templateState ? 0 : 1);
        const data = new FormData();
        data.append("id", templateId);
        data.append("updatedTemplateState", updatedTemplateState);
        data.append("updateState", true);

        fetch('/admin/template/templateTable.php', {
            method: 'POST',
            body: data
        }).then(async (res) => {
            const response = await res.json();
            reloadTable(response);
        })
    } else {
        const checkbox = document.querySelector(`#user-row-${userId} .form-check-input`);
        if (checkbox) {
            checkbox.checked = userState ? true : false;
        }
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

    fetch('/admin/template/templateTableContent.php', {
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
</script>




