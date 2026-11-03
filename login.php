<?php
session_start();

if (isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/conexion.php';

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$correo = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave = $_POST['clave'] ?? '';

    $stmt = $mysqli->prepare(
        'SELECT id, nombre, clave_hash
           FROM usuarios
          WHERE correo = ?'
    );
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($usuario && password_verify($clave, $usuario['clave_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        header('Location: index.php');
        exit;
    }

    $error = 'Correo o contraseña incorrectos.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | AgendaWeb</title>
    <link rel="stylesheet" href="css/estilos.css?v=13">
</head>
<body>
<main class="contenedor">
    <section class="login-card">
        <p class="eyebrow">AgendaWeb</p>
        <h1>Iniciar sesión</h1>
        <p class="intro">Entra para administrar tus eventos.</p>

        <?php if (isset($_GET['registrado'])): ?>
            <div class="alert alert-success" role="status">
                Cuenta creada. Ya puedes iniciar sesión.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['salio'])): ?>
            <div class="alert alert-success" role="status">
                Cerraste sesión. ¡Hasta pronto!
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label for="correo">Correo</label>
                <input
                    id="correo"
                    type="email"
                    name="correo"
                    maxlength="120"
                    value="<?= e($correo) ?>"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="field">
                <label for="clave">Contraseña</label>
                <input
                    id="clave"
                    type="password"
                    name="clave"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button class="button button-full" type="submit">
                Entrar
            </button>
        </form>

        <p class="form-footer">
            ¿No tienes cuenta?
            <a href="registro.php">Crear cuenta</a>
        </p>
    </section>
</main>

<footer class="site-footer">AgendaWeb</footer>
</body>
</html>
