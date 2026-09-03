<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
$reniecConfig = require __DIR__ . '/../../config/reniec.php';

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'La sesión ha expirado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$dni = preg_replace('/\D/', '', (string) ($_GET['numero'] ?? ''));
if ($dni === null || strlen($dni) !== 8) {
    http_response_code(400);
    echo json_encode(['error' => 'El DNI debe tener 8 dígitos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$token = trim((string) ($reniecConfig['token'] ?? ''));
if ($token === '' || $token === 'PEGA_AQUI_TU_TOKEN_NUEVO') {
    http_response_code(500);
    echo json_encode(['error' => 'La consulta de DNI no está configurada en el servidor.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$url = 'https://api.decolecta.com/v1/reniec/dni?numero=' . rawurlencode($dni);
$headers = "Accept: application/json\r\nAuthorization: Bearer {$token}\r\n";
$status = 0;

if (function_exists('curl_init')) {
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Referer: https://apis.net.pe/consulta-dni-api',
            'Authorization: Bearer ' . $token,
        ],
    ]);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
} else {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => $headers . "Referer: https://apis.net.pe/consulta-dni-api\r\n",
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    $curlError = $response === false ? 'HTTP request failed' : '';
    $statusLine = $http_response_header[0] ?? '';
    preg_match('/\s(\d{3})\s/', $statusLine, $statusMatches);
    $status = (int) ($statusMatches[1] ?? 0);
}

if ($response === false || $curlError !== '' || $status === 0) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo conectar con el servicio de RENIEC.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$person = json_decode($response, true);
if ($status < 200 || $status >= 300 || !is_array($person)) {
    http_response_code($status >= 400 && $status < 500 ? $status : 502);
    echo json_encode(['error' => 'No se encontraron datos para ese DNI.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'apellido_paterno' => trim((string) ($person['first_last_name'] ?? $person['apellido_paterno'] ?? '')),
    'apellido_materno' => trim((string) ($person['second_last_name'] ?? $person['apellido_materno'] ?? '')),
    'nombres' => trim((string) ($person['first_name'] ?? $person['nombres'] ?? '')),
], JSON_UNESCAPED_UNICODE);