<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

$redirect = isStudent()
	? '/cepre_untels/public/login_alumnos.php'
	: '/cepre_untels/public/login.php';
logout();
header('Location: ' . $redirect);
exit;
