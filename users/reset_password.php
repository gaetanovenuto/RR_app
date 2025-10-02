<?php
require_once '../config.php';
if (User::isAuthenticated()) header("Location: ../index.php");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$_POST['token'] ?? false) {
        $errors['token'] = "Il token inviato non è corretto";
        echo json_encode(["success" => 0, "message" => $errors['token']]);
        exit();
    } else {
        if ($_POST['password'] || $_POST['confirmation_password']) {
            if (!$_POST['password']) {
                $errors['password'][] = "Inserisci la password";
            }
            if (!$_POST['confirmation_password']) {
                $errors['password'][] = 'Conferma la password';
            }
            if (strlen($_POST['password']) < 8) {
                $errors['password'][] = 'La password deve essere almeno 8 caratteri';
            }
            if ($_POST['password'] !== $_POST['confirmation_password']) {
                $errors['password'][] = 'Le password non coincidono';
            }
            if (!$errors) {
                $_POST['plain_password'] = $_POST['password'];
            }
        }

        $user = User::select([
            "where" => "token = '{$_POST['token']}' AND token_expires_at > NOW()",
            "limit" => 1
        ]);
        if (empty($errors)) {
            $where = sprintf("id = %s AND token = '%s' AND token_expires_at > NOW()", $user[0]['id'], $_POST['token']);
            $updatedUser = User::update([
                "password" => $_POST['password'],
                "token" => null,
                "token_expires_at" => null
            ], $where);
            $emailSent = User::prepareAndSendEmail($user[0]['email'], 'password_changed');
            echo json_encode(["success" => 1, "message" => "Password modificata correttamente"]);
            exit();
        } else {
            echo json_encode(["success" => 0, "message" => $errors]);
            exit();
        }
        exit();
    }
}


if (!$_GET['token']) {
    header("Location: ../login.php");
    exit();
} 

if ($_GET['token'] ?? false) {
    $user = User::select([
        "where" => "token = '{$_GET['token']}' AND token_expires_at > NOW()",
        "limit" => 1
    ]);
}

if (!$user) {
    header("Location: ./login.php");
    exit();
}

$pageTitle = 'Reimposta la tua password';
include_once '../template/header.php';
?>

<div class="container min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="card shadow-lg border-0 rounded-4 p-4 w-100" style="max-width: 600px;">
        <h2 class="text-center mb-4 text-primary">Reimposta la tua password</h2>
        
        <form action="./reset_password.php" method="POST" id="reset_password_form">
            <input type="hidden" value="<?= $_GET['token'] ?>" name="token">
            <div class="mb-3 col-12">
                <label for="input-password" class="form-label">Password</label>
                <input type="password" name="password" id="input-password"
                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                    placeholder="Inserisci la nuova password" required>
            </div>
            <div class="mb-3 col-12">
                <label for="input-confirmation_password" class="form-label">Conferma la password</label>
                <input type="password" name="confirmation_password" id="input-confirmation_password"
                    class="form-control <?= isset($errors['confirmation_password']) ? 'is-invalid' : '' ?>"
                    placeholder="Conferma la password" required>
            </div>
            <div class="text-success text-center mt-2" id="reset_password_success"></div>
            <div class="text-danger mt-2" id="reset_password_error"></div>

             <div class="d-grid mt-4 w-50 mx-auto text-center">
                <button type="submit" class="btn btn-primary btn-sm">Reimposta la password</button>
            </div>
            <div class="text-center mt-3">
                <a href="./login.php" class="text-primary text-decoration-none">Vai alla pagina di login</a>
            </div>
        </form>
        
    </div>
</div>

<?php 
    include_once '../template/footer.php';
?>
<script>
    document.getElementById('reset_password_form').addEventListener('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const form = e.target;
        const data = new FormData(form);
        data.append('submitting', true);

        fetch('/users/reset_password.php', {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();
            console.log(response);
            
            errorBox = document.getElementById('reset_password_error');
            successBox = document.getElementById('reset_password_success')
            if (response.success) {
                window.location.href = './login.php?reset_password=success';
            } else {
                successBox.innerHTML = '';
                let html = `<ul class="list-group list-unstyled">`;
                for (let i = 0; i < (response.message.password).length; i++) {
                    html += `<li>` + (response.message.password)[i] + `</li>`;
                }
                html += `</ul>`;
                errorBox.innerHTML = html;
            }
            
        }).catch(error => {
            console.error('Errore:', error);
        })
    })
</script>