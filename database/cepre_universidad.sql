-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-09-2026 a las 03:40:55
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
(1, 1, 1, 'Tipografia.jpg', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/0b3c85ae104d1087dd5c34e37b4a21bb.jpg', 'image/jpeg', 240267, '363ed0eae1d5da6b916f926c35014758a6851b1817b23c94f4922ab4a5b8f958', '2026-09-07 11:38:10'),
(2, 1, 2, 'Tipografia.pdf', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/9949910cc11898d302d64ae58e1e3bd6.pdf', 'application/pdf', 95688, 'e629d14d58d716e738abfb0acd6f7364315283449956449ae9b162157b76c78a', '2026-09-07 11:38:10'),
(3, 1, 7, 'mayores.pdf', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/9263cbea2ca2fb78d3ba4953440ae7e2.pdf', 'application/pdf', 84418, '63f3c7b53444a7f04a76d3ad898dd8624dad195942109e7b4539c116b8be6986', '2026-09-07 11:38:10'),
(4, 2, 1, 'images.jpg', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/e3ec989cde8155b5c042e847115ce427.jpg', 'image/jpeg', 4475, '0b7b0a7f3414a4ed0cfe6aedaa57e9b596ccdb4e541226574f86b691b91ea6b3', '2026-09-08 19:33:35'),
(5, 2, 2, '11557950844DJ-Anexo-1-VF.pdf', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/31ebcfa74ad0dc6b4a82e3d4a05efcc7.pdf', 'application/pdf', 100337, 'a54501e6e848c93ea9fdcd5a0d8e4929c71af7a770ffcd3bedaf91a3f312c3b3', '2026-09-08 19:33:35'),
(6, 2, 7, '11557950844DJ-Anexo-1-VF.pdf', 'C:\\xampp\\htdocs\\cepre_untels\\app\\models/../storage/matriculas/2be9aa8eeb8ff1b22cfb40d1d2cc4a32.pdf', 'application/pdf', 100337, 'a54501e6e848c93ea9fdcd5a0d8e4929c71af7a770ffcd3bedaf91a3f312c3b3', '2026-09-08 19:33:35');

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
(1, 'Ingeniería de Sistemas', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(2, 'Ingeniería Electrónica y Telecomunicaciones', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(3, 'Ingeniería Mecánica y Eléctrica', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(4, 'Ingeniería Ambiental', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(5, 'Administración de Empresas', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(6, 'Ingeniería de Software', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(7, 'Ingeniería de Ciencia de Datos e Inteligencia Artificial', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(8, 'Ingeniería Mecatrónica', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(9, 'Ingeniería Industrial', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33'),
(10, 'Marketing y Negocios Internacionales', 'Carrera profesional', 'ACTIVO', '2026-09-02 22:48:33');

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
(1, 'Con derecho a vacante (Ingreso Directo)'),
(2, 'Solo Preparación (Sin ingreso directo)');

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
-- Estructura de tabla para la tabla `consultas_contacto`
--

CREATE TABLE `consultas_contacto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `telefono` varchar(40) NOT NULL,
  `mensaje` text NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'PENDIENTE',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `consultas_contacto`
--

INSERT INTO `consultas_contacto` (`id`, `nombre`, `correo`, `telefono`, `mensaje`, `estado`, `fecha_registro`) VALUES
(1, 'Prueba Contacto 2', 'prueba.contacto2@example.com', '999111223', 'Mensaje de prueba guardado en base de datos', 'PENDIENTE', '2026-09-09 01:08:26'),
(2, 'Juan Pérez', 'juan.perez@email.com', '987654321', 'Consulta sobre los ciclos académicos', 'PENDIENTE', '2026-09-09 01:14:26'),
(3, 'JOrge', 'a@gmail.com', '987654321', 'aaaaaaa', 'PENDIENTE', '2026-09-09 01:38:13');

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
  `pais_actual` varchar(50) DEFAULT NULL,
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

INSERT INTO `estudiantes` (`id_estudiante`, `numero_matricula`, `codigo_estudiante`, `apellido_paterno`, `apellido_materno`, `nombres`, `tipo_documento`, `numero_documento`, `sexo`, `fecha_nacimiento`, `email`, `telefono_casa`, `telefono_celular`, `pais_actual`, `departamento_actual`, `provincia_actual`, `distrito_actual`, `direccion_actual`, `pais_nacimiento`, `departamento_nacimiento`, `provincia_nacimiento`, `distrito_nacimiento`, `anio_concluye_secundaria`, `institucion_educativa`, `preparacion_anterior`, `mencion`, `carrera_postula`, `tiene_enfermedad`, `tratamiento`, `nombre_apoderado`, `telefono_apoderado`, `como_se_entero`, `modalidad`, `condicion`, `turno`, `estado`, `fecha_registro`, `fecha_actualizacion`) VALUES
(1, '00001', '10001', 'GONZALES', 'Monge', 'Luis', 'DNI', '47582563', 'MASCULINO', '2004-06-12', 'gonzales@gmail.com', NULL, '984748385', 'Perú', 'Lima', 'Lima', '3928', NULL, 'Perú', 'Lima', 'Lima', '3928', NULL, 'Innova School', 'ACADEMIA', 'ADUNI', 'Ingeniería Mecatrónica', 0, NULL, NULL, NULL, NULL, 'REGULAR', '', 'MANANA', 'ACTIVO', '2026-09-07 16:38:10', '2026-09-07 16:38:10'),
(2, '00002', '10002', 'GONZALES', 'BARBARAN', 'FELICITA', 'DNI', '09668423', 'MASCULINO', '2005-02-12', 'a@gmail.com', NULL, '987456213', 'Perú', 'Lima', 'Lima', '3928', NULL, 'Perú', 'Lima', 'Lima', '3928', '2019', 'inova school', 'ACADEMIA', 'aduni', 'Ingeniería de Sistemas', 0, NULL, NULL, NULL, NULL, 'REGULAR', '', 'MANANA', 'ACTIVO', '2026-09-09 00:33:35', '2026-09-09 00:33:35');

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
  `departamento_extranjero` varchar(120) DEFAULT NULL,
  `provincia_extranjera` varchar(120) DEFAULT NULL,
  `distrito_extranjero` varchar(120) DEFAULT NULL,
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

INSERT INTO `informacion_academica` (`id`, `matricula_id`, `anio_conclusion_secundaria`, `pais`, `departamento_ubigeo`, `provincia_ubigeo`, `distrito_ubigeo`, `departamento_extranjero`, `provincia_extranjera`, `distrito_extranjero`, `sector_id`, `especificar_sector`, `nombre_institucion`, `nombre_institucion_extranjera`, `preparacion_previa_id`, `mencion`, `tiene_discapacidad`, `tipo_discapacidad`, `otro_tipo_discapacidad`, `grado_discapacidad`, `necesidades_especiales`, `tiene_certificado_discapacidad`, `como_se_entero_cepre`) VALUES
(1, 1, '2026', 'Perú', '15', NULL, NULL, NULL, NULL, NULL, 1, 'Publico', 'Innova School', NULL, 1, 'ADUNI', 0, NULL, NULL, NULL, NULL, 0, 'whatsapp'),
(2, 2, '2019', 'Perú', '15', NULL, NULL, NULL, NULL, NULL, 1, 'publico', 'inova school', NULL, 1, 'aduni', 0, NULL, NULL, NULL, NULL, 0, 'amigo_familiar');

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
  `medio_pago` varchar(30) DEFAULT NULL,
  `codigo_voucher` varchar(50) DEFAULT NULL,
  `estado` enum('BORRADOR','PENDIENTE','OBSERVADA','CONFIRMADA','ANULADA') NOT NULL DEFAULT 'BORRADOR',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `observaciones` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `matriculas`
--

INSERT INTO `matriculas` (`id`, `numero`, `estudiante_id`, `periodo_id`, `condicion_id`, `turno_id`, `modalidad_clase_id`, `carrera_id`, `medio_pago`, `codigo_voucher`, `estado`, `fecha_registro`, `observaciones`) VALUES
(1, '00001', 1, 5, 1, 1, 2, 8, NULL, NULL, 'CONFIRMADA', '2026-09-07 11:38:10', NULL),
(2, '00002', 2, 5, 1, 1, 2, 1, 'CAJA_UNTELS', '12as', 'CONFIRMADA', '2026-09-08 19:33:35', NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `periodos`
--

INSERT INTO `periodos` (`id`, `nombre`, `fecha_inicio`, `fecha_fin`, `activo`) VALUES
(1, '2026-I', '2026-01-01', '2026-07-31', 1),
(4, '2026-II', '2026-08-01', '2026-12-31', 1),
(5, '2027-I', '2027-01-01', '2027-07-31', 1),
(6, '2027-II', '2027-08-01', '2027-12-31', 1),
(7, '2028-I', '2028-01-01', '2028-07-31', 1),
(8, '2028-II', '2028-08-01', '2028-12-31', 1);

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
(7, 'DECLARACION_JURADA'),
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
(1, 'admin', 'admin@cepre.com', '$2y$12$ItVj5aQ0tVZAwNJCgzQqM.4NV2xew8PwPDJMtx4WpO6DZAGVrcSX2', 'Administrador del Sistema', 'ADMIN', 'ACTIVO', '2026-09-08 23:37:23', '2026-09-02 22:25:07'),
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
-- Indices de la tabla `consultas_contacto`
--
ALTER TABLE `consultas_contacto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_consultas_contacto_estado` (`estado`),
  ADD KEY `idx_consultas_contacto_fecha` (`fecha_registro`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_auditoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `carreras`
--
ALTER TABLE `carreras`
  MODIFY `id_carrera` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
-- AUTO_INCREMENT de la tabla `consultas_contacto`
--
ALTER TABLE `consultas_contacto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `domicilios`
--
ALTER TABLE `domicilios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id_estudiante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `informacion_academica`
--
ALTER TABLE `informacion_academica`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `modalidades_clase`
--
ALTER TABLE `modalidades_clase`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `periodos`
--
ALTER TABLE `periodos`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
