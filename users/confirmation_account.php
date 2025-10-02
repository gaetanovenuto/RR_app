<?php
require_once '../config.php';

$token = $_GET['token'] ?? '';
$message = '';

if (empty($token)) {
    $message = 'Token di conferma mancante.';
} else {
    $user = User::select([
        "where" => "token = '$token' AND token_expires_at > NOW()",
        "limit" => 1
    ]);

    if (empty($user)) {
        $message = 'Token di conferma non valido o scaduto.';
    } else {
        $data = [
            'enabled' => 1,
            'confirmed_at' => date('Y-m-d H:i:s'),
            'token' => null,
            'token_expires_at' => null
        ];

        if (User::update($data, "id = " . $user[0]['id'])) {
             Log::create([
                [
                    "user_id" => $user[0]['id'],
                    "action_type" => "Account confermato",
                    "ip_address" => $_SERVER['REMOTE_ADDR'],
                    "details" => "L'utente ha confermato il proprio account",
                    "level" => 0
                ]
            ]);
            $message = 'Il tuo account è stato confermato con successo! Ora puoi accedere.';
        } else {
            $message = 'Si è verificato un errore durante la conferma del tuo account.';
        }
    }
}

$pageTitle = 'Conferma Account';
include_once '../template/header.php';
?>

<div class="container min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="card shadow-lg border-0 rounded-4 p-4 w-100" style="max-width: 600px;">
        <h2 class="text-center mb-4 text-primary">Conferma account</h2>
        
        <?php if ($message): ?>
            <div class="alert text-primary text-center"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="./login.php" class="btn btn-primary">Vai alla pagina di login</a>
        </div>
    </div>
</div>

<?php 
include_once '../template/footer.php';
?>