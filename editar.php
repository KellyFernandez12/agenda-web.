<?php
require __DIR__ . '/partes/sesion.php';
require __DIR__ . '/conexion.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $mysqli->prepare('SELECT titulo, fecha, hora, categoria_id, descripcion FROM eventos WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $id, $usuarioId);
$stmt->execute();
$evento = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$evento) { http_response_code(404); exit('Ese evento no existe.'); }

$errores = [];
$titulo = $_POST['titulo'] ?? $evento['titulo'];
$fecha = $_POST['fecha'] ?? $evento['fecha'];
$hora = $_POST['hora'] ?? ($evento['hora'] ? substr($evento['hora'], 0, 5) : '');
$categoriaId = (int) ($_POST['categoria_id'] ?? $evento['categoria_id']);
$descripcion = $_POST['descripcion'] ?? ($evento['descripcion'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($titulo); $descripcion = trim($descripcion);
    if ($titulo === '' || mb_strlen($titulo) > 120) $errores[] = 'El título es obligatorio y debe tener máximo 120 caracteres.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $errores[] = 'La fecha no es válida.';
    if ($categoriaId < 1) $errores[] = 'Selecciona una categoría.';
    if (mb_strlen($descripcion) > 500) $errores[] = 'La descripción debe tener máximo 500 caracteres.';
    if (!$errores) {
        $horaBD = $hora === '' ? null : $hora . ':00'; $descBD = $descripcion === '' ? null : $descripcion;
        $stmt = $mysqli->prepare('UPDATE eventos SET titulo = ?, fecha = ?, hora = ?, categoria_id = ?, descripcion = ? WHERE id = ? AND usuario_id = ?');
        $stmt->bind_param('sssisii', $titulo, $fecha, $horaBD, $categoriaId, $descBD, $id, $usuarioId);
        $stmt->execute(); $stmt->close();
        header('Location: index.php'); exit;
    }
}
$categorias = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Editar evento</title><link rel="stylesheet" href="css/estilos.css"></head><body><main class="contenedor"><h1>Editar evento</h1><?php foreach ($errores as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?><form method="post"><input type="hidden" name="id" value="<?= (int) $id ?>"><label>Título <input name="titulo" maxlength="120" value="<?= e($titulo) ?>" required></label><label>Fecha <input type="date" name="fecha" value="<?= e($fecha) ?>" required></label><label>Hora <input type="time" name="hora" value="<?= e($hora) ?>"></label><label>Categoría <select name="categoria_id" required><?php foreach ($categorias as $cat): ?><option value="<?= (int) $cat['id'] ?>" <?= $categoriaId === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['nombre']) ?></option><?php endforeach; ?></select></label><label>Descripción <textarea name="descripcion" maxlength="500"><?= e($descripcion) ?></textarea></label><button type="submit">Guardar cambios</button> <a href="index.php">Cancelar</a></form></main></body></html>
