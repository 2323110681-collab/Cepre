<?php

declare(strict_types=1);

final class InicioController
{
    public function index(): void
    {
        require __DIR__ . '/../views/matricula/formulario.php';
    }
}
