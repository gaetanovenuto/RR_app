<?php
require_once __DIR__ . '/../config.php';

$errors = [];

if (($_SERVER['REQUEST_METHOD'] == 'POST') && ($_POST['submitting'] ?? false)) {
    
    $updatedData = [
        "firstname" => $_POST['firstname'],
        "lastname" => $_POST['lastname'],
    ];

    if ($_POST['id'] ?? false) {
        $updatedData['id'] = $_POST['id'];
        $user = User::getSingleData($_POST['id']);
    }

    if ($_POST['old-email'] || $_POST['email'] || $_POST['confirmation-email']) {
        if (!$_POST['old-email']) {
            $errors['old-email'][] = "Inserisci l'email attuale";
        }
        if (!$_POST['email']) {
            $errors['email'][] = "Inserisci la nuova email";
        }
        if (!$_POST['confirmation-email']) {
            $errors['confirmation-email'][] = "Conferma la email";
        }

        if ($_POST['old-email'] && $_POST['email'] && $_POST['confirmation-email']) {
            if ($_POST['old-email'] !== $user['email']) {
                $errors['old-email'][] = "L'email attuale non è corretta";
            }
            if ($_POST['email'] !== $_POST['confirmation-email']) {
                $errors['confirmation-email'][] = "Le emails non coincidono";
            }
        }

        if ($_POST['old-email'] === $user['email'] && $_POST['email'] === $_POST['confirmation-email']) {
            if ($_POST['email'] == $_POST['old-email']) {
                $errors['email'][] = "La nuova email non può essere uguale alla precedente";
            }
        }
    }

    if ($_POST['old-password'] || $_POST['password'] || $_POST['confirmation-password']) {
        if (!$_POST['old-password']) {
            $errors['old-password'][] = "Inserisci la password attuale";
        }
        if (!$_POST['password']) {
            $errors['password'][] = 'Inserisci la nuova password';
        }
        if (!$_POST['confirmation-password']) {
            $errors['confirmation-password'][] = 'Conferma la password';
        }  

        if ($_POST['old-password'] && $_POST['password'] && $_POST['confirmation-password']) {
            if (strlen($_POST['password']) < 8) {
                $errors['password'][] = 'La password deve essere almeno 8 caratteri';
            }
            if ($_POST['password'] !== $_POST['confirmation-password']) {
                $errors['confirmation-password'][] = 'Le password non coincidono';
            }
            if (sha1(md5($_POST['old-password'])) !== $user['password']) {
                $errors['old-password'][] = "La password attuale non è corretta";
            }
        }

        if (sha1(md5($_POST['old-password'])) === $user['password'] && $_POST['password'] === $_POST['confirmation-password']) {
            if ($_POST['password'] == $_POST['old-password']) {
                $errors['password'][] = "La nuova password non può essere uguale alla precedente";
            }
        }
    }

    if (empty($errors)) {
        if ($_POST['email']) {
            $updatedData['email'] = $_POST['email'];
        }
        
        if ($_POST['password']) {
            $updatedData['plain_password'] = $_POST['password'];
            $updatedData['password'] = sha1(md5($_POST['password'])); 
        }
        
        if ($_POST['id'] ?? false) {
            $response = User::update($updatedData, "id = " . $_POST['id']); 
             Log::create([
                [
                    "user_id" => $_SESSION['id'],
                    "action_type" => "Dati modificati",
                    "ip_address" => $_SERVER['REMOTE_ADDR'],
                    "details" => "L'utente ha modificato i propri dati",
                    "level" => 1
                ]
            ]);
            $responseMessage = "Dati correttamente modificati";
        }
        
        if (!is_array($response)) {
            echo json_encode(["success" => 1, "message" => $responseMessage]);
        } else {
            echo json_encode(["success" => 0, "errors" => $response]);
        }
    } else {
        echo json_encode(["success" => 0, "errors" => $errors]);
    }
    exit();
}

$pageTitle = 'Il tuo profilo';
include_once __DIR__ . '/../template/header.php';
?>
<div class="container mt-2">
    <h2 class="text-center">
        Il tuo profilo
    </h2>
    <form method="POST" id="update_form">
        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id'] ?? '') ?>">

        <div class="row">
            <div class="mb-3 col-md-6">
                <label for="input-firstname" class="form-label">Nome</label>
                <input type="text" name="firstname" id="input-firstname"
                       class="form-control <?= isset($errors['firstname']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($user['firstname'] ?? '') ?>" required>
                <div id="firstname_errors">
                    <?php if (!empty($errors['firstname'])): ?>
                        <ul class="mb-0 p-0">
                            <?php foreach ($errors['firstname'] as $i => $err): ?>
                                <li class="invalid-feedback d-block" id="firstname_<?= $i ?>_errors"><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3 col-md-6">
                <label for="input-lastname" class="form-label">Cognome</label>
                <input type="text" name="lastname" id="input-lastname"
                       class="form-control <?= isset($errors['lastname']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($user['lastname'] ?? '') ?>" required>
                <div id="lastname_errors">
                    <?php if (!empty($errors['lastname'])): ?>
                        <ul class="mb-0 p-0">
                            <?php foreach ($errors['lastname'] as $i => $err): ?>
                                <li class="invalid-feedback d-block" id="lastname_<?= $i ?>_errors"><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="mb-3 col-md-4">
                <label for="input-old-email" class="form-label">Vecchia email</label>
                <input type="email" name="old-email" id="input-old-email"
                    class="form-control <?= isset($errors['old-email']) ? 'is-invalid' : '' ?>">
                <div id="old-email_errors">
                    <?php if (!empty($errors['old-email'])): ?>
                        <ul class="mb-0 p-0">
                            <?php foreach ($errors['old-email'] as $i => $err): ?>
                                <li class="invalid-feedback d-block" id="old-email_<?= $i ?>_errors"><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3 col-md-4">
                <label for="input-email" class="form-label">Nuova email</label>
                <input type="email" name="email" id="input-email"
                    class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>">
                <div id="email_errors">
                    <?php if (!empty($errors['email'])): ?>
                        <ul class="mb-0 p-0">
                            <?php foreach ($errors['email'] as $i => $err): ?>
                                <li class="invalid-feedback d-block" id="email_<?= $i ?>_errors"><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div id="email_errors">
                    <?php if (!empty($errors['email'])): ?>
                        <ul class="mb-0 p-0">
                            <?php foreach ($errors['email'] as $i => $err): ?>
                                <li class="invalid-feedback d-block" id="email_<?= $i ?>_errors"><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3 col-md-4">
                <label for="input-confirmation-email" class="form-label">Conferma l'email</label>
                <input type="email" name="confirmation-email" id="input-confirmation-email"
                    class="form-control <?= isset($errors['confirmation-email']) ? 'is-invalid' : '' ?>">
                <div id="confirmation-email_errors">
                    <?php if (!empty($errors['confirmation-email'])): ?>
                        <ul class="mb-0 p-0">
                            <?php foreach ($errors['confirmation-email'] as $i => $err): ?>
                                <li class="invalid-feedback d-block" id="confirmation-email_<?= $i ?>_errors"><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="mb-3 col-md-4">
                <label for="input-old-password" class="form-label">Vecchia password</label>
                <input type="password" name="old-password" id="input-old-password"
                       class="form-control <?= isset($errors['old-password']) ? 'is-invalid' : '' ?>">
                <div id="old-password_errors">
                    <?php if (!empty($errors['old-password'])): ?>
                        <ul class="mb-0 p-0">
                            <?php foreach ($errors['old-password'] as $i => $err): ?>
                                <li class="invalid-feedback d-block" id="old-password_<?= $i ?>_errors"><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3 col-md-4">
                <label for="input-password" class="form-label">Nuova password</label>
                <div class="d-flex justify-content-between form-control-style">
                    <input type="password" name="password" id="input-password"
                        class="col border-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                        placeholder="Inserisci la password" 
                        value="<?= isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '' ?>" 
                        oninput="checkPasswordStrength(value)"
                        required>
                    <span role="button" onclick="togglePasswordView()" class="password-toggle-icon col-auto">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <input type="hidden" id="password_score" name="password_score">
                <small id="progress-text" class="text-danger"></small>
                <div id="password_errors">
                    <?php if (!empty($errors['password'])): ?>
                        <ul class="mb-0 p-0">
                            <?php foreach ($errors['password'] as $i => $err): ?>
                                <li class="invalid-feedback d-block" id="password_<?= $i ?>_errors"><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3 col-md-4">
                <label for="input-confirmation-password" class="form-label">Conferma password</label>
                <input type="password" name="confirmation-password" id="input-confirmation-password"
                       class="form-control <?= isset($errors['confirmation-password']) ? 'is-invalid' : '' ?>">
                <div id="confirmation-password_errors">
                    <?php if (!empty($errors['confirmation-password'])): ?>
                        <ul class="mb-0 p-0">
                            <?php foreach ($errors['confirmation-password'] as $i => $err): ?>
                                <li class="invalid-feedback d-block" id="confirmation-password_<?= $i ?>_errors"><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-3">
            <button type="submit" class="btn btn-primary"><?= $user ? 'Modifica' : 'Crea' ?></button>
            <a href="../" class="btn btn-danger">Chiudi</a>
        </div>

        <div class="text-center mt-3">
            <span class="text-success" id="success_message"></span>
            <span class="text-danger" id="error_message"></span>
        </div>
    </form>
</div>
<?php include_once __DIR__ . '/../template/footer.php'; ?>
<script>
    const input_password = document.getElementById('input-password');
    const toggle_password_view = document.querySelector('.password-toggle-icon');
    const toggle_password_icon = document.querySelector('.password-toggle-icon i');

    function togglePasswordView() {
        if (input_password.type === 'password') {
            input_password.type = 'text';
            toggle_password_icon.classList.remove("fa-eye");
            toggle_password_icon.classList.add("fa-eye-slash");
        } else {
            input_password.type = 'password';
            toggle_password_icon.classList.remove("fa-eye-slash");
            toggle_password_icon.classList.add("fa-eye");
        }
    }

    function checkPasswordStrength(value) {
        let score = zxcvbn(value).score;
        const password_score = document.getElementById('password_score');
        password_score.value = score;
        strength_bar = document.querySelector('.progress-bar');
        strength_text = document.getElementById('progress-text');
        strength_bar.classList.remove('bg-danger', 'bg-warning', 'bg-success');
        strength_text.classList.remove('text-danger', 'text-warning', 'text-success');
        if (input_password.value) {
            if (score == 0) {
                strength_bar.classList.add('bg-danger');
                strength_text.classList.add('text-danger');
                strength_bar.ariaValueNow = 0;
                strength_text.innerHTML = 'Molto bassa';
            } else if (score == 1) {
                strength_bar.classList.add('bg-danger');
                strength_text.classList.add('text-danger');
                strength_bar.style = "width: 25%;";
                strength_bar.ariaValueNow = 25;
                strength_text.innerHTML = 'Bassa';
            } else if (score == 2) {
                strength_bar.classList.add('bg-warning');
                strength_text.classList.add('text-warning');
                strength_bar.style = "width: 50%;";
                strength_bar.ariaValueNow = 50;
                strength_text.innerHTML = 'Media';
            } else if (score == 3) {
                strength_bar.classList.add('bg-success');
                strength_text.classList.add('text-success');
                strength_bar.style = "width: 75%;";
                strength_bar.ariaValueNow = 75;
                strength_text.innerHTML = 'Sicura';
            } else if (score == 4) {
                strength_bar.classList.add('bg-success');
                strength_text.classList.add('text-success');
                strength_bar.style = "width: 100%;";
                strength_bar.ariaValueNow = 100;
                strength_text.innerHTML = 'Molto sicura';
            } 
        } else {
            strength_bar.classList.remove('bg-danger' , 'bg-warning', 'bg-success');
            strength_bar.style = "width: 0%;";
            strength_text.classList.remove('text-danger' , 'text-warning', 'text-success');
            strength_text.innerHTML = '';
        }
    }

    checkPasswordStrength(document.getElementById('input-password').value);



    document.getElementById('update_form').addEventListener('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const form = e.target;
        const data = new FormData(form);
        data.append('submitting', true);

        fetch('/users/profile.php', {
            method: 'POST',
            body: data
        }).then(async (res) => {
            const response = await res.json();

            ['firstname', 'lastname', 'old-email', 'email', 'confirmation-email', 'old-password', 'password', 'confirmation-password'].forEach(id => {
                const input = document.getElementById(`input-${id}`);
                const errorBox = document.getElementById(`${id}_errors`);
                if (input && errorBox) {
                    input.classList.remove('is-invalid');
                    errorBox.innerHTML = '';
                }
            });

            if (response.success) {
                document.getElementById('success_message').textContent = response.message;
                document.getElementById('error_message').textContent = '';
                ['old-email', 'email', 'confirmation-email', 'old-password', 'password', 'confirmation-password'].forEach(id => {
                    document.getElementById(`input-${id}`).value = '';
                })
                
            } else {
                const errors = response.errors || {};
                document.getElementById('success_message').textContent = '';
                document.getElementById('error_message').textContent = 'Controlla i campi evidenziati.';

                Object.entries(errors).forEach(([key, messages]) => {
                    const input = document.getElementById(`input-${key}`);
                    const errorBox = document.getElementById(`${key}_errors`);
                    
                    if (input && errorBox) {
                        input.classList.add('is-invalid');
                        
                        if (Array.isArray(messages) && messages.length > 1) {
                            const errorList = document.createElement('ul');
                            errorList.className = 'mb-0 p-0';
                            
                            messages.forEach((error, index) => {
                                const listItem = document.createElement('li');
                                listItem.className = 'invalid-feedback d-block';
                                listItem.id = `${key}_${index}_errors`;
                                listItem.textContent = error;
                                errorList.appendChild(listItem);
                            });
                            
                            errorBox.appendChild(errorList);
                        } else {
                            const message = Array.isArray(messages) ? messages[0] : messages;
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'invalid-feedback d-block';
                            errorDiv.textContent = message;
                            errorBox.appendChild(errorDiv);
                        }
                    }
                });
            }
        }).catch(error => {
            console.error('Errore:', error);
            document.getElementById('error_message').textContent = 'Si è verificato un errore durante l\'elaborazione.';
        });
    });

</script>