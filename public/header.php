<?php
// Valores por defecto por si no se definen en la página
$titulo = $titulo ?? 'CEPRE UNTELS';
$pagina = $pagina ?? 'inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $titulo; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Alatsi&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/styles.css?v=20260908">
</head>
<body>

<?php require __DIR__ . '/../app/views/partials/public-header.php'; ?>