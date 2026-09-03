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
        $model->actualizarFicha($matriculaId, $_POST);
        $carreraId = (int) ($_POST['carrera_id'] ?? 0);
        header('Location: /cepre_untels/public/fichas.php?carrera_id=' . $carreraId . '&matricula_id=' . $matriculaId . '&actualizado=1');
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar ficha | CEPRE UNTELS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1;100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cepre_untels/public/css/app.css?v=20260904">
</head>
<body>
    <?php require __DIR__ . '/../app/views/partials/site-header.php'; ?>
    <main class="page-shell edit-page">
        <div class="page-heading"><p class="eyebrow">Actualización administrativa</p><h1>EDITAR FICHA N.° <?= editValue($ficha, 'numero') ?></h1></div>
        <?php if ($errorMessage !== null): ?><div class="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="matricula_id" value="<?= $matriculaId ?>">
            <section class="section-block"><h2>Datos personales</h2><div class="form-grid form-grid--three">
                <div class="field"><label for="paterno">Apellido paterno</label><input id="paterno" name="apellido_paterno" value="<?= editValue($ficha, 'apellido_paterno') ?>" required></div>
                <div class="field"><label for="materno">Apellido materno</label><input id="materno" name="apellido_materno" value="<?= editValue($ficha, 'apellido_materno') ?>" required></div>
                <div class="field"><label for="nombres">Nombres</label><input id="nombres" name="nombres" value="<?= editValue($ficha, 'nombres') ?>" required></div>
                <div class="field"><label for="tipo">Tipo de documento</label><select id="tipo" name="tipo_documento"><option <?= selectedValue($ficha, 'tipo_documento', 'DNI') ?>>DNI</option><option <?= selectedValue($ficha, 'tipo_documento', 'CE') ?>>CE</option><option <?= selectedValue($ficha, 'tipo_documento', 'PASAPORTE') ?>>PASAPORTE</option></select></div>
                <div class="field"><label for="documento">Número de documento</label><input id="documento" name="numero_documento" value="<?= editValue($ficha, 'numero_documento') ?>" required></div>
                <div class="field"><label for="sexo">Sexo</label><select id="sexo" name="sexo"><option <?= selectedValue($ficha, 'sexo', 'MASCULINO') ?>>MASCULINO</option><option <?= selectedValue($ficha, 'sexo', 'FEMENINO') ?>>FEMENINO</option></select></div>
                <div class="field"><label for="fecha">Fecha de nacimiento</label><input id="fecha" type="date" name="fecha_nacimiento" value="<?= editValue($ficha, 'fecha_nacimiento') ?>" required></div>
                <div class="field"><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" value="<?= editValue($ficha, 'email') ?>" required></div>
                <div class="field"><label for="celular">Teléfono celular</label><input id="celular" name="telefono_celular" value="<?= editValue($ficha, 'telefono_celular') ?>" required></div>
                <div class="field"><label for="casa">Teléfono de casa</label><input id="casa" name="telefono_casa" value="<?= editValue($ficha, 'telefono_casa') ?>"></div>
            </div></section>
            <section class="section-block"><h2>Matrícula y domicilio</h2><div class="form-grid form-grid--three">
                <div class="field"><label for="carrera">Carrera</label><select id="carrera" name="carrera_id" required><?php foreach ($catalogos['carreras'] as $item): ?><option value="<?= (int) $item['id'] ?>" <?= selectedValue($ficha, 'carrera_id', $item['id']) ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="modalidad">Modalidad</label><select id="modalidad" name="modalidad_clase_id" required><?php foreach ($catalogos['modalidades'] as $item): ?><option value="<?= (int) $item['id'] ?>" <?= selectedValue($ficha, 'modalidad_clase_id', $item['id']) ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="condicion">Condición</label><select id="condicion" name="condicion_id" required><?php foreach ($catalogos['condiciones'] as $item): ?><option value="<?= (int) $item['id'] ?>" <?= selectedValue($ficha, 'condicion_id', $item['id']) ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="turno">Turno</label><select id="turno" name="turno_id" required><?php foreach ($catalogos['turnos'] as $item): ?><option value="<?= (int) $item['id'] ?>" <?= selectedValue($ficha, 'turno_id', $item['id']) ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="departamento">Departamento actual</label><input id="departamento" name="departamento_actual" value="<?= editValue($ficha, 'departamento_actual') ?>"></div>
                <div class="field"><label for="provincia">Provincia actual</label><input id="provincia" name="provincia_actual" value="<?= editValue($ficha, 'provincia_actual') ?>"></div>
                <div class="field"><label for="distrito">Distrito actual</label><input id="distrito" name="distrito_actual" value="<?= editValue($ficha, 'distrito_actual') ?>"></div>
                <div class="field field--span-2"><label for="direccion">Dirección actual</label><input id="direccion" name="direccion_actual" value="<?= editValue($ficha, 'direccion_actual') ?>"></div>
            </div></section>
            <section class="section-block"><h2>Origen y formación</h2><div class="form-grid form-grid--three">
                <div class="field"><label for="pais-nacimiento">País de nacimiento</label><input id="pais-nacimiento" name="pais_nacimiento" value="<?= editValue($ficha, 'pais_nacimiento') ?>"></div>
                <div class="field"><label for="departamento-nacimiento">Departamento / estado</label><input id="departamento-nacimiento" name="departamento_nacimiento" value="<?= editValue($ficha, 'departamento_nacimiento') ?>"></div>
                <div class="field"><label for="provincia-nacimiento">Provincia</label><input id="provincia-nacimiento" name="provincia_nacimiento" value="<?= editValue($ficha, 'provincia_nacimiento') ?>"></div>
                <div class="field"><label for="distrito-nacimiento">Distrito / ciudad</label><input id="distrito-nacimiento" name="distrito_nacimiento" value="<?= editValue($ficha, 'distrito_nacimiento') ?>"></div>
                <div class="field"><label for="anio">Año de secundaria</label><input id="anio" type="number" name="anio_conclusion_secundaria" value="<?= editValue($ficha, 'anio_conclusion_secundaria') ?>"></div>
                <div class="field"><label for="pais-estudios">País de estudios</label><input id="pais-estudios" name="pais_estudios" value="<?= editValue($ficha, 'pais_estudios') ?>"></div>
                <div class="field"><label for="sector">Sector</label><select id="sector" name="sector_id"><option value="">No registrado</option><?php foreach ($catalogos['sectores'] as $item): ?><option value="<?= (int) $item['id'] ?>" <?= selectedValue($ficha, 'sector_id', $item['id']) ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="especificar">Especificar sector</label><input id="especificar" name="especificar_sector" value="<?= editValue($ficha, 'especificar_sector') ?>"></div>
                <div class="field"><label for="institucion">Institución educativa</label><input id="institucion" name="nombre_institucion" value="<?= editValue($ficha, 'nombre_institucion') ?>"></div>
                <div class="field"><label for="preparacion">Preparación previa</label><select id="preparacion" name="preparacion_id"><option value="">No registrado</option><?php foreach ($catalogos['preparaciones'] as $item): ?><option value="<?= (int) $item['id'] ?>" <?= selectedValue($ficha, 'preparacion_previa_id', $item['id']) ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="mencion">Mención</label><input id="mencion" name="mencion" value="<?= editValue($ficha, 'mencion_academica') ?>"></div>
                <div class="field"><label for="extranjera">Institución extranjera</label><input id="extranjera" name="nombre_institucion_extranjera" value="<?= editValue($ficha, 'nombre_institucion_extranjera') ?>"></div>
                <div class="field"><label for="discapacidad">¿Tiene alguna discapacidad?</label><select id="discapacidad" name="tiene_discapacidad"><option value="0" <?= selectedValue($ficha, 'tiene_discapacidad', 0) ?>>No</option><option value="1" <?= selectedValue($ficha, 'tiene_discapacidad', 1) ?>>Sí</option></select></div>
                <div class="field"><label for="tipo-discapacidad">Tipo de discapacidad</label><input id="tipo-discapacidad" name="tipo_discapacidad" value="<?= editValue($ficha, 'tipo_discapacidad') ?>"></div>
                <div class="field"><label for="otro-tipo-discapacidad">Especificar discapacidad</label><input id="otro-tipo-discapacidad" name="otro_tipo_discapacidad" value="<?= editValue($ficha, 'otro_tipo_discapacidad') ?>"></div>
                <div class="field"><label for="grado-discapacidad">Grado de discapacidad</label><input id="grado-discapacidad" name="grado_discapacidad" value="<?= editValue($ficha, 'grado_discapacidad') ?>"></div>
                <div class="field field--span-2"><label for="necesidades-especiales">Necesidades especiales o adecuaciones</label><textarea id="necesidades-especiales" name="necesidades_especiales" rows="3"><?= editValue($ficha, 'necesidades_especiales') ?></textarea></div>
                <div class="field"><label for="certificado-discapacidad">¿Posee certificado?</label><select id="certificado-discapacidad" name="tiene_certificado_discapacidad"><option value="0" <?= selectedValue($ficha, 'tiene_certificado_discapacidad', 0) ?>>No</option><option value="1" <?= selectedValue($ficha, 'tiene_certificado_discapacidad', 1) ?>>Sí</option></select></div>
                <div class="field field--span-2"><label for="como-se-entero-cepre">¿Cómo se enteró de la CEPRE UNTELS?</label><input id="como-se-entero-cepre" name="como_se_entero_cepre" value="<?= editValue($ficha, 'como_se_entero_cepre') ?>" maxlength="150"></div>
            </div></section>
            <div class="form-footer"><span>La foto actual se conserva.</span><div class="form-footer__actions"><a class="button button--clear" href="/cepre_untels/public/fichas.php?carrera_id=<?= (int) $ficha['carrera_id'] ?>&matricula_id=<?= $matriculaId ?>">Cancelar</a><button class="button button--submit" type="submit">Guardar cambios</button></div></div>
        </form>
    </main>
</body>
</html>
