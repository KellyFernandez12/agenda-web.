<?php
session_start();
if (isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
require __DIR__ . '/conexion.php';

function e(string $texto): string {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

$nombre = '';
$correo = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave = $_POST['clave'] ?? '';
    $clave2 = $_POST['clave2'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $errores['nombre'] = 'Escribe tu nombre (máximo 80 caracteres).';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 120) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if (mb_strlen($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    if (!$errores) {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        try {
            $stmt = $mysqli->prepare(
                'INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)'
            );
            $stmt->bind_param('sss', $nombre, $correo, $hash);
            $stmt->execute();
            $stmt->close();
            header('Location: login.php?registrado=1');
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {
                $errores['correo'] = 'Ese correo ya tiene una cuenta.';
            } else {
                $errores['general'] = 'No se pudo crear la cuenta. Intenta de nuevo.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Crear cuenta | AgendaWeb</title><link rel="stylesheet" href="css/estilos.css"></head>
<body>
<main class="contenedor">
    <h1>Crear cuenta</h1>
    <?php if (isset($errores['general'])): ?><p class="alert alert--error"><?= e($errores['general']) ?></p><?php endif; ?>
    <form method="post" novalidate>
        <label>Nombre
            <input type="text" name="nombre" maxlength="80" value="<?= e($nombre) ?>" required>
        </label>
        <?php if (isset($errores['nombre'])): ?><small class="error"><?= e($errores['nombre']) ?></small><?php endif; ?>
        <label>Correo
            <input type="email" name="correo" maxlength="120" value="<?= e($correo) ?>" required>
        </label>
        <?php if (isset($errores['correo'])): ?><small class="error"><?= e($errores['correo']) ?></small><?php endif; ?>
        <label>Contraseña
            <input type="password" name="clave" minlength="8" autocomplete="new-password" required>
        </label>
        <?php if (isset($errores['clave'])): ?><small class="error"><?= e($errores['clave']) ?></small><?php endif; ?>
        <label>Repítela
            <input type="password" name="clave2" minlength="8" autocomplete="new-password" required>
        </label>
        <?php if (isset($errores['clave2'])): ?><small class="error"><?= e($errores['clave2']) ?></small><?php endif; ?>
        <button type="submit">Crear cuenta</button>
    </form>
    <p><a href="login.php">Ya tengo cuenta</a></p>
</main>
</body>
</html>
