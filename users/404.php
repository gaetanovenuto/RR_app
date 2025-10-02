<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="text-center">
        <h1 class="text-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i> Errore 404
        </h1>
        <div class="alert alert-danger">
            <p>Pagina non trovata</p>
            <p><?= $error_message ?></p>
        </div>
        <div class="mt-4">
            <a href="dashboard.php" class="btn btn-primary">Torna alla Dashboard</a>
            <a href="homepage.php" class="btn btn-secondary ms-2">Homepage</a>
        </div>
    </div>
</div>