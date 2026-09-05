<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$operacion = trim((string) ($_GET['operacion'] ?? 'paises'));
$pais = trim((string) ($_GET['pais'] ?? ''));
$estado = trim((string) ($_GET['estado'] ?? ''));
$buscar = mb_strtolower(trim((string) ($_GET['buscar'] ?? '')), 'UTF-8');

if (!in_array($operacion, ['paises', 'estados', 'ciudades'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'La operación debe ser paises, estados o ciudades.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($operacion === 'estados' && $pais === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Debe indicar el país.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($operacion === 'ciudades' && ($pais === '' || $estado === '')) {
    http_response_code(400);
    echo json_encode(['error' => 'Debe indicar el país y el estado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Ruta base para archivos JSON
$dataPath = __DIR__ . '/../../app/data/extranjeras/';

/**
 * Cargar datos desde archivo JSON local
 */
function loadJsonFile(string $filePath): array
{
    if (!file_exists($filePath)) {
        return [];
    }
    
    $content = file_get_contents($filePath);
    if ($content === false) {
        return [];
    }
    
    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}

$locations = [];

if ($operacion === 'paises') {
    $locations = loadJsonFile($dataPath . 'paises.json');
} elseif ($operacion === 'estados') {
    // Buscar el archivo de provincias/departamentos/estados del país
    $countryFiles = [
        'AR' => 'AR_provincias.json',
        'BO' => 'BO_departamentos.json',
        'BR' => 'BR_estados.json',
        'CL' => 'CL_regiones.json',
        'CO' => 'CO_departamentos.json',
        'EC' => 'EC_provincias.json',
        'GY' => 'GY_regiones.json',
        'PY' => 'PY_departamentos.json',
        'PE' => 'PE_departamentos.json',
        'SR' => 'SR_distritos.json',
        'UY' => 'UY_departamentos.json',
        'VE' => 'VE_estados.json',
    ];
    
    $fileName = $countryFiles[$pais] ?? null;
    if ($fileName) {
        $locations = loadJsonFile($dataPath . $fileName);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No se encontraron divisiones para el país indicado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} elseif ($operacion === 'ciudades') {
    // Buscar archivo de ciudades
    $fileName = $pais . '_' . $estado . '_ciudades.json';
    $filePath = $dataPath . $fileName;
    
    $data = loadJsonFile($filePath);
    if (empty($data)) {
        // Si no existe, intentar cargar archivo general del país
        $fallbackFile = $dataPath . $pais . '_ciudades.json';
        $data = loadJsonFile($fallbackFile);
    }
    
    // Convertir array de strings a formato con código y nombre
    $locations = array_map(
        static fn ($item): array => is_array($item)
            ? ['codigo' => $item['codigo'] ?? $item['nombre'] ?? '', 'nombre' => $item['nombre'] ?? '']
            : ['codigo' => $item, 'nombre' => $item],
        $data
    );
}

// Validar que tenemos datos
if (empty($locations)) {
    http_response_code(404);
    echo json_encode(['error' => 'No se encontraron resultados para la búsqueda.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Filtrar por búsqueda si se proporciona
if ($buscar !== '') {
    $locations = array_values(array_filter(
        $locations,
        static fn (array $location): bool => str_contains(
            mb_strtolower((string) ($location['nombre'] ?? ''), 'UTF-8'),
            $buscar
        )
    ));
}

// Ordenar alfabéticamente
usort($locations, static fn (array $left, array $right): int => strcasecmp(
    (string) ($left['nombre'] ?? ''),
    (string) ($right['nombre'] ?? '')
));

echo json_encode($locations, JSON_UNESCAPED_UNICODE);
