<?php
include './config.php';

if (User::isAuthenticated()) {
    $users = User::select([
        "where" => "id = " . $_SESSION['id']
    ]);
    $user = $users[0];
    
} else {
    header("Location: /users/login.php");
}

$navLinks = [
    'home' => [
        "level" => 0,
        "label" => Lang::getText("navbar:home"),
        "path" => "/users/home.php",
        "visible" => 1
    ],
    'users_table' => [
        "level" => 1,
        "label" => Lang::getText("navbar:users_management"),
        "path" => "/admin/user/usersTable.php",
        "visible" => 1
    ],
    'rooms_table' => [
        "level" => 1,
        "label" => Lang::getText("navbar:room_management"),
        "path" => "/admin/room/roomsTable.php",
        "visible" => 1
    ],
    'users_update' => [
        "level" => 1,
        "label" => "Modifica Utente",
        "path" => "/admin/user/usersCreateOrModify.php",
        "visible" => 0
    ],
    'langs_table' => [
        "level" => 2,
        "label" => Lang::getText("navbar:translation_management"),
        "path" => "/admin/lang/langsTable.php",
        "visible" => 1
    ],
    'langs_update' => [
        "level" => 2,
        "label" => "Modifica traduzioni",
        "path" => "/admin/lang/langsCreateOrModify.php",
        "visible" => 0
    ],
    'mail_templates' => [
        "level" => 2,
        "label" => Lang::getText("navbar:templates_management"),
        "path" => "/admin/template/templateTable.php",
        "visible" => 1
    ],
    'templates_update' => [
        "level" => 1,
        "label" => "Modifica Email",
        "path" => "/admin/template/templateCreateOrModify.php",
        "visible" => 0
    ],
    'mails_tracking' => [
        "level" => 1,
        "label" => Lang::getText("navbar:tracking_email"),
        "path" => "/admin/emailLogs/emailLogsTable.php",
        'visible' => 1
    ],
    'mails_detail' => [
        "level" => 1,
        "label" => "Dettaglio Email",
        "path" => "/admin/emailLogs/emailLogsDetails.php",
        'visible' => 0
    ],
    'logs' => [
        "level" => 2,
        "label" => Lang::getText("navbar:logs"),
        "path" => "/admin/logs/logsTable.php",
        "visible" => 1
    ], 
    'rooms_update' => [
        "level" => 1,
        "label" => "Modifica Utente",
        "path" => "/admin/room/roomsCreateOrModify.php",
        "visible" => 0
    ],
    'rooms_summary' => [
        "level" => 2,
        "label" => "Sommario prenotazioni",
        "path" => "/admin/rooms_summary/rooms_summary_table.php",
        "visible" => 0
    ],
    'summary_details' => [
        "level" => 0,
        "label" => "Dettaglio prenotazioni stanza",
        "path" => "/admin/rooms_summary/rooms_summary_details.php",
        "visible" => 0
    ],
    'profile' => [
        "level" => 0,
        "label" => Lang::getText("navbar:profile"),
        "path" => "/users/profile.php",
        "visible" => 1
    ],
    'reservations' => [
        "level" => 0,
        "label" => Lang::getText("navbar:reservations"),
        "path" => "/users/reservations/reservationsTable.php",
        "visible" => 1
    ],
    'reservation_details' => [
        "level" => 0,
        "label" => "Dettaglio della prenotazione",
        "path" => "/users/reservations/reservationDetails.php",
        "visible" => 0
    ],
    'add_reservation' => [
        "level" => 0,
        "label" => "Aggiungi prenotazione",
        "path" => "/users/reservations/add_reservation.php",
        "visible" => 0
    ]
    
];

if ($_GET['confirmed_participation'] ?? false) {
    if ($_GET['confirmed_participation'] == 'success') {
        $confirmed_message = "Partecipazione confermata con successo";
    }
};
$currentSection = $_GET['model'];

if (!in_array($currentSection, array_keys($navLinks)))
    $currentSection = 'home';

$pageTitle = "Dashboard | " . $user['username'];
include_once './template/header.php';
?>

<nav class="d-xl-none offcanvas_button dark_blue_bg w-100 d-flex justify-content-between align-items-center">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
        <i class="fa-solid fa-bars fs-1 text-white"></i>
    </button>
    <a href="/home">
        <img src="public/img/RRooms_extended_logo_nobg.png" alt="Reservation rooms" style="height: 50px;" class="py-1">
    </a>
    <div class="user-profile text-white">
        <i class="fa-solid fa-circle-user me-2 text-white"></i> <?= sprintf('%s %s', $user['firstname'], $user['lastname']) ?>
    </div>
</nav>
<div class="row justify-content-between w-100 min-vh-100 p-0 g-0 position-relative">
    <nav class="dark_blue_bg d-none col-2 d-xl-block min-h-100 px-3">
        <div class="row flex-column justify-content-between h-100">
            <div class="links d-flex flex-column">
                <a href="/home" class="">
                    <img src="public/img/RRooms_extended_logo_nobg.png" alt="Reservation rooms" class="w-75 my-2 ms-3">
                </a>
                <?php foreach ($navLinks as $model => $link): ?>
                    <?php if ($user['role'] >= $link['level'] && $link['visible'] > 0): ?>
                        <a class="btn text-white text-start" href="/<?= $model != 'home' ? $model : '' ?>"><?= $link['label'] ?></a>
                    <?php endif; ?>
                <?php endforeach; ?> 
                    <?php if ($user['role'] >= 1): ?>
                        <a class="btn text-white text-start" href="/admin/checks_pages/scanner.php"><?= Lang::getText("checks_pages:scanner") ?></a>
                    <?php endif; ?>
            </div>
            <div class="user-action text-white mb-3 d-flex justify-content-around align-items-center flex-column">
                <div class="user-profile border-top mb-2 d-flex justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-user me-2"></i> <?= sprintf('%s %s', $_SESSION['firstname'], $_SESSION['lastname']) ?>
                    </div>
                    <?php include './template/lang_selector.php' ?>
                </div>
                <a href="./users/logout.php" class="btn btn-danger btn-sm col-auto"><?= Lang::getText("general:logout") ?></a>
            </div>
        </div>
    </nav>
    <main class="col h-100">
        <div id="body" class="d-flex justify-content-center h-100">
            <div id="confirm_message" class="text-success text-center fw-bold fs-5">
                <?= $confirmed_message ?? '' ?>
            </div>
            <?php include __DIR__ . $navLinks[$currentSection]['path'] ?>
        </div>
        
    </main>
    <div class="offcanvas offcanvas-start dark_blue_bg text-white" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header">
            <a href="/home" class="">
                <img src="public/img/RRooms_extended_logo_nobg.png" alt="Reservation rooms" class="w-75 my-2 ms-3">
            </a>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Chiudi"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column justify-content-between">
            <div class="links d-flex flex-column gap-2">
                <?php foreach ($navLinks as $model => $link): ?>
                    <?php if ($_SESSION['role'] >= $link['level'] && $link['visible'] > 0): ?>
                        <a class="btn text-start text-white" href="/<?= $model != 'home' ? $model : '' ?>"><?= $link['label'] ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="user-action border-top pt-3 mt-3">
                <div class="user-profile mb-2 d-flex justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-user me-2"></i> <?= sprintf('%s %s', $_SESSION['firstname'], $_SESSION['lastname']) ?>
                    </div>
                    <?php include './template/lang_selector.php' ?>
                </div>
                <a href="./users/logout.php" class="btn btn-danger btn-sm"><?= Lang::getText("general:logout") ?></a>
            </div>
        </div>
    </div>
            
</div>
<?php include_once './template/footer.php'; ?>
<script>
    setTimeout(function () {
        document.getElementById('confirm_message').style.display = 'none';
    }, 5000)

</script>