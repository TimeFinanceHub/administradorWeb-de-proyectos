# Project Overview

This project is a web-based application for managing projects using a simplified blockchain concept. Each project is a "block" in a chain, and the integrity of the chain is cryptographically verified. Users can register, log in, and manage their own chains of projects.

## Key Features

*   **User Authentication:** Users can register and log in to the application.
*   **Blockchain:** Projects are stored as blocks in a chain, with each block being linked to the previous one through a hash.
*   **Project Management:** Users can add, edit, and delete projects (blocks).
*   **Tasks and Tags:** Users can add tasks and tags to their projects.
*   **Reporting:** The application provides a reporting dashboard.
*   **Media Uploads:** Users can upload media files.

## Technologies Used

*   **Backend:** PHP
*   **Database:** MySQL
*   **Frontend:** PHP templates (.phtml files) with HTML, CSS, and JavaScript.

# Building and Running

This is a standard PHP application. There is no build process.

## Running the Application

1.  **Web Server:** You need a web server (like Apache or Nginx) with PHP support.
2.  **Database:**
    *   Set up a MySQL database with the credentials specified in `config/db.php`.
    *   The database schema needs to be created. You can likely find the schema in a `.sql` file or infer it from the model files.
3.  **Deployment:**
    *   Copy the project files to the web root of your server.
    *   Access the application through `index.php` in your web browser.

# Development Conventions

## Code Structure

*   **`config/`:** Contains configuration files, such as database settings.
*   **`controllers/`:** Contains the controller classes that handle user requests.
*   **`models/`:** Contains the model classes that interact with the database.
*   **`views/`:** Contains the presentation files (phtml templates).
*   **`uploads/`:** Directory for file uploads.
*   **`index.php`:** The single entry point for all requests (front controller).

## Naming Conventions

*   **Classes:** PascalCase (e.g., `ProjectController`).
*   **Methods:** camelCase (e.g., `addProject`).
*   **Variables:** camelCase or snake_case.

## Database Interaction

*   The application uses PDO for database interaction.
*   Models encapsulate all database logic.
