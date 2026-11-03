<?php
declare(strict_types=1);

require __DIR__ . '/partes/sesion.php';
require __DIR__ . '/conexion.php';

$errores = [];
$titulo = '';
$fecha = '';
$hora = '';
$categoriaId = 0;
$descripcion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $fecha = trim((string) ($_POST['fecha'] ?? ''));
    $hora = trim((string) ($_POST['hora'] ?? ''));
    $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
    $descripcion = trim((string) ($_POST['descripcion'] ?? ''));

    if ($titulo === '' || mb_strlen($titulo) > 120) {
        $errores[] = 'El título es obligatorio y debe tener máximo 120 caracteres.';
    }

    $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
        $errores[] = 'La fecha no es válida.';
    }

    if ($hora !== '' && !preg_match('/^\d{2}:\d{2}$/', $hora)) {
        $errores[] = 'La hora no es válida.';
    }

    if ($categoriaId < 1) {
        $errores[] = 'Selecciona una categoría.';
    }

    if (mb_strlen($descripcion) > 500) {
        $errores[] = 'La descripción debe tener máximo 500 caracteres.';
    }

    if (!$errores) {
        $horaBD = $hora === '' ? null : $hora . ':00';
        $descBD = $descripcion === '' ? null : $descripcion;

        $stmt = $mysqli->prepare(
            'INSERT INTO eventos
                (usuario_id, titulo, fecha, hora, categoria_id, descripcion)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->bind_param(
            'isssis',
            $usuarioId,
            $titulo,
            $fecha,
            $horaBD,
            $categoriaId,
            $descBD
        );

        $stmt->execute();
        $stmt->close();
        $mysqli->close();

        header('Location: index.php?ok=1');
        exit;
    }
}

$categorias = $mysqli
    ->query('SELECT id, nombre FROM categorias ORDER BY nombre')
    ->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrar evento | AgendaWeb</title>
    <link rel="stylesheet" href="css/estilos.css?v=14">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php">AgendaWeb</a>
    <nav aria-label="Navegación principal">
        <span>Hola, <?= e($usuarioNombre) ?></span>
        <a href="index.php">Mis eventos</a>
        <a href="logout.php">Salir</a>
    </nav>
</header>

<main class="contenedor">
    <section class="login-card">
        <p class="eyebrow">AgendaWeb</p>
        <h1>Registrar evento</h1>
        <p class="intro">Agrega un nuevo evento a tu agenda.</p>

        <?php if ($errores): ?>
            <div class="alert alert-error" role="alert">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label for="titulo">Título</label>
                <input
                    id="titulo"
                    type="text"
                    name="titulo"
                    maxlength="120"
                    value="<?= e($titulo) ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="fecha">Fecha</label>
                <input
                    id="fecha"
                    type="date"
                    name="fecha"
                    value="<?= e($fecha) ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="hora">Hora <span class="optional">(opcional)</span></label>
                <input
                    id="hora"
                    type="time"
                    name="hora"
                    value="<?= e($hora) ?>"
                >
            </div>

            <div class="field">
                <label for="categoria_id">Categoría</label>
                <select id="categoria_id" name="categoria_id" required>
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option
                            value="<?= (int) $categoria['id'] ?>"
                            <?= $categoriaId === (int) $categoria['id'] ? 'selected' : '' ?>
                        >
                            <?= e($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="descripcion">Descripción <span class="optional">(opcional)</span></label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    maxlength="500"
                ><?= e($descripcion) ?></textarea>
            </div>

            <button class="button button-full" type="submit">
                Guardar evento
            </button>
        </form>

        <p class="form-footer">
            <a href="index.php">Cancelar y volver a mis eventos</a>
        </p>
    </section>
</main>

<footer class="site-footer">AgendaWeb</footer>
</body>
</html>
