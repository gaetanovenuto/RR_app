<?php
include __DIR__ . '/../../config.php';

$logTitle = "Template create/update";
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
$template = null;
$errors = [];

$templateExists = false;
$new = false;

$error_message = "Template non trovato, inserisci un id numerico esistente.";

if (($_GET['id'] ?? false) ) {
    if (is_numeric($_GET['id'])) {
        $template = Mail_templates::getSingleData($_GET['id']);
        
        if ($template) {
            $templateExists = true;
        }
    }
} else {
    $new = true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['saving'] ?? false) {   
    $updatedData = [
        "type" => $_POST['type'],
        "subject" => $_POST['subject'],
        "body" => $_POST['body'],
        "enabled" => isset($_POST['enabled']) ? 1 : 0
    ];
    
    if ($_POST['id'] ?? false) {
        $updatedData['id'] = $_POST['id'];
        $template = Mail_templates::getSingleData($_POST['id']);
    }
    
    if (empty($errors)) {
        if ($_POST['id'] ?? false) {
            if (!in_array($_POST['type'], array_keys(Mail_templates::$possibleTypes))) {
                echo json_encode(["success" => 0, "message" => "Impossibile creare un template per una categoria non esistente."]);
                exit();
            } else {
                $updateResult = Mail_templates::update($updatedData, "id = " . $_POST['id']);
                
                if ($updateResult) {
                    Log::create([
                        [
                            "user_id" => $_SESSION['id'],
                            "action_type" => "Template modificato",
                            "ip_address" => $_SERVER['REMOTE_ADDR'],
                            "details" => "L'utente ha modificato il template con id: {$_POST['id']}",
                            "level" => 2
                        ]
                    ]);
                    echo json_encode(["success" => 1, "message" => "Template aggiornato correttamente"]);
                } else {
                    echo json_encode(["success" => 0, "message" => "Errore durante l'aggiornamento del template"]);
                }
                exit();
            } 
        } else {
            $createResult = Mail_templates::create([$updatedData]);
            if ($createResult) {
                Log::create([
                    [
                        "user_id" => $_SESSION['id'],
                        "action_type" => "Template creato",
                        "ip_address" => $_SERVER['REMOTE_ADDR'],
                        "details" => "L'utente ha creato un nuovo template",
                        "level" => 2
                        ]
                    ]);
                    echo json_encode(["success" => 1, "message" => "Template creato correttamente"]);
            } else {
                echo json_encode(["success" => 0, "message" => "Errore durante la creazione del template"]);
            }
            exit();
        }
    }
}



$pageTitle = "Editor mail template";
include_once './template/header.php';
?>
<?php if (!$deniedAccess): ?>

    <?php if ($templateExists || $new == true): ?>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <div class="row flex-column w-75">
        <div class="row p-2 align-items-center">
            
            <a href="/mail_templates" class="btn btn-secondary rounded-50 col-auto d-flex justify-content-center align-items-center h-100"><i class="fa-solid fa-arrow-left"></i></a>
            <h3 class="text-center mt-2 col">
                <?= $pageTitle ?>
            </h3>
        </div>
        <form method="POST" id="confirmation_email_editor">
            <div class="form-group">
                <div class="row my-2 align-items-center">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($template['id'] ?? '') ?>">
                    <div class="subject_container col-12 col-lg-5 d-flex align-items-center">
                            <label for="subject" class="col-auto me-2">Oggetto:</label>
                            <input id="subject" name="subject" type="text" placeholder="Inserisci l'oggetto della mail" class="form-control-sm col-auto" value="<?= htmlspecialchars($template['subject'] ?? '') ?>">
                    </div>
                    <div class="enabled_container form-check form-switch col-12 col-lg-2 mx-2 mx-lg-0">
                            <label for="enabled" class="col-auto">Abilitato:</label>
                            <input type="checkbox" name="enabled" class="col-auto form-check-input" <?= ($template && $template['enabled']) ? 'checked' : ''?>>
                    </div>
                    <div class="type_container col-12 col-lg-5 d-flex align-items-center">
                            <label for="type" class="col-auto me-2">Nome:</label>
                            <select name="type" id="type" class="form-control-sm col-auto">
                                <option selected disabled>Scegli una tipologia</option>
                                <?php foreach (Mail_templates::$possibleTypes as $key => $value): ?>
                                    <?php $option = Mail_templates::select(["where" => sprintf("type = '%s' AND enabled = 1 AND id != %s", $key, $template['id'] ?? 0)]); ?>
                                    <?php if (!$option): ?>
                                        <option value="<?= $key ?>" <?= ($template && $template['type'] == $key) ? 'selected' : ''?>><?= $value ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                    </div>
                    
                </div>
            </div>
            <div id="editor" class="w-100 h-auto pt-2"></div>
            <div class="row w-100 mt-2">
                <div class="text-success mt-2 text-end" id="template_update_success"></div>
                <div class="text-danger mt-2 text-end" id="template_update_error"></div>
            </div>
            <div class="row mt-2 justify-content-between">
                <div class="col-6">
                    <h5 class="text-center">Tag dinamici utilizzabili</h5>
                    <ul class="list-unstyled mx-2" id="tag_list"></ul>
                </div>
                <div class="buttons col-6 d-flex justify-content-end" style="max-height: 30px;">
                    <button type="submit" class="btn btn-primary btn-sm" id="save-button">
                        <?= $template ? 'Salva modello' : 'Crea' ?>
                    </button>
                </div>
            </div>
        </form>
        
    </div>
    <?php else: ?>
        <?php include_once __DIR__ . '/../../users/404.php' ?>
    <?php endif; ?>
<?php else: ?>
    <?php include_once __DIR__ . '/../../users/access_denied.php' ?>
<?php endif; ?>

<script>
    const toolbarOptions = [
        [{ 'font': [] }],
        ['bold', 'italic', 'underline', 'strike'],        
        ['blockquote', 'code-block'],
        ['link', 'image', 'video', 'formula'],
        [{ 'header': 1 }, { 'header': 2 }],               
        [{ 'script': 'sub'}, { 'script': 'super' }],      
        [{ 'indent': '-1'}, { 'indent': '+1' }],          
        [{ 'direction': 'rtl' }],                                 
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        [{ 'color': [] }, { 'background': [] }],          
        [{ 'align': [] }],
        ['clean']                                         
    ];

    const options = {
        modules: {
            toolbar: toolbarOptions,
        },
        placeholder: 'Crea un template per le mail da inviare',
        theme: 'snow'
    };

    const quill = new Quill('#editor', options);
</script>

<?php if ($template): ?>
    <script>
        const templateJSON = <?= json_encode($template, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
        const templateData = templateJSON;
        const initialData = {
            subject: templateData.subject,
            body: templateData.body,
        };

        const delta = quill.clipboard.convert({html: initialData.body});

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelector('[name="subject"]').value = initialData.subject;
            quill.setContents(delta, 'silent');
        });
    </script>
<?php endif; ?>

<script>
    const input_select_type = document.getElementById('type');
    const type = input_select_type.value;
    
    const tag_list = document.getElementById('tag_list');
    document.getElementById('confirmation_email_editor').addEventListener('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const form = e.target;
        const data = new FormData(form);

        data.append('saving', true);
        data.append('body', quill.root.innerHTML);
        
        fetch('/admin/template/templateCreateOrModify.php', {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();
            console.log(response);
            
            const errorBox = document.getElementById('template_update_error');
            const successBox = document.getElementById('template_update_success');
            
            if (response.success) {
                errorBox.innerHTML = '';
                successBox.innerHTML = response.message;
            } else {
                successBox.innerHTML = '';
                if (typeof response.message === 'object') {
                    let html = `<ul class="list-group list-unstyled">`;
                    for (const [field, error] of Object.entries(response.message)) {
                        html += `<li><strong>${field}:</strong> ${error}</li>`;
                    }
                    html += `</ul>`;
                    errorBox.innerHTML = html;
                } else {
                    errorBox.innerHTML = response.message;
                }
            }
            
        }).catch(error => {
            console.error('Errore:', error);
            document.getElementById('template_update_error').innerHTML = 'Errore di connessione';
        });
    });

    input_select_type.addEventListener('change', fetchTags);

    function fetchTags() {
        tag_list.innerHTML = '';
        const data = new FormData();
        data.append('type', input_select_type.value);
        data.append('loadTags', true);

        fetch('<?= $_ENV['APP_URL'] ?>/admin/template/fetchTags.php', {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();
            const tags = response.tags[input_select_type.value];
            
            for (const [key, value] of Object.entries(tags)) {
                const listItem = document.createElement('li');
                listItem.innerHTML = `<strong>{${key}}:</strong> ${value}`;
                tag_list.appendChild(listItem);
            }
            
        })
    }
    fetchTags();
</script>