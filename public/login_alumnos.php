<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

startSession();
if (isAuthenticated() && isStudent()) {
    header('Location: /cepre_untels/public/');
    exit();
}

$errorMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $dni = trim((string) ($_POST['dni'] ?? ''));
    $banco = trim((string) ($_POST['banco'] ?? ''));
    $operacion = trim((string) ($_POST['operacion'] ?? ''));

    // TODO: La verificación del DNI y el código de baucher de pago
    // se debe realizar aquí mediante la API correspondiente.
    // Actualmente acepta cualquier texto.

    if (!empty($dni) && !empty($operacion)) {
        // Simulando login exitoso del alumno
        session_regenerate_id(true);
        $_SESSION['user_id'] = 'alumno_' . $dni;
        $_SESSION['user'] = [
            'id' => 'alumno_' . $dni,
            'nombre' => 'Alumno ' . $dni,
            'rol' => 'ALUMNO',
            'dni' => $dni,
        ];
        header('Location: /cepre_untels/public/');
        exit();
    } else {
        $errorMessage = 'Debe ingresar su número de documento y el número de operación.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrícula Online | CEPRE UNTELS</title>
    <link rel="icon" type="image/png" href="/cepre_untels/public/img/cepre.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Alatsi&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cepre_untels/public/css/app.css?v=20260906-student-login">
</head>
<body class="student-login-page">
    <header class="student-header">
        <a class="brand" href="/cepre_untels/public/login_alumnos.php" aria-label="CEPRE UNTELS inicio">
            <img class="brand__logo" src="/cepre_untels/public/img/cepre.png" alt="CEPRE UNTELS">
        </a>
        <nav class="student-nav">
            <a href="#">INICIO</a>
            <a href="#">SOBRE NOSOTROS</a>
            <a href="#">DOCENTES</a>
            <a href="#">PÁGINAS</a>
        </nav>
    </header>

    <main class="student-login-container">
        <div class="student-login-left">
            <h2>Matricula Online</h2>
            <?php if ($errorMessage !== null): ?>
                <div class="alert" role="alert" style="margin-bottom: 1rem;"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="post" class="student-login-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-row-buscar">
                    <input id="dni" name="dni" type="text" placeholder="Ingresa tu número de documento" required>
                    <button type="button" class="btn-buscar">Buscar</button>
                </div>

                <div class="field">
                    <select id="banco" name="banco" required>
                        <option value="" disabled selected hidden>Seleccionar Opción</option>
                        <option value="nacion">Banco de la Nación</option>
                        <option value="caja">Caja de la Untels</option>
                    </select>
                </div>

                <div class="field">
                    <input id="operacion" name="operacion" type="text" placeholder="Ingresa tu número de operación o boleta" required>
                </div>

                <button class="button button--submit btn-enviar" type="submit">Enviar</button>
            </form>
        </div>

        <div class="student-login-right">
            <h2>Pasos de la Matrícula</h2>
            <ul class="steps-list">
                <li>Ingresar el Número de documento y hacer clik en Buscar</li>
                <li>Si es el caso Pagar en el Banco de la Nación o en Caja de la Untels</li>
                <li>Elegir entre Banco o Caja</li>
                <li>Ingresar el Número de operación que figura en el voucher o boleta de pago</li>
            </ul>
            <div class="voucher-image-container">
                <img src="/cepre_untels/public/img/voucher_ejemplo.jpg" alt="Ejemplo de voucher">
            </div>
        </div>
    </main>
    <?php require __DIR__ . '/../app/views/partials/site-footer.php'; ?>
</body>
</html>
