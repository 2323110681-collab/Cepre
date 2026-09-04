<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../app/models/MatriculaModel.php';

requireAuthentication();
$model = new MatriculaModel();
$matriculaId = filter_var($_GET['matricula_id'] ?? $_POST['matricula_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($matriculaId === false || $matriculaId === null) {
    http_response_code(404);
    exit('Ficha no encontrada.');
}

$errorMessage = null;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken($_POST['csrf_token'] ?? null);
        $model->actualizarFicha($matriculaId, $_POST, $_FILES);
        header('Location: /cepre_untels/public/editar.php?matricula_id=' . $matriculaId . '&actualizado=1');
        exit;
    }
    $ficha = $model->fichaEstudiante($matriculaId);
    if ($ficha === null) {
        http_response_code(404);
        exit('Ficha no encontrada.');
    }
    $catalogos = $model->catalogos();
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
    $ficha ??= $model->fichaEstudiante($matriculaId);
    $catalogos ??= $model->catalogos();
}

function editValue(array $ficha, string $key): string
{
    return htmlspecialchars((string) ($ficha[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}

function selectedValue(array $ficha, string $key, mixed $value): string
{
    return (string) ($ficha[$key] ?? '') === (string) $value ? 'selected' : '';
}

$discoveryOptions = [
    'redes_sociales' => 'Redes sociales (Facebook, Instagram, etc.)',
    'sitio_web' => 'Sitio web oficial',
    'amigo_familiar' => 'Recomendación de un amigo o familiar',
    'colegio' => 'Difusión en mi colegio',
    'evento_untels' => 'Evento o feria informativa de la UNTELS',
    'publicidad_exterior' => 'Publicidad exterior (vía pública, etc.)',
    'whatsapp' => 'WhatsApp de la CEPRE UNTELS',
];
$discoveryStored = trim((string) ($ficha['como_se_entero_cepre'] ?? ''));
$discoveryIsOther = $discoveryStored !== '' && !array_key_exists($discoveryStored, $discoveryOptions);
$discoverySelected = $discoveryIsOther ? 'otro' : $discoveryStored;
$cambiosGuardados = ($_GET['actualizado'] ?? '') === '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar ficha | CEPRE UNTELS</title>
    <link rel="icon" type="image/png" href="/cepre_untels/public/img/cepre.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1;100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/cepre_untels/public/css/app.css?v=20260908">
</head>
<body>
    <?php require __DIR__ . '/../app/views/partials/site-header.php'; ?>
    <main class="page-shell edit-page">
        <div class="page-heading"><p class="eyebrow">Actualización administrativa</p><h1>EDITAR FICHA N.° <?= editValue($ficha, 'numero') ?></h1></div>
        <?php if ($errorMessage !== null): ?><div class="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <form method="post" class="edit-form enrollment-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="matricula_id" value="<?= $matriculaId ?>">
            <input type="hidden" name="carrera_id" value="<?= (int) $ficha['carrera_id'] ?>">
            <div class="field edit-photo-field">
                <label for="foto">Cambiar foto carnet</label>
                <div class="edit-photo-preview">
                    <img id="edit-photo-preview" src="/cepre_untels/public/archivo.php?matricula_id=<?= $matriculaId ?>" alt="Foto actual del estudiante" onerror="this.hidden=true">
                    <span>Sin foto</span>
                </div>
                <input id="foto" name="foto" type="file" accept="image/jpeg,image/png">
                <small class="field-status">Opcional. JPG o PNG, máximo 5 MB.</small>
            </div>
            <section class="section-block"><h2>Datos personales</h2><div class="form-grid form-grid--three">
                <div class="field"><label for="paterno">Apellido paterno</label><input id="paterno" value="<?= editValue($ficha, 'apellido_paterno') ?>" readonly></div>
                <div class="field"><label for="materno">Apellido materno</label><input id="materno" value="<?= editValue($ficha, 'apellido_materno') ?>" readonly></div>
                <div class="field"><label for="nombres">Nombres</label><input id="nombres" value="<?= editValue($ficha, 'nombres') ?>" readonly></div>
                <div class="field"><label for="tipo">Tipo de documento</label><select id="tipo" disabled><option selected><?= editValue($ficha, 'tipo_documento') ?></option></select></div>
                <div class="field"><label for="documento">Número de documento</label><input id="documento" value="<?= editValue($ficha, 'numero_documento') ?>" readonly></div>
                <div class="field"><label for="sexo">Sexo</label><select id="sexo" disabled><option selected><?= editValue($ficha, 'sexo') ?></option></select></div>
                <div class="field"><label for="fecha">Fecha de nacimiento</label><input id="fecha" type="date" value="<?= editValue($ficha, 'fecha_nacimiento') ?>" readonly></div>
                <div class="field"><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" value="<?= editValue($ficha, 'email') ?>" required></div>
                <div class="field"><label for="celular">Teléfono celular</label><input id="celular" name="telefono_celular" value="<?= editValue($ficha, 'telefono_celular') ?>" required></div>
                <div class="field"><label for="casa">Teléfono de casa</label><input id="casa" name="telefono_casa" value="<?= editValue($ficha, 'telefono_casa') ?>"></div>
            </div></section>
            <section class="section-block"><h2>Matrícula y domicilio</h2><div class="form-grid form-grid--three">
                <div class="field"><label for="carrera">Carrera</label><input id="carrera" value="<?= editValue($ficha, 'nombre_carrera') ?>" readonly></div>
                <div class="field"><label for="modalidad">Modalidad</label><select id="modalidad" name="modalidad_clase_id" required><?php foreach ($catalogos['modalidades'] as $item): ?><option value="<?= (int) $item['id'] ?>" <?= selectedValue($ficha, 'modalidad_clase_id', $item['id']) ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="condicion">Condición</label><select id="condicion" name="condicion_id" required><?php foreach ($catalogos['condiciones'] as $item): ?><option value="<?= (int) $item['id'] ?>" <?= selectedValue($ficha, 'condicion_id', $item['id']) ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="turno">Turno</label><select id="turno" name="turno_id" required><?php foreach ($catalogos['turnos'] as $item): ?><option value="<?= (int) $item['id'] ?>" <?= selectedValue($ficha, 'turno_id', $item['id']) ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="departamento">Departamento actual</label><input id="departamento" name="departamento_actual" value="<?= editValue($ficha, 'departamento_actual') ?>" readonly></div>
                <div class="field"><label for="provincia">Provincia actual</label><input id="provincia" name="provincia_actual" value="<?= editValue($ficha, 'provincia_actual') ?>" readonly></div>
                <div class="field"><label for="distrito">Distrito actual</label><input id="distrito" value="<?= editValue($ficha, 'distrito_actual_nombre') ?>" readonly><input type="hidden" name="distrito_actual" value="<?= editValue($ficha, 'distrito_actual') ?>"></div>
                <div class="field field--span-2"><label for="direccion">Dirección actual</label><input id="direccion" name="direccion_actual" value="<?= editValue($ficha, 'direccion_actual') ?>"></div>
            </div></section>
            <section class="section-block"><h2>Origen y formación</h2><div class="form-grid form-grid--three">
                <div class="field"><label for="pais-nacimiento">País de nacimiento</label><input id="pais-nacimiento" name="pais_nacimiento" value="<?= editValue($ficha, 'pais_nacimiento') ?>" readonly></div>
                <div class="field"><label for="departamento-nacimiento">Departamento / estado</label><input id="departamento-nacimiento" name="departamento_nacimiento" value="<?= editValue($ficha, 'departamento_nacimiento') ?>" readonly></div>
                <div class="field"><label for="provincia-nacimiento">Provincia</label><input id="provincia-nacimiento" name="provincia_nacimiento" value="<?= editValue($ficha, 'provincia_nacimiento') ?>" readonly></div>
                <div class="field">
                    <label for="distrito-nacimiento">Distrito / ciudad</label>
                    <input id="distrito-nacimiento" value="<?= editValue($ficha, 'distrito_nacimiento_nombre') ?>" readonly>
                    <input type="hidden" name="distrito_nacimiento" value="<?= editValue($ficha, 'distrito_nacimiento') ?>">
                </div>
                <div class="field"><label for="anio">Año de secundaria</label><input id="anio" type="number" name="anio_conclusion_secundaria" value="<?= editValue($ficha, 'anio_conclusion_secundaria') ?>"></div>
                <div class="field"><label for="pais-estudios">País de estudios</label><input id="pais-estudios" name="pais_estudios" value="<?= editValue($ficha, 'pais_estudios') ?>"></div>
                <div class="field"><label for="sector">Sector</label><select id="sector" name="sector_id"><option value="">No registrado</option><?php foreach ($catalogos['sectores'] as $item): ?><option value="<?= (int) $item['id'] ?>" <?= selectedValue($ficha, 'sector_id', $item['id']) ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="especificar">Especificar sector</label><input id="especificar" name="especificar_sector" value="<?= editValue($ficha, 'especificar_sector') ?>"></div>
                <div class="field"><label for="institucion">Institución educativa</label><input id="institucion" name="nombre_institucion" value="<?= editValue($ficha, 'nombre_institucion') ?>"></div>
                <div class="field"><label for="preparacion">Preparación previa</label><select id="preparacion" name="preparacion_id"><option value="">No registrado</option><?php foreach ($catalogos['preparaciones'] as $item): ?><option value="<?= (int) $item['id'] ?>" <?= selectedValue($ficha, 'preparacion_previa_id', $item['id']) ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="mencion">Mención</label><input id="mencion" name="mencion" value="<?= editValue($ficha, 'mencion_academica') ?>"></div>
                <div class="field"><label for="extranjera">Institución extranjera</label><input id="extranjera" name="nombre_institucion_extranjera" value="<?= editValue($ficha, 'nombre_institucion_extranjera') ?>"></div>
                <div class="field"><label for="discapacidad">¿Tiene alguna discapacidad?</label><select id="discapacidad" name="tiene_discapacidad"><option value="0" <?= selectedValue($ficha, 'tiene_discapacidad', 0) ?>>No</option><option value="1" <?= selectedValue($ficha, 'tiene_discapacidad', 1) ?>>Sí</option></select></div>
                <div id="seccion-discapacidad" class="field-group field-group--span-3" <?= (int) ($ficha['tiene_discapacidad'] ?? 0) === 1 ? '' : 'hidden' ?>>
                    <div class="field"><label for="tipo-discapacidad">Tipo de discapacidad</label><select id="tipo-discapacidad" name="tipo_discapacidad"><option value="">Seleccione el tipo</option><option value="fisica" <?= selectedValue($ficha, 'tipo_discapacidad', 'fisica') ?>>Física</option><option value="sensorial" <?= selectedValue($ficha, 'tipo_discapacidad', 'sensorial') ?>>Sensorial (visual/auditiva)</option><option value="intelectual" <?= selectedValue($ficha, 'tipo_discapacidad', 'intelectual') ?>>Intelectual</option><option value="psicosocial" <?= selectedValue($ficha, 'tipo_discapacidad', 'psicosocial') ?>>Psicosocial</option><option value="multiple" <?= selectedValue($ficha, 'tipo_discapacidad', 'multiple') ?>>Múltiple</option><option value="otra" <?= selectedValue($ficha, 'tipo_discapacidad', 'otra') ?>>Otra</option></select></div>
                    <div class="field" id="otro-tipo-discapacidad" <?= ($ficha['tipo_discapacidad'] ?? '') === 'otra' ? '' : 'hidden' ?>><label for="otro-tipo-especificar">Especificar tipo de discapacidad</label><input id="otro-tipo-especificar" name="otro_tipo_discapacidad" value="<?= editValue($ficha, 'otro_tipo_discapacidad') ?>" placeholder="Especifique el tipo de discapacidad"></div>
                    <div class="field"><label for="grado-discapacidad">Grado de discapacidad</label><select id="grado-discapacidad" name="grado_discapacidad"><option value="">Seleccione el grado</option><option value="leve" <?= selectedValue($ficha, 'grado_discapacidad', 'leve') ?>>Leve</option><option value="moderado" <?= selectedValue($ficha, 'grado_discapacidad', 'moderado') ?>>Moderado</option><option value="grave" <?= selectedValue($ficha, 'grado_discapacidad', 'grave') ?>>Grave</option><option value="muy-grave" <?= selectedValue($ficha, 'grado_discapacidad', 'muy-grave') ?>>Muy grave</option></select></div>
                    <div class="field field--span-2"><label for="necesidades-especiales">Necesidades especiales o adecuaciones requeridas</label><textarea id="necesidades-especiales" name="necesidades_especiales" rows="3"><?= editValue($ficha, 'necesidades_especiales') ?></textarea></div>
                    <div class="field field--span-2"><label for="certificado-discapacidad">¿Posee certificado de discapacidad?</label><select id="certificado-discapacidad" name="tiene_certificado_discapacidad"><option value="0" <?= selectedValue($ficha, 'tiene_certificado_discapacidad', 0) ?>>No</option><option value="1" <?= selectedValue($ficha, 'tiene_certificado_discapacidad', 1) ?>>Sí</option></select></div>
                    <div class="field field--span-2" id="adjunto-certificado-discapacidad" <?= (int) ($ficha['tiene_discapacidad'] ?? 0) === 1 && (int) ($ficha['tiene_certificado_discapacidad'] ?? 0) === 1 ? '' : 'hidden' ?>><label for="archivo-certificado-discapacidad">Adjuntar certificado de discapacidad</label><input id="archivo-certificado-discapacidad" name="certificado_discapacidad" type="file" accept="image/jpeg,image/png,application/pdf" data-has-existing="<?= empty($ficha['certificado_ruta']) ? 'false' : 'true' ?>" <?= (int) ($ficha['tiene_certificado_discapacidad'] ?? 0) === 1 ? '' : 'disabled' ?> <?= (int) ($ficha['tiene_certificado_discapacidad'] ?? 0) === 1 && empty($ficha['certificado_ruta']) ? 'required' : '' ?>><small class="field-status"><?= empty($ficha['certificado_ruta']) ? 'Debe adjuntar un archivo. ' : 'Seleccione un archivo solo si desea reemplazar el existente. ' ?>Máximo 5 MB.</small></div>
                </div>
                <div class="field field--span-2">
                    <label for="como-se-entero-cepre">¿Cómo se enteró de la CEPRE UNTELS?</label>
                    <select id="como-se-entero-cepre" name="como_se_entero_cepre">
                        <option value="">Seleccione una opción</option>
                        <?php foreach ($discoveryOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $discoverySelected === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                        <option value="otro" <?= $discoverySelected === 'otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                    <div id="otro-como-se-entero" <?= $discoverySelected === 'otro' ? '' : 'hidden' ?>><label for="especificar-como-se-entero" class="sr-only">Especificar cómo se enteró</label><input type="text" id="especificar-como-se-entero" name="especificar_como_se_entero" value="<?= $discoveryIsOther ? editValue($ficha, 'como_se_entero_cepre') : '' ?>" placeholder="Por favor, especifique..." maxlength="150"></div>
                </div>
            </div></section>
            <div class="form-footer">
                <span>La foto actual se conserva si no selecciona una nueva.</span>
                <div class="form-footer__actions"><a class="button button--clear" href="/cepre_untels/public/fichas.php?carrera_id=<?= (int) $ficha['carrera_id'] ?>&matricula_id=<?= $matriculaId ?>">Cancelar</a><button class="button button--submit" type="submit">Guardar cambios</button></div>
            </div>
        </form>
    </main>
    <?php require __DIR__ . '/../app/views/partials/site-footer.php'; ?>
    <script src="/cepre_untels/public/js/app.js?v=20260908"></script>
    <?php if ($cambiosGuardados): ?>
        <script>
            Swal.fire({ title: '¡Cambios realizados correctamente!', icon: 'success', confirmButtonColor: '#23313b' })
                .then(() => {
                    window.location.href = '/cepre_untels/public/fichas.php?carrera_id=<?= (int) $ficha['carrera_id'] ?>&matricula_id=<?= $matriculaId ?>';
                });
        </script>
    <?php endif; ?>
</body>
</html>
