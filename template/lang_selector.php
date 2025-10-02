<?php
include_once __DIR__ . '/../config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['changed_lang'] ?? false) {
        if (in_array($_POST['language'], array_keys(Lang::$languages))) {
            $_SESSION['lang'] = $_POST['language'];
            
            echo json_encode([
                "success" => 1,
                "message" => "Lingua modificata correttamente",
                "active_lang" => $_SESSION['lang']
            ]);
        } else {
            echo json_encode([
                "success" => 0,
                "message" => "Lingua non disponibile"
            ]);
        }
        exit();
    }
}

if ($_SESSION['lang'] ?? false) {

} else {
    $_SESSION['lang'] = 'it';
}
?>

<div class="dropdown">
    <a class="btn btn-sm px-0 ms-3" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <?php foreach (Lang::$languages as $index => $language): ?>
            <?php if ($_SESSION['lang'] == $index): ?>
            <img src="<?= $language['flag'] ?>" alt="<?= $language['label'] ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    </a> 
    <ul class="dropdown-menu">
        <?php foreach (Lang::$languages as $key => $language): ?>
            <?php if ($_SESSION['lang'] != $key && $language['enabled'] > 0): ?>
            <li>
                <button class="dropdown-item" onclick="changeLanguage('<?= $key ?>')">
                    <input type="hidden" name="lang_<?= $key ?>" value="<?= $key ?>">
                    <img src="<?= $language['flag'] ?>" alt="<?= $language['label'] ?>">
                    <span><?= $language['label'] ?></span>
                </button>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>

<script>
    function changeLanguage(lang) {
        const data = new FormData();
        data.append("language", lang);
        data.append("changed_lang", true);

        fetch('/template/lang_selector.php', {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();

            if (response.success) {
                location.reload();
            }
        })
    }
</script>