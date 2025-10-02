<?php
include __DIR__ . '/../../config.php';

$logTitle = "Barcode scanner";

if (User::isModerator()) {
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

$pageTitle = Lang::getText("checks_pages:scanner");
include_once __DIR__ . '/../../template/header.php';
?>
<?php if (!$deniedAccess): ?>
    <div class="container min-vh-100 d-flex align-items-center justify-content-center bg-light position-relative">

        <div class="card shadow-lg border-0 rounded-4 p-4 w-100" style="max-width: 850px;">
            <h2 class="text-center text-primary" id="text"><?= Lang::getText("checks_pages:waiting_for_barcode") ?></h2>

            <form method="POST" id="scanner_form">
                <input 
                    type="text" 
                    id="barcode_reader" 
                    name="barcode"
                    autofocus
                    style="position: absolute; left: -9999px;" 
                >
            </form>
        </div>
    </div>
<?php else: ?>
    <?php include_once __DIR__ . '/../../users/access_denied.php' ?>
<?php endif; ?>

<?php 
    include_once __DIR__ . '/../../template/footer.php';
?>

<script>
    const input = document.getElementById('barcode_reader');
    const form = document.getElementById('scanner_form');
    const text = document.getElementById('text');

    document.addEventListener('click', () => {
        input.focus();
    });

    async function scanBarcode(e) {
        e.preventDefault();
        e.stopPropagation();

        const data = new FormData(form);
        data.append('read_barcode', true);

        const res = await fetch('/admin/checks_pages/scan_logic.php', {
            method: "POST",
            body: data
        });
        const response = await res.json();
        input.value = '';

        text.classList.remove('text-primary', 'text-success', 'text-danger');
        if (response.success) {
            text.classList.add('text-success');
        } else {
            text.classList.add('text-danger');
        }
        text.innerHTML = response.message;

        setTimeout(() => {
            text.classList.remove('text-success', 'text-danger');
            text.classList.add('text-primary');
            text.innerHTML = '<?= Lang::getText("checks_pages:waiting_for_barcode") ?>';
        }, 4000);
    }

    form.addEventListener('submit', scanBarcode);
</script>