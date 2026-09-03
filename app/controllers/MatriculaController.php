<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/MatriculaModel.php';
require_once __DIR__ . '/../../config/auth.php';

final class MatriculaController
{
    public function index(): void
    {
        $catalogos = [];
        $databaseReady = true;
        $errorMessage = null;
        $registroExitoso = false;
        $numeroRegistrado = null;

        try {
            $model = new MatriculaModel();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verifyCsrfToken($_POST['csrf_token'] ?? null);
                $numeroRegistrado = $model->registrar($_POST, $_FILES);
                header('Location: /cepre_untels/public/?registrado=1&numero=' . urlencode($numeroRegistrado));
                exit;
            }

            $registroExitoso = ($_GET['registrado'] ?? '') === '1';
            $numeroRegistrado = $registroExitoso ? (string) ($_GET['numero'] ?? '') : null;
            $catalogos = $model->catalogos();
            $numeroMatricula = $model->siguienteNumero();
            $codigoCepre = $numeroMatricula;
        } catch (Throwable $exception) {
            $databaseReady = false;
            $errorMessage = 'No se pudo procesar la matrícula. Verifique los datos e inténtelo nuevamente.';
            $numeroMatricula = '00001';
        }

        require __DIR__ . '/../views/matricula/formulario.php';
    }
}
