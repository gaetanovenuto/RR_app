<?php
include __DIR__ . '/../../config.php';

$logTitle = "Users table";
if (User::isModerator()) {
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
    
if ($_POST['deleteUserData'] ?? false) {
    $result = User::delete('id = ' . $_POST['id']);
    if (!$result) {
        echo json_encode([
                "success" => 0,
                "message" => Lang::getText("error:delete_failed")
            ]);
            exit();
    }
    echo json_encode([
        "success" => 1,
        "message" => Lang::getText("success:delete")
    ]);
    exit();
}

if ($_POST['updateState'] ?? false) {
    $users = User::select([
        "where" => sprintf("id = %s", $_POST['id'])
    ]);
    $user = $users[0];
    
    if ($_POST['updatedUserState'] == 0) {
        if ($_SESSION['id'] != $user['id']) {
            if ($_SESSION['role'] > $user['role']) {
                $result = User::update(["enabled" => $_POST['updatedUserState']], "id = " . $_POST['id']);
                Log::create([
                    [
                        "user_id" => $_SESSION['id'],
                        "action_type" => sprintf("Utente %s correttamente", $_POST['updatedUserState'] ? Lang::getText("general:enabled") : Lang::getText("general:disabled")),
                        "ip_address" => $_SERVER['REMOTE_ADDR'],
                        "details" => sprintf("L'utente ha %s correttamente l'utente con id: {$_POST['id']}", $_POST['updatedUserState'] ? Lang::getText("general:enabled") : Lang::getText("general:disabled")),
                        "level" => 2
                    ]
                ]);
            } else {
                Log::create([
                    [
                        "user_id" => $_SESSION['id'],
                        "action_type" => "Tentativo disabilitazione utente",
                        "ip_address" => $_SERVER['REMOTE_ADDR'],
                        "details" => "L'utente ha provato a disabilitare l'utente con id: {$_POST['id']} ma ha fallito in quanto l'utente ha un ruolo pari o superiore al suo.",
                        "level" => 3
                        ]
                ]);
                echo json_encode([
                    "success" => 0,
                    "message" => Lang::getText("error:user_disable_superior")
                ]);
                exit();
            }
        } else {
            echo json_encode([
                "success" => 0,
                "message" => Lang::getText("error:user_disable_self")
            ]);
            exit();
        }
    } else {
        $result = User::update(["enabled" => $_POST['updatedUserState']], "id = " . $_POST['id']);
    }

    if (!$result) {
        echo json_encode([
            "success" => 0,
            "message" => sprintf(Lang::getText("error:user_disabling_text"), $_POST['updatedUserState'] ? Lang::getText("error:user_enabling") : Lang::getText("error:user_disabling"))
            
        ]);
        exit();
    }
    echo json_encode([
        "success" => 1,
        "message" => sprintf(Lang::getText("user:enable_success"), $_POST['updatedUserState'] ? Lang::getText("user:enabled") : Lang::getText("user:disabled"))
    ]);
    exit();
}

if ($_POST['deleteMultipleUsers'] ?? false) {
    $deletingUsersArray = json_decode($_POST['deletingUsersArray'], true);
    if ($deletingUsersArray ?? false) {
        $result = User::delete('id in (' . implode(", ", $deletingUsersArray) . ")");
        
        if (!$result) {
            echo json_encode([
                "success" => 0, "message" => Lang::getText("error:user_multiple_delete")
            ]);
            exit();
        }
        echo json_encode([
            "success" => 1,
            "message" => Lang::getText("success:user_multiple_delete")
        ]);
        exit();
    }
}

$pageTitle = Lang::getText("user:registered_users");
include_once __DIR__ . '/../../template/header.php';
?>
<?php if (!$deniedAccess): ?>
    <div class="row w-100 mx-2">
        <h3 class="my-1 text-center fw-bold">
        <?= $pageTitle ?>
    </h3>
    <div id="tableContent">
        <?php include_once 'usersTableContent.php' ?>
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

async function deleteUser(userId) {
    if (confirm('Sei sicuro di voler eliminare questo utente?')) {
        const data = new FormData();
        data.append("id", userId);
        data.append("deleteUserData", true);

        fetch("/admin/user/usersTable.php", {
            method: 'POST',
            body: data
        }).then(async (res) => {
            const response = await res.json();
            reloadTable(response);
        });
    }
}

async function deleteMultipleUsers(usersArray) {
    if (usersArray.length === 0) {
        toast.classList.add('show', 'bg-danger');
        toastBody.innerHTML = '<?= Lang::getText("user:no_selected_user") ?>';
        setTimeout(() => {
            toast.classList.remove('show', 'bg-success', 'bg-danger');
        }, 3000);
        return;
    }
    
    if (confirm('<?= Lang::getText("user:confirm_delete_multiple_users") ?>')) {
        const data = new FormData();
        data.append("deletingUsersArray", JSON.stringify(usersArray));
        data.append("deleteMultipleUsers", true);

        fetch("/admin/user/usersTable.php", {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();
            reloadTable(response);
        });
    }
}

async function toggleUserAbilitation(userId, userFirstname, userLastname, userState) {
    let updatedUserState;
    if (confirm("<?= Lang::getText("user:are_you_sure_you_want") ?>" + " " + (userState ? "<?= Lang::getText("user:to_disable") ?>" : "<?= Lang::getText("user:to_enable") ?>") + " " + userFirstname + " " + userLastname + "?")) {
        updatedUserState = (userState ? 0 : 1);
        const data = new FormData();
        data.append("id", userId);
        data.append("updatedUserState", updatedUserState);
        data.append("updateState", true);

        fetch('/admin/user/usersTable.php', {
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

function updateFilterBadges(search_text) {
    const badgeContainer = document.querySelector('.tags');
    if (!badgeContainer) return;
    
    badgeContainer.innerHTML = '';
    
    if (search_text.trim() !== '') {
        badgeContainer.innerHTML += `<span class="badge dark_blue_bg me-1 p-2 text-white">${search_text}</span>`;
    }
}

async function reloadTable(response = null) {
    const search_text = document.getElementById('search_text').value;

    const data = new FormData();
    data.append("orderTable", true);
    data.append('orderBy', key);
    data.append('orderDirection', orderDirection);
    data.append('page', parseInt(page)); 
    data.append('perPage', parseInt(perPage));
    data.append('response', JSON.stringify(response));
    data.append('search_filter', search_text);


    fetch('/admin/user/usersTableContent.php', {
        method: 'POST',
        body: data
    }).then(async (res) => {
        if (!res.ok) {
            errorBox.innerHTML = '<?= Lang::getText("general:error_loading_data") ?>'; 
            return;
        }
        const tableData = await res.text();
        let tableContent = document.getElementById('tableContent');
        tableContent.innerHTML = tableData;

        
        restoreInputValues(search_text);
        updateFilterBadges(search_text);
        
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
function restoreInputValues(search_text) {
    const searchInput = document.getElementById('search_text');
    
    if (searchInput) {
        searchInput.value = search_text;
    }
}

function resetFilters() {
    document.getElementById('search_text').value = '';
    
    page = 1;
    
    reloadTable();
}
</script>




