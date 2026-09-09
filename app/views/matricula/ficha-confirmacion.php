<?php

require_once __DIR__ . '/../../../config/auth.php';

$ficha = $ficha ?? null;
$numeroRegistrado = $numeroRegistrado ?? '';
$fotoDataUri = null;
if ($ficha !== null && !empty($ficha['foto_ruta']) && is_file($ficha['foto_ruta'])) {
    $fotoMime = (string) ($ficha['foto_mime'] ?? '');
    if (in_array($fotoMime, ['image/jpeg', 'image/png'], true)) {
        $fotoDataUri = 'data:' . $fotoMime . ';base64,' . base64_encode((string) file_get_contents($ficha['foto_ruta']));
    }
}

function confirmationValue(array $ficha, string $key): string
{
    $value = $ficha[$key] ?? null;
    return $value === null || $value === ''
        ? 'No registrado'
        : htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha registrada | CEPRE UNTELS</title>
    <link rel="icon" type="image/png" href="/cepre_untels/public/img/cepre.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alatsi&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cepre_untels/public/css/app.css?v=20260919">
    <style>
        .confirmation-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; margin: 28px 0; }
        .confirmation-note { text-align: center; color: #53616b; margin: 0 auto 24px; max-width: 680px; }
        .confirmation-card { max-width: 920px; margin: 0 auto; }
        .confirmation-card .sheet-section { margin-top: 0; }
        .confirmation-page-shell { margin-top: 14px; }
        @media (max-width: 900px) {
            .confirmation-page-shell { margin-top: 8px; }
        }
        @media print {
            @page { size: A4 portrait; margin: 8mm; }
            .public-site-header, .site-footer, .confirmation-actions, .confirmation-note { display: none !important; }
            .page-shell { padding: 0 !important; margin: 0 !important; }
            .confirmation-card { width: 100%; max-width: none; box-shadow: none; border: 1px solid #d9dfe2; }
            .confirmation-photo { margin: 0 0 6px; }
            .confirmation-photo img { width: 80px; height: 100px; }
            .confirmation-card .sheet-section { padding: 14px 16px; }
            .confirmation-card .sheet-section h2 { margin: 0 0 9px; padding-bottom: 5px; font-size: 13px; }
            .confirmation-card .detail-grid { gap: 8px 16px; }
            .confirmation-card dt { margin-bottom: 2px; font-size: 9px; }
            .confirmation-card dd { font-size: 10px; line-height: 1.25; }
        }
    </style>
</head>
<body>
    <?php $pagina = ''; require __DIR__ . '/../partials/public-header.php'; ?>
    <main class="page-shell confirmation-page-shell">
        <div class="page-heading">
            <p class="eyebrow">Registro completado</p>
            <h1>FICHA DE MATRÍCULA REGISTRADA</h1>
            <p class="enrollment-period">Código CEPRE: <strong><?= htmlspecialchars($numeroRegistrado, ENT_QUOTES, 'UTF-8') ?></strong></p>
        </div>

        <p class="confirmation-note">Tu matrícula fue guardada correctamente. Descarga o imprime esta ficha para conservar tu constancia.</p>
        <div class="confirmation-actions">
            <button class="button button--gold" type="button" onclick="window.print()">Descargar ficha</button>
        </div>

        <?php if ($ficha !== null): ?>
            <article class="panel confirmation-card">
                <?php if ($fotoDataUri !== null): ?>
                    <div class="confirmation-photo">
                        <img src="<?= $fotoDataUri ?>" alt="Foto del estudiante">
                    </div>
                <?php endif; ?>
                <div class="sheet-section">
                    <h2>Datos del estudiante</h2>
                    <dl class="detail-grid">
                        <div><dt>Número de matrícula</dt><dd><?= confirmationValue($ficha, 'numero') ?></dd></div>
                        <div><dt>Código de estudiante</dt><dd><?= confirmationValue($ficha, 'codigo_estudiante') ?></dd></div>
                        <div><dt>Estudiante</dt><dd><?= confirmationValue($ficha, 'nombres') ?> <?= confirmationValue($ficha, 'apellido_paterno') ?> <?= confirmationValue($ficha, 'apellido_materno') ?></dd></div>
                        <div><dt>Documento</dt><dd><?= confirmationValue($ficha, 'tipo_documento') ?> <?= confirmationValue($ficha, 'numero_documento') ?></dd></div>
                        <div><dt>Correo electrónico</dt><dd><?= confirmationValue($ficha, 'email') ?></dd></div>
                        <div><dt>Teléfono celular</dt><dd><?= confirmationValue($ficha, 'telefono_celular') ?></dd></div>
                    </dl>
                </div>
                <div class="sheet-section">
                    <h2>Datos de matrícula</h2>
                    <dl class="detail-grid">
                        <div><dt>Carrera</dt><dd><?= confirmationValue($ficha, 'nombre_carrera') ?></dd></div>
                        <div><dt>Modalidad de clase</dt><dd><?= confirmationValue($ficha, 'modalidad_nombre') ?></dd></div>
                        <div><dt>Modalidad de ingreso</dt><dd><?= confirmationValue($ficha, 'condicion_nombre') ?></dd></div>
                        <div><dt>Turno</dt><dd><?= confirmationValue($ficha, 'turno_nombre') ?></dd></div>
                        <div><dt>Medio de pago</dt><dd><?= confirmationValue($ficha, 'medio_pago') ?></dd></div>
                        <div><dt>Código de voucher</dt><dd><?= confirmationValue($ficha, 'codigo_voucher') ?></dd></div>
                        <div><dt>Estado</dt><dd><?= confirmationValue($ficha, 'estado') ?></dd></div>
                        <div><dt>Fecha de registro</dt><dd><?= confirmationValue($ficha, 'fecha_registro') ?></dd></div>
                    </dl>
                </div>
                <div class="sheet-section">
                    <h2>Domicilio y formación</h2>
                    <dl class="detail-grid">
                        <div><dt>País de nacimiento</dt><dd><?= confirmationValue($ficha, 'pais_nacimiento') ?></dd></div>
                        <div><dt>Departamento de nacimiento</dt><dd><?= confirmationValue($ficha, 'departamento_nacimiento') ?></dd></div>
                        <div><dt>Distrito actual</dt><dd><?= confirmationValue($ficha, 'distrito_actual_nombre') ?></dd></div>
                        <div><dt>Dirección actual</dt><dd><?= confirmationValue($ficha, 'direccion_actual') ?></dd></div>
                        <div><dt>Año de secundaria</dt><dd><?= confirmationValue($ficha, 'anio_conclusion_secundaria') ?></dd></div>
                        <div><dt>Institución educativa</dt><dd><?= confirmationValue($ficha, 'nombre_institucion') ?></dd></div>
                    </dl>
                </div>
            </article>
        <?php else: ?>
            <div class="alert">La matrícula fue registrada, pero no se pudo cargar la ficha. Conserva tu código CEPRE: <?= htmlspecialchars($numeroRegistrado, ENT_QUOTES, 'UTF-8') ?>.</div>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../partials/site-footer.php'; ?>
</body>
</html>
