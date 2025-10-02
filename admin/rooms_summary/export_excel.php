<?php
include __DIR__ . '/../../config.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['export_form'] ?? false)) {
    $roomId = intval($_POST['room_id']);
    $start = $_POST['starting_date'] . ' 00:00:00';
    $end = $_POST['ending_date'] . ' 23:59:59';

    if ($start > $end) {
        http_response_code(400);
        echo json_encode(["error" => "Date non valide"]);
        exit;
    }

    $rooms = Room::select(["where" => "id = $roomId"]);
    if (!$rooms) {
        http_response_code(404);
        echo json_encode(["error" => "Stanza non trovata"]);
        exit;
    }
    $room = $rooms[0];

    $events = Event::select([
        "where" => sprintf("starting_time >= '%s' AND ending_time <= '%s'", $start, $end)
    ]);
    if (!$events) {
        http_response_code(404);
        echo json_encode(["error" => "Nessun evento disponibile"]);
        exit;
    }

    $headers = array_keys($events[0]);
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($headers as $i => $header) {
        $sheet->setCellValue([$i + 1, 1], $header);
    }

    foreach ($events as $i => $event) {
        $j = 0;
        foreach ($event as $val) {
            $sheet->setCellValue([$j + 1, $i + 2], $val);
            $j++;
        }
    }

    $room['name'] = str_replace(' ', '_', strtolower($room['name']));
    $filename = sprintf("report_%s_%s_%s.xlsx", $room['name'], $start, $end);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>
