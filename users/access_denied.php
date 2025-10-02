<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = 'Accesso negato';
?>
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="text-center">
        <h1 class="text-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i> Accesso Negato
        </h1>
        <div class="alert alert-danger">
            <p>Non hai i permessi necessari per accedere a questa pagina.</p>
            <p>Questa sezione è riservata agli amministratori del sistema.</p>
        </div>
        <div class="mt-4">
            <?php if (isset($_SESSION['user_id']) || isset($_SESSION['id'])): ?>
                <a href="<?= PROTOCOL ?>/home" class="btn btn-primary">Torna alla Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">Accedi</a>
            <?php endif; ?>
        </div>
    </div>
</div>
    