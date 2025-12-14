<?php
namespace App\Controladores;
use PDO;

class ControladorBase {
    
    protected $pdo;
    protected $mensaje;

    // Constructor: Inicializa la conexión PDO y el mensaje de retroalimentación
    public function __construct($pdo, $mensaje = null) {
        $this->pdo = $pdo;
        $this->mensaje = $mensaje;
    }

    // Método para cargar y mostrar una vista
    protected function renderizar($nombreVista, $datos = []) {
        // La función 'extract' convierte las claves del array $datos 
        // (ej: 'bloques', 'nombre_usuario') en variables locales ($bloques, $nombre_usuario) 
        // para que sean accesibles en la vista.
        extract($datos); 
        
        // Incluye el archivo de vista (ej: 'views/dashboard.phtml')
        require 'views/' . $nombreVista . '.phtml';
    }

    // Método para realizar una redirección HTTP
    protected function redireccionar($url) {
        header("Location: " . $url);
        exit();
    }
}