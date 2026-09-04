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

$apiUrl = match ($operacion) {
    'paises' => 'https://countriesnow.space/api/v0.1/countries',
    'estados' => 'https://countriesnow.space/api/v0.1/countries/states',
    'ciudades' => 'https://countriesnow.space/api/v0.1/countries/state/cities',
};

$payload = match ($operacion) {
    'paises' => null,
    'estados' => ['country' => $pais],
    'ciudades' => ['country' => $pais, 'state' => $estado],
};

$response = false;
$status = 0;
$error = '';

if (function_exists('curl_init')) {
    $curl = curl_init($apiUrl);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
    ]);
    if ($payload !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
} else {
    $options = [
        'http' => [
            'method' => $payload === null ? 'GET' : 'POST',
            'header' => "Accept: application/json\r\nContent-Type: application/json\r\n",
            'content' => $payload === null ? '' : json_encode($payload, JSON_UNESCAPED_UNICODE),
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($apiUrl, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    preg_match('/\s(\d{3})\s/', $statusLine, $statusMatches);
    $status = (int) ($statusMatches[1] ?? 0);
    $error = $response === false ? 'No se pudo conectar con el servicio externo.' : '';
}

if (is_string($response) && $response !== '') {
    $error = '';
    $status = $status > 0 ? $status : 200;
}

if ($payload !== null && ($response === false || $error !== '' || $status < 200 || $status >= 300)) {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Accept: application/json\r\nContent-Type: application/json\r\n",
            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents($apiUrl, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    preg_match('/\s(\d{3})\s/', $statusLine, $statusMatches);
    $status = (int) ($statusMatches[1] ?? 0);
    $error = $response === false ? 'No se pudo conectar con el servicio externo.' : '';
    if (is_string($response) && $response !== '') {
        $error = '';
        $status = $status > 0 ? $status : 200;
    }
}

if ($response === false || $error !== '' || $status < 200 || $status >= 300) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo consultar el servicio de ubicaciones extranjeras.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = json_decode((string) $response, true);
if (!is_array($result) || !empty($result['error'])) {
    http_response_code(502);
    echo json_encode(['error' => $result['msg'] ?? 'El servicio devolvió una respuesta no válida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = $result['data'] ?? [];
if ($operacion === 'paises') {
    $locations = array_map(
        static fn (array $item): array => [
            'codigo' => (string) ($item['iso2'] ?? $item['iso3'] ?? ''),
            'nombre' => (string) ($item['country'] ?? ''),
        ],
        is_array($data) ? $data : []
    );
} elseif ($operacion === 'estados') {
    $locations = array_map(
        static fn (array $item): array => [
            'codigo' => (string) ($item['state_code'] ?? $item['name'] ?? ''),
            'nombre' => (string) ($item['name'] ?? ''),
        ],
        is_array($data['states'] ?? null) ? $data['states'] : []
    );
} else {
    $locations = array_map(
        static fn (string $item): array => ['codigo' => $item, 'nombre' => $item],
        array_values(array_filter(is_array($data) ? $data : [], 'is_string'))
    );
}

if ($buscar !== '') {
    $locations = array_values(array_filter(
        $locations,
        static fn (array $location): bool => str_contains(mb_strtolower($location['nombre'], 'UTF-8'), $buscar)
    ));
}

usort($locations, static fn (array $left, array $right): int => strcasecmp($left['nombre'], $right['nombre']));
echo json_encode($locations, JSON_UNESCAPED_UNICODE);
