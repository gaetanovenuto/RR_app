<?php 
require_once '../config.php';
function tsToDate($date) {
    return date('H:i:s', $date);
}

function tsToISO($date) {
    return date('Y-m-d H:i:s', $date);
}

$pdf = Helper::generatePdf([
    "emails" => ['gaetano.venuto@axterisko.it', '2ldfqctg7w0xhl6j7b@gmail.com', '2mbtjbjgud464cjg1aq@yahoo.com'],
    "event_id" => 45
], 'print_tickets');
?>
