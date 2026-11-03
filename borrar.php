<?php
require __DIR__ . '/partes/sesion.php';
require __DIR__ . '/conexion.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id) {
    $stmt = $mysqli->prepare('DELETE FROM eventos WHERE id = ? AND usuario_id = ?');
    $stmt->bind_param('ii', $id, $usuarioId);
    $stmt->execute();
    $stmt->close();
}
header('Location: index.php');
exit;
