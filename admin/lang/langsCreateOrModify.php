<?php
include __DIR__ . '/../../config.php';

$logTitle = "Languages create/update";
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

$lang = null;
$langExists = false;
$new = false;
if ($_GET['id'] ?? false) {
    if (is_numeric($_GET['id'] ?? false))
    $lang = Lang::getSingleData($_GET['id']);
    if ($lang) {
        $langExists = true;
    }
} else {
    $new = true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['translation_saved'] ?? false) {
    unset($_POST['translation_saved']);
    $text = Lang::select([
        "where" => sprintf("key_lang = '%s'", $_POST['key_lang'])
    ]);
    if ($text ?? false) {
        $updatedData = Lang::update($_POST, sprintf("id = %d", $text[0]['id']));
        Log::create([
            [
                "user_id" => $_SESSION['id'],
                "action_type" => "Traduzione modificata",
                "ip_address" => $_SERVER['REMOTE_ADDR'],
                "details" => "L'utente ha modificato la traduzione con id: {$text[0]['id']}",
                "level" => 1
            ]
        ]);
    } else {
        $updatedData = Lang::create([$_POST]);
        Log::create([
            [
                "user_id" => $_SESSION['id'],
                "action_type" => "Traduzione creata",
                "ip_address" => $_SERVER['REMOTE_ADDR'],
                "details" => "L'utente ha creato una nuova traduzione",
                "level" => 1
            ]
        ]);
    }

    if ($updatedData ?? false) {
        echo json_encode([
            "success" => 1,
            "message" => "Traduzione " . ($text ? "aggiornata" : "creata") . " correttamente"
        ]);
    } else {
        echo json_encode([
            "success" => 0,
            "message" => "Si è verificato un errore nel salvataggio"
        ]);
    }
exit();    
}

$pageTitle = $lang ? 'MODIFICA TRADUZIONE' : 'AGGIUNGI TRADUZIONE';
include_once './template/header.php';
?>
<?php if (!$deniedAccess): ?>
    <?php if ($langExists || $new = true): ?>
    <div class="toast position-absolute align-items-center text-white border-0 rounded-0 opacity-9" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex justify-content-center align-items-center">
            <div class="toast-body text-white fw-bold">
            </div>
        </div>
    </div>
    <form class="mt-3 w-75" method="POST" id="translation_form">
        <h3 class="text-center mb-4"><?= $pageTitle ?></h3>
        
        <div class="row justify-content-center align-items-center mb-3">
            <label for="key_lang" class="form-label mb-0 col-auto fw-bold">Chiave di riconoscimento</label>
            <input type="text" name="key_lang" id="key_lang" class="form-control form-control-sm col" value="<?= $lang['key_lang'] ?? '' ?>">
        </div>
        <div class="accordion d-flex flex-wrap justify-content-between" id="accordion_languages">
            <?php foreach (Lang::$languages as $key => $language): ?>
            <div class="col-12 dark_blue_bg text-white d-flex justify-content-between">
                <div class="lang-info ps-2 col-4 d-flex justify-content-start align-items-center">
                    <span><img src="<?= $language['flag'] ?>" alt="<?= $key ?>"></span><?= $language['label'] ?>
                </div>
                <textarea class="col-6 m-2 rounded" name="<?= $key ?>" id="translation_area_<?= $key ?>"><?= $lang[$key] ?? '' ?></textarea>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-end mt-2">
            <button class="btn btn-sm btn-primary">Salva</button>
        </div>
    </form>
    <?php else: ?>
        <?php include_once __DIR__ . '/../../users/404.php' ?>
    <?php endif; ?>
<?php else: ?>
    <?php include_once __DIR__ . '/../../users/access_denied.php' ?>
<?php endif; ?>

<?php include_once './template/footer.php'; ?>

<script>
const toast = document.querySelector('.toast');
const toastBody = document.querySelector('.toast-body');

document.getElementById('translation_form').addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const form = e.target;
    const data = new FormData(form);
    data.append('translation_saved', true);

    fetch('/admin/lang/langsCreateOrModify.php', {
        method: "POST",
        body: data
    }).then(async (res) => {
        const response = await res.json();
        toast.classList.remove('show', 'bg-success', 'bg-danger');
        toastBody.innerHTML = '';
        if (response.success) {
            toast.classList.add('show', 'bg-success');
        } else {
            toast.classList.add('show', 'bg-danger');
        }
        toastBody.innerHTML = response.message;

        setTimeout(() => {
            toast.classList.remove('show', 'bg-success', 'bg-danger');
        }, 5000);
    })
})
</script>
