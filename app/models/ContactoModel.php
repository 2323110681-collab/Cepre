<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class ContactoModel
{
    private PDO $connection;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? database();
    }

    public function registrar(string $nombre, string $correo, string $telefono, string $mensaje): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO consultas_contacto (nombre, correo, telefono, mensaje, estado)
             VALUES (:nombre, :correo, :telefono, :mensaje, "PENDIENTE")'
        );
        $statement->execute([
            'nombre' => $nombre,
            'correo' => $correo,
            'telefono' => $telefono,
            'mensaje' => $mensaje,
        ]);
    }
}
