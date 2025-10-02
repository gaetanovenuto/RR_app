<?php
require_once '../config.php';
if (User::isAuthenticated()) header("Location: ../index.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['submitting'] ?? false) {
    $loginData = trim($_POST['forgot_password'] ?? '');
    
    if (empty($loginData)) {
        echo json_encode([
            "success" => 0, 
            "message" => "Inserisci username o email."
        ]);
        exit();
    }
    
    $result = User::requestPasswordReset($loginData);
    
    if ($result['success']) {
        echo json_encode([
            "success" => 1,
            "message" => "È stata inviata una mail all'email associata a questo account."
        ]);
    } else {
        echo json_encode([
            "success" => 0,
            "message" => $result['message']
        ]);
    }
    
    exit();
}

$pageTitle = 'Password dimenticata';
include_once '../template/header.php';
?>

<div class="d-flex justify-content-center align-items-center vh-100">
    <form method="POST" action="forgotPassword.php" class="border p-4 gap-3 rounded shadow bg-light col-9 col-md-6 d-flex flex-column justify-content-evenly" id="forgot_password_form">
        <h2 class="text-center pt-2">
            Recupera la password
        </h2>
        <div>
            <label for="input-forgot_password" class="form-label">Username o Email</label>
            <input type="text" name="forgot_password" id="input-forgot_password" class="form-control" placeholder="Inserisci l'username o l'email" value="<?= isset($_POST["forgot_password"]) ? htmlspecialchars($_POST["forgot_password"]) : "" ?>" required>
        </div>
        <div class="text-success text-center mt-2" id="forgot_password_success"></div>
        <div class="text-danger text-center mt-2" id="forgot_password_error"></div>
        <p class="m-0">
            <small>Inserisci il tuo username o la tua email. Se il dato inserito è associato ad un account, verrà inviata una mail tua casella di posta associata con un link per reimpostare la password.</small>
        </p>
        <div class="text-center">
            <button type="submit" class="btn btn-primary mb-3">Invia email</button>
            <a href="../login.php" class="btn btn-secondary mb-3">Annulla</a>
        </div>
        
    </form>
</div>

<?php 
include_once '../template/footer.php';
?>
<script>
    document.getElementById('forgot_password_form').addEventListener('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const form = e.target;
        const data = new FormData(form);
        data.append('submitting', true);

        fetch('/users/forgotPassword.php', {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();
            console.log(response);
            errorBox = document.getElementById('forgot_password_error');
            successBox = document.getElementById('forgot_password_success')
            if (response.success > 0) {
                errorBox.innerHTML = '';
                successBox.innerHTML = response.message;
                document.getElementById('input-forgot_password').value = '';
            } else {
                successBox.innerHTML = '';
                errorBox.innerHTML = response.message;
            }
            
        }).catch(error => {
            console.error('Errore:', error);
        })
    })
</script>