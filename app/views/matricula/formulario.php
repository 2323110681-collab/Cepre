<?php

require_once __DIR__ . '/../../../config/auth.php';

$catalogos = $catalogos ?? [];
$catalogos['carreras'] = $catalogos['carreras'] ?? [];
$catalogos['condiciones'] = $catalogos['condiciones'] ?? [];
$catalogos['turnos'] = $catalogos['turnos'] ?? [];
$catalogos['periodos'] = $catalogos['periodos'] ?? [];
$catalogos['modalidades'] = $catalogos['modalidades'] ?? [];
$catalogos['sectores'] = $catalogos['sectores'] ?? [];
$catalogos['preparaciones'] = $catalogos['preparaciones'] ?? [];
$catalogos['departamentos'] = $catalogos['departamentos'] ?? [];
$numeroMatricula = $numeroMatricula ?? '00001';
$usuarioActual = currentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de matrícula | CEPRE UNTELS</title>
    <link rel="icon" type="image/png" href="/cepre_untels/public/img/cepre.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/cepre_untels/public/css/app.css?v=20260909">
</head>
<body>
    <?php require __DIR__ . '/../partials/site-header.php'; ?>

    <main class="page-shell" id="ficha">
        <div class="page-heading">
            <p class="eyebrow">Registro de estudiante</p>
            <h1 id="titulo-ficha" data-numero-ficha="<?= htmlspecialchars($numeroMatricula, ENT_QUOTES, 'UTF-8') ?>">FICHA DE MATRÍCULA N.° <?= htmlspecialchars($numeroMatricula) ?></h1>
            <p class="enrollment-period">Periodo de matrícula: <strong>2027-1</strong></p>
        </div>

        <?php if (!$databaseReady): ?>
            <div class="alert"><?= htmlspecialchars($errorMessage ?? 'No se pudo procesar la matrícula.', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form class="enrollment-form" method="post" enctype="multipart/form-data" action="#">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <section class="top-grid" aria-label="Datos de matrícula">
                <div class="panel panel--compact">
                    <div class="field">
                        <label for="codigo-cepre">Código CEPRE asignado</label>
                        <input id="codigo-cepre" type="text" value="<?= htmlspecialchars($codigoCepre ?? $numeroMatricula, ENT_QUOTES, 'UTF-8') ?>" data-codigo-regular="<?= htmlspecialchars($codigoCepreRegular ?? $codigoCepre ?? $numeroMatricula, ENT_QUOTES, 'UTF-8') ?>" data-codigo-escolar="<?= htmlspecialchars($codigoCepreEscolar ?? $codigoCepre ?? $numeroMatricula, ENT_QUOTES, 'UTF-8') ?>" readonly>
                    </div>
                    <div class="field">
                        <label for="semestre">Semestre</label>
                        <select id="semestre" name="semestre" required>
                            <?php foreach ($catalogos['periodos'] as $periodo): ?>
                                <option value="<?= htmlspecialchars($periodo['nombre'], ENT_QUOTES, 'UTF-8') ?>" <?= $periodo['nombre'] === '2027-1' ? 'selected' : '' ?>><?= htmlspecialchars($periodo['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($catalogos['condiciones'])): ?>
                        <input type="hidden" name="condicion_id" value="<?= (int) $catalogos['condiciones'][0]['id'] ?>">
                    <?php endif; ?>
                    <div class="upload-row">
                        <label for="foto">Foto Carnet</label>
                        <input id="foto" name="foto" type="file" accept="image/jpeg,image/png" required>
                        <span class="file-button">Subir foto</span>
                    </div>
                    <div class="upload-row">
                        <label for="documento">Copia de Documento</label>
                        <input id="documento" name="documento" type="file" accept="image/jpeg,image/png,application/pdf" required>
                        <span class="file-button">Subir DNI</span>
                    </div>
                    <p class="hint">Foto del rostro con fondo blanco.<br>Escaneo o foto del DNI por ambos caras.<br>Formatos permitidos: JPG, PNG o PDF.</p>
                </div>

                <div class="panel panel--compact">
                    <div class="field">
                        <label for="modalidad-clase">Modalidad de clase</label>
                        <select id="modalidad-clase" name="modalidad_clase_id" required>
                            <?php foreach ($catalogos['modalidades'] as $item): ?>
                                <option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="turno">Turno</label>
                        <select id="turno" name="turno_id" data-turno-nombres>
                            <?php foreach ($catalogos['turnos'] as $item): ?>
                                <option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="preview-grid">
                        <div class="preview preview--photo"><span>Foto</span></div>
                        <div class="preview preview--document"><span>DNI</span></div>
                    </div>
                    <div class="actions">
                        <button class="button button--gold" type="button" data-preview="foto">Previsualizar foto</button>
                        <button class="button button--gold" type="button" data-preview="documento">Previsualizar documento</button>
                        <button class="button button--dark" type="button" id="download-form">Descargar Declaración Jurada</button>
                    </div>
                </div>
            </section>

            <section class="section-block" id="datos-personales">
                <h2>Datos personales</h2>
                <div class="form-grid form-grid--three">
                    <div class="field"><label for="tipo-documento">Tipo de documento</label><select id="tipo-documento" name="tipo_documento"><option>DNI</option><option>CE</option><option>PASAPORTE</option></select></div>
                    <div class="field"><label for="numero-documento">Número</label><input id="numero-documento" name="numero_documento" type="text" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" autocomplete="off" required><small class="field-status" id="dni-status" aria-live="polite"></small></div>
                    <div class="field"><label for="apellido-paterno">Apellido paterno</label><input id="apellido-paterno" name="apellido_paterno" type="text" required></div>
                    <div class="field"><label for="apellido-materno">Apellido materno</label><input id="apellido-materno" name="apellido_materno" type="text" required></div>
                    <div class="field"><label for="nombres">Nombres</label><input id="nombres" name="nombres" type="text" required></div>
                    <div class="field"><label for="sexo">Sexo</label><select id="sexo" name="sexo"><option>MASCULINO</option><option>FEMENINO</option></select></div>
                    <div class="field field--span-2"><label for="correo">Correo electrónico</label><input id="correo" name="correo" type="email" required></div>
                    <div class="field"><label for="fecha-nacimiento">Fecha nacimiento</label><input id="fecha-nacimiento" name="fecha_nacimiento" type="date" required></div>
                    <div class="field"><label for="telefono-casa">Teléfono de casa</label><input id="telefono-casa" name="telefono_casa" type="tel"></div>
                    <div class="field"><label for="telefono-celular">Teléfono celular</label><input id="telefono-celular" name="telefono_celular" type="tel" required></div>
                </div>
            </section>

            <section class="section-block" id="datos-apoderado">
                <h2>Datos de tus apoderados</h2>
                <div class="form-grid form-grid--three">
                    <div class="field"><label for="apoderado-nombres">Nombres completos</label><input id="apoderado-nombres" name="apoderado_nombres" type="text"></div>
                    <div class="field"><label for="apoderado-parentesco">Parentesco</label><input id="apoderado-parentesco" name="apoderado_parentesco" type="text"></div>
                    <div class="field"><label for="apoderado-documento">Número de documento</label><input id="apoderado-documento" name="apoderado_documento" type="text"></div>
                    <div class="field"><label for="apoderado-telefono">Teléfono celular</label><input id="apoderado-telefono" name="apoderado_telefono" type="tel"></div>
                    <div class="field field--span-2"><label for="apoderado-correo">Correo electrónico</label><input id="apoderado-correo" name="apoderado_correo" type="email"></div>
                </div>
            </section>

            <section class="section-block">
                <h2>Lugar de domicilio actual</h2>
                <div class="form-grid form-grid--three">
                    <div class="field"><label for="departamento-actual">Departamento</label><select id="departamento-actual" name="departamento_actual" data-location="departamento"><option value="">Seleccione departamento</option><?php foreach ($catalogos['departamentos'] as $item): ?><option value="<?= htmlspecialchars($item['codigo']) ?>"><?= htmlspecialchars($item['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="field"><label for="provincia-actual">Provincia</label><select id="provincia-actual" name="provincia_actual" data-location="provincia" disabled><option value="">Seleccione provincia</option></select></div>
                    <div class="field"><label for="distrito-actual">Distrito</label><select id="distrito-actual" name="distrito_actual" data-location="distrito" disabled><option value="">Seleccione distrito</option></select></div>
                    <div class="field field--span-3"><label for="direccion-actual">Dirección actual</label><input id="direccion-actual" name="direccion_actual" type="text"></div>
                </div>
            </section>

            <section class="section-block">
                <h2>Lugar de nacimiento</h2>
                <div class="form-grid form-grid--four">
                    <div class="field"><label for="pais">País</label><select id="pais" name="pais_nacimiento"><option value="Perú">Perú</option><option value="Otro">Otro</option></select><input id="pais-extranjero" name="pais_nacimiento_otro" type="text" placeholder="Escriba el país" hidden disabled></div>
                    <div class="field"><label for="departamento-nacimiento">Departamento / estado</label><select id="departamento-nacimiento" name="departamento_nacimiento" data-location="departamento" data-peru-location><option value="">Seleccione departamento</option><?php foreach ($catalogos['departamentos'] as $item): ?><option value="<?= htmlspecialchars($item['codigo']) ?>"><?= htmlspecialchars($item['nombre']) ?></option><?php endforeach; ?></select><input id="departamento-nacimiento-extranjero" name="departamento_nacimiento" type="text" placeholder="Escriba departamento o estado" hidden disabled></div>
                    <div class="field"><label for="provincia-nacimiento">Provincia</label><select id="provincia-nacimiento" name="provincia_nacimiento" data-location="provincia" data-peru-location disabled><option value="">Seleccione provincia</option></select><input id="provincia-nacimiento-extranjero" name="provincia_nacimiento" type="text" placeholder="Escriba provincia" hidden disabled></div>
                    <div class="field"><label for="distrito-nacimiento">Distrito / ciudad</label><select id="distrito-nacimiento" name="distrito_nacimiento" data-location="distrito" data-peru-location disabled><option value="">Seleccione distrito</option></select><input id="distrito-nacimiento-extranjero" name="distrito_nacimiento" type="text" placeholder="Escriba distrito o ciudad" hidden disabled></div>
                </div>
            </section>

            <section class="section-block" id="academica">
                <h2>Información académica</h2>
                <div class="form-grid form-grid--four">
                    <div class="field" data-academic-adult><label for="anio">Año concluyó secundaria</label><input id="anio" name="anio_conclusion_secundaria" type="number" min="1950" max="2100"></div>
                    <div class="field" data-academic-adult><label for="pais-estudios">País</label><select id="pais-estudios" name="pais_estudios"><option value="Perú">Perú</option><option value="Otro">Otro</option></select></div>
                    <div class="field" data-academic-adult><label for="departamento-estudios">Departamento</label><select id="departamento-estudios" name="departamento_estudios" data-location="departamento"><option value="">Seleccione departamento</option><?php foreach ($catalogos['departamentos'] as $item): ?><option value="<?= htmlspecialchars($item['codigo']) ?>"><?= htmlspecialchars($item['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="field" data-academic-adult><label for="provincia-estudios">Provincia</label><select id="provincia-estudios" name="provincia_estudios" data-location="provincia" disabled><option value="">Seleccione provincia</option></select></div>
                    <div class="field" data-academic-adult><label for="distrito-estudios">Distrito</label><select id="distrito-estudios" name="distrito_estudios" data-location="distrito" disabled><option value="">Seleccione distrito</option></select></div>
                    <div class="field" data-academic-adult><label for="sector">Sector</label><select id="sector" name="sector_id"><?php foreach ($catalogos['sectores'] as $item): ?><option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="field field--span-2" data-academic-adult><label for="especificar-sector">Especificar sector</label><input id="especificar-sector" name="especificar_sector" type="text"></div>
                    <div class="field field--span-2"><label for="institucion">Colegio donde estudia</label><input id="institucion" name="nombre_institucion" type="text"></div>
                    <div class="field field--span-2" data-academic-adult><label for="institucion-extranjera">Nombre de institución extranjera</label><input id="institucion-extranjera" name="nombre_institucion_extranjera" type="text"></div>
                    <div class="field" data-academic-adult><label for="preparacion">Preparación anterior</label><select id="preparacion" name="preparacion_previa_id"><?php foreach ($catalogos['preparaciones'] as $item): ?><option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="field field--span-2" data-academic-adult><label for="mencion">Mención</label><input id="mencion" name="mencion" type="text"></div>
                    <div class="field"><label for="discapacidad">¿Tiene alguna discapacidad?</label><select id="discapacidad" name="tiene_discapacidad"><option value="0">No</option><option value="1">Sí</option></select></div>
                    <div id="seccion-discapacidad" class="field-group field-group--span-3" hidden>
                        <div class="field"><label for="tipo-discapacidad">Tipo de discapacidad</label><select id="tipo-discapacidad" name="tipo_discapacidad"><option value="">Seleccione el tipo</option><option value="fisica">Física</option><option value="sensorial">Sensorial (visual/auditiva)</option><option value="intelectual">Intelectual</option><option value="psicosocial">Psicosocial</option><option value="multiple">Múltiple</option><option value="otra">Otra</option></select></div>
                        <div class="field" id="otro-tipo-discapacidad" hidden><label for="otro-tipo-especificar">Especificar tipo de discapacidad</label><input id="otro-tipo-especificar" name="otro_tipo_discapacidad" type="text" placeholder="Especifique el tipo de discapacidad"></div>
                        <div class="field"><label for="grado-discapacidad">Grado de discapacidad</label><select id="grado-discapacidad" name="grado_discapacidad"><option value="">Seleccione el grado</option><option value="leve">Leve</option><option value="moderado">Moderado</option><option value="grave">Grave</option><option value="muy-grave">Muy grave</option></select></div>
                        <div class="field field--span-2"><label for="necesidades-especiales">Necesidades especiales o adecuaciones requeridas</label><textarea id="necesidades-especiales" name="necesidades_especiales" rows="3" placeholder="Describa las necesidades de apoyo o adecuaciones necesarias"></textarea></div>
                        <div class="field field--span-2"><label for="certificado-discapacidad">¿Posee certificado de discapacidad?</label><select id="certificado-discapacidad" name="tiene_certificado_discapacidad"><option value="0">No</option><option value="1">Sí</option></select></div>
                        <div class="field field--span-2" id="adjunto-certificado-discapacidad" hidden><label for="archivo-certificado-discapacidad">Adjuntar certificado de discapacidad</label><input id="archivo-certificado-discapacidad" name="certificado_discapacidad" type="file" accept="image/jpeg,image/png,application/pdf" disabled><small class="field-status">Formatos permitidos: JPG, PNG o PDF. Máximo 5 MB.</small></div>
                    </div>
                    <div class="field field--span-2">
                        <label for="como-se-entero-cepre">¿Cómo se enteró de la CEPRE UNTELS?</label>
                        <select id="como-se-entero-cepre" name="como_se_entero_cepre">
                            <option value="">Seleccione una opción</option>
                            <option value="redes_sociales">Redes sociales (Facebook, Instagram, etc.)</option>
                            <option value="sitio_web">Sitio web oficial</option>
                            <option value="amigo_familiar">Recomendación de un amigo o familiar</option>
                            <option value="colegio">Difusión en mi colegio</option>
                            <option value="evento_untels">Evento o feria informativa de la UNTELS</option>
                            <option value="publicidad_exterior">Publicidad exterior (vía pública, etc.)</option>
                            <option value="whatsapp">WhatsApp de la CEPRE UNTELS</option>
                            <option value="otro">Otro</option>
                        </select>
                        <div id="otro-como-se-entero" hidden>
                            <label for="especificar-como-se-entero" class="sr-only">Especificar cómo se enteró</label>
                            <input type="text" id="especificar-como-se-entero" name="especificar_como_se_entero" placeholder="Por favor, especifique..." maxlength="150">
                        </div>
                    </div>
                    <div class="field"><label for="carrera">Carrera a la que postula</label><select id="carrera" name="carrera_id"><?php foreach ($catalogos['carreras'] as $item): ?><option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['nombre']) ?></option><?php endforeach; ?></select></div>
                </div>
            </section>

            <div class="form-footer"><span>Los campos marcados son obligatorios.</span><div class="form-footer__actions"><button class="button button--clear" type="button" id="clear-form">Limpiar ficha</button><button class="button button--submit" type="submit">Guardar matrícula</button></div></div>
        </form>
    </main>
    <?php require __DIR__ . '/../partials/site-footer.php'; ?>
    <script src="/cepre_untels/public/js/app.js?v=20260907"></script>
    <?php if ($registroExitoso && $numeroRegistrado !== null): ?>
        <script>
            window.history.replaceState({}, document.title, window.location.pathname);
            Swal.fire({ title: '¡Registro correcto!', text: 'El código de alumno <?= htmlspecialchars($numeroRegistrado, ENT_QUOTES, 'UTF-8') ?> fue guardado en la base de datos.', icon: 'success', confirmButtonColor: '#23313b' });
        </script>
    <?php endif; ?>
</body>
</html>
