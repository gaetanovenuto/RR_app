<?php 
include __DIR__ . '/../../config.php';

$users = User::select([
  "columns" => 'name'
]);

var_dump($users);