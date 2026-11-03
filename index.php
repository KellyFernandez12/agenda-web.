<?php
declare(strict_types=1);

// El guardia debe ser lo primero: sin sesión redirige a login.php.
require __DIR__ . '/partes/sesion.php';
require __DIR__ . '/conexion.php';

$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

function formatearFecha(string $fecha): string
{
    $marcaTiempo = strtotime($fecha);
    return $marcaTiempo === false ? $fecha : date('d/m/Y', $marcaTiempo);
}

function mostrarEvento(array $ev, string $csrfToken): string
{
    $html = '<article class="card event-card">';
    $html .= '<div class="event-meta">';
    $html .= '<span class="badge">' . e((string)($ev['categoria'] ?? '')) . '</span>';
    $fecha = (string)($ev['fecha'] ?? '');
    $html .= '<time datetime="' . e($fecha) . '">' . e(formatearFecha($fecha)) . '</time>';

    $hora = $ev['hora'] ?? null;
    if ($hora !== null && $hora !== '') {
        $html .= '<span>' . e(substr((string)$hora, 0, 5)) . ' h</span>';
    }
    $html .= '</div>';
    $html .= '<h2>' . e((string)($ev['titulo'] ?? '')) . '</h2>';

    $descripcion = $ev['descripcion'] ?? null;
    if ($descripcion !== null && $descripcion !== '') {
        $html .= '<p class="event-description">' . nl2br(e((string)$descripcion)) . '</p>';
    }

    $id = (int)($ev['id'] ?? 0);
    $html .= '<div class="card__actions">';
    $html .= '<a href="editar.php?id=' . $id . '" class="action-button action-edit">Editar</a>';
    $html .= '<form method="post" action="borrar.php" class="form-inline" onsubmit="return confirm(\'¿Seguro que quieres borrar este evento?\');">';
    $html .= '<input type="hidden" name="id" value="' . $id . '">';
    $html .= '<input type="hidden" name="csrf_token" value="' . e($csrfToken) . '">';
    $html .= '<button type="submit" class="action-button action-delete">Borrar</button>';
    $html .= '</form></div>';

    return $html . '</article>';
}

$eventos = [];
$resumen = [];
$errorGeneral = '';

try {
    // Lista: solo eventos pertenecientes al usuario de la sesión.
    $stmt = $mysqli->prepare(
        'SELECT e.id, e.titulo, e.fecha, e.hora, e.descripcion,
                c.nombre AS categoria
           FROM eventos e
           JOIN categorias c ON c.id = e.categoria_id
          WHERE e.usuario_id = ?
          ORDER BY e.fecha ASC, e.hora ASC, e.id DESC'
    );
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // El filtro va en ON para que las categorías sin eventos sigan apareciendo con 0.
    $stmtResumen = $mysqli->prepare(
        'SELECT c.nombre, COUNT(e.id) AS total
           FROM categorias c
           LEFT JOIN eventos e
             ON e.categoria_id = c.id
            AND e.usuario_id = ?
          GROUP BY c.id, c.nombre
          ORDER BY c.nombre'
    );
    $stmtResumen->bind_param('i', $usuarioId);
    $stmtResumen->execute();
    $resumen = $stmtResumen->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtResumen->close();

    $mysqli->close();
} catch (Throwable $ex) {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
    }
    $errorGeneral = 'No se pudieron cargar los eventos. Revisa la conexión y las tablas eventos y categorias.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AgendaWeb | Mis eventos</title>
    <link rel="stylesheet" href="/css/estilos.css?v=6">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php">AgendaWeb</a>
    <nav aria-label="Navegación principal">
        <span>Hola, <?= e((string)$usuarioNombre) ?></span>
        <a href="registrar.php">Registrar evento</a>
        <a href="logout.php">Salir</a>
    </nav>
</header>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Tu espacio organizado</p>
            <h1>Mis eventos</h1>
            <p class="intro"><?= count($eventos) ?> eventos registrados</p>
        </div>
        <a class="button" href="registrar.php">+ Nuevo evento</a>
    </div>

    <?php if (isset($_GET['ok']) && $_GET['ok'] === '1'): ?>
        <div class="alert alert-success" role="status">Evento guardado correctamente.</div>
    <?php elseif (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
        <div class="alert alert-success" role="status">Evento actualizado correctamente.</div>
    <?php elseif (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>
        <div class="alert alert-success" role="status">Evento eliminado correctamente.</div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'notfound'): ?>
        <div class="alert alert-error" role="alert">No se encontró ese evento.</div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?>
        <div class="alert alert-error" role="alert">La solicitud expiró o no es válida. Vuelve a intentarlo.</div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'db'): ?>
        <div class="alert alert-error" role="alert">No se pudo completar la operación en la base de datos.</div>
    <?php endif; ?>

    <?php if ($errorGeneral !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($errorGeneral) ?></div>
    <?php else: ?>
        <div class="resumen" aria-label="Cantidad de eventos por categoría">
            <?php foreach ($resumen as $r): ?>
                <span class="badge"><?= e((string)$r['nombre']) ?> · <?= (int)$r['total'] ?></span>
            <?php endforeach; ?>
        </div>

        <?php if (empty($eventos)): ?>
            <section class="card empty-state">
                <h2>Aún no hay eventos</h2>
                <p>Agrega tu primer evento para empezar a organizarte.</p>
                <a class="button" href="registrar.php">Registrar evento</a>
            </section>
        <?php else: ?>
            <section class="event-list" aria-label="Lista de eventos">
                <?php foreach ($eventos as $ev): ?>
                    <?= mostrarEvento($ev, $csrfToken) ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>
<footer class="site-footer">AgendaWeb</footer>
</body>
</html>
