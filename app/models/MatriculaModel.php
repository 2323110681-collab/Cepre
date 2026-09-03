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
        $districtNames = $this->districtNames();
        $districts = $this->connection->query(
            "SELECT DISTINCT TRIM(distrito_actual) AS id
             FROM estudiantes
             WHERE distrito_actual IS NOT NULL AND distrito_actual <> ''"
        )->fetchAll();
        foreach ($districts as &$district) {
            $district['nombre'] = $districtNames[$district['id']] ?? $district['id'];
        }
        unset($district);
        usort($districts, static fn (array $left, array $right): int => strcasecmp($left['nombre'], $right['nombre']));

        return [
            'carreras' => $this->connection->query("SELECT id_carrera AS id, nombre_carrera AS nombre FROM carreras WHERE estado = 'ACTIVO' ORDER BY nombre_carrera")->fetchAll(),
            'condiciones' => $this->connection->query('SELECT id, nombre FROM condiciones_matricula ORDER BY id')->fetchAll(),
            'turnos' => $this->connection->query("SELECT id, CASE nombre WHEN 'MANANA' THEN 'Turno Mañana: 8:00 a.m. a 1:30 p.m.' WHEN 'TARDE' THEN 'Turno Tarde: 2:30 p.m. a 8:00 p.m.' WHEN 'ESCOLAR' THEN 'Turno Escolar: lunes a viernes 7:00 p.m. a 9:40 p.m. y sábado 8:00 a.m. a 1:20 p.m.' END AS nombre FROM turnos WHERE nombre IN ('MANANA', 'TARDE', 'ESCOLAR') ORDER BY id")->fetchAll(),
            'periodos' => $this->connection->query('SELECT id, nombre FROM periodos ORDER BY fecha_inicio, id')->fetchAll(),
            'modalidades' => $this->connection->query('SELECT id, nombre FROM modalidades_clase ORDER BY id')->fetchAll(),
            'sectores' => $this->connection->query('SELECT id, nombre FROM sectores ORDER BY id')->fetchAll(),
            'preparaciones' => $this->connection->query('SELECT id, nombre FROM preparaciones_previas ORDER BY id')->fetchAll(),
            'departamentos' => $this->connection->query("SELECT TRIM(codigo) AS codigo, nombre FROM ubigeos WHERE nivel = 'DEPARTAMENTO' ORDER BY nombre")->fetchAll(),
            'distritos' => $districts,
        ];
    }

    private function districtNames(): array
    {
        $path = __DIR__ . '/../data/ubigeos/distritos.json';
        $districts = json_decode((string) file_get_contents($path), true);
        $names = [];
        foreach (is_array($districts) ? $districts : [] as $locations) {
            foreach (is_array($locations) ? $locations : [] as $location) {
                if (!empty($location['id_ubigeo']) && !empty($location['nombre_ubigeo'])) {
                    $names[(string) $location['id_ubigeo']] = (string) $location['nombre_ubigeo'];
                }
            }
        }

        return $names;
    }

    public function reportes(
        ?int $periodoId,
        ?string $desde,
        ?string $hasta,
        ?int $carreraId = null,
        ?string $sexo = null,
        ?int $sectorId = null,
        ?string $distrito = null
    ): array
    {
        $filters = ['m.estado <> "ANULADA"'];
        $parameters = [];
        if ($periodoId !== null) {
            $filters[] = 'm.periodo_id = :periodo';
            $parameters['periodo'] = $periodoId;
        }
        if ($desde !== null) {
            $filters[] = 'm.fecha_registro >= :desde';
            $parameters['desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== null) {
            $filters[] = 'm.fecha_registro <= :hasta';
            $parameters['hasta'] = $hasta . ' 23:59:59';
        }
        if ($carreraId !== null) {
            $filters[] = 'm.carrera_id = :carrera';
            $parameters['carrera'] = $carreraId;
        }
        if ($sexo !== null && $sexo !== '') {
            $filters[] = 'e.sexo = :sexo';
            $parameters['sexo'] = $sexo;
        }
        if ($sectorId !== null) {
            $filters[] = 'ia.sector_id = :sector';
            $parameters['sector'] = $sectorId;
        }
        if ($distrito !== null && $distrito !== '') {
            $filters[] = 'e.distrito_actual = :distrito';
            $parameters['distrito'] = $distrito;
        }
        $where = implode(' AND ', $filters);
        $query = function (string $select, string $groupBy = '', string $orderBy = '') use ($where, $parameters): array {
            $sql = "SELECT {$select} FROM matriculas m
                    INNER JOIN estudiantes e ON e.id_estudiante = m.estudiante_id
                    LEFT JOIN informacion_academica ia ON ia.matricula_id = m.id
                    LEFT JOIN carreras c ON c.id_carrera = m.carrera_id
                    LEFT JOIN sectores s ON s.id = ia.sector_id
                    LEFT JOIN ubigeos d ON TRIM(d.codigo) = TRIM(e.distrito_actual)
                    WHERE {$where} {$groupBy} {$orderBy}";
            $statement = $this->connection->prepare($sql);
            $statement->execute($parameters);
            return $statement->fetchAll();
        };

        $daily = $query('DATE(m.fecha_registro) AS fecha, COUNT(*) AS total', 'GROUP BY DATE(m.fecha_registro)', 'ORDER BY fecha');
        $dailyByCareer = $query('DATE(m.fecha_registro) AS fecha, COALESCE(c.nombre_carrera, "No registrado") AS carrera, COUNT(*) AS total', 'GROUP BY DATE(m.fecha_registro), c.nombre_carrera', 'ORDER BY fecha, total DESC, carrera');
        $dailyBySector = $query('DATE(m.fecha_registro) AS fecha, COALESCE(s.nombre, "No registrado") AS sector, COUNT(*) AS total', 'GROUP BY DATE(m.fecha_registro), s.nombre', 'ORDER BY fecha, total DESC');
        $cumulative = 0;
        $dailyIndex = [];
        foreach ($daily as &$row) {
            $cumulative += (int) $row['total'];
            $row['total'] = (int) $row['total'];
            $row['acumulado'] = $cumulative;
            $dailyIndex[$row['fecha']] = $row;
        }
        unset($row);
        foreach ($dailyByCareer as $row) {
            if (!isset($dailyIndex[$row['fecha']]['carrera_top'])) {
                $dailyIndex[$row['fecha']]['carrera_top'] = $row['carrera'];
            }
        }
        foreach ($dailyBySector as $row) {
            if ($row['sector'] === 'PUBLICO') {
                $dailyIndex[$row['fecha']]['publico'] = (int) $row['total'];
            } elseif ($row['sector'] === 'PRIVADO') {
                $dailyIndex[$row['fecha']]['privado'] = (int) $row['total'];
            }
        }
        foreach ($dailyIndex as &$row) {
            $row['carrera_top'] ??= 'No registrado';
            $row['publico'] ??= 0;
            $row['privado'] ??= 0;
        }
        unset($row);

        $total = $cumulative;
        return [
            'total' => $total,
            'por_dia' => array_values($dailyIndex),
            'por_sexo' => $query('COALESCE(NULLIF(e.sexo, ""), "No registrado") AS etiqueta, COUNT(*) AS total', 'GROUP BY e.sexo', 'ORDER BY total DESC'),
            'por_carrera' => $query('COALESCE(c.nombre_carrera, "No registrado") AS etiqueta, COUNT(*) AS total', 'GROUP BY c.nombre_carrera', 'ORDER BY total DESC, etiqueta'),
            'por_distrito' => $this->districtReport($query('COALESCE(NULLIF(e.distrito_actual, ""), "No registrado") AS etiqueta, COUNT(*) AS total', 'GROUP BY e.distrito_actual', 'ORDER BY total DESC, etiqueta')),
            'por_sector' => $query('COALESCE(s.nombre, "No registrado") AS etiqueta, COUNT(*) AS total', 'GROUP BY s.nombre', 'ORDER BY total DESC, etiqueta'),
            'por_conocimiento' => $query('COALESCE(NULLIF(ia.como_se_entero_cepre, ""), "No registrado") AS etiqueta, COUNT(*) AS total', 'GROUP BY ia.como_se_entero_cepre', 'ORDER BY total DESC, etiqueta'),
        ];
    }

    private function districtReport(array $rows): array
    {
        $names = $this->districtNames();
        foreach ($rows as &$row) {
            $row['etiqueta'] = $names[(string) $row['etiqueta']] ?? $row['etiqueta'];
        }
        unset($row);
        usort($rows, static fn (array $left, array $right): int => (int) $right['total'] <=> (int) $left['total'] ?: strcasecmp($left['etiqueta'], $right['etiqueta']));

        return $rows;
    }

    public function siguienteNumero(): string
    {
        $statement = $this->connection->query("SELECT COALESCE(MAX(CAST(numero AS UNSIGNED)), 0) + 1 FROM matriculas");
        $nextNumber = (int) $statement->fetchColumn();

        return str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function siguienteCodigoCepre(int $turnoId, string $periodo): string
    {
        $turno = $this->connection->prepare('SELECT nombre FROM turnos WHERE id = :id LIMIT 1');
        $turno->execute(['id' => $turnoId]);
        $turnoNombre = (string) $turno->fetchColumn();
        $prefix = $turnoNombre === 'ESCOLAR' ? '2' : '1';

        $statement = $this->connection->prepare(
            'SELECT COALESCE(MAX(CAST(RIGHT(codigo_estudiante, 4) AS UNSIGNED)), 0) + 1
             FROM estudiantes WHERE CHAR_LENGTH(codigo_estudiante) = 5 AND codigo_estudiante LIKE :prefix'
        );
        $statement->execute(['prefix' => $prefix . '%']);

        $correlative = (int) $statement->fetchColumn();
        if ($correlative > 9999) {
            throw new RuntimeException('Se alcanzó el máximo de códigos disponibles para este turno.');
        }

        return $prefix . str_pad((string) $correlative, 4, '0', STR_PAD_LEFT);
    }

    public function listarEstudiantes(?int $carreraId = null): array
    {
        $sql = 'SELECT m.id AS matricula_id, m.numero, e.id_estudiante, e.codigo_estudiante,
                       e.apellido_paterno, e.apellido_materno, e.nombres, c.nombre_carrera
                FROM matriculas m
                INNER JOIN estudiantes e ON e.id_estudiante = m.estudiante_id
                INNER JOIN carreras c ON c.id_carrera = m.carrera_id
                WHERE m.estado <> "ANULADA"';
        $parameters = [];
        if ($carreraId !== null) {
            $sql .= ' AND m.carrera_id = :carrera';
            $parameters['carrera'] = $carreraId;
        }
        $sql .= ' ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres';
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function fichaEstudiante(int $matriculaId): ?array
    {
        $statement = $this->connection->prepare(
                'SELECT m.id AS matricula_id, m.numero, m.estado, m.fecha_registro,
                    m.carrera_id, m.modalidad_clase_id, m.condicion_id, m.turno_id,
                    e.*, c.nombre_carrera, cm.nombre AS condicion_nombre,
                    t.nombre AS turno_nombre, mc.nombre AS modalidad_nombre,
                    ia.anio_conclusion_secundaria, ia.pais AS pais_estudios,
                    ia.departamento_ubigeo AS departamento_estudios,
                    ia.provincia_ubigeo AS provincia_estudios,
                    ia.distrito_ubigeo AS distrito_estudios,
                    s.nombre AS sector_nombre, ia.especificar_sector,
                    ia.nombre_institucion, ia.nombre_institucion_extranjera,
                    pp.nombre AS preparacion_nombre, ia.mencion AS mencion_academica,
                    ia.tiene_discapacidad, ia.tipo_discapacidad, ia.otro_tipo_discapacidad,
                    ia.grado_discapacidad, ia.necesidades_especiales,
                    ia.tiene_certificado_discapacidad, ia.como_se_entero_cepre,
                    af.ruta AS foto_ruta, af.mime_type AS foto_mime,
                    cert.ruta AS certificado_ruta
             FROM matriculas m
             INNER JOIN estudiantes e ON e.id_estudiante = m.estudiante_id
             INNER JOIN carreras c ON c.id_carrera = m.carrera_id
             LEFT JOIN condiciones_matricula cm ON cm.id = m.condicion_id
             LEFT JOIN turnos t ON t.id = m.turno_id
             LEFT JOIN modalidades_clase mc ON mc.id = m.modalidad_clase_id
             LEFT JOIN informacion_academica ia ON ia.matricula_id = m.id
             LEFT JOIN sectores s ON s.id = ia.sector_id
             LEFT JOIN preparaciones_previas pp ON pp.id = ia.preparacion_previa_id
             LEFT JOIN archivos_matricula af ON af.matricula_id = m.id
                 AND af.tipo_archivo_id = (SELECT id FROM tipos_archivo WHERE nombre = "FOTO_CARNET" LIMIT 1)
             LEFT JOIN archivos_matricula cert ON cert.matricula_id = m.id
                 AND cert.tipo_archivo_id = (SELECT id FROM tipos_archivo WHERE nombre = "CERTIFICADO_DISCAPACIDAD" LIMIT 1)
             WHERE m.id = :matricula AND m.estado <> "ANULADA"
             LIMIT 1'
        );
        $statement->execute(['matricula' => $matriculaId]);
        $ficha = $statement->fetch();

        if ($ficha !== false) {
            $districtNames = $this->districtNames();
            $ficha['distrito_actual_nombre'] = $districtNames[(string) ($ficha['distrito_actual'] ?? '')]
                ?? ($ficha['distrito_actual'] ?? null);
            $ficha['distrito_nacimiento_nombre'] = $districtNames[(string) ($ficha['distrito_nacimiento'] ?? '')]
                ?? ($ficha['distrito_nacimiento'] ?? null);
            $ficha['distrito_estudios_nombre'] = $districtNames[(string) ($ficha['distrito_estudios'] ?? '')]
                ?? ($ficha['distrito_estudios'] ?? null);
        }

        return $ficha === false ? null : $ficha;
    }

    public function archivoFoto(int $matriculaId): ?array
    {
        return $this->archivoPorTipo($matriculaId, 'FOTO_CARNET');
    }

    public function archivoCertificado(int $matriculaId): ?array
    {
        return $this->archivoPorTipo($matriculaId, 'CERTIFICADO_DISCAPACIDAD');
    }

    private function archivoPorTipo(int $matriculaId, string $tipo): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT af.ruta, af.mime_type
             FROM archivos_matricula af
             INNER JOIN matriculas m ON m.id = af.matricula_id
             INNER JOIN tipos_archivo ta ON ta.id = af.tipo_archivo_id
             WHERE af.matricula_id = :matricula AND ta.nombre = :tipo
               AND m.estado <> "ANULADA"
             LIMIT 1'
        );
        $statement->execute(['matricula' => $matriculaId, 'tipo' => $tipo]);
        $archivo = $statement->fetch();

        return $archivo === false ? null : $archivo;
    }

    public function actualizarFicha(int $matriculaId, array $data, array $files = []): void
    {
        foreach (['correo', 'telefono_celular'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new InvalidArgumentException('Complete todos los campos obligatorios.');
            }
        }
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }
        $this->connection->beginTransaction();
        try {
            $lookup = $this->connection->prepare('SELECT estudiante_id FROM matriculas WHERE id = :id AND estado <> "ANULADA"');
            $lookup->execute(['id' => $matriculaId]);
            $estudianteId = $lookup->fetchColumn();
            if ($estudianteId === false) throw new RuntimeException('La ficha no existe.');

            $student = $this->connection->prepare(
                'UPDATE estudiantes SET email = :email, telefono_casa = :casa,
                 telefono_celular = :celular, distrito_actual = :distrito_actual,
                 direccion_actual = :direccion, pais_nacimiento = :pais_nacimiento,
                 anio_concluye_secundaria = :anio,
                 institucion_educativa = :institucion, preparacion_anterior = :preparacion,
                 mencion = :mencion WHERE id_estudiante = :estudiante'
            );
            $student->execute([
                'email' => trim((string) $data['correo']),
                'casa' => trim((string) ($data['telefono_casa'] ?? '')) ?: null, 'celular' => trim((string) $data['telefono_celular']),
                'distrito_actual' => trim((string) ($data['distrito_actual'] ?? '')) ?: null,
                'direccion' => trim((string) ($data['direccion_actual'] ?? '')) ?: null,
                'pais_nacimiento' => trim((string) ($data['pais_nacimiento'] ?? '')) ?: null,
                'anio' => ($data['anio_conclusion_secundaria'] ?? '') !== '' ? (int) $data['anio_conclusion_secundaria'] : null,
                'institucion' => trim((string) ($data['nombre_institucion'] ?? '')) ?: null,
                'preparacion' => trim((string) ($data['preparacion_anterior'] ?? '')) ?: null,
                'mencion' => trim((string) ($data['mencion'] ?? '')) ?: null, 'estudiante' => $estudianteId,
            ]);

            $registration = $this->connection->prepare(
                'UPDATE matriculas SET modalidad_clase_id = :modalidad,
                 condicion_id = :condicion, turno_id = :turno WHERE id = :id'
            );
            $registration->execute([
                'modalidad' => (int) $data['modalidad_clase_id'],
                'condicion' => (int) $data['condicion_id'], 'turno' => (int) $data['turno_id'], 'id' => $matriculaId,
            ]);

            $academic = $this->connection->prepare(
                'UPDATE informacion_academica SET anio_conclusion_secundaria = :anio, pais = :pais,
                 sector_id = :sector_id,
                 nombre_institucion = :institucion, preparacion_previa_id = :preparacion,
                 mencion = :mencion, especificar_sector = :sector,
                 tiene_discapacidad = :discapacidad, tipo_discapacidad = :tipo_discapacidad,
                 otro_tipo_discapacidad = :otro_tipo_discapacidad, grado_discapacidad = :grado_discapacidad,
                 necesidades_especiales = :necesidades_especiales,
                 tiene_certificado_discapacidad = :tiene_certificado, como_se_entero_cepre = :como_se_entero
                 WHERE matricula_id = :matricula'
            );
            $academic->execute([
                'anio' => ($data['anio_conclusion_secundaria'] ?? '') !== '' ? (int) $data['anio_conclusion_secundaria'] : date('Y'),
                'sector_id' => ($data['sector_id'] ?? '') !== '' ? (int) $data['sector_id'] : null,
                'pais' => trim((string) ($data['pais_estudios'] ?? 'Perú')), 'institucion' => trim((string) ($data['nombre_institucion'] ?? '')) ?: null,
                'preparacion' => ($data['preparacion_id'] ?? '') !== '' ? (int) $data['preparacion_id'] : null,
                'mencion' => trim((string) ($data['mencion'] ?? '')) ?: null, 'sector' => trim((string) ($data['especificar_sector'] ?? '')) ?: null,
                'discapacidad' => !empty($data['tiene_discapacidad']) ? 1 : 0,
                'tipo_discapacidad' => trim((string) ($data['tipo_discapacidad'] ?? '')) ?: null,
                'otro_tipo_discapacidad' => trim((string) ($data['otro_tipo_discapacidad'] ?? '')) ?: null,
                'grado_discapacidad' => trim((string) ($data['grado_discapacidad'] ?? '')) ?: null,
                'necesidades_especiales' => trim((string) ($data['necesidades_especiales'] ?? '')) ?: null,
                'tiene_certificado' => !empty($data['tiene_certificado_discapacidad']) ? 1 : 0,
                'como_se_entero' => $this->discoverySource($data),
                'matricula' => $matriculaId,
            ]);
            if (!empty($data['tiene_discapacidad']) && !empty($data['tiene_certificado_discapacidad'])) {
                $this->storeCertificate($files, $matriculaId);
            }
            $this->storePhoto($files, $matriculaId);
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function registrar(array $data, array $files): string
    {
        $this->validateData($data);
        $this->connection->beginTransaction();

        try {
            $numero = $this->siguienteNumero();
            $codigoAlumno = $this->siguienteCodigoCepre((int) $data['turno_id'], (string) $data['semestre']);
            $carreraStatement = $this->connection->prepare('SELECT nombre_carrera FROM carreras WHERE id_carrera = :id AND estado = "ACTIVO"');
            $carreraStatement->execute(['id' => (int) ($data['carrera_id'] ?? 0)]);
            $carrera = $carreraStatement->fetchColumn();
            if ($carrera === false) {
                throw new RuntimeException('La carrera seleccionada no es válida.');
            }

            $studentStatement = $this->connection->prepare(
                'INSERT INTO estudiantes (
                    numero_matricula, codigo_estudiante, apellido_paterno, apellido_materno, nombres,
                    tipo_documento, numero_documento, sexo, fecha_nacimiento, email,
                    telefono_casa, telefono_celular, departamento_actual, provincia_actual,
                    distrito_actual, direccion_actual, pais_nacimiento, departamento_nacimiento,
                    provincia_nacimiento, distrito_nacimiento, anio_concluye_secundaria,
                    institucion_educativa, preparacion_anterior, mencion, carrera_postula,
                    modalidad, condicion, turno
                ) VALUES (
                    :numero, :codigo_estudiante, :apellido_paterno, :apellido_materno, :nombres,
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
                'codigo_estudiante' => $codigoAlumno,
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
                'pais_nacimiento' => trim((string) (($data['pais_nacimiento'] ?? 'Perú') === 'Otro' ? ($data['pais_nacimiento_otro'] ?? '') : ($data['pais_nacimiento'] ?? 'Perú'))) ?: null,
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

            $periodoStatement = $this->connection->prepare('SELECT id FROM periodos WHERE nombre = :nombre AND activo = 1 LIMIT 1');
            $periodoStatement->execute(['nombre' => $data['semestre']]);
            $periodoId = (int) $periodoStatement->fetchColumn();
            if ($periodoId < 1) {
                throw new InvalidArgumentException('El semestre seleccionado no es válido.');
            }
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
                    matricula_id, anio_conclusion_secundaria, pais, departamento_ubigeo,
                    provincia_ubigeo, distrito_ubigeo, sector_id,
                    especificar_sector, nombre_institucion, nombre_institucion_extranjera,
                    preparacion_previa_id, mencion, tiene_discapacidad, tipo_discapacidad,
                    otro_tipo_discapacidad, grado_discapacidad, necesidades_especiales,
                    tiene_certificado_discapacidad, como_se_entero_cepre
                ) VALUES (:matricula, :anio, :pais, :departamento, :provincia, :distrito, :sector, :especificar, :institucion, :institucion_extranjera, :preparacion, :mencion, :discapacidad, :tipo_discapacidad, :otro_tipo_discapacidad, :grado_discapacidad, :necesidades_especiales, :tiene_certificado, :como_se_entero)'
            );
            $academicStatement->execute([
                'matricula' => $matriculaId,
                'anio' => (int) ($data['anio_conclusion_secundaria'] ?: date('Y')),
                'pais' => trim((string) ($data['pais_estudios'] ?? 'Perú')),
                'departamento' => $this->ubigeoOrNull($data['departamento_estudios'] ?? ''),
                'provincia' => $this->ubigeoOrNull($data['provincia_estudios'] ?? ''),
                'distrito' => $this->ubigeoOrNull($data['distrito_estudios'] ?? ''),
                'sector' => ($data['sector_id'] ?? '') !== '' ? (int) $data['sector_id'] : null,
                'especificar' => trim((string) ($data['especificar_sector'] ?? '')) ?: null,
                'institucion' => trim((string) ($data['nombre_institucion'] ?? '')) ?: null,
                'institucion_extranjera' => trim((string) ($data['nombre_institucion_extranjera'] ?? '')) ?: null,
                'preparacion' => ($data['preparacion_previa_id'] ?? '') !== '' ? (int) $data['preparacion_previa_id'] : null,
                'mencion' => trim((string) ($data['mencion'] ?? '')) ?: null,
                'discapacidad' => !empty($data['tiene_discapacidad']) ? 1 : 0,
                'tipo_discapacidad' => trim((string) ($data['tipo_discapacidad'] ?? '')) ?: null,
                'otro_tipo_discapacidad' => trim((string) ($data['otro_tipo_discapacidad'] ?? '')) ?: null,
                'grado_discapacidad' => trim((string) ($data['grado_discapacidad'] ?? '')) ?: null,
                'necesidades_especiales' => trim((string) ($data['necesidades_especiales'] ?? '')) ?: null,
                'tiene_certificado' => !empty($data['tiene_certificado_discapacidad']) ? 1 : 0,
                'como_se_entero' => $this->discoverySource($data),
            ]);

            $this->storeFiles($files, $matriculaId, !empty($data['tiene_certificado_discapacidad']));

            $this->connection->commit();
            return $codigoAlumno;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    private function validateData(array $data): void
    {
        foreach (['apellido_paterno', 'apellido_materno', 'nombres', 'numero_documento', 'correo', 'fecha_nacimiento', 'telefono_celular'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new InvalidArgumentException('Complete todos los campos obligatorios.');
            }
        }
        if (!preg_match('/^\d{4}-(?:[12]|I|II)$/', (string) ($data['semestre'] ?? ''))) {
            throw new InvalidArgumentException('El semestre seleccionado no es válido.');
        }
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }
        if (!empty($data['tiene_discapacidad'])) {
            $tiposDiscapacidad = ['fisica', 'sensorial', 'intelectual', 'psicosocial', 'multiple', 'otra'];
            if (!in_array($data['tipo_discapacidad'] ?? '', $tiposDiscapacidad, true)
                || trim((string) ($data['grado_discapacidad'] ?? '')) === ''
                || trim((string) ($data['necesidades_especiales'] ?? '')) === '') {
                throw new InvalidArgumentException('Complete los datos de la discapacidad.');
            }
            if (($data['tipo_discapacidad'] ?? '') === 'otra' && trim((string) ($data['otro_tipo_discapacidad'] ?? '')) === '') {
                throw new InvalidArgumentException('Especifique el tipo de discapacidad.');
            }
        }
        if (($data['como_se_entero_cepre'] ?? '') === 'otro'
            && trim((string) ($data['especificar_como_se_entero'] ?? '')) === '') {
            throw new InvalidArgumentException('Especifique cómo se enteró de la CEPRE UNTELS.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['fecha_nacimiento']);
        if (!$date || $date->format('Y-m-d') !== $data['fecha_nacimiento'] || $date > new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('La fecha de nacimiento no es válida.');
        }
        if (($data['pais_nacimiento'] ?? 'Perú') === 'Otro' && trim((string) ($data['pais_nacimiento_otro'] ?? '')) === '') {
            throw new InvalidArgumentException('Indique el país de nacimiento.');
        }
        foreach (['condicion_id', 'turno_id', 'modalidad_clase_id', 'carrera_id'] as $field) {
            if (!filter_var($data[$field] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                throw new InvalidArgumentException('Seleccione opciones válidas de matrícula.');
            }
        }
    }

    private function storeFiles(array $files, int $matriculaId, bool $certificateRequired = false): void
    {
        $fileTypes = ['foto' => 'FOTO_CARNET', 'documento' => 'COPIA_DOCUMENTO'];
        if ($certificateRequired) $fileTypes['certificado_discapacidad'] = 'CERTIFICADO_DISCAPACIDAD';
        $directory = __DIR__ . '/../storage/matriculas/';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento de archivos.');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $allowed = [
            'foto' => ['image/jpeg' => 'jpg', 'image/png' => 'png'],
            'documento' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'],
            'certificado_discapacidad' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'],
        ];
        foreach ($fileTypes as $field => $typeName) {
            if (($files[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                throw new InvalidArgumentException($field === 'certificado_discapacidad'
                    ? 'Debe adjuntar el certificado de discapacidad.'
                    : 'Debe adjuntar la foto y la copia del documento.');
            }
            if (($files[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($files[$field]['size'] ?? 0) > 5 * 1024 * 1024) {
                throw new InvalidArgumentException('Los archivos deben ser válidos y pesar como máximo 5 MB.');
            }
            $mime = $finfo->file($files[$field]['tmp_name']);
            if (!isset($allowed[$field][$mime])) throw new InvalidArgumentException('El formato de archivo no está permitido.');
            $storedName = bin2hex(random_bytes(16)) . '.' . $allowed[$field][$mime];
            $storedPath = $directory . $storedName;
            if (!move_uploaded_file($files[$field]['tmp_name'], $storedPath)) throw new RuntimeException('No se pudo guardar un archivo.');
            $typeStatement = $this->connection->prepare('SELECT id FROM tipos_archivo WHERE nombre = :nombre');
            $typeStatement->execute(['nombre' => $typeName]);
            $typeId = $typeStatement->fetchColumn();
            if ($typeId === false) throw new RuntimeException('Tipo de archivo no configurado.');
            $statement = $this->connection->prepare('INSERT INTO archivos_matricula (matricula_id, tipo_archivo_id, nombre_original, ruta, mime_type, tamano_bytes, hash_archivo) VALUES (:matricula, :tipo, :original, :ruta, :mime, :tamano, :hash)');
            $statement->execute(['matricula' => $matriculaId, 'tipo' => $typeId, 'original' => basename((string) $files[$field]['name']), 'ruta' => $storedPath, 'mime' => $mime, 'tamano' => $files[$field]['size'], 'hash' => hash_file('sha256', $storedPath)]);
        }
    }

    private function storeCertificate(array $files, int $matriculaId): void
    {
        if (($files['certificado_discapacidad']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }
        $file = $files['certificado_discapacidad'];
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('El certificado debe ser válido y pesar como máximo 5 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
        if (!isset($extensions[$mime])) {
            throw new InvalidArgumentException('El formato del certificado no está permitido.');
        }
        $directory = __DIR__ . '/../storage/matriculas/';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento del certificado.');
        }
        $storedPath = $directory . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
            throw new RuntimeException('No se pudo guardar el certificado.');
        }
        $typeStatement = $this->connection->prepare('SELECT id FROM tipos_archivo WHERE nombre = :nombre');
        $typeStatement->execute(['nombre' => 'CERTIFICADO_DISCAPACIDAD']);
        $typeId = $typeStatement->fetchColumn();
        if ($typeId === false) {
            throw new RuntimeException('Tipo de certificado no configurado.');
        }
        $oldStatement = $this->connection->prepare('SELECT ruta FROM archivos_matricula WHERE matricula_id = :matricula AND tipo_archivo_id = :tipo LIMIT 1');
        $oldStatement->execute(['matricula' => $matriculaId, 'tipo' => $typeId]);
        $oldPath = $oldStatement->fetchColumn();
        $statement = $this->connection->prepare(
            'INSERT INTO archivos_matricula (matricula_id, tipo_archivo_id, nombre_original, ruta, mime_type, tamano_bytes, hash_archivo)
             VALUES (:matricula, :tipo, :original, :ruta, :mime, :tamano, :hash)
             ON DUPLICATE KEY UPDATE nombre_original = VALUES(nombre_original), ruta = VALUES(ruta), mime_type = VALUES(mime_type), tamano_bytes = VALUES(tamano_bytes), hash_archivo = VALUES(hash_archivo)'
        );
        $statement->execute([
            'matricula' => $matriculaId,
            'tipo' => $typeId,
            'original' => basename((string) $file['name']),
            'ruta' => $storedPath,
            'mime' => $mime,
            'tamano' => $file['size'],
            'hash' => hash_file('sha256', $storedPath),
        ]);
        if (is_string($oldPath) && $oldPath !== $storedPath && is_file($oldPath)) {
            unlink($oldPath);
        }
    }

    private function storePhoto(array $files, int $matriculaId): void
    {
        if (($files['foto']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }
        $file = $files['foto'];
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('La foto debe ser válida y pesar como máximo 5 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($extensions[$mime])) {
            throw new InvalidArgumentException('La foto debe estar en formato JPG o PNG.');
        }
        $directory = __DIR__ . '/../storage/matriculas/';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento de la foto.');
        }
        $storedPath = $directory . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
            throw new RuntimeException('No se pudo guardar la foto.');
        }
        $typeStatement = $this->connection->prepare('SELECT id FROM tipos_archivo WHERE nombre = :nombre');
        $typeStatement->execute(['nombre' => 'FOTO_CARNET']);
        $typeId = $typeStatement->fetchColumn();
        if ($typeId === false) {
            throw new RuntimeException('Tipo de foto no configurado.');
        }
        $oldStatement = $this->connection->prepare('SELECT ruta FROM archivos_matricula WHERE matricula_id = :matricula AND tipo_archivo_id = :tipo LIMIT 1');
        $oldStatement->execute(['matricula' => $matriculaId, 'tipo' => $typeId]);
        $oldPath = $oldStatement->fetchColumn();
        $statement = $this->connection->prepare(
            'INSERT INTO archivos_matricula (matricula_id, tipo_archivo_id, nombre_original, ruta, mime_type, tamano_bytes, hash_archivo)
             VALUES (:matricula, :tipo, :original, :ruta, :mime, :tamano, :hash)
             ON DUPLICATE KEY UPDATE nombre_original = VALUES(nombre_original), ruta = VALUES(ruta), mime_type = VALUES(mime_type), tamano_bytes = VALUES(tamano_bytes), hash_archivo = VALUES(hash_archivo)'
        );
        $statement->execute([
            'matricula' => $matriculaId,
            'tipo' => $typeId,
            'original' => basename((string) $file['name']),
            'ruta' => $storedPath,
            'mime' => $mime,
            'tamano' => $file['size'],
            'hash' => hash_file('sha256', $storedPath),
        ]);
        if (is_string($oldPath) && $oldPath !== $storedPath && is_file($oldPath)) {
            unlink($oldPath);
        }
    }

    private function catalogName(string $table, mixed $id): string
    {
        $allowedTables = ['condiciones_matricula', 'turnos', 'preparaciones_previas'];
        if (!in_array($table, $allowedTables, true)) {
            throw new InvalidArgumentException('Catálogo no permitido.');
        }
        $turnoFilter = $table === 'turnos' ? " AND nombre IN ('MANANA', 'TARDE', 'ESCOLAR')" : '';
        $statement = $this->connection->prepare("SELECT nombre FROM {$table} WHERE id = :id{$turnoFilter}");
        $statement->execute(['id' => (int) $id]);
        $name = $statement->fetchColumn();
        if ($name === false) {
            throw new RuntimeException('Seleccione una condición y un turno válidos.');
        }
        return (string) $name;
    }

    private function ubigeoOrNull(mixed $value): ?string
    {
        $code = trim((string) $value);
        if ($code === '') return null;

        $statement = $this->connection->prepare('SELECT codigo FROM ubigeos WHERE codigo = :codigo LIMIT 1');
        $statement->execute(['codigo' => $code]);
        $validCode = $statement->fetchColumn();

        return $validCode === false ? null : (string) $validCode;
    }

    private function discoverySource(array $data): ?string
    {
        $source = trim((string) ($data['como_se_entero_cepre'] ?? ''));
        if ($source === '') return null;
        if ($source !== 'otro') return $source;

        $other = trim((string) ($data['especificar_como_se_entero'] ?? ''));
        return $other === '' ? 'otro' : 'Otro: ' . $other;
    }
}
