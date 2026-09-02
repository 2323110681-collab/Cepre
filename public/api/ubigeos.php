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

$baseUrl = 'https://raw.githubusercontent.com/joseluisq/ubigeos-peru/master/json/';
$departments = json_decode((string) file_get_contents($baseUrl . 'departamentos.json'), true);
$provinces = json_decode((string) file_get_contents($baseUrl . 'provincias.json'), true);
$districts = json_decode((string) file_get_contents($baseUrl . 'distritos.json'), true);
$departments = is_array($departments) ? $departments : [];
$provinces = is_array($provinces) ? $provinces : [];
$districts = is_array($districts) ? $districts : [];

$parentDepartment = null;
foreach ($departments as $department) {
    if (($department['codigo_ubigeo'] ?? '') === $parent) {
        $parentDepartment = $department;
        break;
    }
}

$locations = [];
if ($parentDepartment !== null) {
    foreach ($provinces as $province) {
        if (($province['id_padre_ubigeo'] ?? '') === ($parentDepartment['id_ubigeo'] ?? '')) {
            $locations[] = ['codigo' => $province['id_ubigeo'], 'nombre' => $province['nombre_ubigeo']];
        }
    }
} else {
    foreach ($provinces as $province) {
        if (($province['id_ubigeo'] ?? '') === $parent) {
            foreach ($districts as $district) {
                if (($district['id_padre_ubigeo'] ?? '') === $parent) {
                    $locations[] = ['codigo' => $district['id_ubigeo'], 'nombre' => $district['nombre_ubigeo']];
                }
            }
            break;
        }
    }
}

if ($locations === []) {
    $statement = database()->prepare(
        "SELECT TRIM(codigo) AS codigo, nombre FROM ubigeos WHERE codigo_padre = :padre ORDER BY nombre"
    );
    $statement->execute(['padre' => $parent]);
    $locations = $statement->fetchAll();
}

echo json_encode($locations, JSON_UNESCAPED_UNICODE);
