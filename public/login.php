<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

startSession();
if (isAuthenticated()) {
    header('Location: /cepre_untels/public/');
    exit;
}

$errorMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    try {
        $statement = database()->prepare(
            'SELECT id_usuario, nombre_usuario, email, nombre_completo, rol, contrasena_hash
             FROM usuarios
               WHERE estado = "ACTIVO" AND (nombre_usuario = :username OR email = :email)
             LIMIT 1'
        );
           $statement->execute(['username' => $login, 'email' => $login]);
        $user = $statement->fetch();

        if ($user && password_verify($password, $user['contrasena_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id_usuario'];
            $_SESSION['user'] = [
                'id' => (int) $user['id_usuario'],
                'nombre' => $user['nombre_completo'],
                'rol' => $user['rol'],
            ];
            database()->prepare('UPDATE usuarios SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id_usuario = :id')
                ->execute(['id' => $user['id_usuario']]);
            header('Location: /cepre_untels/public/');
            exit;
        }

        $errorMessage = 'Usuario o contraseña incorrectos.';
    } catch (Throwable $exception) {
        $errorMessage = 'No se pudo conectar con el sistema.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | CEPRE UNTELS</title>
    <link rel="icon" type="image/png" href="/cepre_untels/public/img/cepre.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cepre_untels/public/css/app.css">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="login-brand"><span class="brand__mark">C</span><span><strong>CEPRE</strong><b>UNTELS</b></span></div>
        <p class="eyebrow">Sistema de matrícula</p>
        <h1>Iniciar sesión</h1>
        <p class="login-copy">Ingresa tus credenciales para continuar.</p>
        <?php if ($errorMessage !== null): ?>
            <div class="alert" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" class="login-form">
            <div class="field"><label for="login">Usuario o correo</label><input id="login" name="login" type="text" autocomplete="username" required autofocus></div>
            <div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
            <button class="button button--submit" type="submit">Ingresar</button>
        </form>
    </main>
</body>
</html>
