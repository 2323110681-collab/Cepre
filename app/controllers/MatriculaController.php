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
        $matriculaRegistrada = null;

        try {
            $model = new MatriculaModel();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verifyCsrfToken($_POST['csrf_token'] ?? null);
                $registro = $model->registrar($_POST, $_FILES);
                header('Location: /cepre_untels/public/test_index.php?registrado=1&matricula_id=' . (int) $registro['matricula_id'] . '&numero=' . urlencode($registro['codigo_alumno']));
                exit;
            }

            $registroExitoso = ($_GET['registrado'] ?? '') === '1';
            $numeroRegistrado = $registroExitoso ? (string) ($_GET['numero'] ?? '') : null;
            $matriculaRegistrada = $registroExitoso ? (int) ($_GET['matricula_id'] ?? 0) : null;
            $catalogos = $model->catalogos();
            $numeroMatricula = $model->siguienteNumero();
            $turnoRegularId = (int) ($catalogos['turnos'][0]['id'] ?? 0);
            $turnoEscolarId = 0;
            foreach ($catalogos['turnos'] as $turno) {
                if (stripos((string) $turno['nombre'], 'Escolar') !== false) $turnoEscolarId = (int) $turno['id'];
            }
            $codigoCepreRegular = $model->siguienteCodigoCepre($turnoRegularId, '2027-I');
            $codigoCepreEscolar = $model->siguienteCodigoCepre($turnoEscolarId, '2027-I');
            $codigoCepre = $codigoCepreRegular;
        } catch (Throwable $exception) {
            $databaseReady = false;
            error_log('CEPRE matrícula: ' . get_class($exception) . ' - ' . $exception->getMessage());
            $errorMessage = $exception instanceof InvalidArgumentException || $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'No se pudo procesar la matrícula. Verifique los datos e inténtelo nuevamente.';
            $numeroMatricula = '00001';
            try {
                $catalogos = (new MatriculaModel())->catalogos();
            } catch (Throwable) {
                $catalogos = [];
            }
        }

        if ($registroExitoso && $matriculaRegistrada > 0) {
            $ficha = $model->fichaEstudiante($matriculaRegistrada);
            require __DIR__ . '/../views/matricula/ficha-confirmacion.php';
            return;
        }

        require __DIR__ . '/../views/matricula/formulario.php';
    }
}
