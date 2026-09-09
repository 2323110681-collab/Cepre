# CEPRE UNTELS

Sistema web para registrar y consultar matrículas del Centro Preuniversitario de la UNTELS. Está desarrollado en PHP nativo, utiliza MySQL/MariaDB y se ejecuta localmente con XAMPP.

## Requisitos

- Windows con XAMPP.
- Apache activo.
- MySQL activo.
- PHP 8.0 o superior con `pdo_mysql` habilitado.
- Navegador web moderno.

## Instalación en XAMPP

1. Copiar el proyecto en:

   `C:\xampp\htdocs\cepre_untels`

2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. Crear o verificar la base de datos `cepre_universidad` en phpMyAdmin.
4. Para una instalación nueva, importar `database/cepre_universidad.sql`.
5. Revisar la conexión en `config/database.php`:

   - Servidor: `127.0.0.1`
   - Puerto: `3306`
   - Base de datos: `cepre_universidad`
   - Usuario: `root`
   - Contraseña: vacía por defecto en XAMPP

6. Abrir:

   `http://localhost/cepre_untels/public/`

No se debe volver a importar el SQL sobre una base que ya contiene matrículas sin realizar antes una copia de seguridad.


## Acceso

El sistema tiene dos entradas:

- **Alumnos:** `http://localhost/cepre_untels/public/login_alumnos.php`. Solicita DNI, entidad de pago y número de operación.
- **Personal administrativo:** `http://localhost/cepre_untels/public/login.php`. Usa las credenciales registradas en la tabla `usuarios`.

Las pantallas de matrícula, consulta y edición requieren una sesión activa. Los reportes y la administración de fichas requieren un perfil distinto de `ALUMNO`.

La sesión utiliza cookies `HttpOnly`, protección `SameSite` y tokens CSRF para formularios que modifican datos.

### Estado del acceso de alumnos

El formulario de alumnos ya crea una sesión con rol `ALUMNO`, pero la verificación del DNI y del número de operación todavía está pendiente de integración con la API o servicio de pagos correspondiente. Por ahora se valida que ambos campos no estén vacíos; no debe considerarse una autenticación de producción.

## Funcionalidades

### Matrícula

La pantalla principal permite registrar:

- Datos personales y documento de identidad.
- Datos de contacto.
- Domicilio actual y lugar de nacimiento.
- Semestre, modalidad, turno y carrera.
- Modalidad de ingreso: **Con derecho a vacante (Ingreso Directo)** o **Solo Preparación (Sin ingreso directo)**.
- Modalidad de clase: actualmente solo **VIRTUAL**.
- Periodo de matrícula `2027-1` para el ciclo vigente.
- Información del colegio y preparación anterior.
- Foto carnet y copia del documento.
- Discapacidad y necesidades de apoyo.
- Cómo se enteró de la CEPRE UNTELS.

El DNI puede consultarse mediante `public/api/dni.php` si la configuración de RENIEC está disponible.

### Formulario de contacto

`public/contacto.php` permite registrar consultas de postulantes. El formulario:

- Valida nombre, correo, teléfono y mensaje en el servidor.
- Verifica el token CSRF.
- Valida reCAPTCHA v2 antes de guardar la consulta.
- Guarda los datos en la tabla `consultas_contacto` con estado `PENDIENTE`.
- Muestra una confirmación con SweetAlert2 después de guardar correctamente.

Para una base existente, crea la tabla ejecutando:

```sql
CREATE TABLE IF NOT EXISTS consultas_contacto (
  id INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(150) NOT NULL,
  correo VARCHAR(150) NOT NULL,
  telefono VARCHAR(40) NOT NULL,
  mensaje TEXT NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_consultas_contacto_estado (estado),
  KEY idx_consultas_contacto_fecha (fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Campos condicionales

En `app/views/matricula/formulario.php` se encuentra la sección **Información académica**.

- Si discapacidad es **No**, se ocultan, desactivan y limpian los campos de discapacidad.
- Si discapacidad es **Sí**, aparecen tipo, especificación de “Otra”, grado, necesidades especiales y certificado.
- Si en “Cómo se enteró” se selecciona **Otro**, aparece un campo para escribir el motivo.
- El motivo personalizado se guarda como `Otro: motivo`.

El comportamiento dinámico está en `public/js/app.js`. La validación también se realiza en el servidor dentro de `app/models/MatriculaModel.php`.

### Consulta y edición

- `public/fichas.php`: lista estudiantes y muestra una ficha completa.
- `public/editar.php`: permite actualizar datos de la matrícula.
- `public/archivo.php`: entrega archivos asociados a una matrícula autorizada.

### APIs

- `public/api/dni.php`: consulta de DNI cuando RENIEC está configurado.
- `public/api/ubigeos.php`: consulta catálogos de departamentos, provincias y distritos.
- `public/api/extranjeras.php`: consulta catálogos de países y ubicaciones extranjeras.

### Reportes

`public/reportes.php` está disponible desde el enlace **Reportes** del encabezado. Permite filtrar por semestre y rango de fechas, y muestra:

- Inscripciones por día.
- Inscripciones por sexo.
- Inscripciones por carrera.
- Distrito de procedencia.
- Colegio público o privado.
- Cómo se enteró de la CEPRE UNTELS.
- Total de inscripciones del periodo filtrado.

Las consultas estadísticas están implementadas en el método `reportes()` de `app/models/MatriculaModel.php`.

### Códigos por turno

Los códigos de alumno tienen como máximo cinco dígitos y usan series independientes:

- Mañana y tarde: códigos de `10001` a `19999`.
- Escolar: códigos de `20001` a `29999`.

El primer dígito identifica el grupo y los cuatro restantes son el correlativo. Un código escolar no consume la numeración de mañana/tarde. Los códigos antiguos de 9 dígitos no se reutilizan ni se modifican.

## Estructura del proyecto

```text
app/
  controllers/       Coordinación de solicitudes, especialmente matrícula.
  models/            Acceso a datos y reglas de negocio.
  views/             Vistas PHP y componentes compartidos.
  data/              Catálogos JSON de ubigeos.
  storage/           Archivos subidos de matrículas.
config/
  auth.php           Sesiones, autenticación y CSRF.
  database.php       Conexión PDO a MySQL.
  recaptcha.php      Claves y validación de Google reCAPTCHA v2.
  reniec.php         Configuración de consulta de DNI.
database/
  cepre_universidad.sql
                     Esquema y datos iniciales de la base.
public/
  index.php          Entrada para registrar matrículas.
  login.php          Inicio de sesión.
  fichas.php         Consulta de estudiantes.
  editar.php         Edición de fichas.
  contacto.php       Formulario público de consultas.
  reportes.php       Reportes estadísticos.
  api/               Endpoints JSON.
  css/               Estilos.
  js/                Comportamiento de los formularios públicos y de matrícula.
```

## Flujo de una matrícula

1. `public/index.php` verifica la sesión y llama a `MatriculaController`.
2. `MatriculaController` carga catálogos o recibe el formulario enviado.
3. `MatriculaModel::validateData()` valida los datos recibidos.
4. `MatriculaModel::registrar()` guarda estudiante, matrícula e información académica dentro de una transacción.
5. `storeFiles()` valida y almacena foto y documento en `app/storage/matriculas/`.
6. El usuario es redirigido mediante PRG para evitar reenvíos al refrescar.

## Base de datos

La información académica se guarda en `informacion_academica`. Los campos relacionados con discapacidad son:

- `tiene_discapacidad`
- `tipo_discapacidad`
- `otro_tipo_discapacidad`
- `grado_discapacidad`
- `necesidades_especiales`
- `tiene_certificado_discapacidad`
- `como_se_entero_cepre`

Las tablas más importantes son `estudiantes`, `matriculas`, `informacion_academica`, `carreras`, `periodos`, `sectores`, `ubigeos`, `archivos_matricula` y `usuarios`.

## Solución de problemas

- **No conecta a MySQL:** comprobar que MySQL está iniciado y que `pdo_mysql` aparece en `php -m`.
- **No aparecen cambios visuales:** usar `Ctrl + F5` para limpiar la caché del navegador.
- **No permite guardar discapacidad:** seleccionar tipo y grado, completar necesidades especiales y, si corresponde, especificar el tipo “Otra”.
- **No aparece el reporte:** iniciar sesión y acceder a `public/reportes.php`.
- **reCAPTCHA muestra “Invalid site key”:** comprobar que la clave sea de reCAPTCHA v2 Checkbox y que `localhost` esté registrado como dominio autorizado en Google.
- **El contacto no se guarda:** comprobar que exista la tabla `consultas_contacto`, que MySQL esté activo y que el reCAPTCHA se haya validado correctamente.
- **No importar nuevamente el SQL:** si la base ya tiene datos, hacer una exportación desde phpMyAdmin antes de modificar el esquema.

## Desarrollo

El proyecto no utiliza Composer, npm ni un framework frontend. Los cambios PHP pueden comprobarse con:

```powershell
C:\xampp\php\php.exe -l ruta\al\archivo.php
```

Los archivos subidos no deben exponerse directamente desde una URL pública; el acceso se realiza mediante `public/archivo.php`.
