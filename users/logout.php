<?php
require_once '../config.php';

User::logout();
header("Location: login.php");
?>