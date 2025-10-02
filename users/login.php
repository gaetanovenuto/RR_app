<?php
require_once '../config.php';
$errors = [];
$successMessage = '';
$errorMessage = '';

if (User::isAuthenticated()) header("Location: ../index.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['tryLogin'] ?? false) {
    if (!$_POST['g-recaptcha-response']) {
        echo json_encode([
            "success" => 0,
            "captcha" => "Controlla il captcha"
        ]);
        exit();
    } else {
        $captcha_verified = User::verifyCaptcha($_POST['g-recaptcha-response']);

        if ($captcha_verified->success) {
            unset($_POST['g-recaptcha-response']);
            $user = User::login($_POST);
            if (isset($user['login']) || isset($user['password']) || isset($user['general']) || isset($user['account'])) {
                $errors = $user;
                echo json_encode($errors);
                exit();
            }
            echo json_encode(["success" => 1, "message" => "Login effettuato con successo"]);
            exit();
        } else {
            echo json_encode([
                "success" => 0,
                "captcha" => "Si è verificato un errore nella verifica del captcha. Riprova più tardi"
            ]);
            exit();
        }
    }
}

if ($_GET['registration_status'] ?? false) {
    if ($_GET['registration_status'] === 'success') {
        $message['registration_success'] = "La registrazione è avvenuta con successo. Clicca sul link inviato alla mail per confermare l'account.";
    }
}

if ($_GET['reset_password'] ?? false) {
    if ($_GET['reset_password'] === 'success') {
        $message['reset_password'] = "La password è stata modificata correttamente.";
    }
}
$pageTitle = Lang::getText("login:login");
include_once '../template/header.php';
?>

<div class="container d-flex justify-content-center align-items-center vh-100 position-relative">
    <div class="position-absolute top-0 end-0 m-3 z-1">
        <?php include_once '../template/lang_selector.php'; ?>
    </div>

    <form method="POST" action="login.php" class="border p-4 rounded shadow bg-light col-10 col-lg-6 h-75 d-flex flex-column justify-content-evenly" id="login_form">
        <h2 class="text-center">
            <?= $pageTitle ?>
        </h2>

        <div class="mb-3">
            <label for="input-login" class="form-label"><?= Lang::getText("login:usernameoremail") ?></label>
            <input type="text" name="login" id="input-login" class="form-control" placeholder="<?= Lang::getText("login:insert_username_or_email") ?>" value="<?= isset($_POST["login"]) ? htmlspecialchars($_POST["login"]) : "" ?>" required>
            <div class="text-danger text-center mt-2" id="login_errors"></div>
        </div>
        
        <div class="mb-3">
            <label for="input-password" class="form-label"><?= Lang::getText("user_form:password") ?></label>
            <div class="d-flex justify-content-between form-control-style bg-white">
                <input type="password" name="password" id="input-password" class="col border-0" required placeholder="<?= Lang::getText("login:insert_password") ?>">
                <span role="button" onclick="togglePasswordView()" class="password-toggle-icon col-auto">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <div class="text-danger text-center mt-2" id="login_errors"></div>
        </div>
        
        <div class="mb-1">
            <input type="checkbox" name="remember-me" id="remember-me" />
            <label for="remember-me"><?= Lang::getText("login:remember_me") ?></label>
        </div>
        <div class="row justify-content-center align-items-center">
            <div id="captcha_element" class="<?= isset($errors['captcha']) ? 'is-invalid' : '' ?>"></div>
            <div class="text-danger text-center mt-2" id="captcha_errors"></div>
            
        </div>
        <span class="text-danger text-center mt-2" id="general_errors"></span>
        
        <div class="text-center row justify-content-around">
            <button type="submit" class="btn btn-primary col-12 col-lg-5 mb-2 btn-sm"><?= Lang::getText("login:login") ?></button>
        </div>
        <div class="text-center w-100">
            <a href="./forgotPassword.php"><?= Lang::getText("login:forgot_password") ?></a>
        </div>
        <div class="text-center w-100">
            <?= Lang::getText("login:not_registered_yet") ?> <br><a href="./register.php"><?= Lang::getText("login:click_here_to_register") ?></a>
        </div>
        
        <div class="text-success text-center mt-2" id="registration_success_message">
            <?= $message['registration_success'] ?? '' ?>
            <?= $message['reset_password'] ?? '' ?>
        </div>
    </form>
</div>
<?php 
include_once '../template/footer.php';
?>

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

    document.getElementById('login_form').addEventListener('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const form = e.target;
        const data = new FormData(form);
        data.append('tryLogin', true);

        fetch('/users/login.php', {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();
            console.log(response);

            if (response.success) {
                window.location.href = '../index.php';
            }

            ['login', 'password', 'general', 'captcha'].forEach(id => {
                const input = document.getElementById(`input-${id}`);
                const errorBox = document.getElementById(`${id}_errors`);
                if (input) input.classList.remove('is-invalid');
                if (errorBox) errorBox.textContent = '';
            });

            const inputLogin = document.getElementById('input-login');
            const errorLogin = document.getElementById('login_errors');
            const inputPassword = document.getElementById('input-password');
            const errorPassword = document.getElementById('password_errors');
            
            if (response.login) {
                if (inputLogin) inputLogin.classList.add('is-invalid');
                if (errorLogin) errorLogin.textContent = response.login;
            }

            if (response.password) {
                if (inputPassword) inputPassword.classList.add('is-invalid');
                if (errorPassword) errorPassword.textContent = response.password;
            }   

            if (response.general) {
                const errorGeneral = document.getElementById('general_errors');
                if (errorGeneral) errorGeneral.textContent = response.general;
                inputPassword.value = '';
            }

            if (response.captcha) {
                const errorCaptcha = document.getElementById('captcha_errors');
                if (errorCaptcha) errorCaptcha.textContent = response.captcha;
                inputPassword.value = '';
            }
        });
    });
</script>

