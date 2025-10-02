<?php
include './config.php';
$now = date('Y-m-d H:i:s');
$hasPassedEvents = false;
$hasFutureEvents = false;
$hasParticipantEvents = false;

if ($user ?? false) {
    $future_events = Event::select([
        "where" => sprintf("user_id = %d AND starting_time > '%s'", $user['id'], $now)
    ]);

    if ($future_events ?? false) {
        $hasFutureEvents = true;
    }

    $passed_events = Event::select([
        "where" => sprintf("user_id = %d AND starting_time < '%s'", $user['id'], $now)
    ]);
    if ($passed_events ?? false) {
        $hasPassedEvents = true;
    }

    $participant_events = Event::select([
        "columns" => "events.name, events.starting_time, participants.confirmed",
        "joins" => 
        [
            [
                "type" => "INNER",
                "sql" => "participants ON participants.event_id = events.id"
            ]
        ],
        "orderBy" => "events.starting_time",
        "orderDirection" => "DESC",
        "where" => sprintf("participants.email = '%s' AND events.user_id != %d", $user['email'], $user['id'])
    ]);

    if ($participant_events ?? false) {
        $hasParticipantEvents = true;
    }
}

include_once './template/header.php';
?>

<div class="d-flex flex-column w-100 justify-content-between h-100">
    <!-- Header Section -->
    <header class="d-flex dark_blue_bg w-100 p-3 p-md-4 justify-content-between align-items-center" id="homepage_header">
        <h1 class="text-white mb-0 fs-4 fs-md-2">
            <?= Lang::getText("home:your_reservations") ?>
        </h1>
        <a href="/home" class="d-flex justify-content-end">
            <img src="public/img/RRooms_full_logo.png" alt="Reservation rooms" class="img-fluid" style="max-width: 120px; max-height: 60px;">
        </a>
    </header>

    <!-- Welcome Section -->
    <section class="bg-white w-100 p-3 p-md-4" id="presentation_banner">
        <h3 class="mb-0 fs-5 fs-md-4">
            <?= Lang::getText("home:welcome") ?>, <?= $user['firstname'] ?>. <br>
            <?= Lang::getText("home:banner") ?>
        </h3>
    </section>

    <!-- Events Section -->
    <section class="dark_blue_bg w-100 py-4 py-md-5 px-3 px-md-4 d-flex align-items-center" id="homepage_reservations_section">
        <div class="container-fluid px-0">
            <div class="row g-3 g-md-4 mx-0">
                
                <!-- Future Events Column -->
                <div class="col-12 col-md-6 col-xl-4 mb-4 mb-xl-0">
                    <div class="text-white text-center fw-bold mb-3 fs-6">
                        <?= Lang::getText("home:future_events") ?>
                    </div>
                    <div class="card shadow event_wrapper" style="min-height: 300px;">
                        <div class="card-body p-0 d-flex flex-column">
                            <?php if ($hasFutureEvents): ?>
                                <div class="event_list flex-grow-1">
                                    <?php foreach ($future_events as $index => $event): ?>
                                        <div class="event_card d-flex flex-column flex-lg-row align-items-stretch border-bottom border-light-subtle <?= $index === 0 ? '' : 'border-top' ?>">
                                            <div class="event_card_name flex-grow-1 p-3 d-flex align-items-center">
                                                <span class="fw-semibold text-truncate"><?= htmlspecialchars($event['name']) ?></span>
                                            </div>
                                            <div class="event_card_datetime d-none d-lg-flex align-items-center px-3 py-2 border-start border-light-subtle bg-light">
                                                <small class="text-muted text-nowrap">
                                                    <?= date('d/m/Y H:i', strtotime($event['starting_time'])) ?>
                                                </small>
                                            </div>
                                            <div class="event_card_datetime_mobile d-lg-none px-3 py-2 bg-light">
                                                <small class="text-muted">
                                                    <?= date('d/m/Y H:i', strtotime($event['starting_time'])) ?>
                                                </small>
                                            </div>
                                            <div class="event_card_actions d-flex align-items-center justify-content-center p-3 border-start border-light-subtle bg-light">
                                                <a href="/reservation_details?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1 d-none d-lg-inline"></i>
                                                    <?= Lang::getText("general:details") ?>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="d-flex justify-content-center align-items-center h-100 p-4">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-calendar-times fa-2x mb-3"></i>
                                        <p class="mb-0 fw-bold"><?= Lang::getText("home:no_events_available") ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Past Events Column -->
                <div class="col-12 col-md-6 col-xl-4 mb-4 mb-xl-0">
                    <div class="text-white text-center fw-bold mb-3 fs-6">
                        <?= Lang::getText("home:past_events") ?>
                    </div>
                    <div class="card shadow event_wrapper" style="min-height: 300px;">
                        <div class="card-body p-0 d-flex flex-column">
                            <?php if ($hasPassedEvents): ?>
                                <div class="event_list flex-grow-1">
                                    <?php foreach ($passed_events as $index => $event): ?>
                                        <div class="event_card d-flex flex-column flex-lg-row align-items-stretch border-bottom border-light-subtle <?= $index === 0 ? '' : 'border-top' ?> opacity-75">
                                            <div class="event_card_name flex-grow-1 p-3 d-flex align-items-center">
                                                <span class="fw-semibold text-truncate"><?= htmlspecialchars($event['name']) ?></span>
                                            </div>
                                            <div class="event_card_datetime d-none d-lg-flex align-items-center px-3 py-2 border-start border-light-subtle bg-light">
                                                <small class="text-muted text-nowrap">
                                                    <?= date('d/m/Y H:i', strtotime($event['starting_time'])) ?>
                                                </small>
                                            </div>
                                            <div class="event_card_datetime_mobile d-lg-none px-3 py-2 bg-light">
                                                <small class="text-muted">
                                                    <?= date('d/m/Y H:i', strtotime($event['starting_time'])) ?>
                                                </small>
                                            </div>
                                            <div class="event_card_actions d-flex align-items-center justify-content-center p-3 border-start border-light-subtle bg-light">
                                                <a href="/reservation_details?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-eye me-1 d-none d-lg-inline"></i>
                                                    <?= Lang::getText("general:details") ?>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="d-flex justify-content-center align-items-center h-100 p-4">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-calendar-check fa-2x mb-3"></i>
                                        <p class="mb-0 fw-bold"><?= Lang::getText("home:no_events_available") ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Participant Events Column -->
                <div class="col-12 col-md-12 col-xl-4">
                    <div class="text-white text-center fw-bold mb-3 fs-6">
                        <?= Lang::getText("home:participations") ?>
                    </div>
                    <div class="card shadow event_wrapper" style="min-height: 300px;">
                        <div class="card-body p-0 d-flex flex-column">
                            <?php if ($hasParticipantEvents): ?>
                                <div class="event_list flex-grow-1">
                                    <?php foreach ($participant_events as $index => $event): ?>
                                        <?php
                                        $statusClass = '';
                                        $statusIcon = '';
                                        $statusText = '';
                                        
                                        if ($event['confirmed'] && $event['starting_time'] > $now) {
                                            $statusClass = 'bg-success text-white';
                                            $statusIcon = 'fas fa-check-circle';
                                            $statusText = Lang::getText("home:confirmed");
                                        } elseif (!$event['confirmed'] && $event['starting_time'] > $now) {
                                            $statusClass = 'bg-warning text-dark';
                                            $statusIcon = 'fas fa-clock';
                                            $statusText = Lang::getText("home:to_confirm");
                                        } else {
                                            $statusClass = 'bg-secondary text-white opacity-75';
                                            $statusIcon = 'fas fa-history';
                                            $statusText = Lang::getText("home:concluded");
                                        }
                                        ?>
                                        <div class="event_card d-flex flex-column border-bottom border-light-subtle <?= $index === 0 ? '' : 'border-top' ?> <?= $statusClass ?>">
                                            <div class="d-flex align-items-center p-3">
                                                <div class="flex-grow-1 col-6">
                                                    <div class="fw-semibold text-truncate mb-1">
                                                        <?= htmlspecialchars($event['name']) ?>
                                                    </div>
                                                    <small class="opacity-75">
                                                        <?= date('d/m/Y H:i', strtotime($event['starting_time'])) ?>
                                                    </small>
                                                </div>
                                                <div class="ms-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="<?= $statusIcon ?> me-2"></i>
                                                        <small class="fw-bold"><?= $statusText ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="d-flex justify-content-center align-items-center h-100 p-4">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-users fa-2x mb-3"></i>
                                        <p class="mb-0 fw-bold"><?= Lang::getText("home:no_events_available") ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>
</div>

<?php include_once './template/footer.php'; ?>