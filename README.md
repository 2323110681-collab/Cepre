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

