<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = (int) $_SESSION['uid'];
$usuarioNombre = $_SESSION['nombre'] ?? '';

function e(string $texto): string {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}
