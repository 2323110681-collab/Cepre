<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../app/models/MatriculaModel.php';

requireAuthentication();

$model = new MatriculaModel();
$carreras = $model->catalogos()['carreras'];
$carreraId = filter_var($_GET['carrera_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$matriculaId = filter_var($_GET['matricula_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$carreraSeleccionada = $carreraId !== false && $carreraId !== null;
$estudiantes = $carreraSeleccionada ? $model->listarEstudiantes($carreraId) : [];
$ficha = $matriculaId === false || $matriculaId === null ? null : $model->fichaEstudiante($matriculaId);
$actualizado = ($_GET['actualizado'] ?? '') === '1';

function fichaValue(array $ficha, string $key): string
{
    $value = $ficha[$key] ?? null;
    return $value === null || $value === '' ? 'No registrado' : htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichas de estudiantes | CEPRE UNTELS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/cepre_untels/public/css/app.css?v=20260904">
</head>
<body>
    <?php require __DIR__ . '/../app/views/partials/site-header.php'; ?>

    <main class="page-shell">
        <div class="page-heading">
            <p class="eyebrow">Consulta administrativa</p>
            <h1>FICHAS DE ESTUDIANTES</h1>
        </div>

        <section class="panel ficha-toolbar" aria-label="Filtros de estudiantes">
            <form method="get" class="filter-form">
                <div class="field">
                    <label for="carrera-filtro">Filtrar por carrera</label>
                    <div class="filter-control-row">
                        <select id="carrera-filtro" name="carrera_id" onchange="this.form.submit()">
                            <option value="">Todas las carreras</option>
                            <?php foreach ($carreras as $carrera): ?>
                                <option value="<?= (int) $carrera['id'] ?>" <?= $carreraId === (int) $carrera['id'] ? 'selected' : '' ?>><?= htmlspecialchars($carrera['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a class="button button--clear filter-clear" href="/cepre_untels/public/fichas.php">Limpiar</a>
                    </div>
                </div>
            </form>
            <?php if ($carreraSeleccionada): ?>
                <span class="result-count"><?= count($estudiantes) ?> estudiante<?= count($estudiantes) === 1 ? '' : 's' ?></span>
            <?php endif; ?>
        </section>

        <?php if (!$carreraSeleccionada): ?>
            <p class="selection-hint selection-hint--initial">Selecciona un estudiante para ver su ficha completa.</p>
        <?php else: ?>
        <section class="ficha-layout">
            <div class="panel student-list">
                <div class="student-search">
                    <label for="buscar-estudiante">Buscar estudiante</label>
                    <input id="buscar-estudiante" type="search" placeholder="Nombre, apellido o código" autocomplete="off">
                </div>
                <h2 class="panel-title">Estudiantes registrados</h2>
                <?php if (!$estudiantes): ?>
                    <p class="empty-state">No hay estudiantes registrados para este filtro.</p>
                <?php else: ?>
                    <div class="student-list__items">
                        <?php foreach ($estudiantes as $estudiante): ?>
                            <a class="student-row <?= $ficha !== null && (int) $ficha['matricula_id'] === (int) $estudiante['matricula_id'] ? 'student-row--active' : '' ?>" href="?carrera_id=<?= (int) ($carreraId ?? 0) ?>&matricula_id=<?= (int) $estudiante['matricula_id'] ?>">
                                <span class="student-row__number"><?= htmlspecialchars($estudiante['numero'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="student-row__name"><?= htmlspecialchars(trim($estudiante['apellido_paterno'] . ' ' . $estudiante['apellido_materno'] . ', ' . $estudiante['nombres']), ENT_QUOTES, 'UTF-8') ?></span>
                                <small><?= htmlspecialchars((string) ($estudiante['codigo_estudiante'] ?? 'Sin código'), ENT_QUOTES, 'UTF-8') ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($ficha !== null): ?>
                <article class="panel student-sheet">
                    <div class="sheet-header">
                        <div>
                            <p class="eyebrow">Ficha de matrícula N.° <?= fichaValue($ficha, 'numero') ?></p>
                            <h2><?= fichaValue($ficha, 'nombres') ?> <?= fichaValue($ficha, 'apellido_paterno') ?> <?= fichaValue($ficha, 'apellido_materno') ?></h2>
                            <p class="sheet-subtitle"><?= fichaValue($ficha, 'nombre_carrera') ?></p>
                        </div>
                        <div class="sheet-header__actions">
                            <img class="student-photo" src="/cepre_untels/public/archivo.php?matricula_id=<?= (int) $ficha['matricula_id'] ?>" alt="Foto carnet de <?= fichaValue($ficha, 'nombres') ?>">
                            <a class="button button--dark" href="/cepre_untels/public/editar.php?matricula_id=<?= (int) $ficha['matricula_id'] ?>">Editar ficha</a>
                        </div>
                    </div>

                    <div class="sheet-section">
                        <h3>Identificación y contacto</h3>
                        <dl class="detail-grid">
                            <div><dt>Código de estudiante</dt><dd><?= fichaValue($ficha, 'codigo_estudiante') ?></dd></div>
                            <div><dt>Tipo de documento</dt><dd><?= fichaValue($ficha, 'tipo_documento') ?></dd></div>
                            <div><dt>Número de documento</dt><dd><?= fichaValue($ficha, 'numero_documento') ?></dd></div>
                            <div><dt>Sexo</dt><dd><?= fichaValue($ficha, 'sexo') ?></dd></div>
                            <div><dt>Fecha de nacimiento</dt><dd><?= fichaValue($ficha, 'fecha_nacimiento') ?></dd></div>
                            <div><dt>Correo electrónico</dt><dd><?= fichaValue($ficha, 'email') ?></dd></div>
                            <div><dt>Teléfono de casa</dt><dd><?= fichaValue($ficha, 'telefono_casa') ?></dd></div>
                            <div><dt>Teléfono celular</dt><dd><?= fichaValue($ficha, 'telefono_celular') ?></dd></div>
                        </dl>
                    </div>

                    <div class="sheet-section">
                        <h3>Matrícula y domicilio</h3>
                        <dl class="detail-grid">
                            <div><dt>Carrera</dt><dd><?= fichaValue($ficha, 'nombre_carrera') ?></dd></div>
                            <div><dt>Modalidad</dt><dd><?= fichaValue($ficha, 'modalidad_nombre') ?></dd></div>
                            <div><dt>Condición</dt><dd><?= fichaValue($ficha, 'condicion_nombre') ?></dd></div>
                            <div><dt>Turno</dt><dd><?= fichaValue($ficha, 'turno_nombre') ?></dd></div>
                            <div><dt>Estado</dt><dd><?= fichaValue($ficha, 'estado') ?></dd></div>
                            <div><dt>Fecha de registro</dt><dd><?= fichaValue($ficha, 'fecha_registro') ?></dd></div>
                            <div><dt>Departamento actual</dt><dd><?= fichaValue($ficha, 'departamento_actual') ?></dd></div>
                            <div><dt>Provincia actual</dt><dd><?= fichaValue($ficha, 'provincia_actual') ?></dd></div>
                            <div><dt>Distrito actual</dt><dd><?= fichaValue($ficha, 'distrito_actual') ?></dd></div>
                            <div class="detail-grid__wide"><dt>Dirección actual</dt><dd><?= fichaValue($ficha, 'direccion_actual') ?></dd></div>
                        </dl>
                    </div>

                    <div class="sheet-section">
                        <h3>Lugar de nacimiento y formación</h3>
                        <dl class="detail-grid">
                            <div><dt>País de nacimiento</dt><dd><?= fichaValue($ficha, 'pais_nacimiento') ?></dd></div>
                            <div><dt>Departamento / estado</dt><dd><?= fichaValue($ficha, 'departamento_nacimiento') ?></dd></div>
                            <div><dt>Provincia</dt><dd><?= fichaValue($ficha, 'provincia_nacimiento') ?></dd></div>
                            <div><dt>Distrito / ciudad</dt><dd><?= fichaValue($ficha, 'distrito_nacimiento') ?></dd></div>
                            <div><dt>Año de secundaria</dt><dd><?= fichaValue($ficha, 'anio_conclusion_secundaria') ?></dd></div>
                            <div><dt>País de estudios</dt><dd><?= fichaValue($ficha, 'pais_estudios') ?></dd></div>
                            <div><dt>Sector</dt><dd><?= fichaValue($ficha, 'sector_nombre') ?></dd></div>
                            <div><dt>Institución educativa</dt><dd><?= fichaValue($ficha, 'nombre_institucion') !== 'No registrado' ? fichaValue($ficha, 'nombre_institucion') : fichaValue($ficha, 'institucion_educativa') ?></dd></div>
                            <div><dt>Preparación previa</dt><dd><?= fichaValue($ficha, 'preparacion_nombre') ?></dd></div>
                            <div><dt>Mención</dt><dd><?= fichaValue($ficha, 'mencion_academica') !== 'No registrado' ? fichaValue($ficha, 'mencion_academica') : fichaValue($ficha, 'mencion') ?></dd></div>
                            <div><dt>Especificar sector</dt><dd><?= fichaValue($ficha, 'especificar_sector') ?></dd></div>
                            <div><dt>Institución extranjera</dt><dd><?= fichaValue($ficha, 'nombre_institucion_extranjera') ?></dd></div>
                            <div><dt>Discapacidad</dt><dd><?= (int) ($ficha['tiene_discapacidad'] ?? 0) === 1 ? 'Sí' : 'No' ?></dd></div>
                            <div><dt>Tipo de discapacidad</dt><dd><?= fichaValue($ficha, 'tipo_discapacidad') !== 'No registrado' ? fichaValue($ficha, 'tipo_discapacidad') : fichaValue($ficha, 'otro_tipo_discapacidad') ?></dd></div>
                            <div><dt>Grado de discapacidad</dt><dd><?= fichaValue($ficha, 'grado_discapacidad') ?></dd></div>
                            <div><dt>Certificado de discapacidad</dt><dd><?= (int) ($ficha['tiene_certificado_discapacidad'] ?? 0) === 1 ? 'Sí' : 'No' ?></dd></div>
                            <div class="detail-grid__wide"><dt>Necesidades especiales o adecuaciones</dt><dd><?= fichaValue($ficha, 'necesidades_especiales') ?></dd></div>
                            <div><dt>¿Cómo se enteró de la CEPRE UNTELS?</dt><dd><?= fichaValue($ficha, 'como_se_entero_cepre') ?></dd></div>
                        </dl>
                    </div>
                </article>
            <?php else: ?>
                <p class="selection-hint">Selecciona un estudiante para ver su ficha completa.</p>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </main>
    <script>
        const searchInput = document.getElementById('buscar-estudiante');
        searchInput?.addEventListener('input', () => {
            const search = searchInput.value.trim().toLocaleLowerCase();
            document.querySelectorAll('.student-row').forEach((row) => {
                row.hidden = search !== '' && !row.textContent.toLocaleLowerCase().includes(search);
            });
        });
        <?php if ($actualizado): ?>
        Swal.fire({
            title: 'Cambios guardados',
            text: 'La ficha del estudiante se actualizó correctamente.',
            icon: 'success',
            confirmButtonColor: '#23313b'
        });
        <?php endif; ?>
    </script>
</body>
</html>
