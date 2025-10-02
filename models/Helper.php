<?php

class Helper {
    public static function generatePdf($params, $templateType) {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                 
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
                 
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Reservations Rooms');
        
        $pdf->AddPage();
        
        switch ($templateType) {
            
            case 'participation_confirmed':
                $pdf->SetTitle('Reservation Resume');
                $pdf->SetSubject('Reservation Resume');
                $pdf->SetFont('times', '', 9);
                $pdf->SetMargins(10, 15, 10);
                [$html, $qrPngData, $barcodeData] = Event::writePdf(
                    [
                        "email" => $params['email'], 
                        "event_id" => $params['event_id']
                    ], $templateType);
                $logo = $_ENV['APP_URL'] . '/public/img/RRooms_full_logo.png';
                $pdf->writeHTML($html, true, 0, true, 0);
                $pdf->Image($logo, 150, 15, 30, 30, 'PNG');
                $pdf->Image('@' . $qrPngData, 150, 55, 30, 30, 'PNG');
                $barcodeX = 125;
                $barcodeY = 225;
                $barcodeWidth = 75;
                $barcodeHeight = 25;
                $padding = 5; 
                
                $pdf->Image('@' . $barcodeData, $barcodeX, $barcodeY, $barcodeWidth, $barcodeHeight, 'PNG');
                
                $pdf->SetDrawColor(0, 0, 0); 
                $pdf->SetLineWidth(.5);

                $pdf->RoundedRect(
                    $barcodeX - $padding, 
                    $barcodeY - $padding, 
                    $barcodeWidth + (2 * $padding), 
                    $barcodeHeight + (2 * $padding), 
                    3.50, 
                    '1111', 
                    'D',
                    ["color" => array(4, 15, 57)]
                );
                $filename = 'Riepilogo_evento_' . $params['event_id'] . '_' . date('Ymd') . '.pdf';
                $tempDir = __DIR__ . '/../' . $_ENV['TMP_DIR'];
                $filepath = $tempDir . DIRECTORY_SEPARATOR . $filename;
                $pdf->Output($filepath, 'F');
                                 
                if (file_exists($filepath) && filesize($filepath) > 0) {
                    return $filepath;
                }
                $pdf->lastPage();
                break;
                
            case 'print_tickets':
                $pdf->SetTitle('Reservation tickets');
                $pdf->SetSubject('Reservation tickets');
                $pdf->SetFont('times', '', 14);
                $pdf->SetMargins(10, 15, 10);
                $allBarcodesData = [];
                $allEmails = []; 
                
                $events = Event::select([
                    "where" => sprintf("id = %d", $params['event_id'])
                ]);
                $event = $events[0];
                
                foreach ($params['emails'] as $key => $email) {
                    [$html, $barcodeData] = Event::writePdf([
                        "email" => $email,
                        "event_id" => $params['event_id']
                    ], $templateType);
                    
                    $allBarcodesData[] = $barcodeData;
                    $allEmails[] = $email;
                    
                    $pdf->writeHTML($html, true, 0, true, 0);
                    
                    $barcodeX = 125;
                    $barcodeY = 225;
                    $barcodeWidth = 75;
                    $barcodeHeight = 25;
                    $padding = 5; 
                    
                    $pdf->Image('@' . $barcodeData, $barcodeX, $barcodeY, $barcodeWidth, $barcodeHeight, 'PNG');
                    
                    $pdf->SetDrawColor(0, 0, 0);

                    $pdf->RoundedRect(
                        $barcodeX - $padding, 
                        $barcodeY - $padding, 
                        $barcodeWidth + (2 * $padding), 
                        $barcodeHeight + (2 * $padding), 
                        3.50, 
                        '1111', 
                        'D',
                        ["color" => array(4, 15, 57)]
                    );
                    
                    $pdf->AddPage();
                }
                
                $perPage = (int) $_ENV['SUMMARY_TICKETS_QT_BARCODE'];
                $cols = 2;
                $rows = ceil($perPage / $cols);

                $pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
                $pageHeight = $pdf->getPageHeight() - $pdf->getMargins()['top'] - $pdf->getMargins()['bottom'];

                $spacingX = 5;
                $spacingY = 5;

                $cellWidth = ($pageWidth - ($cols - 1) * $spacingX) / $cols;
                $cellHeight = ($pageHeight - ($rows - 1) * $spacingY) / $rows;

                $pdf->SetFont('times', '', 10);
                $total = count($allEmails);

                for ($i = 0; $i < $total; $i++) {
                    if ($i % $perPage === 0 && $i != 0) {
                        $pdf->AddPage();
                    }

                    $indexInPage = $i % $perPage;
                    $row = floor($indexInPage / $cols);
                    $col = $indexInPage % $cols;

                    $x = $pdf->getMargins()['left'] + $col * ($cellWidth + $spacingX);
                    $y = $pdf->getMargins()['top'] + $row * ($cellHeight + $spacingY);

                    $email = $allEmails[$i];
                    $barcodeImage = $allBarcodesData[$i];

                    $pdf->SetDrawColor(0, 0, 0);
                    $pdf->RoundedRect($x, $y, $cellWidth, $cellHeight, 3, '1111', 'D');

                    $barcodeHeight = $cellHeight * 0.7;
                    $pdf->Image('@' . $barcodeImage, $x + 5, $y + 5, $cellWidth - 10, $barcodeHeight - 10, 'PNG');

                    $pdf->SetXY($x, $y + $barcodeHeight);
                    $pdf->Cell($cellWidth, 5, $email, 0, 0, 'C');
                }
                
                $pdf->LastPage();
                $filename = 'Biglietti_' . date('Ymd') . '.pdf';
                $pdf->Output($filename, 'D');
                return true;
                break;
        }
                                    
        return false;
    }
}

