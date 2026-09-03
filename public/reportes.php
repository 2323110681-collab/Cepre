<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../app/models/MatriculaModel.php';

requireAuthentication();
$model = new MatriculaModel();
$catalogos = $model->catalogos();
$catalogos['periodos'] = array_values(array_filter(
  $catalogos['periodos'],
  static fn (array $periodo): bool => preg_match('/^\d{4}-(?:I|II)$/', (string) $periodo['nombre']) === 1
));

$positiveInt = static function (mixed $value): ?int {
    $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value === false ? null : $value;
};
$date = static function (mixed $value): ?string {
    return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
};
$periodoId = $positiveInt($_GET['periodo_id'] ?? null);
$periodoIds = array_map(static fn (array $periodo): int => (int) $periodo['id'], $catalogos['periodos']);
if ($periodoId !== null && !in_array($periodoId, $periodoIds, true)) {
  $periodoId = null;
}
$carreraId = $positiveInt($_GET['carrera_id'] ?? null);
$sectorId = $positiveInt($_GET['sector_id'] ?? null);
$sexo = in_array($_GET['sexo'] ?? '', ['MASCULINO', 'FEMENINO'], true) ? $_GET['sexo'] : null;
$distrito = trim((string) ($_GET['distrito'] ?? '')) ?: null;
$desde = $date($_GET['desde'] ?? null);
$hasta = $date($_GET['hasta'] ?? null);
$reportes = $model->reportes($periodoId, $desde, $hasta, $carreraId, $sexo, $sectorId, $distrito);

function reportLabel(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function reportNumber(mixed $value): string
{
    return number_format((int) $value, 0, ',', '.');
}

function reportPercent(int $part, int $total): string
{
    return $total > 0 ? (string) round($part * 100 / $total) . '%' : '0%';
}

function reportOptions(array $options, mixed $selected): void
{
    foreach ($options as $option) {
        $value = (string) ($option['id'] ?? $option['nombre'] ?? '');
        $label = (string) ($option['nombre'] ?? '');
        echo '<option value="' . reportLabel($value) . '"' . ((string) $selected === $value ? ' selected' : '') . '>' . reportLabel($label) . '</option>';
    }
}

$daily = $reportes['por_dia'];
$latestDaily = array_reverse(array_slice($daily, -5));
$maxDaily = max(array_map(static fn (array $row): int => (int) $row['total'], $daily) ?: [1]);
$topCareer = $reportes['por_carrera'][0] ?? ['etiqueta' => 'Sin datos', 'total' => 0];
$topSource = $reportes['por_conocimiento'][0] ?? ['etiqueta' => 'Sin datos', 'total' => 0];
$periodName = 'Todos los periodos';
foreach ($catalogos['periodos'] as $periodo) {
    if ((int) $periodo['id'] === $periodoId) {
        $periodName = (string) $periodo['nombre'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard CEPRE - Reportes Estadísticos</title>
  <link rel="icon" type="image/png" href="/cepre_untels/public/img/cepre.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/cepre_untels/public/css/app.css?v=20260907">
  <link rel="stylesheet" href="/cepre_untels/public/css/reportes.css?v=20260907">
</head>
<body>
<?php require __DIR__ . '/../app/views/partials/site-header.php'; ?>
<main class="container">
  <form class="filters" method="get">
    <div class="filter"><label for="periodo_id">Periodo</label><select id="periodo_id" name="periodo_id"><option value="">Todos</option><?php reportOptions($catalogos['periodos'], $periodoId); ?></select></div>
    <div class="filter"><label for="desde">Fecha inicio</label><input id="desde" type="date" name="desde" value="<?= reportLabel($desde) ?>"></div>
    <div class="filter"><label for="hasta">Fecha fin</label><input id="hasta" type="date" name="hasta" value="<?= reportLabel($hasta) ?>"></div>
    <div class="filter"><label for="carrera_id">Carrera</label><select id="carrera_id" name="carrera_id"><option value="">Todas</option><?php reportOptions($catalogos['carreras'], $carreraId); ?></select></div>
    <div class="filter">
      <label for="sexo">Sexo</label>
      <select id="sexo" name="sexo">
        <option value="">Todos</option>
        <?php reportOptions([['nombre' => 'MASCULINO'], ['nombre' => 'FEMENINO']], $sexo); ?>
      </select>
    </div>
    <div class="filter">
      <label for="sector_id">Tipo de colegio</label>
      <select id="sector_id" name="sector_id">
        <option value="">Todos</option>
        <?php reportOptions($catalogos['sectores'], $sectorId); ?>
      </select>
    </div>
    <div class="filter">
      <label for="distrito">Distrito</label>
      <select id="distrito" name="distrito">
        <option value="">Todos</option>
        <?php reportOptions($catalogos['distritos'], $distrito); ?>
      </select>
    </div>
    <div class="filter-actions">
      <button type="submit">Aplicar filtros</button>
      <a href="reportes.php">Limpiar</a>
    </div>
  </form>

  <section class="grid kpis">
    <article class="card">
      <div class="metric-label">Total inscritos</div>
      <div class="metric"><?= reportNumber($reportes['total']) ?></div>
      <div class="muted">Matrículas no anuladas</div>
    </article>
    <article class="card">
      <div class="metric-label">Último día registrado</div>
      <div class="metric"><?= reportNumber($daily === [] ? 0 : $daily[count($daily) - 1]['total']) ?></div>
      <div class="muted"><?= reportLabel($daily === [] ? 'Sin registros' : $daily[count($daily) - 1]['fecha']) ?></div>
    </article>
    <article class="card">
      <div class="metric-label">Carrera líder</div>
      <div class="metric metric-small"><?= reportLabel($topCareer['etiqueta']) ?></div>
      <div class="muted"><?= reportNumber($topCareer['total']) ?> inscritos</div>
    </article>
    <article class="card">
      <div class="metric-label">Canal principal</div>
      <div class="metric metric-small"><?= reportLabel($topSource['etiqueta']) ?></div>
      <div class="muted"><?= reportPercent((int) $topSource['total'], $reportes['total']) ?> del total</div>
    </article>
  </section>

  <section class="grid charts">
    <article class="card">
      <h2>Inscripciones diarias vs. acumulado</h2>
      <?php if ($daily === []): ?>
        <p class="empty">No hay registros para los filtros seleccionados.</p>
      <?php else: ?>
        <div class="daily">
          <?php foreach ($daily as $row): ?>
            <div class="daily-item">
              <span class="daily-total"><?= reportNumber($row['acumulado']) ?></span>
              <div
                class="daily-bar"
                style="--height: <?= max(8, (int) round($row['total'] * 100 / $maxDaily)) ?>px"
                title="<?= reportNumber($row['total']) ?> registros"
              ></div>
              <span><?= reportLabel($row['fecha']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="legend">
          <span class="dot"></span> Diarias
          <span class="dot pink"></span> Acumulado (número superior)
        </div>
      <?php endif; ?>
    </article>
    <article class="card">
      <h2>Ranking de carreras (Top 10)</h2>
      <?php foreach (array_slice($reportes['por_carrera'], 0, 10) as $row): ?>
        <div class="bar-row">
          <span><?= reportLabel($row['etiqueta']) ?></span>
          <div class="bar-bg">
            <div
              class="bar orange"
              style="width: <?= reportPercent((int) $row['total'], (int) $topCareer['total']) ?>"
            ></div>
          </div>
          <strong><?= reportNumber($row['total']) ?></strong>
        </div>
      <?php endforeach; ?>
      <?php if ($reportes['por_carrera'] === []): ?>
        <p class="empty">Sin datos.</p>
      <?php endif; ?>
    </article>
  </section>

  <section class="grid three">
    <article class="card">
      <h2>Distribución por sexo</h2>
      <?php foreach ($reportes['por_sexo'] as $row): ?>
        <div class="bar-row">
          <span><?= reportLabel($row['etiqueta']) ?></span>
          <div class="bar-bg">
            <div class="bar" style="width: <?= reportPercent((int) $row['total'], $reportes['total']) ?>"></div>
          </div>
          <strong><?= reportPercent((int) $row['total'], $reportes['total']) ?></strong>
        </div>
      <?php endforeach; ?>
    </article>
    <article class="card">
      <h2>¿Cómo se enteró?</h2>
      <?php foreach ($reportes['por_conocimiento'] as $row): ?>
        <div class="bar-row">
          <span><?= reportLabel($row['etiqueta']) ?></span>
          <div class="bar-bg">
            <div class="bar green" style="width: <?= reportPercent((int) $row['total'], $reportes['total']) ?>"></div>
          </div>
          <strong><?= reportPercent((int) $row['total'], $reportes['total']) ?></strong>
        </div>
      <?php endforeach; ?>
    </article>
    <article class="card">
      <h2>Top distritos</h2>
      <?php foreach (array_slice($reportes['por_distrito'], 0, 10) as $row): ?>
        <div class="bar-row">
          <span><?= reportLabel($row['etiqueta']) ?></span>
          <div class="bar-bg">
            <div
              class="bar orange"
              style="width: <?= reportPercent((int) $row['total'], (int) ($reportes['por_distrito'][0]['total'] ?? 1)) ?>"
            ></div>
          </div>
          <strong><?= reportNumber($row['total']) ?></strong>
        </div>
      <?php endforeach; ?>
    </article>
  </section>

  <section class="table-container">
    <div class="table-head">
      <h2 class="table-title">Reporte diario detallado</h2>
      <p class="table-subtitle">Últimos días con registros dentro de los filtros aplicados.</p>
    </div>
    <?php if ($latestDaily === []): ?>
      <p class="empty">No hay registros para los filtros seleccionados.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Nuevos</th>
            <th>Público</th>
            <th>Privado</th>
            <th>Carrera top</th>
            <th>Acumulado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($latestDaily as $row): ?>
            <tr>
              <td><?= reportLabel($row['fecha']) ?></td>
              <td><?= reportNumber($row['total']) ?></td>
              <td><?= reportNumber($row['publico']) ?></td>
              <td><?= reportNumber($row['privado']) ?></td>
              <td><?= reportLabel($row['carrera_top']) ?></td>
              <td><?= reportNumber($row['acumulado']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</main>
<?php require __DIR__ . '/../app/views/partials/site-footer.php'; ?>
</body>
</html>
