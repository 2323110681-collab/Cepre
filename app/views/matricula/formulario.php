<?php

require_once __DIR__ . '/../../../config/auth.php';

$catalogos = $catalogos ?? [];
$catalogos['carreras'] = $catalogos['carreras'] ?? [];
$catalogos['condiciones'] = $catalogos['condiciones'] ?? [];
$catalogos['turnos'] = $catalogos['turnos'] ?? [];
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/cepre_untels/public/css/app.css?v=20260903">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="/cepre_untels/public/" aria-label="CEPRE UNTELS inicio">
            <span class="brand__mark">C</span>
            <span><strong>CEPRE</strong><b>UNTELS</b></span>
        </a>
        <nav aria-label="Navegación principal">          
            <span class="user-label"><?= htmlspecialchars($usuarioActual['nombre'] ?? 'Usuario') ?></span>
            <a href="/cepre_untels/public/logout.php">Salir</a>
        </nav>
    </header>

    <main class="page-shell" id="ficha">
        <div class="page-heading">
            <p class="eyebrow">Registro de estudiante</p>
            <h1>FICHA DE MATRÍCULA N.° <?= htmlspecialchars($numeroMatricula) ?></h1>
            <p>MODALIDAD REGULAR - DERECHO A VACANTE</p>
            <button class="button button--new" type="button" id="new-enrollment">+ Registrar nueva matrícula</button>
        </div>

        <?php if (!$databaseReady): ?>
            <div class="alert"><?= $_SERVER['REQUEST_METHOD'] === 'POST' ? 'No se pudo guardar la matrícula.' : 'No se pudieron cargar los catálogos.' ?> <?= htmlspecialchars($errorMessage ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form class="enrollment-form" method="post" enctype="multipart/form-data" action="#">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <section class="top-grid" aria-label="Datos de matrícula">
                <div class="panel panel--compact">
                    <div class="field">
                        <label for="codigo-cepre">Código CEPRE asignado</label>
                        <input id="codigo-cepre" type="text" value="<?= htmlspecialchars($codigoCepre ?? $numeroMatricula, ENT_QUOTES, 'UTF-8') ?>" readonly>
                    </div>
                    <div class="field">
                        <label for="semestre">Semestre</label>
                        <select id="semestre" name="semestre" required>
                            <option value="01">I</option>
                            <option value="11">II</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="codigo-alumno">Código de alumno</label>
                        <input id="codigo-alumno" type="text" value="Se generará al completar" readonly>
                    </div>
                    <div class="field">
                        <label for="condicion">Condición</label>
                        <select id="condicion" name="condicion_id">
                            <?php foreach ($catalogos['condiciones'] as $item): ?>
                                <option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
                        <select id="turno" name="turno_id">
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
                    <div class="field"><label for="apellido-paterno">Apellido paterno</label><input id="apellido-paterno" name="apellido_paterno" type="text" required></div>
                    <div class="field"><label for="apellido-materno">Apellido materno</label><input id="apellido-materno" name="apellido_materno" type="text" required></div>
                    <div class="field"><label for="nombres">Nombres</label><input id="nombres" name="nombres" type="text" required></div>
                    <div class="field"><label for="tipo-documento">Tipo de documento</label><select id="tipo-documento" name="tipo_documento"><option>DNI</option><option>CE</option><option>PASAPORTE</option></select></div>
                    <div class="field"><label for="numero-documento">Número</label><input id="numero-documento" name="numero_documento" type="text" required></div>
                    <div class="field"><label for="sexo">Sexo</label><select id="sexo" name="sexo"><option>MASCULINO</option><option>FEMENINO</option></select></div>
                    <div class="field field--span-2"><label for="correo">Correo electrónico</label><input id="correo" name="correo" type="email" required></div>
                    <div class="field"><label for="fecha-nacimiento">Fecha nacimiento</label><input id="fecha-nacimiento" name="fecha_nacimiento" type="date" required></div>
                    <div class="field"><label for="telefono-casa">Teléfono de casa</label><input id="telefono-casa" name="telefono_casa" type="tel"></div>
                    <div class="field"><label for="telefono-celular">Teléfono celular</label><input id="telefono-celular" name="telefono_celular" type="tel" required></div>
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
                    <div class="field"><label for="anio">Año concluyó secundaria</label><input id="anio" name="anio_conclusion_secundaria" type="number" min="1950" max="2100"></div>
                    <div class="field"><label for="pais-estudios">País</label><select id="pais-estudios" name="pais_estudios"><option value="Perú">Perú</option><option value="Otro">Otro</option></select></div>
                    <div class="field"><label for="departamento-estudios">Departamento</label><select id="departamento-estudios" name="departamento_estudios" data-location="departamento"><option value="">Seleccione departamento</option><?php foreach ($catalogos['departamentos'] as $item): ?><option value="<?= htmlspecialchars($item['codigo']) ?>"><?= htmlspecialchars($item['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="field"><label for="provincia-estudios">Provincia</label><select id="provincia-estudios" name="provincia_estudios" data-location="provincia" disabled><option value="">Seleccione provincia</option></select></div>
                    <div class="field"><label for="distrito-estudios">Distrito</label><select id="distrito-estudios" name="distrito_estudios" data-location="distrito" disabled><option value="">Seleccione distrito</option></select></div>
                    <div class="field"><label for="sector">Sector</label><select id="sector" name="sector_id"><?php foreach ($catalogos['sectores'] as $item): ?><option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="field field--span-2"><label for="especificar-sector">Especificar sector</label><input id="especificar-sector" name="especificar_sector" type="text"></div>
                    <div class="field field--span-2"><label for="institucion">Nombre de institución</label><input id="institucion" name="nombre_institucion" type="text"></div>
                    <div class="field field--span-2"><label for="institucion-extranjera">Nombre de institución extranjera</label><input id="institucion-extranjera" name="nombre_institucion_extranjera" type="text"></div>
                    <div class="field"><label for="preparacion">Preparación anterior</label><select id="preparacion" name="preparacion_previa_id"><?php foreach ($catalogos['preparaciones'] as $item): ?><option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="field field--span-2"><label for="mencion">Mención</label><input id="mencion" name="mencion" type="text"></div>
                    <div class="field"><label for="carrera">Carrera a la que postula</label><select id="carrera" name="carrera_id"><?php foreach ($catalogos['carreras'] as $item): ?><option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['nombre']) ?></option><?php endforeach; ?></select></div>
                </div>
            </section>

            <div class="form-footer"><span>Los campos marcados son obligatorios.</span><div class="form-footer__actions"><button class="button button--clear" type="button" id="clear-form">Limpiar ficha</button><button class="button button--submit" type="submit">Guardar matrícula</button></div></div>
        </form>
    </main>
    <script src="/cepre_untels/public/js/app.js"></script>
    <?php if ($registroExitoso && $numeroRegistrado !== null): ?>
        <script>Swal.fire({ title: '¡Registro correcto!', text: 'El código de alumno <?= htmlspecialchars($numeroRegistrado, ENT_QUOTES, 'UTF-8') ?> fue guardado en la base de datos.', icon: 'success', confirmButtonColor: '#23313b' });</script>
    <?php endif; ?>
</body>
</html>
