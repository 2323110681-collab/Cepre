<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class MatriculaModel
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = database();
    }

    public function catalogos(): array
    {
        return [
            'carreras' => $this->connection->query("SELECT id_carrera AS id, nombre_carrera AS nombre FROM carreras WHERE estado = 'ACTIVO' ORDER BY nombre_carrera")->fetchAll(),
            'condiciones' => $this->connection->query('SELECT id, nombre FROM condiciones_matricula ORDER BY id')->fetchAll(),
            'turnos' => $this->connection->query('SELECT id, nombre FROM turnos ORDER BY id')->fetchAll(),
            'modalidades' => $this->connection->query('SELECT id, nombre FROM modalidades_clase ORDER BY id')->fetchAll(),
            'sectores' => $this->connection->query('SELECT id, nombre FROM sectores ORDER BY id')->fetchAll(),
            'preparaciones' => $this->connection->query('SELECT id, nombre FROM preparaciones_previas ORDER BY id')->fetchAll(),
            'departamentos' => $this->connection->query("SELECT TRIM(codigo) AS codigo, nombre FROM ubigeos WHERE nivel = 'DEPARTAMENTO' ORDER BY nombre")->fetchAll(),
        ];
    }

    public function siguienteNumero(): string
    {
        $statement = $this->connection->query("SELECT COALESCE(MAX(CAST(numero AS UNSIGNED)), 0) + 1 FROM matriculas");
        $nextNumber = (int) $statement->fetchColumn();

        return str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function registrar(array $data, array $files): string
    {
        $this->connection->beginTransaction();

        try {
            $numero = $this->siguienteNumero();
            $carreraStatement = $this->connection->prepare('SELECT nombre_carrera FROM carreras WHERE id_carrera = :id AND estado = "ACTIVO"');
            $carreraStatement->execute(['id' => (int) ($data['carrera_id'] ?? 0)]);
            $carrera = $carreraStatement->fetchColumn();
            if ($carrera === false) {
                throw new RuntimeException('La carrera seleccionada no es válida.');
            }

            $studentStatement = $this->connection->prepare(
                'INSERT INTO estudiantes (
                    numero_matricula, apellido_paterno, apellido_materno, nombres,
                    tipo_documento, numero_documento, sexo, fecha_nacimiento, email,
                    telefono_casa, telefono_celular, departamento_actual, provincia_actual,
                    distrito_actual, direccion_actual, pais_nacimiento, departamento_nacimiento,
                    provincia_nacimiento, distrito_nacimiento, anio_concluye_secundaria,
                    institucion_educativa, preparacion_anterior, mencion, carrera_postula,
                    modalidad, condicion, turno
                ) VALUES (
                    :numero, :apellido_paterno, :apellido_materno, :nombres,
                    :tipo_documento, :numero_documento, :sexo, :fecha_nacimiento, :email,
                    :telefono_casa, :telefono_celular, :departamento_actual, :provincia_actual,
                    :distrito_actual, :direccion_actual, :pais_nacimiento, :departamento_nacimiento,
                    :provincia_nacimiento, :distrito_nacimiento, :anio_concluye_secundaria,
                    :institucion_educativa, :preparacion_anterior, :mencion, :carrera_postula,
                    "REGULAR", :condicion, :turno
                )'
            );
            $studentStatement->execute([
                'numero' => $numero,
                'apellido_paterno' => trim((string) ($data['apellido_paterno'] ?? '')),
                'apellido_materno' => trim((string) ($data['apellido_materno'] ?? '')),
                'nombres' => trim((string) ($data['nombres'] ?? '')),
                'tipo_documento' => $data['tipo_documento'] ?? 'DNI',
                'numero_documento' => trim((string) ($data['numero_documento'] ?? '')),
                'sexo' => $data['sexo'] ?? 'MASCULINO',
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'email' => trim((string) ($data['correo'] ?? '')),
                'telefono_casa' => trim((string) ($data['telefono_casa'] ?? '')) ?: null,
                'telefono_celular' => trim((string) ($data['telefono_celular'] ?? '')),
                'departamento_actual' => trim((string) ($data['departamento_actual_nombre'] ?? $data['departamento_actual'] ?? '')) ?: null,
                'provincia_actual' => trim((string) ($data['provincia_actual_nombre'] ?? $data['provincia_actual'] ?? '')) ?: null,
                'distrito_actual' => trim((string) ($data['distrito_actual_nombre'] ?? $data['distrito_actual'] ?? '')) ?: null,
                'direccion_actual' => trim((string) ($data['direccion_actual'] ?? '')) ?: null,
                'pais_nacimiento' => trim((string) ($data['pais_nacimiento'] ?? 'Perú')) ?: null,
                'departamento_nacimiento' => trim((string) ($data['departamento_nacimiento_nombre'] ?? $data['departamento_nacimiento'] ?? '')) ?: null,
                'provincia_nacimiento' => trim((string) ($data['provincia_nacimiento_nombre'] ?? $data['provincia_nacimiento'] ?? '')) ?: null,
                'distrito_nacimiento' => trim((string) ($data['distrito_nacimiento_nombre'] ?? $data['distrito_nacimiento'] ?? '')) ?: null,
                'anio_concluye_secundaria' => ($data['anio_conclusion_secundaria'] ?? '') !== '' ? (int) $data['anio_conclusion_secundaria'] : null,
                'institucion_educativa' => trim((string) ($data['nombre_institucion'] ?? '')) ?: null,
                'preparacion_anterior' => $this->catalogName('preparaciones_previas', $data['preparacion_previa_id'] ?? 0),
                'mencion' => trim((string) ($data['mencion'] ?? '')) ?: null,
                'carrera_postula' => $carrera,
                'condicion' => $this->catalogName('condiciones_matricula', $data['condicion_id'] ?? 0),
                'turno' => $this->catalogName('turnos', $data['turno_id'] ?? 0),
            ]);
            $estudianteId = (int) $this->connection->lastInsertId();

            $periodoId = (int) $this->connection->query('SELECT id FROM periodos WHERE activo = 1 ORDER BY fecha_inicio DESC LIMIT 1')->fetchColumn();
            $matriculaStatement = $this->connection->prepare(
                'INSERT INTO matriculas (numero, estudiante_id, periodo_id, condicion_id, turno_id, modalidad_clase_id, carrera_id, estado)
                 VALUES (:numero, :estudiante, :periodo, :condicion, :turno, :modalidad, :carrera, "CONFIRMADA")'
            );
            $matriculaStatement->execute([
                'numero' => $numero,
                'estudiante' => $estudianteId,
                'periodo' => $periodoId,
                'condicion' => (int) ($data['condicion_id'] ?? 0),
                'turno' => (int) ($data['turno_id'] ?? 0),
                'modalidad' => (int) ($data['modalidad_clase_id'] ?? 1),
                'carrera' => (int) $data['carrera_id'],
            ]);
            $matriculaId = (int) $this->connection->lastInsertId();

            $academicStatement = $this->connection->prepare(
                'INSERT INTO informacion_academica (
                    matricula_id, anio_conclusion_secundaria, pais, sector_id,
                    especificar_sector, nombre_institucion, nombre_institucion_extranjera,
                    preparacion_previa_id, mencion
                ) VALUES (:matricula, :anio, :pais, :sector, :especificar, :institucion, :institucion_extranjera, :preparacion, :mencion)'
            );
            $academicStatement->execute([
                'matricula' => $matriculaId,
                'anio' => (int) ($data['anio_conclusion_secundaria'] ?: date('Y')),
                'pais' => trim((string) ($data['pais_estudios'] ?? 'Perú')),
                'sector' => ($data['sector_id'] ?? '') !== '' ? (int) $data['sector_id'] : null,
                'especificar' => trim((string) ($data['especificar_sector'] ?? '')) ?: null,
                'institucion' => trim((string) ($data['nombre_institucion'] ?? '')) ?: null,
                'institucion_extranjera' => trim((string) ($data['nombre_institucion_extranjera'] ?? '')) ?: null,
                'preparacion' => ($data['preparacion_previa_id'] ?? '') !== '' ? (int) $data['preparacion_previa_id'] : null,
                'mencion' => trim((string) ($data['mencion'] ?? '')) ?: null,
            ]);

            $this->connection->commit();
            return $numero;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    private function catalogName(string $table, mixed $id): string
    {
        $allowedTables = ['condiciones_matricula', 'turnos', 'preparaciones_previas'];
        if (!in_array($table, $allowedTables, true)) {
            throw new InvalidArgumentException('Catálogo no permitido.');
        }
        $statement = $this->connection->prepare("SELECT nombre FROM {$table} WHERE id = :id");
        $statement->execute(['id' => (int) $id]);
        $name = $statement->fetchColumn();
        if ($name === false) {
            throw new RuntimeException('Seleccione una condición y un turno válidos.');
        }
        return (string) $name;
    }
}
