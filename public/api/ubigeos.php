<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$parent = trim((string) ($_GET['padre'] ?? ''));

if ($parent === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Debe indicar un departamento o provincia.']);
    exit;
}

$basePath = __DIR__ . '/../../app/data/ubigeos/';
$departments = json_decode((string) file_get_contents($basePath . 'departamentos.json'), true);
$provinces = json_decode((string) file_get_contents($basePath . 'provincias.json'), true);
$districts = json_decode((string) file_get_contents($basePath . 'distritos.json'), true);
$departments = is_array($departments) ? $departments : [];
$provinces = is_array($provinces) ? $provinces : [];
$districts = is_array($districts) ? $districts : [];

$locations = [];
$parentId = null;
foreach ($departments as $department) {
    if (($department['codigo_ubigeo'] ?? '') === $parent) {
        $parentId = (string) ($department['id_ubigeo'] ?? '');
        break;
    }
}

if ($parentId !== null) {
    $locations = array_map(
        static fn (array $location): array => [
            'codigo' => $location['id_ubigeo'],
            'nombre' => $location['nombre_ubigeo'],
        ],
        $provinces[$parentId] ?? []
    );
} else {
    $locations = array_map(
        static fn (array $location): array => [
            'codigo' => $location['id_ubigeo'],
            'nombre' => $location['nombre_ubigeo'],
        ],
        $districts[$parent] ?? []
    );
}

if ($locations === []) {
    $statement = database()->prepare(
        "SELECT TRIM(codigo) AS codigo, nombre FROM ubigeos WHERE codigo_padre = :padre ORDER BY nombre"
    );
    $statement->execute(['padre' => $parent]);
    $locations = $statement->fetchAll();
}

echo json_encode($locations, JSON_UNESCAPED_UNICODE);
