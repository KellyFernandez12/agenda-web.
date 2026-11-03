<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli('localhost', 'TU_USUARIO', 'TU_CONTRASEÑA', 'agenda');
$mysqli->set_charset('utf8mb4');
