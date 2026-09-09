<?php
$titulo = 'Docentes — CEPRE UNTELS';
$pagina = 'docentes';
include 'header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Plana Docente Seleccionada</span>
    <h1>Nuestros Docentes</h1>
  </div>
</section>

<section>
  <div class="container">
    <p style="font-weight:700;color:var(--primary-variant);margin-bottom:14px;">Filtrar por Especialidad:</p>
    <div class="filter-bar" id="filterBar">
      <button type="button" class="filter-btn active" data-filter="todos">Todos</button>
      <button type="button" class="filter-btn" data-filter="matematicas">Matemáticas</button>
      <button type="button" class="filter-btn" data-filter="fisica-quimica">Física y Química</button>
      <button type="button" class="filter-btn" data-filter="comunicacion">Comunicación</button>
      <button type="button" class="filter-btn" data-filter="biologia">Biología</button>
      <button type="button" class="filter-btn" data-filter="ciencias-sociales">Ciencias Sociales</button>
    </div>

    <div class="teachers-grid" id="teachersGrid">
      <article class="teacher-card" data-cat="matematicas">
        <div class="avatar">AP</div>
        <div class="teacher-body">
          <h3>Dr. Arnaldo Peralta</h3>
          <span class="teacher-cat">Matemáticas</span>
          <p class="teacher-spec">Geometría Analítica y Cálculo</p>
          <p class="teacher-bio">Ph.D. en Ciencias Matemáticas con amplia experiencia en olimpiadas internacionales de ciencias.</p>
        </div>
      </article>
      <article class="teacher-card" data-cat="fisica-quimica">
        <div class="avatar">BS</div>
        <div class="teacher-body">
          <h3>Dra. Beatriz Santos</h3>
          <span class="teacher-cat">Física y Química</span>
          <p class="teacher-spec">Termodinámica e Inorgánica</p>
          <p class="teacher-bio">Investigadora con patentes registradas. Pasión por la enseñanza y el método científico.</p>
        </div>
      </article>
      <article class="teacher-card" data-cat="comunicacion">
        <div class="avatar">CV</div>
        <div class="teacher-body">
          <h3>Mg. Claudio Valdivia</h3>
          <span class="teacher-cat">Comunicación</span>
          <p class="teacher-spec">Redacción y Literatura</p>
          <p class="teacher-bio">Consultor de estilo y lingüista apasionado por la comprensión crítica del texto moderno.</p>
        </div>
      </article>
      <article class="teacher-card" data-cat="biologia">
        <div class="avatar">DP</div>
        <div class="teacher-body">
          <h3>Mg. Dora del Pilar</h3>
          <span class="teacher-cat">Biología</span>
          <p class="teacher-spec">Citología y Genética Básica</p>
          <p class="teacher-bio">Especialista en botánica médica y asesora del laboratorio nacional de microscopía.</p>
        </div>
      </article>
      <article class="teacher-card" data-cat="ciencias-sociales">
        <div class="avatar">ER</div>
        <div class="teacher-body">
          <h3>Dr. Ernesto Ramos</h3>
          <span class="teacher-cat">Ciencias Sociales</span>
          <p class="teacher-spec">Historia del Perú e Internacional</p>
          <p class="teacher-bio">Autor de manuales escolares y ensayos sobre la consolidación republicana andina.</p>
        </div>
      </article>
      <article class="teacher-card" data-cat="matematicas">
        <div class="avatar">FS</div>
        <div class="teacher-body">
          <h3>Mg. Fanny Solórzano</h3>
          <span class="teacher-cat">Matemáticas</span>
          <p class="teacher-spec">Álgebra y Trigonometría</p>
          <p class="teacher-bio">Enfoque interactivo y mentoría dedicada a resolver problemas complejos con facilidad.</p>
        </div>
      </article>
      <article class="teacher-card" data-cat="fisica-quimica">
        <div class="avatar">GC</div>
        <div class="teacher-body">
          <h3>Dr. Gustavo Castillo</h3>
          <span class="teacher-cat">Física y Química</span>
          <p class="teacher-spec">Electromagnetismo</p>
          <p class="teacher-bio">Asesor científico de la federación nacional de industrias aplicadas de energía.</p>
        </div>
      </article>
      <article class="teacher-card" data-cat="comunicacion">
        <div class="avatar">HB</div>
        <div class="teacher-body">
          <h3>Dra. Hilda Barreto</h3>
          <span class="teacher-cat">Comunicación</span>
          <p class="teacher-spec">Comprensión Lectora Avanzada</p>
          <p class="teacher-bio">Dedicada a perfeccionar las destrezas de análisis crítico textual para postulantes.</p>
        </div>
      </article>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/site-footer.php'; ?>
</body>
</html>