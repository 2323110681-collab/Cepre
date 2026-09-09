<?php
$titulo = 'Preguntas Frecuentes — CEPRE UNTELS';
$pagina = 'faq';
include 'header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Centro de Ayuda</span>
    <h1>Preguntas Frecuentes</h1>
  </div>
</section>

<section>
  <div class="container">
    <div class="faq-list">
      <div class="faq-item open">
        <button type="button" class="faq-q">¿Cuándo inician los ciclos académicos en el CEPREUNTELS?<span class="chev">+</span></button>
        <div class="faq-a"><p>El inicio de los ciclos académicos puede consultarse en la oficina del CEPREUNTELS o comunicándose al 905-452-332 o al teléfono (01) 715-8878, anexo 183.</p></div>
      </div>
      <div class="faq-item">
        <button type="button" class="faq-q">¿Qué significa con opción de ingreso directo?<span class="chev">+</span></button>
        <div class="faq-a"><p>La modalidad con derecho a vacante brinda 15 vacantes por carrera. Los estudiantes ingresan según orden de mérito a través de dos evaluaciones y dos simulacros adicionales.</p></div>
      </div>
      <div class="faq-item">
        <button type="button" class="faq-q">¿Qué significa la opción de solo preparación?<span class="chev">+</span></button>
        <div class="faq-a"><p>Es una preparación dirigida a estudiantes o egresados que deseen ampliar sus conocimientos para postular a la UNTELS u otras universidades. Incluye dos simulacros.</p></div>
      </div>
      <div class="faq-item">
        <button type="button" class="faq-q">¿Dónde puedo hacer el pago para los ciclos académicos del CEPREUNTELS?<span class="chev">+</span></button>
        <div class="faq-a"><p>Los pagos se realizan en las entidades bancarias autorizadas por la universidad o directamente en la oficina del CEPREUNTELS. Consulta los detalles al (01) 715-8878, anexo 183.</p></div>
      </div>
      <div class="faq-item">
        <button type="button" class="faq-q">Después de realizar el pago, ¿cómo puedo matricularme en el ciclo académico del CEPREUNTELS?<span class="chev">+</span></button>
        <div class="faq-a"><p>Con tu voucher de pago, acércate a la oficina del CEPREUNTELS o completa la matrícula virtual con tus datos personales y la carrera de tu interés.</p></div>
      </div>
      <div class="faq-item">
        <button type="button" class="faq-q">¿Cuáles son los horarios de clases de los ciclos académicos en el CEPREUNTELS?<span class="chev">+</span></button>
        <div class="faq-a"><p>Los horarios varían según el ciclo y la modalidad. Puedes consultarlos en la oficina del CEPREUNTELS o llamando al 905-452-332.</p></div>
      </div>
    </div>

    <div class="cta-band">
      <span class="eyebrow" style="color:var(--secondary);justify-content:center;">¿Aún tienes preguntas?</span>
      <h2>¿No encontraste la <em>respuesta</em> que buscabas?</h2>
      <p>Nuestro equipo de atención al postulante está disponible para resolver cualquier duda adicional sobre matrícula o pagos.</p>
      <a href="contacto.php" class="btn btn-secondary">Contáctanos Directamente</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/site-footer.php'; ?>
</body>
</html>