-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-09-2026 a las 23:13:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `cepre_universidad`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `buscar_estudiantes_simple` (IN `p_termino` VARCHAR(100))   BEGIN
    SELECT 
        id_estudiante,
        numero_matricula,
        CONCAT(apellido_paterno, ' ', apellido_materno, ', ', nombres) AS nombre_completo,
        apellido_paterno,
        apellido_materno,
        nombres,
        numero_documento,
        email,
        telefono_celular,
        carrera_postula,
        turno,
        fecha_registro,
        estado
    FROM estudiantes 
    WHERE estado = 'ACTIVO'
    AND (
        nombres LIKE CONCAT('%', p_termino, '%')
        OR apellido_paterno LIKE CONCAT('%', p_termino, '%')
        OR apellido_materno LIKE CONCAT('%', p_termino, '%')
        OR numero_documento LIKE CONCAT('%', p_termino, '%')
        OR numero_matricula LIKE CONCAT('%', p_termino, '%')
    )
    ORDER BY fecha_registro DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `obtener_estadisticas_simple` ()   BEGIN
    -- Total de estudiantes activos
    SELECT COUNT(*) AS total_estudiantes 
    FROM estudiantes 
    WHERE estado = 'ACTIVO';
    
    -- Estudiantes por turno
    SELECT 
        turno,
        COUNT(*) AS cantidad,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM estudiantes WHERE estado = 'ACTIVO')), 2) AS porcentaje
    FROM estudiantes 
    WHERE estado = 'ACTIVO'
    GROUP BY turno;
    
    -- Estudiantes por carrera
    SELECT 
        carrera_postula,
        COUNT(*) AS cantidad,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM estudiantes WHERE estado = 'ACTIVO')), 2) AS porcentaje
    FROM estudiantes 
    WHERE estado = 'ACTIVO'
    GROUP BY carrera_postula
    ORDER BY cantidad DESC;
    
    -- Estudiantes por sexo
    SELECT 
        sexo,
        COUNT(*) AS cantidad,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM estudiantes WHERE estado = 'ACTIVO')), 2) AS porcentaje
    FROM estudiantes 
    WHERE estado = 'ACTIVO'
    GROUP BY sexo;
    
    -- Estudiantes registrados por mes (últimos 12 meses)
    SELECT 
        DATE_FORMAT(fecha_registro, '%Y-%m') AS mes,
        COUNT(*) AS cantidad
    FROM estudiantes 
    WHERE estado = 'ACTIVO'
    GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m')
    ORDER BY mes DESC
    LIMIT 12;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrar_estudiante_simple` (IN `p_apellido_paterno` VARCHAR(50), IN `p_apellido_materno` VARCHAR(50), IN `p_nombres` VARCHAR(100), IN `p_tipo_documento` VARCHAR(20), IN `p_numero_documento` VARCHAR(20), IN `p_sexo` VARCHAR(10), IN `p_fecha_nacimiento` DATE, IN `p_email` VARCHAR(100), IN `p_telefono_celular` VARCHAR(15), IN `p_direccion_actual` TEXT, IN `p_carrera_postula` VARCHAR(100), IN `p_turno` VARCHAR(10), IN `p_condicion` VARCHAR(20), IN `p_modalidad` VARCHAR(20), OUT `p_numero_matricula` VARCHAR(10))   BEGIN
    -- Generar número de matrícula
    SET p_numero_matricula = generar_numero_matricula_func();
    
    -- Insertar estudiante
    INSERT INTO estudiantes (
        numero_matricula,
        apellido_paterno,
        apellido_materno,
        nombres,
        tipo_documento,
        numero_documento,
        sexo,
        fecha_nacimiento,
        email,
        telefono_celular,
        direccion_actual,
        carrera_postula,
        turno,
        condicion,
        modalidad,
        estado
    ) VALUES (
        p_numero_matricula,
        p_apellido_paterno,
        p_apellido_materno,
        p_nombres,
        p_tipo_documento,
        p_numero_documento,
        p_sexo,
        p_fecha_nacimiento,
        p_email,
        p_telefono_celular,
        p_direccion_actual,
        p_carrera_postula,
        p_turno,
        p_condicion,
        p_modalidad,
        'ACTIVO'
    );
    
    -- Retornar el número de matrícula
    SELECT p_numero_matricula AS numero_matricula;
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `generar_numero_matricula_func` () RETURNS VARCHAR(10) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    DECLARE ultimo_numero INT DEFAULT 0;
    DECLARE anio_actual VARCHAR(4);
    DECLARE nuevo_numero VARCHAR(10);
    
    SET anio_actual = DATE_FORMAT(CURDATE(), '%Y');
    
    SELECT MAX(CAST(SUBSTRING_INDEX(numero_matricula, '-', -1) AS UNSIGNED)) 
    INTO ultimo_numero 
    FROM estudiantes 
    WHERE numero_matricula LIKE CONCAT(anio_actual, '-%');
    
    IF ultimo_numero IS NULL THEN
        SET ultimo_numero = 1;
    ELSE
        SET ultimo_numero = ultimo_numero + 1;
    END IF;
    
    SET nuevo_numero = CONCAT(anio_actual, '-', LPAD(ultimo_numero, 5, '0'));
    
    RETURN nuevo_numero;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_matricula`
--

CREATE TABLE `archivos_matricula` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `matricula_id` bigint(20) UNSIGNED NOT NULL,
  `tipo_archivo_id` tinyint(3) UNSIGNED NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `ruta` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `tamano_bytes` int(10) UNSIGNED NOT NULL,
  `hash_archivo` char(64) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `archivos_matricula`
--

INSERT INTO `archivos_matricula` (`id`, `matricula_id`, `tipo_archivo_id`, `nombre_original`, `ruta`, `mime_type`, `tamano_bytes`, `hash_archivo`, `creado_en`) VALUES
(1, 7, 1, 'logo.png', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/c41ba30ca9d2c974cd02cea54b4652d3.png', 'image/png', 27446, 'fc32c65efeed36a24aa03a689995526e412fcd919f3e32e400269ef0361a35c5', '2026-09-02 21:50:40'),
(2, 7, 2, '04. COMPROMISO - SEGURIDAD Y SALUD EN EL TRABAJO (ANEXO 02).pdf', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/e5003b8c9c0366b50342c9c1d56776d5.pdf', 'application/pdf', 171795, '5ff67492f405407d7f27df2bc1632b89175c2432cab94031c79ba8a5db38c933', '2026-09-02 21:50:40'),
(3, 8, 1, 'logo.png', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/725fae939faf1975a7868ad9ef308e48.png', 'image/png', 27446, 'fc32c65efeed36a24aa03a689995526e412fcd919f3e32e400269ef0361a35c5', '2026-09-02 22:52:43'),
(4, 8, 2, '01. ANEXOS - SUSCRIPCIÓN DE CONTRATO 2026.pdf', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/6a702a2a62e7baab10b5a6fe85f6979e.pdf', 'application/pdf', 499742, 'e446f58062adde9225a21d6b4220a16726d2f658150b78f2658927c322990064', '2026-09-02 22:52:43'),
(5, 9, 1, 'logo.png', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/ef887aeef22c5f73c2e3ece541b493d0.png', 'image/png', 27446, 'fc32c65efeed36a24aa03a689995526e412fcd919f3e32e400269ef0361a35c5', '2026-09-03 08:28:13'),
(6, 9, 2, '02. DECLARACIÓN JURADA IMPEDIMENTO CONTRATAR CON EL ESTADO.pdf', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/54e663b6f847fd19a4f0fe62d6ca57c0.pdf', 'application/pdf', 316011, 'a291e5e9a07660e00d8052b9e9edd600154b658f1eaff20f767381358df63de2', '2026-09-03 08:28:13'),
(7, 2, 1, 'logo.png', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/b5c65f16363f33872a19a08b88b67d5a.png', 'image/png', 27446, 'fc32c65efeed36a24aa03a689995526e412fcd919f3e32e400269ef0361a35c5', '2026-09-03 11:13:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id_auditoria` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `datos_anteriores` text DEFAULT NULL,
  `datos_nuevos` text DEFAULT NULL,
  `ip_usuario` varchar(45) DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carreras`
--

CREATE TABLE `carreras` (
  `id_carrera` int(11) NOT NULL,
  `nombre_carrera` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carreras`
--

INSERT INTO `carreras` (`id_carrera`, `nombre_carrera`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(1, 'MEDICINA', 'Carrera de Medicina Humana - 7 años', 'INACTIVO', '2026-09-02 22:25:07'),
(2, 'INGENIERIA CIVIL', 'Carrera de Ingeniería Civil - 5 años', 'INACTIVO', '2026-09-02 22:25:07'),
(3, 'ADMINISTRACION', 'Carrera de Administración de Empresas - 5 años', 'INACTIVO', '2026-09-02 22:25:07'),
(4, 'DERECHO', 'Carrera de Derecho - 5 años', 'INACTIVO', '2026-09-02 22:25:07'),
(5, 'PSICOLOGIA', 'Carrera de Psicología - 5 años', 'INACTIVO', '2026-09-02 22:25:07'),
(6, 'INGENIERIA SISTEMAS', 'Carrera de Ingeniería de Sistemas - 5 años', 'INACTIVO', '2026-09-02 22:25:07'),
(7, 'ARQUITECTURA', 'Carrera de Arquitectura - 6 años', 'INACTIVO', '2026-09-02 22:25:07'),
(8, 'ENFERMERIA', 'Carrera de Enfermería - 5 años', 'INACTIVO', '2026-09-02 22:25:07'),
(9, 'ODONTOLOGIA', 'Carrera de Odontología - 6 años', 'INACTIVO', '2026-09-02 22:25:07'),
(10, 'CONTABILIDAD', 'Carrera de Contabilidad - 5 años', 'INACTIVO', '2026-09-02 22:25:07'),
(11, 'Ingeniería de Sistemas', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(12, 'Ingeniería Electrónica y Telecomunicaciones', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(13, 'Ingeniería Mecánica y Eléctrica', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(14, 'Ingeniería Ambiental', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(15, 'Administración de Empresas', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(16, 'Ingeniería de Software', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(17, 'Ingeniería de Ciencia de Datos e Inteligencia Artificial', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(18, 'Ingeniería Mecatrónica', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(19, 'Ingeniería Industrial', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(20, 'Marketing y Negocios Internacionales', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `condiciones_matricula`
--

CREATE TABLE `condiciones_matricula` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `condiciones_matricula`
--

INSERT INTO `condiciones_matricula` (`id`, `nombre`) VALUES
(2, 'EXTRAORDINARIO'),
(1, 'ORDINARIO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

CREATE TABLE `configuracion_sistema` (
  `id_config` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion_sistema`
--

INSERT INTO `configuracion_sistema` (`id_config`, `clave`, `valor`, `descripcion`) VALUES
(1, 'nombre_cepre', 'Centro Preuniversitario - UNETELS', 'Nombre de la CEPRE'),
(2, 'anio_actual', '2026', 'Año académico actual'),
(3, 'limite_estudiantes', '200', 'Límite máximo de estudiantes por ciclo'),
(4, 'costo_matricula', '150.00', 'Costo de matrícula'),
(5, 'fecha_inicio_clases', '2026-03-15', 'Fecha de inicio de clases'),
(6, 'fecha_fin_clases', '2026-07-15', 'Fecha de fin de clases'),
(7, 'director_cepre', 'Dr. Juan Pérez', 'Director del Centro Preuniversitario'),
(8, 'telefono_contacto', '01-555-1234', 'Teléfono de contacto'),
(9, 'email_contacto', 'info@cepre.edu.pe', 'Email de contacto'),
(10, 'direccion_cepre', 'Av. Universitaria 123, Lima', 'Dirección de la CEPRE');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `domicilios`
--

CREATE TABLE `domicilios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `tipo` enum('ACTUAL','NACIMIENTO') NOT NULL,
  `ubigeo_codigo` char(6) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `referencia` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id_estudiante` int(11) NOT NULL,
  `numero_matricula` varchar(10) NOT NULL,
  `codigo_estudiante` varchar(20) DEFAULT NULL,
  `apellido_paterno` varchar(50) NOT NULL,
  `apellido_materno` varchar(50) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `tipo_documento` enum('DNI','CE','PASAPORTE') DEFAULT 'DNI',
  `numero_documento` varchar(20) NOT NULL,
  `sexo` enum('MASCULINO','FEMENINO') NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono_casa` varchar(15) DEFAULT NULL,
  `telefono_celular` varchar(15) NOT NULL,
  `departamento_actual` varchar(50) DEFAULT NULL,
  `provincia_actual` varchar(50) DEFAULT NULL,
  `distrito_actual` varchar(50) DEFAULT NULL,
  `direccion_actual` text DEFAULT NULL,
  `pais_nacimiento` varchar(50) DEFAULT NULL,
  `departamento_nacimiento` varchar(50) DEFAULT NULL,
  `provincia_nacimiento` varchar(50) DEFAULT NULL,
  `distrito_nacimiento` varchar(50) DEFAULT NULL,
  `anio_concluye_secundaria` year(4) DEFAULT NULL,
  `institucion_educativa` varchar(100) DEFAULT NULL,
  `preparacion_anterior` varchar(50) DEFAULT NULL,
  `mencion` varchar(100) DEFAULT NULL,
  `carrera_postula` varchar(100) NOT NULL,
  `tiene_enfermedad` tinyint(1) DEFAULT 0,
  `tratamiento` text DEFAULT NULL,
  `nombre_apoderado` varchar(100) DEFAULT NULL,
  `telefono_apoderado` varchar(15) DEFAULT NULL,
  `como_se_entero` varchar(100) DEFAULT NULL,
  `modalidad` enum('REGULAR','INTENSIVO','SEMIPRESENCIAL') DEFAULT 'REGULAR',
  `condicion` enum('ORDINARIO','EXTRAORDINARIO') DEFAULT 'ORDINARIO',
  `turno` enum('MANANA','TARDE','NOCHE') NOT NULL,
  `estado` enum('ACTIVO','INACTIVO','RETIRADO') DEFAULT 'ACTIVO',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id_estudiante`, `numero_matricula`, `codigo_estudiante`, `apellido_paterno`, `apellido_materno`, `nombres`, `tipo_documento`, `numero_documento`, `sexo`, `fecha_nacimiento`, `email`, `telefono_casa`, `telefono_celular`, `departamento_actual`, `provincia_actual`, `distrito_actual`, `direccion_actual`, `pais_nacimiento`, `departamento_nacimiento`, `provincia_nacimiento`, `distrito_nacimiento`, `anio_concluye_secundaria`, `institucion_educativa`, `preparacion_anterior`, `mencion`, `carrera_postula`, `tiene_enfermedad`, `tratamiento`, `nombre_apoderado`, `telefono_apoderado`, `como_se_entero`, `modalidad`, `condicion`, `turno`, `estado`, `fecha_registro`, `fecha_actualizacion`) VALUES
(5, '00001', NULL, 'Monge', 'Peralta', 'Luis', 'DNI', '71017875', 'MASCULINO', '2002-04-18', 'ronalmonge27@gmail.com', '0145895623', '987654321', 'Lima', 'Lima', '3959', 'ronalmonge', 'Perú', 'Lima', 'Lima', '3959', '2018', 'ADUNI', NULL, 'cepre', 'Ingeniería de Sistemas', 0, NULL, NULL, NULL, NULL, 'REGULAR', 'ORDINARIO', 'MANANA', 'ACTIVO', '2026-09-02 23:25:38', '2026-09-03 02:02:36'),
(10, '00002', '260100002', 'Lopez', 'Alvarez', 'Alfonso', 'DNI', '71017872', 'MASCULINO', '2004-06-15', 'ronalmonge45@gmail.com', NULL, '984748386', 'Lima', 'Lima', '3928', 'Av. Tusilagos', 'Perú', 'Lima', 'Lima', '3928', '2018', 'Innova School', NULL, 'ADUNI', 'Administración de Empresas', 0, NULL, NULL, NULL, NULL, 'REGULAR', 'ORDINARIO', 'MANANA', 'ACTIVO', '2026-09-03 02:50:40', '2026-09-03 02:53:31'),
(11, '00003', '260100003', 'Huacho', 'Huamani', 'Jeremy Mathías', 'DNI', '71002593', 'MASCULINO', '2005-04-11', 'jeremy@gmail.com', NULL, '963258741', 'Lima', 'Lima', '3940', 'Av. Jesus María 123', 'Perú', 'Lima', 'Lima', '3940', '2018', 'daniel alomia robles', 'ACADEMIA', 'cesar vallejo', 'Ingeniería Industrial', 0, NULL, NULL, NULL, NULL, 'REGULAR', 'ORDINARIO', 'MANANA', 'ACTIVO', '2026-09-03 03:52:43', '2026-09-03 03:52:43'),
(12, '00004', '270100004', 'YAYA', 'PORTUGAL', 'JULIO CESAR', 'DNI', '09668752', 'MASCULINO', '2000-05-11', 'ronal@gmail.com', NULL, '963258742', 'Lima', 'Lima', '3939', 'Av. Jesus María 123', 'Perú', 'Lima', 'Lima', '3928', '2016', 'daniel alomia robles', 'ACADEMIA', 'cesar vallejo', 'Ingeniería Mecánica y Eléctrica', 0, NULL, NULL, NULL, NULL, 'REGULAR', 'ORDINARIO', 'MANANA', 'ACTIVO', '2026-09-03 13:28:13', '2026-09-03 13:28:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `informacion_academica`
--

CREATE TABLE `informacion_academica` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `matricula_id` bigint(20) UNSIGNED NOT NULL,
  `anio_conclusion_secundaria` year(4) NOT NULL,
  `pais` varchar(80) NOT NULL,
  `departamento_ubigeo` char(6) DEFAULT NULL,
  `provincia_ubigeo` char(6) DEFAULT NULL,
  `distrito_ubigeo` char(6) DEFAULT NULL,
  `sector_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `especificar_sector` varchar(120) DEFAULT NULL,
  `nombre_institucion` varchar(150) DEFAULT NULL,
  `nombre_institucion_extranjera` varchar(150) DEFAULT NULL,
  `preparacion_previa_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `mencion` varchar(120) DEFAULT NULL,
  `tiene_discapacidad` tinyint(1) NOT NULL DEFAULT 0,
  `tipo_discapacidad` varchar(30) DEFAULT NULL,
  `otro_tipo_discapacidad` varchar(120) DEFAULT NULL,
  `grado_discapacidad` varchar(20) DEFAULT NULL,
  `necesidades_especiales` varchar(500) DEFAULT NULL,
  `tiene_certificado_discapacidad` tinyint(1) NOT NULL DEFAULT 0,
  `como_se_entero_cepre` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `informacion_academica`
--

INSERT INTO `informacion_academica` (`id`, `matricula_id`, `anio_conclusion_secundaria`, `pais`, `departamento_ubigeo`, `provincia_ubigeo`, `distrito_ubigeo`, `sector_id`, `especificar_sector`, `nombre_institucion`, `nombre_institucion_extranjera`, `preparacion_previa_id`, `mencion`, `tiene_discapacidad`, `tipo_discapacidad`, `otro_tipo_discapacidad`, `grado_discapacidad`, `necesidades_especiales`, `tiene_certificado_discapacidad`, `como_se_entero_cepre`) VALUES
(2, 2, '2018', 'Perú', NULL, NULL, NULL, NULL, 'Publico', 'ADUNI', NULL, NULL, 'cepre', 0, NULL, NULL, NULL, NULL, 0, 'sitio_web'),
(7, 7, '2018', 'Perú', '15', NULL, NULL, NULL, 'Privado', 'Innova School', NULL, NULL, 'ADUNI', 0, NULL, NULL, NULL, NULL, 0, NULL),
(8, 8, '2018', 'Perú', '15', NULL, NULL, 1, 'publico', 'daniel alomia robles', NULL, 1, 'cesar vallejo', 0, NULL, NULL, NULL, NULL, 0, NULL),
(9, 9, '2016', 'Perú', '15', NULL, NULL, 1, NULL, 'daniel alomia robles', NULL, 1, 'cesar vallejo', 0, NULL, NULL, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matriculas`
--

CREATE TABLE `matriculas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `numero` varchar(30) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `periodo_id` smallint(5) UNSIGNED NOT NULL,
  `condicion_id` tinyint(3) UNSIGNED NOT NULL,
  `turno_id` tinyint(3) UNSIGNED NOT NULL,
  `modalidad_clase_id` tinyint(3) UNSIGNED NOT NULL,
  `carrera_id` int(11) NOT NULL,
  `estado` enum('BORRADOR','PENDIENTE','OBSERVADA','CONFIRMADA','ANULADA') NOT NULL DEFAULT 'BORRADOR',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `observaciones` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `matriculas`
--

INSERT INTO `matriculas` (`id`, `numero`, `estudiante_id`, `periodo_id`, `condicion_id`, `turno_id`, `modalidad_clase_id`, `carrera_id`, `estado`, `fecha_registro`, `observaciones`) VALUES
(2, '00001', 5, 1, 1, 1, 1, 11, 'CONFIRMADA', '2026-09-02 18:25:38', NULL),
(7, '00002', 10, 1, 1, 1, 1, 15, 'CONFIRMADA', '2026-09-02 21:50:40', NULL),
(8, '00003', 11, 1, 1, 1, 1, 19, 'CONFIRMADA', '2026-09-02 22:52:43', NULL),
(9, '00004', 12, 5, 1, 1, 1, 13, 'CONFIRMADA', '2026-09-03 08:28:13', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modalidades_clase`
--

CREATE TABLE `modalidades_clase` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modalidades_clase`
--

INSERT INTO `modalidades_clase` (`id`, `nombre`) VALUES
(1, 'PRESENCIAL'),
(2, 'VIRTUAL');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodos`
--

CREATE TABLE `periodos` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Volcado de datos para la tabla `periodos`
--

INSERT INTO `periodos` (`id`, `nombre`, `fecha_inicio`, `fecha_fin`, `activo`) VALUES
(1, '2026-I', '2026-01-01', '2026-07-31', 1),
(4, '2026-II', '2026-08-01', '2026-12-31', 1),
(5, '2027-I', '2027-01-01', '2027-07-31', 1),
(6, '2027-II', '2027-08-01', '2027-12-31', 1),
(7, '2028-I', '2028-01-01', '2028-07-31', 1),
(8, '2028-II', '2028-08-01', '2028-12-31', 1),
(9, '2027-1', '2026-08-01', '2027-02-28', 1),
(10, '2027-2', '2027-03-01', '2027-07-31', 1),
(11, '2028-1', '2027-08-01', '2027-12-31', 1),
(12, '2028-2', '2028-01-01', '2028-07-31', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preparaciones_previas`
--

CREATE TABLE `preparaciones_previas` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preparaciones_previas`
--

INSERT INTO `preparaciones_previas` (`id`, `nombre`) VALUES
(1, 'ACADEMIA'),
(3, 'AUTOPREPARACION'),
(2, 'COLEGIO'),
(4, 'OTRO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultados_examen`
--

CREATE TABLE `resultados_examen` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `matricula_id` bigint(20) UNSIGNED NOT NULL,
  `fecha_examen` date DEFAULT NULL,
  `puntaje` decimal(6,2) DEFAULT NULL,
  `aprobado` tinyint(1) DEFAULT NULL,
  `observaciones` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sectores`
--

CREATE TABLE `sectores` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sectores`
--

INSERT INTO `sectores` (`id`, `nombre`) VALUES
(3, 'OTRO'),
(2, 'PRIVADO'),
(1, 'PUBLICO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_archivo`
--

CREATE TABLE `tipos_archivo` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_archivo`
--

INSERT INTO `tipos_archivo` (`id`, `nombre`) VALUES
(6, 'CERTIFICADO_DISCAPACIDAD'),
(2, 'COPIA_DOCUMENTO'),
(1, 'FOTO_CARNET');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`id`, `nombre`) VALUES
(8, 'ESCOLAR'),
(1, 'MANANA'),
(3, 'NOCHE'),
(2, 'TARDE');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubigeos`
--

CREATE TABLE `ubigeos` (
  `codigo` char(6) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `nivel` enum('DEPARTAMENTO','PROVINCIA','DISTRITO') NOT NULL,
  `codigo_padre` char(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ubigeos`
--

INSERT INTO `ubigeos` (`codigo`, `nombre`, `nivel`, `codigo_padre`) VALUES
('01', 'Amazonas', 'DEPARTAMENTO', NULL),
('02', 'Ancash', 'DEPARTAMENTO', NULL),
('03', 'Apurimac', 'DEPARTAMENTO', NULL),
('04', 'Arequipa', 'DEPARTAMENTO', NULL),
('05', 'Ayacucho', 'DEPARTAMENTO', NULL),
('06', 'Cajamarca', 'DEPARTAMENTO', NULL),
('07', 'Callao', 'DEPARTAMENTO', NULL),
('08', 'Cusco', 'DEPARTAMENTO', NULL),
('09', 'Huancavelica', 'DEPARTAMENTO', NULL),
('10', 'Huanuco', 'DEPARTAMENTO', NULL),
('11', 'Ica', 'DEPARTAMENTO', NULL),
('12', 'Junin', 'DEPARTAMENTO', NULL),
('13', 'La Libertad', 'DEPARTAMENTO', NULL),
('14', 'Lambayeque', 'DEPARTAMENTO', NULL),
('15', 'Lima', 'DEPARTAMENTO', NULL),
('16', 'Loreto', 'DEPARTAMENTO', NULL),
('17', 'Madre de Dios', 'DEPARTAMENTO', NULL),
('18', 'Moquegua', 'DEPARTAMENTO', NULL),
('19', 'Pasco', 'DEPARTAMENTO', NULL),
('20', 'Piura', 'DEPARTAMENTO', NULL),
('21', 'Puno', 'DEPARTAMENTO', NULL),
('22', 'San Martin', 'DEPARTAMENTO', NULL),
('23', 'Tacna', 'DEPARTAMENTO', NULL),
('24', 'Tumbes', 'DEPARTAMENTO', NULL),
('25', 'Ucayali', 'DEPARTAMENTO', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `rol` enum('ADMIN','SECRETARIO','DOCENTE') DEFAULT 'SECRETARIO',
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_usuario`, `email`, `contrasena_hash`, `nombre_completo`, `rol`, `estado`, `ultimo_acceso`, `fecha_creacion`) VALUES
(1, 'admin', 'admin@cepre.com', '$2y$12$ItVj5aQ0tVZAwNJCgzQqM.4NV2xew8PwPDJMtx4WpO6DZAGVrcSX2', 'Administrador del Sistema', 'ADMIN', 'ACTIVO', '2026-09-04 21:00:34', '2026-09-02 22:25:07'),
(2, 'secretario', 'secretario@cepre.com', '$2y$10$2A.SFvFk9f4E9QYjKMTn4OGNW9DnTBO6pYmNwZ9kT7sFE6XWXwLx2', 'Secretario Académico', 'SECRETARIO', 'ACTIVO', NULL, '2026-09-02 22:25:07');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_estadisticas_carreras`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_estadisticas_carreras` (
`carrera_postula` varchar(100)
,`total_estudiantes` bigint(21)
,`turno_manana` decimal(22,0)
,`turno_tarde` decimal(22,0)
,`turno_noche` decimal(22,0)
,`masculino` decimal(22,0)
,`femenino` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_estudiantes_activos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_estudiantes_activos` (
`id_estudiante` int(11)
,`numero_matricula` varchar(10)
,`nombre_completo` varchar(203)
,`apellido_paterno` varchar(50)
,`apellido_materno` varchar(50)
,`nombres` varchar(100)
,`numero_documento` varchar(20)
,`email` varchar(100)
,`telefono_celular` varchar(15)
,`carrera_postula` varchar(100)
,`turno` enum('MANANA','TARDE','NOCHE')
,`condicion` enum('ORDINARIO','EXTRAORDINARIO')
,`modalidad` enum('REGULAR','INTENSIVO','SEMIPRESENCIAL')
,`fecha_registro_format` varchar(10)
,`fecha_registro` timestamp
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_estadisticas_carreras`
--
DROP TABLE IF EXISTS `v_estadisticas_carreras`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_estadisticas_carreras`  AS SELECT `estudiantes`.`carrera_postula` AS `carrera_postula`, count(0) AS `total_estudiantes`, sum(case when `estudiantes`.`turno` = 'MANANA' then 1 else 0 end) AS `turno_manana`, sum(case when `estudiantes`.`turno` = 'TARDE' then 1 else 0 end) AS `turno_tarde`, sum(case when `estudiantes`.`turno` = 'NOCHE' then 1 else 0 end) AS `turno_noche`, sum(case when `estudiantes`.`sexo` = 'MASCULINO' then 1 else 0 end) AS `masculino`, sum(case when `estudiantes`.`sexo` = 'FEMENINO' then 1 else 0 end) AS `femenino` FROM `estudiantes` WHERE `estudiantes`.`estado` = 'ACTIVO' GROUP BY `estudiantes`.`carrera_postula` ORDER BY count(0) DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_estudiantes_activos`
--
DROP TABLE IF EXISTS `v_estudiantes_activos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_estudiantes_activos`  AS SELECT `estudiantes`.`id_estudiante` AS `id_estudiante`, `estudiantes`.`numero_matricula` AS `numero_matricula`, concat(`estudiantes`.`apellido_paterno`,' ',`estudiantes`.`apellido_materno`,', ',`estudiantes`.`nombres`) AS `nombre_completo`, `estudiantes`.`apellido_paterno` AS `apellido_paterno`, `estudiantes`.`apellido_materno` AS `apellido_materno`, `estudiantes`.`nombres` AS `nombres`, `estudiantes`.`numero_documento` AS `numero_documento`, `estudiantes`.`email` AS `email`, `estudiantes`.`telefono_celular` AS `telefono_celular`, `estudiantes`.`carrera_postula` AS `carrera_postula`, `estudiantes`.`turno` AS `turno`, `estudiantes`.`condicion` AS `condicion`, `estudiantes`.`modalidad` AS `modalidad`, date_format(`estudiantes`.`fecha_registro`,'%d/%m/%Y') AS `fecha_registro_format`, `estudiantes`.`fecha_registro` AS `fecha_registro` FROM `estudiantes` WHERE `estudiantes`.`estado` = 'ACTIVO' ORDER BY `estudiantes`.`fecha_registro` DESC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos_matricula`
--
ALTER TABLE `archivos_matricula`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_archivo_matricula_tipo` (`matricula_id`,`tipo_archivo_id`),
  ADD KEY `fk_archivos_tipo` (`tipo_archivo_id`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_auditoria`);

--
-- Indices de la tabla `carreras`
--
ALTER TABLE `carreras`
  ADD PRIMARY KEY (`id_carrera`),
  ADD UNIQUE KEY `nombre_carrera` (`nombre_carrera`);

--
-- Indices de la tabla `condiciones_matricula`
--
ALTER TABLE `condiciones_matricula`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`id_config`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `domicilios`
--
ALTER TABLE `domicilios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_domicilio_estudiante_tipo` (`estudiante_id`,`tipo`),
  ADD KEY `fk_domicilios_ubigeo` (`ubigeo_codigo`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id_estudiante`),
  ADD UNIQUE KEY `numero_matricula` (`numero_matricula`),
  ADD UNIQUE KEY `numero_documento` (`numero_documento`),
  ADD UNIQUE KEY `codigo_estudiante` (`codigo_estudiante`),
  ADD KEY `idx_documento` (`numero_documento`),
  ADD KEY `idx_nombres` (`apellido_paterno`,`apellido_materno`,`nombres`),
  ADD KEY `idx_carrera` (`carrera_postula`),
  ADD KEY `idx_matricula` (`numero_matricula`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `informacion_academica`
--
ALTER TABLE `informacion_academica`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula_id` (`matricula_id`),
  ADD KEY `fk_academica_departamento` (`departamento_ubigeo`),
  ADD KEY `fk_academica_provincia` (`provincia_ubigeo`),
  ADD KEY `fk_academica_distrito` (`distrito_ubigeo`),
  ADD KEY `fk_academica_sector` (`sector_id`),
  ADD KEY `fk_academica_preparacion` (`preparacion_previa_id`);

--
-- Indices de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD UNIQUE KEY `uq_matricula_estudiante_periodo` (`estudiante_id`,`periodo_id`),
  ADD KEY `fk_matriculas_periodo` (`periodo_id`),
  ADD KEY `fk_matriculas_condicion` (`condicion_id`),
  ADD KEY `fk_matriculas_turno` (`turno_id`),
  ADD KEY `fk_matriculas_modalidad` (`modalidad_clase_id`),
  ADD KEY `fk_matriculas_carrera` (`carrera_id`);

--
-- Indices de la tabla `modalidades_clase`
--
ALTER TABLE `modalidades_clase`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `periodos`
--
ALTER TABLE `periodos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `preparaciones_previas`
--
ALTER TABLE `preparaciones_previas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `resultados_examen`
--
ALTER TABLE `resultados_examen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula_id` (`matricula_id`);

--
-- Indices de la tabla `sectores`
--
ALTER TABLE `sectores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `tipos_archivo`
--
ALTER TABLE `tipos_archivo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `ubigeos`
--
ALTER TABLE `ubigeos`
  ADD PRIMARY KEY (`codigo`),
  ADD KEY `fk_ubigeos_padre` (`codigo_padre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos_matricula`
--
ALTER TABLE `archivos_matricula`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_auditoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `carreras`
--
ALTER TABLE `carreras`
  MODIFY `id_carrera` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `condiciones_matricula`
--
ALTER TABLE `condiciones_matricula`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `domicilios`
--
ALTER TABLE `domicilios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id_estudiante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `informacion_academica`
--
ALTER TABLE `informacion_academica`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `modalidades_clase`
--
ALTER TABLE `modalidades_clase`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `periodos`
--
ALTER TABLE `periodos`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `preparaciones_previas`
--
ALTER TABLE `preparaciones_previas`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `resultados_examen`
--
ALTER TABLE `resultados_examen`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sectores`
--
ALTER TABLE `sectores`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tipos_archivo`
--
ALTER TABLE `tipos_archivo`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos_matricula`
--
ALTER TABLE `archivos_matricula`
  ADD CONSTRAINT `fk_archivos_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_archivos_tipo` FOREIGN KEY (`tipo_archivo_id`) REFERENCES `tipos_archivo` (`id`);

--
-- Filtros para la tabla `domicilios`
--
ALTER TABLE `domicilios`
  ADD CONSTRAINT `fk_domicilios_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_domicilios_ubigeo` FOREIGN KEY (`ubigeo_codigo`) REFERENCES `ubigeos` (`codigo`);

--
-- Filtros para la tabla `informacion_academica`
--
ALTER TABLE `informacion_academica`
  ADD CONSTRAINT `fk_academica_departamento` FOREIGN KEY (`departamento_ubigeo`) REFERENCES `ubigeos` (`codigo`),
  ADD CONSTRAINT `fk_academica_distrito` FOREIGN KEY (`distrito_ubigeo`) REFERENCES `ubigeos` (`codigo`),
  ADD CONSTRAINT `fk_academica_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_academica_preparacion` FOREIGN KEY (`preparacion_previa_id`) REFERENCES `preparaciones_previas` (`id`),
  ADD CONSTRAINT `fk_academica_provincia` FOREIGN KEY (`provincia_ubigeo`) REFERENCES `ubigeos` (`codigo`),
  ADD CONSTRAINT `fk_academica_sector` FOREIGN KEY (`sector_id`) REFERENCES `sectores` (`id`);

--
-- Filtros para la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD CONSTRAINT `fk_matriculas_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id_carrera`),
  ADD CONSTRAINT `fk_matriculas_condicion` FOREIGN KEY (`condicion_id`) REFERENCES `condiciones_matricula` (`id`),
  ADD CONSTRAINT `fk_matriculas_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id_estudiante`),
  ADD CONSTRAINT `fk_matriculas_modalidad` FOREIGN KEY (`modalidad_clase_id`) REFERENCES `modalidades_clase` (`id`),
  ADD CONSTRAINT `fk_matriculas_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`),
  ADD CONSTRAINT `fk_matriculas_turno` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`);

--
-- Filtros para la tabla `resultados_examen`
--
ALTER TABLE `resultados_examen`
  ADD CONSTRAINT `fk_resultados_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ubigeos`
--
ALTER TABLE `ubigeos`
  ADD CONSTRAINT `fk_ubigeos_padre` FOREIGN KEY (`codigo_padre`) REFERENCES `ubigeos` (`codigo`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
