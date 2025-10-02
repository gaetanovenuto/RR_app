<?php
include __DIR__ . '/../../config.php';
$logTitle = "Email logs details";
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
$mail = null;
$errors = [];

$mailExists = false;
$error_message = "Log Email non trovato, inserisci un id numerico esistente.";

if (($_GET['id'] ?? false) && is_numeric($_GET['id'])) {
    $mail = Mail::getSingleData($_GET['id']);
    
    if ($mail) {
        $mailExists = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['resend_email'] ?? false) {
    $sentEmail = Mail::send($_POST['email'], $_POST['subject'], $_POST['body']);
    
    if ($sentEmail['success'] == 0) {
        echo json_encode([
            "success" => 0,
            "message" => $sentEmail['message']
        ]);
    } else {
        echo json_encode([
            "success" => 1,
            "message" => $sentEmail['message']
        ]);
    }
exit();
}

$pageTitle = "Dettaglio email";
include_once './template/header.php';
?>

<?php if (!$deniedAccess): ?>
    <?php if ($mailExists): ?>
        <div class="row flex-column">
            <div class="row p-2 align-items-center">
                <a href="/mails_tracking" class="btn btn-secondary rounded-50 col-auto d-flex justify-content-center align-items-center h-100"><i class="fa-solid fa-arrow-left"></i></a>
                <h3 class="text-center mt-2 col">
                    <?= $pageTitle ?>
                </h3>
                <button class="btn-warning btn btn-sm col-auto" id="resend_email">
                    Reinvia
                </button>
            </div>
            <div id="error_message" class="text-center text-danger"></div>
            <div id="success_message" class="text-center text-success"></div>
            <table class="table">
                <thead>
                    <?php foreach (Mail::$indexLabels as $key => $heading): ?>
                    <th class="fw-bold text-center">
                        <?= $heading ?>
                    </th>
                    <?php endforeach; ?>
                    <th class="fw-bold text-center">
                        Timestamp
                    </th>
                </thead>
                <tbody>
                    <?php foreach (Mail::$indexLabels as $key => $value): ?>
                    <td class="text-center">
                        <?php if ($key == 'state'): ?>
                            <i class="fa-solid fa-circle" style="color: <?= $mail['state'] ? '#118609' : '#eb0017'?>;"></i>
                        <?php else: ?>
                            <?= $mail[$key] ?>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="text-center">
                        <?= strtotime($mail['created_at']) ?>
                    </td>
                </tbody>
            </table>

            <div id="mailBody" class="card border-dark p-2">
                <?= $mail['body'] ?>
            </div>
        </div>

    <?php else: ?>
        <?php include_once __DIR__ . '/../../users/404.php' ?>
    <?php endif; ?>
<?php else: ?>
    <?php include_once __DIR__ . '/../../users/access_denied.php' ?>
<?php endif; ?>

<?php include_once './template/header.php'; ?>

<script>
    const resend_button = document.getElementById('resend_email');
    resend_button.addEventListener('click', function() {
        let email = '<?= $mail['recipient'] ?>';
        let subject = '<?= $mail['subject'] ?>';
        let body = '<?= $mail['body'] ?>';
        
        const data = new FormData();
        data.append("email", email);
        data.append("subject", subject);
        data.append("body", body);
        data.append("resend_email", true);

        fetch('/admin/emailLogs/emailLogsDetails.php', {
            method: "POST",
            body: data
        }).then(async (res) => {
            const response = await res.json();
            console.log(response);

            const errorBox = document.getElementById('error_message');
            const successBox = document.getElementById('success_message');
            errorBox.innerHTML = successBox.innerHTML = '';
            
            if (response.success) {
                errorBox.innerHTML = '';
                successBox.innerHTML = response.message;
            } else {
                errorBox.innerHTML = response.message;
                successBox.innerHTML = '';
            }
        })
    })
</script>