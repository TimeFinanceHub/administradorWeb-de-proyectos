#!/bin/bash

# Este script configura la base de datos y las tablas para el proyecto.
#
# USO:
# 1. Asegúrate de tener el cliente de línea de comandos de MySQL instalado.
# 2. Edita las variables DB_USER, DB_PASS, y DB_NAME a continuación si no estás utilizando los valores por defecto.
# 3. Opcionalmente, puedes crear un archivo .env y el script lo usará.
# 4. Ejecuta el script: ./setup.sh

# --- Configuración ---
DB_USER="admin"
DB_PASS="admin"
DB_NAME="project_db"
DB_HOST="localhost"

# --- Script ---

# Leer desde .env si existe
if [ -f .env ]; then
    export $(cat .env | sed 's/#.*//g' | xargs)
    DB_USER=${DB_USERNAME:-$DB_USER}
    DB_PASS=${DB_PASSWORD:-$DB_PASS}
    DB_NAME=${DB_DATABASE:-$DB_NAME}
    DB_HOST=${DB_HOST:-$DB_HOST}
fi

echo "--- Iniciando configuración de la base de datos ---"
echo "Host: $DB_HOST"
echo "Usuario: $DB_USER"
echo "Base de datos: $DB_NAME"

# SQL para crear la base de datos y el usuario
SQL_CREATE_DB_AND_USER="
CREATE DATABASE IF NOT EXISTS \
`$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'$DB_HOST' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \
`$DB_NAME`.* TO '$DB_USER'@'$DB_HOST';
FLUSH PRIVILEGES;
"

# SQL para crear las tablas
SQL_CREATE_TABLES="
USE \
`$DB_NAME`;

CREATE TABLE IF NOT EXISTS \
`users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(50),
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS \
`blocks` (
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

CREATE TABLE IF NOT EXISTS \
`tags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS \
`block_tags` (
  `block_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  PRIMARY KEY (`block_id`, `tag_id`),
  FOREIGN KEY (`block_id`) REFERENCES `blocks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS \
`tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `block_id` INT NOT NULL,
  `description` TEXT NOT NULL,
  `status` VARCHAR(50) DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`block_id`) REFERENCES `blocks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS \
`changelogs` (
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

CREATE TABLE IF NOT EXISTS \
`media_files` (
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
"

# Ejecutar los comandos SQL
echo "Creando base de datos y usuario (si no existen)..."
echo "NOTA: Se te podría pedir la contraseña de root de MySQL."
mysql -u root -p -e "$SQL_CREATE_DB_AND_USER"

if [ $? -eq 0 ]; then
    echo "Base de datos y usuario configurados correctamente."
    echo "Creando tablas..."
    mysql -u"$DB_USER" -p"$DB_PASS" -D"$DB_NAME" -e "$SQL_CREATE_TABLES"
    if [ $? -eq 0 ]; then
        echo "¡Éxito! Todas las tablas han sido creadas en la base de datos '$DB_NAME'."
        echo "--- Configuración de la base de datos finalizada ---"
    else
        echo "Error: No se pudieron crear las tablas. Verifica los permisos del usuario '$DB_USER'."
    fi
else
    echo "Error: No se pudo crear la base de datos o el usuario. Verifica tu contraseña de root y los permisos de MySQL."
fi
