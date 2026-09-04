CREATE DATABASE IF NOT EXISTS cepre_universidad
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cepre_universidad;

CREATE TABLE IF NOT EXISTS periodos (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_periodo_fechas CHECK (fecha_fin >= fecha_inicio)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS condiciones_matricula (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(40) NOT NULL UNIQUE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS turnos (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS modalidades_clase (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS sectores (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS preparaciones_previas (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS ubigeos (
    codigo CHAR(6) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nivel ENUM ('DEPARTAMENTO', 'PROVINCIA', 'DISTRITO') NOT NULL,
    codigo_padre CHAR(6) NULL,
    CONSTRAINT fk_ubigeos_padre FOREIGN KEY (codigo_padre) REFERENCES ubigeos (codigo)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS matriculas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(30) NOT NULL UNIQUE,
    estudiante_id INT NOT NULL,
    periodo_id SMALLINT UNSIGNED NOT NULL,
    condicion_id TINYINT UNSIGNED NOT NULL,
    turno_id TINYINT UNSIGNED NOT NULL,
    modalidad_clase_id TINYINT UNSIGNED NOT NULL,
    carrera_id INT NOT NULL,
    estado ENUM ('BORRADOR', 'PENDIENTE', 'OBSERVADA', 'CONFIRMADA', 'ANULADA') NOT NULL DEFAULT 'BORRADOR',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones VARCHAR(500) NULL,
    UNIQUE KEY uq_matricula_estudiante_periodo (estudiante_id, periodo_id),
    CONSTRAINT fk_matriculas_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes (id_estudiante),
    CONSTRAINT fk_matriculas_periodo FOREIGN KEY (periodo_id) REFERENCES periodos (id),
    CONSTRAINT fk_matriculas_condicion FOREIGN KEY (condicion_id) REFERENCES condiciones_matricula (id),
    CONSTRAINT fk_matriculas_turno FOREIGN KEY (turno_id) REFERENCES turnos (id),
    CONSTRAINT fk_matriculas_modalidad FOREIGN KEY (modalidad_clase_id) REFERENCES modalidades_clase (id),
    CONSTRAINT fk_matriculas_carrera FOREIGN KEY (carrera_id) REFERENCES carreras (id_carrera)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS domicilios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    tipo ENUM ('ACTUAL', 'NACIMIENTO') NOT NULL,
    ubigeo_codigo CHAR(6) NOT NULL,
    direccion VARCHAR(200) NOT NULL,
    referencia VARCHAR(200) NULL,
    UNIQUE KEY uq_domicilio_estudiante_tipo (estudiante_id, tipo),
    CONSTRAINT fk_domicilios_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes (id_estudiante) ON DELETE CASCADE,
    CONSTRAINT fk_domicilios_ubigeo FOREIGN KEY (ubigeo_codigo) REFERENCES ubigeos (codigo)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS informacion_academica (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    matricula_id BIGINT UNSIGNED NOT NULL UNIQUE,
    anio_conclusion_secundaria YEAR NOT NULL,
    pais VARCHAR(80) NOT NULL,
    departamento_ubigeo CHAR(6) NULL,
    provincia_ubigeo CHAR(6) NULL,
    distrito_ubigeo CHAR(6) NULL,
    sector_id TINYINT UNSIGNED NULL,
    especificar_sector VARCHAR(120) NULL,
    nombre_institucion VARCHAR(150) NULL,
    nombre_institucion_extranjera VARCHAR(150) NULL,
    preparacion_previa_id TINYINT UNSIGNED NULL,
    mencion VARCHAR(120) NULL,
    tiene_discapacidad BOOLEAN NOT NULL DEFAULT FALSE,
    tipo_discapacidad VARCHAR(30) NULL,
    otro_tipo_discapacidad VARCHAR(120) NULL,
    grado_discapacidad VARCHAR(20) NULL,
    necesidades_especiales VARCHAR(500) NULL,
    tiene_certificado_discapacidad BOOLEAN NOT NULL DEFAULT FALSE,
    como_se_entero_cepre VARCHAR(150) NULL,
    CONSTRAINT fk_academica_matricula FOREIGN KEY (matricula_id) REFERENCES matriculas (id) ON DELETE CASCADE,
    CONSTRAINT fk_academica_departamento FOREIGN KEY (departamento_ubigeo) REFERENCES ubigeos (codigo),
    CONSTRAINT fk_academica_provincia FOREIGN KEY (provincia_ubigeo) REFERENCES ubigeos (codigo),
    CONSTRAINT fk_academica_distrito FOREIGN KEY (distrito_ubigeo) REFERENCES ubigeos (codigo),
    CONSTRAINT fk_academica_sector FOREIGN KEY (sector_id) REFERENCES sectores (id),
    CONSTRAINT fk_academica_preparacion FOREIGN KEY (preparacion_previa_id) REFERENCES preparaciones_previas (id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS resultados_examen (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    matricula_id BIGINT UNSIGNED NOT NULL UNIQUE,
    fecha_examen DATE NULL,
    puntaje DECIMAL(6,2) NULL,
    aprobado BOOLEAN NULL,
    observaciones VARCHAR(500) NULL,
    CONSTRAINT fk_resultados_matricula FOREIGN KEY (matricula_id) REFERENCES matriculas (id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS tipos_archivo (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS archivos_matricula (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    matricula_id BIGINT UNSIGNED NOT NULL,
    tipo_archivo_id TINYINT UNSIGNED NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    tamano_bytes INT UNSIGNED NOT NULL,
    hash_archivo CHAR(64) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_archivo_matricula_tipo (matricula_id, tipo_archivo_id),
    CONSTRAINT fk_archivos_matricula FOREIGN KEY (matricula_id) REFERENCES matriculas (id) ON DELETE CASCADE,
    CONSTRAINT fk_archivos_tipo FOREIGN KEY (tipo_archivo_id) REFERENCES tipos_archivo (id)
) ENGINE = InnoDB;

UPDATE condiciones_matricula SET nombre = 'EXTRAORDINARIO' WHERE nombre = 'EXONERADO';
INSERT IGNORE INTO condiciones_matricula (nombre) VALUES ('ORDINARIO'), ('EXTRAORDINARIO');
INSERT IGNORE INTO turnos (nombre) VALUES ('MANANA'), ('TARDE'), ('ESCOLAR'), ('NOCHE');
INSERT IGNORE INTO modalidades_clase (nombre) VALUES ('PRESENCIAL'), ('VIRTUAL');
INSERT IGNORE INTO sectores (nombre) VALUES ('PUBLICO'), ('PRIVADO'), ('OTRO');
INSERT IGNORE INTO preparaciones_previas (nombre) VALUES ('ACADEMIA'), ('COLEGIO'), ('AUTOPREPARACION'), ('OTRO');
INSERT IGNORE INTO tipos_archivo (nombre) VALUES ('FOTO_CARNET'), ('COPIA_DOCUMENTO'), ('CERTIFICADO_DISCAPACIDAD');
UPDATE periodos SET nombre = '2026-I' WHERE nombre = 'Ciclo 2026-I';
INSERT IGNORE INTO periodos (nombre, fecha_inicio, fecha_fin)
VALUES ('2026-I', '2026-01-01', '2026-07-31');
INSERT IGNORE INTO periodos (nombre, fecha_inicio, fecha_fin)
VALUES ('2027-1', '2026-08-01', '2027-02-28'),
       ('2027-2', '2027-03-01', '2027-07-31'),
       ('2028-1', '2027-08-01', '2027-12-31'),
       ('2028-2', '2028-01-01', '2028-07-31');
INSERT IGNORE INTO periodos (nombre, fecha_inicio, fecha_fin)
VALUES ('2026-I', '2026-01-01', '2026-07-31'),
       ('2026-II', '2026-08-01', '2026-12-31'),
       ('2027-I', '2027-01-01', '2027-07-31'),
       ('2027-II', '2027-08-01', '2027-12-31'),
       ('2028-I', '2028-01-01', '2028-07-31'),
       ('2028-II', '2028-08-01', '2028-12-31');

INSERT INTO carreras (nombre_carrera, descripcion, estado)
SELECT 'Ingeniería de Sistemas', 'Carrera profesional', 'ACTIVO'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Ingeniería de Sistemas');
INSERT INTO carreras (nombre_carrera, descripcion, estado)
SELECT 'Ingeniería Electrónica y Telecomunicaciones', 'Carrera profesional', 'ACTIVO'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Ingeniería Electrónica y Telecomunicaciones');
INSERT INTO carreras (nombre_carrera, descripcion, estado)
SELECT 'Ingeniería Mecánica y Eléctrica', 'Carrera profesional', 'ACTIVO'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Ingeniería Mecánica y Eléctrica');
INSERT INTO carreras (nombre_carrera, descripcion, estado)
SELECT 'Ingeniería Ambiental', 'Carrera profesional', 'ACTIVO'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Ingeniería Ambiental');
INSERT INTO carreras (nombre_carrera, descripcion, estado)
SELECT 'Administración de Empresas', 'Carrera profesional', 'ACTIVO'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Administración de Empresas');
INSERT INTO carreras (nombre_carrera, descripcion, estado)
SELECT 'Ingeniería de Software', 'Carrera profesional', 'ACTIVO'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Ingeniería de Software');
INSERT INTO carreras (nombre_carrera, descripcion, estado)
SELECT 'Ingeniería de Ciencia de Datos e Inteligencia Artificial', 'Carrera profesional', 'ACTIVO'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Ingeniería de Ciencia de Datos e Inteligencia Artificial');
INSERT INTO carreras (nombre_carrera, descripcion, estado)
SELECT 'Ingeniería Mecatrónica', 'Carrera profesional', 'ACTIVO'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Ingeniería Mecatrónica');
INSERT INTO carreras (nombre_carrera, descripcion, estado)
SELECT 'Ingeniería Industrial', 'Carrera profesional', 'ACTIVO'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Ingeniería Industrial');
INSERT INTO carreras (nombre_carrera, descripcion, estado)
SELECT 'Marketing y Negocios Internacionales', 'Carrera profesional', 'ACTIVO'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Marketing y Negocios Internacionales');

UPDATE carreras
SET estado = 'INACTIVO'
WHERE nombre_carrera NOT IN (
    'Ingeniería de Sistemas',
    'Ingeniería Electrónica y Telecomunicaciones',
    'Ingeniería Mecánica y Eléctrica',
    'Ingeniería Ambiental',
    'Administración de Empresas',
    'Ingeniería de Software',
    'Ingeniería de Ciencia de Datos e Inteligencia Artificial',
    'Ingeniería Mecatrónica',
    'Ingeniería Industrial',
    'Marketing y Negocios Internacionales'
);

UPDATE carreras SET nombre_carrera = CONVERT(0x496E67656E696572C3AD612064652053697374656D6173 USING utf8mb4) WHERE id_carrera = 11;
UPDATE carreras SET nombre_carrera = CONVERT(0x496E67656E696572C3AD6120456C65637472C3B36E69636120792054656C65636F6D756E69636163696F6E6573 USING utf8mb4) WHERE id_carrera = 12;
UPDATE carreras SET nombre_carrera = CONVERT(0x496E67656E696572C3AD61204D6563C3A16E696361207920456CC3A9637472696361 USING utf8mb4) WHERE id_carrera = 13;
UPDATE carreras SET nombre_carrera = CONVERT(0x496E67656E696572C3AD6120416D6269656E74616C USING utf8mb4) WHERE id_carrera = 14;
UPDATE carreras SET nombre_carrera = CONVERT(0x41646D696E69737472616369C3B36E20646520456D707265736173 USING utf8mb4) WHERE id_carrera = 15;
UPDATE carreras SET nombre_carrera = CONVERT(0x496E67656E696572C3AD6120646520536F667477617265 USING utf8mb4) WHERE id_carrera = 16;
UPDATE carreras SET nombre_carrera = CONVERT(0x496E67656E696572C3AD61206465204369656E636961206465204461746F73206520496E74656C6967656E636961204172746966696369616C USING utf8mb4) WHERE id_carrera = 17;
UPDATE carreras SET nombre_carrera = CONVERT(0x496E67656E696572C3AD61204D6563617472C3B36E696361 USING utf8mb4) WHERE id_carrera = 18;
UPDATE carreras SET nombre_carrera = CONVERT(0x496E67656E696572C3AD6120496E647573747269616C USING utf8mb4) WHERE id_carrera = 19;
UPDATE carreras SET nombre_carrera = CONVERT(0x4D61726B6574696E672079204E65676F63696F7320496E7465726E6163696F6E616C6573 USING utf8mb4) WHERE id_carrera = 20;

INSERT IGNORE INTO ubigeos (codigo, nombre, nivel) VALUES
('01', 'Amazonas', 'DEPARTAMENTO'), ('02', 'Ancash', 'DEPARTAMENTO'),
('03', 'Apurimac', 'DEPARTAMENTO'), ('04', 'Arequipa', 'DEPARTAMENTO'),
('05', 'Ayacucho', 'DEPARTAMENTO'), ('06', 'Cajamarca', 'DEPARTAMENTO'),
('07', 'Callao', 'DEPARTAMENTO'), ('08', 'Cusco', 'DEPARTAMENTO'),
('09', 'Huancavelica', 'DEPARTAMENTO'), ('10', 'Huanuco', 'DEPARTAMENTO'),
('11', 'Ica', 'DEPARTAMENTO'), ('12', 'Junin', 'DEPARTAMENTO'),
('13', 'La Libertad', 'DEPARTAMENTO'), ('14', 'Lambayeque', 'DEPARTAMENTO'),
('15', 'Lima', 'DEPARTAMENTO'), ('16', 'Loreto', 'DEPARTAMENTO'),
('17', 'Madre de Dios', 'DEPARTAMENTO'), ('18', 'Moquegua', 'DEPARTAMENTO'),
('19', 'Pasco', 'DEPARTAMENTO'), ('20', 'Piura', 'DEPARTAMENTO'),
('21', 'Puno', 'DEPARTAMENTO'), ('22', 'San Martin', 'DEPARTAMENTO'),
('23', 'Tacna', 'DEPARTAMENTO'), ('24', 'Tumbes', 'DEPARTAMENTO'),
('25', 'Ucayali', 'DEPARTAMENTO');
