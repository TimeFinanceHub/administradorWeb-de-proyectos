# Documentación Técnica

## 1. Visión General del Proyecto

Este proyecto es un sistema de gestión de proyectos ligero basado en la web. Su característica principal es el uso de un concepto de "blockchain" para almacenar los datos de los proyectos de forma segura, donde cada proyecto es un "bloque" en una cadena y la integridad de la cadena se verifica criptográficamente.

### Características Principales

*   **Autenticación de Usuarios:** Registro e inicio de sesión.
*   **Blockchain de Proyectos:** Los proyectos se almacenan como bloques inmutables (conceptualemente).
*   **Gestión de Proyectos:** Operaciones CRUD para proyectos (bloques).
*   **Gestión de Tareas y Etiquetas:** Asignación de tareas y etiquetas a los proyectos.
*   **Subida de Archivos:** Adjuntar archivos a los proyectos.
*   **Reportes:** Un dashboard con estadísticas sobre el estado de los proyectos y tareas.

## 2. Requisitos del Sistema

*   **Servidor web:** Apache, Nginx, o similar.
*   **PHP:** v7.4 o superior.
*   **Extensiones de PHP:**
    *   `pdo`
    *   `pdo_mysql`
*   **Base de Datos:** MySQL 5.7 o superior (o MariaDB 10.2 o superior).
*   **Herramientas de línea de comandos:**
    *   `mysql`
    *   `composer`
    *   `git`

## 3. Instalación

Sigue estos pasos para configurar el proyecto en un entorno de desarrollo local.

### 1. Clonar el Repositorio

```bash
git clone <URL_DEL_REPOSITORIO>
cd <NOMBRE_DEL_DIRECTORIO>
```

### 2. Instalar Dependencias de PHP

Usa Composer para instalar las dependencias y generar el autoloader.

```bash
composer install
```

### 3. Ejecutar el Script de Configuración Interactivo

El script `setup.sh` te guiará para crear el archivo `.env` con las credenciales, la base de datos, el usuario, y las tablas necesarias.

1.  Asegúrate de que el script sea ejecutable:
    ```bash
    chmod +x setup.sh
    ```
2.  Ejecuta el script:
    ```bash
    ./setup.sh
    ```
    Sigue las instrucciones en pantalla. Se te pedirá la contraseña de `root` de MySQL para poder crear la base de datos y el nuevo usuario.

### 4. Iniciar el Servidor

Puedes usar el servidor web incorporado de PHP para un desarrollo rápido:

```bash
php -S localhost:8000
```

Ahora puedes acceder al proyecto en tu navegador visitando `http://localhost:8000`.

## 4. Esquema de la Base de Datos

A continuación se muestran las sentencias SQL que el script `setup.sh` ejecuta.

```sql
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `telefono` VARCHAR(50),
  `contrasena` VARCHAR(255) NOT NULL,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `bloques` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_usuario` INT NOT NULL,
  `indice_cadena` INT NOT NULL,
  `fecha_creacion` DATETIME NOT NULL,
  `datos` TEXT NOT NULL,
  `hash_anterior` VARCHAR(64) NOT NULL,
  `hash` VARCHAR(64) NOT NULL,
  `alias` VARCHAR(255) DEFAULT '',
  `estado` VARCHAR(50) DEFAULT 'activo',
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `etiquetas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `bloque_etiquetas` (
  `id_bloque` INT NOT NULL,
  `id_etiqueta` INT NOT NULL,
  PRIMARY KEY (`id_bloque`, `id_etiqueta`),
  FOREIGN KEY (`id_bloque`) REFERENCES `bloques`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_etiqueta`) REFERENCES `etiquetas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `tareas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_bloque` INT NOT NULL,
  `descripcion` TEXT NOT NULL,
  `estado` VARCHAR(50) DEFAULT 'pendiente',
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_bloque`) REFERENCES `bloques`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `registros_de_cambios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_bloque` INT NOT NULL,
  `id_usuario` INT NOT NULL,
  `nombre_campo` VARCHAR(100) NOT NULL,
  `valor_antiguo` TEXT,
  `valor_nuevo` TEXT,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_bloque`) REFERENCES `bloques`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `archivos_media` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_bloque` INT NOT NULL,
  `id_usuario` INT NOT NULL,
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `tipo_mime` VARCHAR(100) NOT NULL,
  `subido_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_bloque`) REFERENCES `bloques`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
```

## 5. Estructura del Proyecto

El proyecto sigue una estructura MVC (Modelo-Vista-Controlador) y utiliza un enrutador simple (Front Controller).

```
.
├── composer.json
├── config/
│   └── db.php
├── docs/
│   └── documentacion_tecnica.md
├── index.php
├── setup.sh
├── src/
│   ├── controladores/
│   │   ├── ControladorBase.php
│   │   ├── ControladorMedia.php
│   │   ├── ControladorProyecto.php
│   │   ├── ControladorReporte.php
│   │   └── ControladorTarea.php
│   └── modelos/
│       ├── ModeloBloque.php
│       └── ModeloReporte.php
├── uploads/
└── views/
    └── ... (archivos .phtml)
```

*   **`index.php`**: Es el punto de entrada único (Front Controller). Procesa todas las solicitudes, inicializa la aplicación y llama al controlador apropiado.
*   **`src/`**: Contiene todo el código fuente de la aplicación, siguiendo el estándar PSR-4.
*   **`src/controladores/`**: Maneja la lógica de la aplicación, procesa la entrada del usuario y prepara los datos para las vistas.
*   **`src/modelos/`**: Gestiona la lógica de negocio y la interacción con la base de datos.
*   **`views/`**: Contiene las plantillas de presentación (HTML/PHP).
*   **`config/`**: Almacena los archivos de configuración (ej. conexión a la base de datos).
*   **`vendor/`**: Directorio gestionado por Composer que contiene las dependencias.

## 6. Autocarga (PSR-4)

El proyecto utiliza Composer para la autocarga de clases, siguiendo el estándar PSR-4. El archivo `composer.json` mapea los namespaces a sus directorios correspondientes.

```json
"autoload": {
    "psr-4": {
        "App\\Controladores\\": "src/controladores/",
        "App\\Modelos\\": "src/modelos/"
    }
}
```

Esto significa que una clase como `App\Controladores\ControladorProyecto` se buscará automáticamente en `src/controladores/ControladorProyecto.php`.
