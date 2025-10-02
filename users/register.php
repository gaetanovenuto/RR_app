<?php
require_once '../config.php';

$errors = [];
$successMessage = '';
$errorMessage = '';
$auth = User::isAuthenticated();
if ($auth) header("Location: ../index.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$_POST['g-recaptcha-response']) {
        $errors['captcha'][] = Lang::getText("error:check_captcha");

    } else {
        $captcha_verified = User::verifyCaptcha($_POST['g-recaptcha-response']);
        
        if ($captcha_verified->success) {
            unset($_POST['g-recaptcha-response']);
            $registrationResult = User::registerAndSendConfirmation($_POST);
            
            if ($registrationResult['success'] > 0) {
                $successMessage = Lang::getText("success:registration_success");
                header("Location: ./login.php?registration_status=success");
                exit();
            } else {
                if (is_array($registrationResult['message'])) {
                    foreach ($registrationResult['message'] as $key => $err) {
                        $errors[$key] = $err;
                    }
                } else {
                    $errors["general"] = $registrationResult['message'];
                }
            }
        } else {
            $errors['captcha'][] = Lang::getText("error:captcha_generic");
        }
    }
    
}

$pageTitle = Lang::getText("user:register");
include_once '../template/header.php';
?>
<div class="container min-vh-100 d-flex align-items-center justify-content-center bg-light position-relative">
    <div class="position-absolute top-0 end-0 m-3 z-1">
        <?php include_once '../template/lang_selector.php'; ?>
    </div>
    <div class="card shadow-lg border-0 rounded-4 p-4 w-100" style="max-width: 850px;">
        <h2 class="text-center mb-4 text-primary"><?= Lang::getText("user_form:create_account") ?></h2>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= $successMessage ?></div>
        <?php endif; ?>

        <form method="POST" id="register_form">
            <div class="row">
                <div class="mb-3 col-12 col-md-6">
                    <label for="input-firstname" class="form-label"><?= Lang::getText("user_form:firstname") ?></label>
                    <input type="text" name="firstname" id="input-firstname"
                        class="form-control <?= isset($errors['firstname']) ? 'is-invalid' : '' ?>"
                        placeholder="<?= Lang::getText("user_form:insert_firstname") ?>"
                        value="<?= isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : '' ?>" required>
                    <?php if (isset($errors['firstname'])): ?>
                        <div class="invalid-feedback">
                            <ul class="list-unstyled">
                                <?php foreach ($errors['firstname'] as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3 col-12 col-md-6">
                    <label for="input-lastname" class="form-label"><?= Lang::getText("user_form:lastname") ?></label>
                    <input type="text" name="lastname" id="input-lastname"
                        class="form-control <?= isset($errors['lastname']) ? 'is-invalid' : '' ?>"
                        placeholder="<?= Lang::getText("user_form:insert_lastname") ?>"
                        value="<?= isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : '' ?>" required>
                    <?php if (isset($errors['lastname'])): ?>
                        <div class="invalid-feedback">
                            <ul class="list-unstyled">
                                <?php foreach ($errors['lastname'] as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="input-email" class="form-label"><?= Lang::getText("user_form:email") ?></label>
                <input type="email" name="email" id="input-email"
                    class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                    placeholder="<?= Lang::getText("user_form:insert_email") ?>"
                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback">
                            <ul class="list-unstyled">
                                <?php foreach ($errors['email'] as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
            </div>

             <div class="mb-3">
                <label for="input-username" class="form-label"><?= Lang::getText("user:username") ?></label>
                <input type="text" name="username" id="input-username"
                    class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                    placeholder="<?= Lang::getText("user_form:insert_username") ?>"
                    value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="invalid-feedback">
                            <ul class="list-unstyled">
                                <?php foreach ($errors['username'] as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
            </div>

            <div class="row">
                <div class="mb-3 col-12 col-md-6">
                    <label for="input-password" class="form-label"><?= Lang::getText("user_form:password") ?></label>
                    <div class="d-flex justify-content-between form-control-style">
                        <input type="password" name="password" id="input-password"
                            class="col border-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                            placeholder="<?= Lang::getText("user_form:insert_password") ?>" 
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
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback d-block">
                            <ul class="list-unstyled">
                                <?php foreach ($errors['password'] as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3 col-12 col-md-6">
                    <label for="input-confirmation_password" class="form-label"><?= Lang::getText("user_form:confirmation_password") ?></label>
                    <input type="password" name="confirmation_password" id="input-confirmation_password"
                        class="form-control <?= isset($errors['confirmation_password']) ? 'is-invalid' : '' ?>"
                        placeholder="<?= Lang::getText("user_form:confirmation_password") ?>" 
                        value="<?= isset($_POST['confirmation_password']) ? htmlspecialchars($_POST['confirmation_password']) : '' ?>" required>
                        <?php if (isset($errors['confirmation_password'])): ?>
                            <div class="invalid-feedback">
                                <ul class="list-unstyled">
                                    <?php foreach ($errors['confirmation_password'] as $error): ?>
                                        <li><?= $error ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                </div>  
            </div>
            <div class="row justify-content-center align-items-center">
                <div id="captcha_element" class="<?= isset($errors['captcha']) ? 'is-invalid' : '' ?>"></div>
                <?php if (isset($errors['captcha'])): ?>
                    <div class="invalid-feedback">
                        <ul class="list-unstyled">
                            <?php foreach ($errors['captcha'] as $error): ?>
                                <li class="fw-bold"><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg"><?= Lang::getText("user_form:register") ?></button>
            </div>
            <div class="text-center mt-3">
                <?= Lang::getText("user_form:already_registered") ?> <a href="./login.php" class="text-decoration-none"><?= Lang::getText("login:login") ?></a>.
            </div>
            <div class="text-danger fw-bold text-center mt-2"><?= $errors['general'] ?? '' ?></div>
        </form>
    </div>
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
                strength_text.innerHTML = '<?= Lang::getText("passw:very_weak") ?>';
            } else if (score == 1) {
                strength_bar.classList.add('bg-danger');
                strength_text.classList.add('text-danger');
                strength_bar.style = "width: 25%;";
                strength_bar.ariaValueNow = 25;
                strength_text.innerHTML = '<?= Lang::getText("passw:weak") ?>';
            } else if (score == 2) {
                strength_bar.classList.add('bg-warning');
                strength_text.classList.add('text-warning');
                strength_bar.style = "width: 50%;";
                strength_bar.ariaValueNow = 50;
                strength_text.innerHTML = '<?= Lang::getText("passw:medium") ?>';
            } else if (score == 3) {
                strength_bar.classList.add('bg-success');
                strength_text.classList.add('text-success');
                strength_bar.style = "width: 75%;";
                strength_bar.ariaValueNow = 75;
                strength_text.innerHTML = '<?= Lang::getText("passw:strong") ?>';
            } else if (score == 4) {
                strength_bar.classList.add('bg-success');
                strength_text.classList.add('text-success');
                strength_bar.style = "width: 100%;";
                strength_bar.ariaValueNow = 100;
                strength_text.innerHTML = '<?= Lang::getText("passw:very_strong") ?>';
            } 
        } else {
            strength_bar.classList.remove('bg-danger', 'bg-warning', 'bg-success');
            strength_bar.style = "width: 0%;";
            strength_text.classList.remove('text-danger', 'text-warning', 'text-success');
            strength_text.innerHTML = '';
        }
    }

    checkPasswordStrength(document.getElementById('input-password').value);
</script>