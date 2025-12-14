<?php
$host = getenv('DB_HOST') ?: 'localhost'; 
$db = getenv('DB_DATABASE') ?: 'project_db';
$user = getenv('DB_USERNAME') ?: 'admin';
$pass = getenv('DB_PASSWORD') ?: 'admin';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexión exitosa."; // Descomentar para debug
} catch (PDOException $e) { 
    die("Error de conexión a la Base de Datos: " . $e->getMessage()); 
}