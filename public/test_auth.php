<?php
require_once __DIR__ . '/../../config/auth.php';
startSession();
$_SESSION['user_id'] = 1;
$_SESSION['user'] = ['id' => 1, 'rol' => 'ALUMNO', 'nombres' => 'Test', 'apellido_paterno' => 'User'];
echo "Authenticated";
