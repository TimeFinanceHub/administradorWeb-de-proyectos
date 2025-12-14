#!/bin/bash

# Este script configura la base de datos y las tablas para el proyecto de forma interactiva.

# --- Banner de Bienvenida ---
echo "======================================================"
echo " Asistente de Configuración del Proyecto AdminOfPjs "
echo "======================================================"
echo

# --- Configuración por Defecto ---
DB_USER_DEFAULT="proyecto_user"
DB_PASS_DEFAULT="password_seguro_123"
DB_NAME_DEFAULT="proyecto_db"
DB_HOST_DEFAULT="localhost"

# --- Script ---
echo "Este script te guiará para configurar la base de datos."
echo "Puedes presionar Enter para aceptar los valores por defecto."
echo

# --- Recopilar Información del Usuario ---
read -p "Introduce el nombre de host de la DB [defecto: $DB_HOST_DEFAULT]: " DB_HOST
DB_HOST=${DB_HOST:-$DB_HOST_DEFAULT}

read -p "Introduce el nombre para la nueva base de datos [defecto: $DB_NAME_DEFAULT]: " DB_NAME
DB_NAME=${DB_NAME:-$DB_NAME_DEFAULT}

read -p "Introduce el nombre para el nuevo usuario de la DB [defecto: $DB_USER_DEFAULT]: " DB_USER
DB_USER=${DB_USER:-$DB_USER_DEFAULT}

read -sp "Introduce la contraseña para el nuevo usuario '$DB_USER' [defecto: $DB_PASS_DEFAULT]: " DB_PASS
DB_PASS=${DB_PASS:-$DB_PASS_DEFAULT}
echo
echo

# --- Guardar en .env ---
echo "Guardando configuración en el archivo .env..."
echo "DB_HOST=$DB_HOST" > .env
echo "DB_DATABASE=$DB_NAME" >> .env
echo "DB_USERNAME=$DB_USER" >> .env
echo "DB_PASSWORD=$DB_PASS" >> .env
echo "Configuración guardada."
echo

# --- Interacción con MySQL ---
read -sp "Por favor, introduce la contraseña del usuario 'root' de MySQL para continuar: " MYSQL_ROOT_PASS
echo
echo

echo "--- Iniciando configuración de la base de datos ---"
echo "Host: $DB_HOST"
echo "Usuario a crear: $DB_USER"
echo "Base de datos a crear: $DB_NAME"

# SQL para crear la base de datos y el usuario
SQL_CREATE_DB_AND_USER="
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'$DB_HOST' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'$DB_HOST';
FLUSH PRIVILEGES;
"

# SQL para crear las tablas (traducido)
SQL_CREATE_TABLES="
USE \`$DB_NAME\`;

CREATE TABLE IF NOT EXISTS \`usuarios\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`nombre\` VARCHAR(255) NOT NULL,
  \`email\` VARCHAR(255) NOT NULL UNIQUE,
  \`telefono\` VARCHAR(50),
  \`contrasena\` VARCHAR(255) NOT NULL,
  \`fecha_creacion\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS \`bloques\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`id_usuario\` INT NOT NULL,
  \`indice_cadena\` INT NOT NULL,
  \`fecha_creacion\` DATETIME NOT NULL,
  \`datos\` TEXT NOT NULL,
  \`hash_anterior\` VARCHAR(64) NOT NULL,
  \`hash\` VARCHAR(64) NOT NULL,
  \`alias\` VARCHAR(255) DEFAULT '',
  \`estado\` VARCHAR(50) DEFAULT 'activo',
  FOREIGN KEY (\`id_usuario\`) REFERENCES \`usuarios\`(\`id\`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS \`etiquetas\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`nombre\` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS \`bloque_etiquetas\` (
  \`id_bloque\` INT NOT NULL,
  \`id_etiqueta\` INT NOT NULL,
  PRIMARY KEY (\`id_bloque\`, \`id_etiqueta\`),
  FOREIGN KEY (\`id_bloque\`) REFERENCES \`bloques\`(\`id\`) ON DELETE CASCADE,
  FOREIGN KEY (\`id_etiqueta\`) REFERENCES \`etiquetas\`(\`id\`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS \`tareas\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`id_bloque\` INT NOT NULL,
  \`descripcion\` TEXT NOT NULL,
  \`estado\` VARCHAR(50) DEFAULT 'pendiente',
  \`fecha_creacion\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (\`id_bloque\`) REFERENCES \`bloques\`(\`id\`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS \`registros_de_cambios\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`id_bloque\` INT NOT NULL,
  \`id_usuario\` INT NOT NULL,
  \`nombre_campo\` VARCHAR(100) NOT NULL,
  \`valor_antiguo\` TEXT,
  \`valor_nuevo\` TEXT,
  \`fecha_creacion\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (\`id_bloque\`) REFERENCES \`bloques\`(\`id\`) ON DELETE CASCADE,
  FOREIGN KEY (\`id_usuario\`) REFERENCES \`usuarios\`(\`id\`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS \`archivos_media\` (
  \`id\` INT AUTO_INCREMENT PRIMARY KEY,
  \`id_bloque\` INT NOT NULL,
  \`id_usuario\` INT NOT NULL,
  \`nombre_archivo\` VARCHAR(255) NOT NULL,
  \`ruta_archivo\` VARCHAR(255) NOT NULL,
  \`tipo_mime\` VARCHAR(100) NOT NULL,
  \`subido_en\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (\`id_bloque\`) REFERENCES \`bloques\`(\`id\`) ON DELETE CASCADE,
  FOREIGN KEY (\`id_usuario\`) REFERENCES \`usuarios\`(\`id\`) ON DELETE CASCADE
) ENGINE=InnoDB;
"

# Ejecutar los comandos SQL
echo "Creando base de datos y usuario (si no existen)..."
mysql -u root -p"$MYSQL_ROOT_PASS" -e "$SQL_CREATE_DB_AND_USER"

if [ $? -eq 0 ]; then
    echo "Base de datos y usuario configurados correctamente."
    echo "Creando tablas..."
    mysql -u"$DB_USER" -p"$DB_PASS" -D"$DB_NAME" -e "$SQL_CREATE_TABLES"
    if [ $? -eq 0 ]; then
        echo "¡Éxito! Todas las tablas han sido creadas en la base de datos '$DB_NAME'."
        echo "--- Configuración de la base de datos finalizada ---"
    else
        echo "Error: No se pudieron crear las tablas. Verifica los permisos del usuario '$DB_USER' y la contraseña."
    fi
else
    echo "Error: No se pudo crear la base de datos o el usuario. Verifica tu contraseña de root y los permisos de MySQL."
fi

