<?php
require_once __DIR__ . '/../app/models/ContactoModel.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/recaptcha.php';

$errorMessage = null;
$mensajeEnviado = ($_GET['enviado'] ?? '') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $correo = trim((string) ($_POST['correo'] ?? ''));
    $telefono = trim((string) ($_POST['telefono'] ?? ''));
    $mensaje = trim((string) ($_POST['mensaje'] ?? ''));
    $recaptchaResponse = trim((string) ($_POST['g-recaptcha-response'] ?? ''));

    if (!verifyRecaptcha($recaptchaResponse, $_SERVER['REMOTE_ADDR'] ?? null)) {
      throw new InvalidArgumentException('Confirma que no eres un robot para enviar el mensaje.');
    }

    if ($nombre === '' || $telefono === '' || $mensaje === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException('Complete todos los campos con datos válidos.');
    }
    if (mb_strlen($nombre) > 150 || mb_strlen($correo) > 150 || mb_strlen($telefono) > 40 || mb_strlen($mensaje) > 5000) {
      throw new InvalidArgumentException('Uno de los campos supera la longitud permitida.');
    }

    (new ContactoModel())->registrar($nombre, $correo, $telefono, $mensaje);
    header('Location: /cepre_untels/public/contacto.php?enviado=1');
    exit;
  } catch (Throwable $exception) {
    $errorMessage = $exception instanceof InvalidArgumentException
      ? $exception->getMessage()
      : 'No se pudo registrar el mensaje. Inténtelo nuevamente.';
  }
}

$titulo = 'Contacto — CEPRE UNTELS';
$pagina = 'contacto';
include 'header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Atención al Postulante</span>
    <h1>Contacto</h1>
  </div>
</section>

<section>
  <div class="container contact-grid">
    <form class="contact-form" id="contactForm" method="post" action="contacto.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
      <h2>Formulario de Consultas</h2>
      <p>Déjanos tus datos y un asesor se comunicará contigo hoy mismo.</p>
      <div class="field">
        <label for="nombre">Nombre Completo</label>
        <input type="text" id="nombre" name="nombre" maxlength="150" placeholder="Ej. Juan Pérez" required>
      </div>
      <div class="field">
        <label for="correo">Correo Electrónico</label>
        <input type="email" id="correo" name="correo" maxlength="150" placeholder="Ej. juan.perez@email.com" required>
      </div>
      <div class="field">
        <label for="telefono">Teléfono / Celular</label>
        <input type="tel" id="telefono" name="telefono" maxlength="40" placeholder="Ej. 987 654 321" required>
      </div>
      <div class="field">
        <label for="mensaje">Mensaje</label>
        <textarea id="mensaje" name="mensaje" rows="5" maxlength="5000" placeholder="Escribe tus preguntas sobre ciclos, costos o sedes..." required></textarea>
      </div>
      <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>"></div>
      <button type="submit" class="btn btn-secondary">Enviar Mensaje</button>
      <?php if ($errorMessage !== null): ?><p class="form-note show" id="formNote"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p><?php else: ?><p class="form-note" id="formNote"></p><?php endif; ?>
    </form>

    <div>
      <span class="eyebrow">Información de contacto</span>
      <div class="info-list">
        <div class="info-item">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12 21C15.5 17.4 19 14.1764 19 10.2C19 6.22355 15.866 3 12 3C8.13401 3 5 6.22355 5 10.2C5 14.1764 8.5 17.4 12 21Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
          <div>
            <h4>Dirección</h4>
            <p>Campus - Sector 3 Grupo 1A 03, Cercado (Av. Central y Av. Bolívar) Villa El Salvador.</p>
          </div>
        </div>
        <div class="info-item">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M3 5.5C3 14.0604 9.93959 21 18.5 21C18.8862 21 19.2691 20.9859 19.6483 20.9581C20.0834 20.9262 20.3009 20.9103 20.499 20.7963C20.663 20.7019 20.8185 20.5345 20.9007 20.364C21 20.1582 21 19.9181 21 19.438V16.6207C21 16.2169 21 16.015 20.9335 15.842C20.8749 15.6891 20.7795 15.553 20.6559 15.4456C20.516 15.324 20.3262 15.255 19.9468 15.117L16.74 13.9509C16.2985 13.7904 16.0777 13.7101 15.8683 13.7237C15.6836 13.7357 15.5059 13.7988 15.3549 13.9058C15.1837 14.0271 15.0629 14.2285 14.8212 14.6314L14 16C11.3501 14.7999 9.2019 12.6489 8 10L9.36863 9.17882C9.77145 8.93713 9.97286 8.81628 10.0942 8.64506C10.2012 8.49408 10.2643 8.31637 10.2763 8.1317C10.2899 7.92227 10.2096 7.70153 10.0491 7.26005L8.88299 4.05321C8.745 3.67376 8.67601 3.48403 8.55442 3.3441C8.44701 3.22049 8.31089 3.12515 8.15802 3.06645C7.98496 3 7.78308 3 7.37932 3H4.56201C4.08188 3 3.84181 3 3.63598 3.09925C3.4655 3.18146 3.29814 3.33701 3.2037 3.50103C3.08968 3.69907 3.07375 3.91662 3.04189 4.35173C3.01413 4.73086 3 5.11378 3 5.5Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
          <div>
            <h4>Teléfono</h4>
            <p>(01) 715 8878 anexo 183</p>
          </div>
        </div>
        <div class="info-item">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M4 18L9 12M20 18L15 12M3 8L10.225 12.8166C10.8665 13.2443 11.1872 13.4582 11.5339 13.5412C11.8403 13.6147 12.1597 13.6147 12.4661 13.5412C12.8128 13.4582 13.1335 13.2443 13.775 12.8166L21 8M6.2 19H17.8C18.9201 19 19.4802 19 19.908 18.782C20.2843 18.5903 20.5903 18.2843 20.782 17.908C21 17.4802 21 16.9201 21 15.8V8.2C21 7.0799 21 6.51984 20.782 6.09202C20.5903 5.71569 20.2843 5.40973 19.908 5.21799C19.4802 5 18.9201 5 17.8 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V15.8C3 16.9201 3 17.4802 3.21799 17.908C3.40973 18.2843 3.71569 18.5903 4.09202 18.782C4.51984 19 5.07989 19 6.2 19Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
          <div>
            <h4>Dirección de correo electrónico</h4>
            <p>cepreuntels@untels.edu.pe</p>
          </div>
        </div>
      </div>
      <span class="eyebrow" style="margin-top:30px;display:inline-flex;">// Localización de la sede</span>
      <div class="map-placeholder">Google Maps Iframe Placeholder</div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/site-footer.php'; ?>
<?php if ($mensajeEnviado): ?>
<script>
  Swal.fire({
    title: '¡Mensaje enviado!',
    text: '¡Gracias! Tu mensaje fue registrado. Un asesor te contactará pronto.',
    icon: 'success',
    confirmButtonColor: '#23313b',
    confirmButtonText: 'Aceptar'
  });
</script>
<?php endif; ?>
</body>
</html>