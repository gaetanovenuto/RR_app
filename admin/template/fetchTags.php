<?php
include __DIR__ . '/../../config.php';

User::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['loadTags'] ?? false) {
    
    foreach (Mail_templates::$possibleTags as $tags => $info) {
        if ($_POST['type'] == $tags) {
            echo json_encode([
                "success" => 1,
                "tags" => [
                    $tags => $info
                ]
            ]);
        }
    }
    exit();
}