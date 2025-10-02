<?php
include __DIR__ . '/../../config.php';

$logTitle = "Rooms table";
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

if ($_POST['deleteRoomData'] ?? false) {
    $rooms = Room::select([
        "where" => sprintf("id = %d", $_POST['id'])
    ]);
    $room = $rooms[0];
    $now = date('Y-m-d H:i:s');

    $futureEvents = Event::select([  
        "where" => sprintf("room_id = %d AND ending_time > '%s'", $_POST['id'], $now),
        "orderBy" => "ending_time",
        "orderDirection" => "DESC"
    ]);
    $futureEvent = $futureEvents[0];
    if (!$futureEvents) {
        $evs = Event::select([
            "where" => sprintf("room_id = %d", $_POST['id'])
        ]);
        if ($evs) {
            $ev = $evs[0];
            $deletedParticipants = Participant::delete(sprintf("event_id = %d", $ev['id']));
            
            if ($deletedParticipants) {
                $deletedEvents = Event::delete(sprintf("room_id = %d", $_POST['id']));
                
                if ($deletedEvents) {
                    $result = Room::delete('id = ' . $_POST['id']);
                }
            }
        } else {
            $result = Room::delete('id = ' . $_POST['id']);
        }
        if (!$result) {
            echo json_encode([
                "success" => 0,
                "message" => Lang::getText("error:delete_failed")
            ]);
            exit();
        }
        echo json_encode([
            "success" => 1,
            "message" => Lang::getText("success:delete")
        ]);
        exit();
    } else {
        if ($room['availability'] == 1) {
            $updateRoom = Room::update(["availability" => 0], sprintf("id = %d", $_POST['id']));
        }
        $availableDateToDel = date('d-m-Y H:i:s', strtotime($futureEvent['ending_time']));
        echo json_encode([
            "success" => 0,
            "message" => Lang::getText("error:room_events_available") . $availableDateToDel
        ]);
    }
    exit();
}

if ($_POST['updateState'] ?? false) {
    $result = Room::update(["availability" => $_POST['updatedRoomState']], "id = " . $_POST['id']);
    
    if (!$result) {
        echo json_encode([
            "success" => 0,
            "message" => sprintf("Errore %s della stanza", $_POST['updatedRoomState'] ? 'nell\'abilitazione' : 'nella disabilitazione')
        ]);
        exit();
    }
    echo json_encode([
        "success" => 1,
        "message" => sprintf("Stanza %s correttamente", $_POST['updatedRoomState'] ? 'abilitata' : 'disabilitata')
    ]);

    exit();
}

if ($_POST['deleteMultipleRooms'] ?? false) {
    $deletingRoomsArray = json_decode($_POST['deletingRoomsArray'], true);
    if ($deletingRoomsArray ?? false) {
        $result = Room::delete('id in (' . implode(", ", $deletingRoomsArray) . ")");
        
        if (!$result) {
            echo json_encode(["success" => 0, "message" => "Errore nell'eliminazione delle stanze"]);
            exit();
        }
        echo json_encode(["success" => 1]);
        exit();
    }
}

if ($_POST['fetch_ranges'] ?? false) {
    $rooms = Room::select([
        "where" => sprintf("id = %s", $_POST['id'])
    ]);
    
    if ($rooms ?? false) {
        $room = $rooms[0];

        echo json_encode([
            "success" => 1,
            "room" => $room
        ]);
    } else {
        echo json_encode([
            "success" => 0,
            "message" => "Si è verificato un errore nella visualizzazione dei range di apertura"
        ]);
    }
    exit();
}

$pageTitle = 'STANZE ESISTENTI';
include_once __DIR__ . '/../../template/header.php';
?>
<?php if (!$deniedAccess): ?>
    <div class="row w-100 mx-2">
        <h3 class="my-1 text-center fw-bold">
            <?= $pageTitle ?>
        </h3>
        <div id="tableContent" class="position-relative px-0">
            <?php include_once 'roomsTableContent.php' ?>
            <div id="modal_ranges" style="visibility: hidden;">
                <div class="modal_ranges_body">
                    <h5 class="text-center">
                        
                    </h5>
                </div>
            </div>
        </div>
        <div class="toast position-absolute align-items-center text-white border-0 rounded-0 opacity-9" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex justify-content-center align-items-center">
                <div class="toast-body text-white fw-bold">
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php include_once __DIR__ . '/../../users/access_denied.php' ?>
<?php endif; ?>

<?php 
    include_once __DIR__ . '/../../template/footer.php';
?>
<script>
// GENERIC FUNCTIONS
let key = 'id';
let orderDirection = 'ASC';
let page = 1;
let perPage = 10;

const toast = document.querySelector('.toast');
const toastBody = document.querySelector('.toast-body');
const modal = document.getElementById('modal_ranges');
const modal_body = document.querySelector('.modal_ranges_body');

async function deleteRoom(roomId) {
    if (confirm('Attenzione: eliminando questa stanza verranno eliminate anche tutte le prenotazioni passate legate a questa stanza. Sei sicuro di voler eliminare questa stanza?')) {
        if (confirm('Sicuro sicuro?')) {
            const data = new FormData();
            data.append("id", roomId);
            data.append("deleteRoomData", true);
    
            fetch("/admin/room/roomsTable.php", {
                method: 'POST',
                body: data
            }).then(async (res) => {
                const response = await res.json();
                reloadTable(response);
            });
        }
    }
}

async function deleteMultipleRooms(roomsArray) {
    if (roomsArray.length === 0) {
        alert("Nessuna stanza selezionata");
        return;
    }
    
    if (confirm('Attenzione: eliminando queste stanze verranno eliminate anche tutte le prenotazioni passate legate a questa stanza. Sei sicuro di voler procedere?')) {
        if (confirm('Sicuro sicuro?')) {
            const data = new FormData();
            data.append("deletingRoomsArray", JSON.stringify(roomsArray));
            data.append("deleteMultipleRooms", true);
    
            fetch("/admin/room/roomsTable.php", {
                method: "POST",
                body: data
            }).then(async (res) => {
                const response = await res.json();
                reloadTable(response);
            });
        }
    }
}

async function toggleRoomAbilitation(roomId, roomName, roomState) {
    let updatedRoomState;
    
    if (confirm("Sei sicuro di voler " + (roomState ? "disabilitare" : "abilitare") + " " + roomName + "?")) {
        console.log('roomState: ' + roomState);
        updatedRoomState = roomState == 0 ? 1 : 0;
        // if (roomState == 0) {
        //     updatedRoomState = 1;
        // } else {
        //     updatedRoomState = 0;
        // }
        console.log('updatedRoomState: ' + updatedRoomState);
        
        const data = new FormData();
        data.append("id", roomId);
        data.append("updatedRoomState", updatedRoomState);
        data.append("updateState", true);

        fetch('/admin/room/roomsTable.php', {
            method: 'POST',
            body: data
        }).then(async (res) => {
            const response = await res.json();
            reloadTable(response);
        });
    } else {
        const checkbox = document.querySelector(`#room-row-${roomId} .form-check-input`);
        if (checkbox) {
            checkbox.checked = roomState ? true : false;
        }
    }
}


async function reloadTable(response = null) {
    const data = new FormData();
    data.append("orderTable", true);
    data.append('orderBy', key);
    data.append('orderDirection', orderDirection);
    data.append('page', parseInt(page)); 
    data.append('perPage', parseInt(perPage));
    data.append('response', JSON.stringify(response));

    fetch('/admin/room/roomsTableContent.php', {
        method: 'POST',
        body: data
    }).then(async (res) => {
        if (!res.ok) {
            errorBox.innerHTML = 'Errore nel caricamento dei dati';
            return;
        }
        const tableData = await res.text();
        let tableContent = document.getElementById('tableContent');
        tableContent.innerHTML = tableData;
        
        if (response ?? false) {
            toast.classList.add('show');
            if (response.success) {
                toast.classList.add('bg-success');
            } else {
                toast.classList.add('bg-danger');
            }
            toastBody.innerHTML = response.message;
    
            setTimeout(() => {
                toast.classList.remove('show', 'bg-success', 'bg-danger');
            }, 10000);
        }
    })
}

function showModal(id) {
    
    const data = new FormData();
    data.append("id", id);
    data.append("fetch_ranges", true);

    fetch('/admin/room/roomsTable.php', {
        method: "POST",
        body: data
    }).then(async (res) => {
        const response = await res.json();
        const ranges = JSON.parse(response.room.opening_range);

        let html = `<h5 class="my-1 text-center">Orari di apertura ${response.room.name}</h5>
                        <ul class="mx-3 list-unstyled d-flex flex-column w-75">`
        for (let i = 0; i < ranges.length; i++) {
            html += `<li class="d-flex justify-content-between align-items-center">
                        <span class="range_start"><strong>Inizio: </strong>${ranges[i].start}</span>
                        <span class="range_end"><strong>Fine: </strong>${ranges[i].end}</span>
                    </li>`
        }
        html += `</ul>
                    <button class="btn btn-danger btn-sm my-2" onclick="closeModal()">Chiudi</button>`;
        
        modal.style.visibility = 'visible';
        modal_body.innerHTML = html;
        
    })
}

function closeModal() {
    modal.style.visibility = 'hidden';
    modal_body.innerHTML = '';
}
</script>



