<?php
include __DIR__ . '/../../config.php';
User::setIndexLabels();
User::setRoles();

$logTitle = 'User create/update';

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

$user = null;
$successMessage = '';
$errorMessage = '';
$errors = [];

$userExists = false;
$new = false;

$error_message = Lang::getText("error:user_not_found");

$possibleRoles = User::$roles;

if ($_GET['id'] ?? false) {
    if (is_numeric($_GET['id'] ?? false))
    $user = User::getSingleData($_GET['id']);

    if ($user) {
        $userExists = true;
    }
} else {
    $new = true;

}
if (($_SERVER['REQUEST_METHOD'] == 'POST') && ($_POST['submitting'] ?? false)) {
    $updatedData = [
        "firstname" => $_POST['firstname'],
        "lastname" => $_POST['lastname'],
        "email" => $_POST['email'],
        "username" => $_POST['username']
    ];

    if ($_POST['id'] ?? false) {
        $users = User::select([
            "where" => sprintf("id = %d", $_POST['id'])
        ]);
        $user = $users[0];
        $updatedData['id'] = $_POST['id'];

        if (($_SESSION['role'] > $user['role'])) {

            if ($user['role'] != $_POST['role']) {
                $updatedData['role'] = $_POST['role'];
            }

            if ($user['enabled'] ?? false) {
                if ($user['enabled'] != $_POST['enabled']) {
                    $updatedData['enabled'] = isset($_POST['enabled']) ? 1 : 0;
                }
            }

        } else if ($_SESSION['role'] == $user['role']) {

            if ($user['role'] <= $_POST['role']) {
                $updatedData['role'] = $_POST['role'];
            } else {
                $errors['general'][] = "Impossibile impostare un ruolo minore di un utente con un ruolo pari al proprio";
            }

            if ($_POST['enabled'] ?? false) {
                if ($user['enabled'] <= $_POST['enabled']) {
                    if (isset($_POST['enabled'])) $updatedData['enabled'] = 1;
                }
            } else {
                $errors['general'][] = "Impossibile disabilitare un utente con un ruolo pari al proprio";
            }
            
        } else {
            $errors['general'][] = "Impossibile modificare i dati di un utente con un ruolo superiore al proprio";
        }
        
    }

    if (empty($errors)) {
            if ($_POST['id'] ?? false) {
                $responseMessage = "Dati correttamente modificati";
                $response = User::update($updatedData, "id = " . $_POST['id']); 
            } else {
                $responseMessage = "Utente creato correttamente";
                $response = User::create([$updatedData]);
            }
            if (!is_array($response)) {
                echo json_encode(["success" => 1, "message" => $responseMessage]); //
            } else {
                echo json_encode(["success" => 0, "errors" => $response]);
            }
        } else {
            echo json_encode(["success" => 0, "errors" => $errors]);
        }
        exit();
}

include_once './template/header.php';
$pageTitle = $user ? Lang::getText("user_create:user_update") : Lang::getText("user_create:user_create");

?>
<?php if (!$deniedAccess): ?>
    <?php if ($userExists || $new = true): ?>
    <div class="container mt-4 w-75">
        <h3 class="text-center mb-4"><?= $pageTitle ?></h3>
        <form method="POST" id="update_form">
            <div class="d-flex flex-wrap my-3 align-items-center justify-content-start">
                <a href="/users_table" class="btn btn-secondary rounded-50 col-auto d-none d-md-inline-block"><i class="fa-solid fa-arrow-left"></i></a>
            </div>
            <input type="hidden" name="id" value="<?= htmlspecialchars($user['id'] ?? '') ?>">

            <div class="d-flex flex-wrap">
                <div class="mb-3 col-12 col-md-6 p-0 pe-md-3">
                    <label for="input-firstname" class="form-label"><?= Lang::getText("user_form:firstname") ?></label>
                    <input type="text" name="firstname" id="input-firstname"
                        class="form-control <?= isset($errors['firstname']) ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($user['firstname'] ?? '') ?>" required>
                    <div class="invalid-feedback" id="firstname_errors"><?= $errors['firstname'] ?? '' ?></div>
                </div>

                <div class="mb-3 col-12 col-md-6 p-0 ps-md-3">
                    <label for="input-lastname" class="form-label"><?= Lang::getText("user_form:lastname") ?></label>
                    <input type="text" name="lastname" id="input-lastname"
                        class="form-control <?= isset($errors['lastname']) ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($user['lastname'] ?? '') ?>" required>
                    <div class="invalid-feedback" id="lastname_errors"><?= $errors['lastname'] ?? '' ?></div>
                </div>
            </div>

            <div class="mb-3 p-0">
                <label for="input-email" class="form-label"><?= Lang::getText("user_form:email") ?></label>
                <input type="email" name="email" id="input-email"
                    class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                <div class="invalid-feedback" id="email_errors"><?= $errors['email'] ?? '' ?></div>
            </div>

            <div class="mb-3 p-0">
                <label for="input-username" class="form-label"><?= Lang::getText("user:username") ?></label>
                <input type="text" name="username" id="input-username"
                    class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                <div class="invalid-feedback" id="username_errors"><?= $errors['username'] ?? '' ?></div>
            </div>

            <div class="d-flex flex-wrap">
                <div class="mb-3 col-12 col-md-6 p-0 pe-md-3">
                    <label for="input-password" class="form-label"><?= Lang::getText("user_form:new_password") ?></label>
                    <div class="d-flex justify-content-between form-control-style">
                        <input type="password" name="password" id="input-password"
                            class="col border-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>">
                        <span role="button" onclick="togglePasswordView()" class="password-toggle-icon col-auto">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
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

                <div class="mb-3 col-12 col-md-6 p-0 ps-md-3">
                    <label for="input-confirmation_password" class="form-label"><?= Lang::getText("user_form:confirmation_password") ?></label>
                    <input type="password" name="confirmation_password" id="input-confirmation_password"
                        class="form-control <?= isset($errors['confirmation_password']) ? 'is-invalid' : '' ?>"
                        <?= !$user ? 'required' : '' ?>>
                    <div class="invalid-feedback" id="confirmation_password_errors"><?= $errors['confirmation_password'] ?? '' ?></div>
                </div>
            </div>
                        
            <div class="d-flex flex-wrap">
                <div class="mb-3 col-6 p-0 pe-3 form-check form-switch ps-5">
                    <label class="form-check-label " for="input-enabled"><?= Lang::getText("general:enabled") ?></label>
                    <input type="checkbox" name="enabled" class="form-check-input" role="switch" id="input-enabled" value="1"
                        <?= $user && $user['enabled'] ? 'checked' : '' ?>>
                </div>
                <div class="mb-3 col-6 p-0 ps-3">
                    <label for="input-role" class="form-label"><?= Lang::getText("user:role") ?></label>
                    <select name="role" id="input-role" class="form-select">
                        <?php foreach ($possibleRoles as $key => $role): ?>
                            <option value="<?= $key ?>" <?= $user && $user['role'] === $key ? 'selected' : '' ?>>
                                <?= $role ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4 p-0">
            </div>

            <div class="d-flex justify-content-center gap-3">
                <button type="submit" class="btn btn-primary"><?= $user ? Lang::getText("general:update") : Lang::getText("general:create") ?></button>
                <a href="../users_table" class="btn btn-danger"><?= Lang::getText("general:close") ?></a>
            </div>

            <div class="text-center mt-3">
                <ul class="list-unstyled text-danger" id="error_message">

                </ul>
                <span class="text-success" id="success_message"></span>
            </div>
        </form>
    </div>
    <?php else: ?>
        <?php include_once __DIR__ . '/../../users/404.php' ?>
    <?php endif; ?>
<?php else: ?>
    <?php include_once __DIR__ . '/../../users/access_denied.php' ?>
<?php endif; ?>

<?php include_once './template/footer.php'; ?>

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
    
    document.getElementById('update_form').addEventListener('submit', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const form = e.target;
    const data = new FormData(form);
    data.append("submitting", true);

    fetch('/admin/user/usersCreateOrModify.php', {
        method: 'POST',
        body: data
    }).then(async (res) => {
        const response = await res.json();

        ['firstname', 'lastname', 'email', 'username', 'confirmation_password'].forEach(id => {
            const input = document.getElementById(`input-${id}`);
            const errorBox = document.getElementById(`${id}_errors`);
            if (input && errorBox) {
                input.classList.remove('is-invalid');
                errorBox.textContent = '';
            }
        });

        const passwordInput = document.getElementById('input-password');
        const passwordErrorsContainer = document.getElementById('password_errors');
        if (passwordInput && passwordErrorsContainer) {
            passwordInput.classList.remove('is-invalid');
            if (passwordErrorsContainer.querySelector('ul')) {
                passwordErrorsContainer.innerHTML = '';
            }
        }

        if (response.success) {
            document.getElementById('success_message').textContent = response.message;
            document.getElementById('error_message').textContent = '';
        } else {
            const errors = response.errors || {};
            document.getElementById('success_message').textContent = '';
            response.errors.general.forEach((err) => {
                document.getElementById('error_message').innerHTML += `<li>` + err + `</li>`

            })

            Object.entries(errors).forEach(([key, msg]) => {
                if (key === 'password' && Array.isArray(msg)) {
                    const passwordInput = document.getElementById('input-password');
                    const passwordErrorsContainer = document.getElementById('password_errors');
                    
                    if (passwordInput && passwordErrorsContainer) {
                        passwordInput.classList.add('is-invalid');
                        
                        const errorList = document.createElement('ul');
                        errorList.className = 'mb-0 px-0';
                        
                        msg.forEach((error, index) => {
                            const listItem = document.createElement('li');
                            listItem.className = 'invalid-feedback d-block';
                            listItem.id = `password_${index}_errors`;
                            listItem.textContent = error;
                            errorList.appendChild(listItem);
                        });
                        
                        passwordErrorsContainer.appendChild(errorList);
                    }
                } else {
                    const input = document.getElementById(`input-${key}`);
                    const errorBox = document.getElementById(`${key}_errors`);
                    if (input && errorBox) {
                        input.classList.add('is-invalid');
                        errorBox.textContent = msg;
                    }
                }
            });
        }
    });
});
</script>
