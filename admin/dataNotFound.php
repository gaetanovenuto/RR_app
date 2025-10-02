<div class="container d-flex justify-content-center align-items-center h-100">
    <div class="text-center">
        <h4 class="text-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i> Nessun dato presente
        </h4>
        <div class="alert alert-danger">
            <p><?= $error_message ?? 'Nessun dato è stato trovato all\'interno del database' ?></p>
        </div>
        <div class="mt-4">
            <a href="dashboard.php" class="btn btn-primary">Torna alla Dashboard</a>
        </div>
    </div>
</div>