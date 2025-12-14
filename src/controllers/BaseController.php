<?php
namespace App\Controllers;
use PDO;

class BaseController {
    
    protected $pdo;
    protected $message;

    // Constructor: Inicializa la conexión PDO y el mensaje de retroalimentación
    public function __construct($pdo, $message = null) {
        $this->pdo = $pdo;
        $this->message = $message;
    }

    // Método para cargar y mostrar una vista
    protected function render($viewName, $data = []) {
        // La función 'extract' convierte las claves del array $data 
        // (ej: 'blocks', 'user_name') en variables locales ($blocks, $user_name) 
        // para que sean accesibles en la vista.
        extract($data); 
        
        // Incluye el archivo de vista (ej: 'views/dashboard.phtml')
        require 'views/' . $viewName . '.phtml';
    }

    // Método para realizar una redirección HTTP
    protected function redirect($url) {
        header("Location: " . $url);
        exit();
    }
}