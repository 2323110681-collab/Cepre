<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../app/models/MatriculaModel.php';

requireAuthentication();

$matriculaId = filter_var($_GET['matricula_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($matriculaId === false || $matriculaId === null) {
    http_response_code(404);
    exit;
}

$tipo = ($_GET['tipo'] ?? 'foto') === 'certificado' ? 'certificado' : 'foto';
$archivo = $tipo === 'certificado'
    ? (new MatriculaModel())->archivoCertificado($matriculaId)
    : (new MatriculaModel())->archivoFoto($matriculaId);
$storageRoot = realpath(__DIR__ . '/../app/storage/matriculas');
$filePath = $archivo !== null ? realpath((string) $archivo['ruta']) : false;
$allowedMimeTypes = $tipo === 'certificado'
    ? ['image/jpeg', 'image/png', 'application/pdf']
    : ['image/jpeg', 'image/png'];

if (!$storageRoot || !$filePath || !str_starts_with($filePath, $storageRoot . DIRECTORY_SEPARATOR)
    || !in_array($archivo['mime_type'], $allowedMimeTypes, true) || !is_file($filePath)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $archivo['mime_type']);
header('Content-Length: ' . (string) filesize($filePath));
header('X-Content-Type-Options: nosniff');
if ($tipo === 'certificado' && $archivo['mime_type'] === 'application/pdf') {
    header('Content-Disposition: inline; filename="certificado.pdf"');
}
readfile($filePath);
