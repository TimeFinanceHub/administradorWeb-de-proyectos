# Administrador Web de Proyectos (rg-glez-tfh-syntax-sanctuary/admin-of-pjs)

![Banner de Proyecto](rg012-logo.png)

Este proyecto es un sistema de gestión de proyectos ligero y basado en la web, que incorpora un concepto simplificado de "blockchain" para garantizar la integridad de los datos de tus proyectos. Cada proyecto se almacena como un "bloque" en una cadena digital inmutable, lo que permite un seguimiento seguro y verificable de su historial.

## 🚀 Características Principales

*   **Gestión de Proyectos:** Crea, edita y organiza tus proyectos de forma eficiente.
*   **Seguimiento de Tareas:** Desglosa tus proyectos en tareas manejables y sigue su progreso.
*   **Integridad de Datos:** Utiliza un mecanismo similar a blockchain para asegurar que el historial de tus proyectos no sea alterado.
*   **Subida de Archivos:** Adjunta documentos y medios relevantes a cada proyecto.
*   **Autenticación de Usuarios:** Sistema seguro de registro e inicio de sesión para múltiples usuarios.
*   **Reportes y Estadísticas:** Visualiza el progreso de tus proyectos y tareas a través de un panel de reportes.

## 🛠️ Requisitos del Sistema

Para ejecutar este proyecto, necesitas:

*   PHP v7.4 o superior (con extensiones `pdo` y `pdo_mysql`)
*   Servidor web (Apache, Nginx, PHP-FPM)
*   MySQL 5.7+ o MariaDB 10.2+
*   Composer (para la gestión de dependencias PHP)
*   Cliente de línea de comandos de MySQL (para el script de instalación)

## 📦 Instalación

Sigue estos pasos para poner en marcha el proyecto:

1.  **Clona el repositorio:**
    ```bash
    git clone git@github.com:TimeFinanceHub/administradorWeb-de-proyectos.git
    cd administradorWeb-de-proyectos
    ```

2.  **Instala las dependencias de Composer:**
    ```bash
    composer install
    ```

3.  **Ejecuta el script de configuración interactivo:**
    Este script te guiará para crear el archivo `.env` (con las credenciales de la DB), configurar la base de datos, el usuario y todas las tablas necesarias.
    ```bash
    chmod +x setup.sh
    ./setup.sh
    ```

4.  **Inicia el servidor web (ej. con el servidor integrado de PHP):**
    ```bash
    php -S localhost:8000
    ```
    Luego, abre tu navegador y ve a `http://localhost:8000`.

Para una guía de instalación más detallada, consulta `docs/documentacion_tecnica.md`.

## 📖 Documentación

*   **[Documentación Técnica](docs/documentacion_tecnica.md)**: Información detallada sobre la arquitectura, base de datos, estructura del código y más.
*   **[Manual de Usuario](docs/manual_de_usuario.md)**: Una guía completa para el usuario final sobre cómo utilizar todas las funcionalidades de la aplicación.
*   **[Guía de Pruebas](docs/guia_de_pruebas.md)**: Pasos para verificar que todas las características del proyecto funcionan correctamente.

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Si encuentras un error o tienes una sugerencia de mejora, por favor abre un _issue_ o envía un _pull request_.

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Consulta el archivo [LICENSE](LICENSE) para más detalles.

---

**Desarrollado con pasión por [Ramiro G Glez.](mailto:ramirogglez31@gmail.com) para la comunidad de [Mostly PHP Software](mailto:mostlyphpsoftware@gmail.com).**
