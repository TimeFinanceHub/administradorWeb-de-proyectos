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

### 3. Configurar el Entorno

El proyecto utiliza un archivo `.env` para gestionar las credenciales de la base de datos.

1.  Copia el archivo de ejemplo:
    ```bash
    cp .env.example .env
    ```
2.  Abre el archivo `.env` y, si es necesario, modifica las credenciales de la base de datos para que coincidan con tu configuración local.

    ```dotenv
    DB_HOST=localhost
    DB_DATABASE=project_db
    DB_USERNAME=admin
    DB_PASSWORD=admin
    ```

### 4. Ejecutar el Script de Configuración

El script `setup.sh` creará la base de datos y el usuario en MySQL, y luego ejecutará las migraciones para crear todas las tablas necesarias.

1.  Asegúrate de que el script sea ejecutable:
    ```bash
    chmod +x setup.sh
    ```
2.  Ejecuta el script:
    ```bash
    ./setup.sh
    ```
    **Nota:** Se te podría pedir la contraseña de `root` de MySQL para crear la base de datos y el nuevo usuario.

### 5. Iniciar el Servidor

Puedes usar el servidor web incorporado de PHP para un desarrollo rápido:

```bash
php -S localhost:8000
```

Ahora puedes acceder al proyecto en tu navegador visitando `http://localhost:8000`.

## 4. Esquema de la Base de Datos

A continuación se muestran las sentencias SQL para crear todas las tablas.

```sql
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(50),
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `blocks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `block_index` INT NOT NULL,
  `timestamp` DATETIME NOT NULL,
  `data` TEXT NOT NULL,
  `previous_hash` VARCHAR(64) NOT NULL,
  `hash` VARCHAR(64) NOT NULL,
  `alias` VARCHAR(255) DEFAULT '',
  `status` VARCHAR(50) DEFAULT 'active',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `block_tags` (
  `block_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  PRIMARY KEY (`block_id`, `tag_id`),
  FOREIGN KEY (`block_id`) REFERENCES `blocks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `block_id` INT NOT NULL,
  `description` TEXT NOT NULL,
  `status` VARCHAR(50) DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`block_id`) REFERENCES `blocks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `changelogs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `block_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `field_name` VARCHAR(100) NOT NULL,
  `old_value` TEXT,
  `new_value` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`block_id`) REFERENCES `blocks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `media_files` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `block_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`block_id`) REFERENCES `blocks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
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
│   └── technical_documentation.md
├── index.php
├── setup.sh
├── src/
│   ├── controllers/
│   │   ├── BaseController.php
│   │   ├── MediaController.php
│   │   ├── ProjectController.php
│   │   ├── ReportController.php
│   │   └── TaskController.php
│   └── models/
│       ├── BlockModel.php
│       └── ReportModel.php
├── uploads/
└── views/
    ├── auth.phtml
    ├── dashboard.phtml
    └── ... (otras vistas)
```

*   **`index.php`**: Es el punto de entrada único (Front Controller). Procesa todas las solicitudes, inicializa la aplicación y llama al controlador apropiado.
*   **`src/`**: Contiene todo el código fuente de la aplicación, siguiendo el estándar PSR-4.
*   **`src/controllers/`**: Maneja la lógica de la aplicación, procesa la entrada del usuario y prepara los datos para las vistas.
*   **`src/models/`**: Gestiona la lógica de negocio y la interacción con la base de datos.
*   **`views/`**: Contiene las plantillas de presentación (HTML/PHP).
*   **`config/`**: Almacena los archivos de configuración (ej. conexión a la base de datos).
*   **`vendor/`**: Directorio gestionado por Composer que contiene las dependencias.

## 6. Autocarga (PSR-4)

El proyecto utiliza Composer para la autocarga de clases, siguiendo el estándar PSR-4. El archivo `composer.json` mapea el namespace `App\` al directorio `src/`.

```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

Esto significa que una clase como `App\Controllers\ProjectController` se buscará automáticamente en `src/controllers/ProjectController.php`.
