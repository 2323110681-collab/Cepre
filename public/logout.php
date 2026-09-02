<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

logout();
header('Location: /cepre_untels/public/login.php');
exit;
