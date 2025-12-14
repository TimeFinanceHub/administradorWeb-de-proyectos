<?php
namespace App\Controladores;

use App\Modelos\ModeloReporte; 

class ControladorReporte extends ControladorBase {
    
    private $modeloReporte;

    public function __construct($pdo, $mensaje = null) {
        parent::__construct($pdo, $mensaje);
        $this->modeloReporte = new ModeloReporte($pdo);
    }

    /**
     * Muestra el dashboard de reportes y estadísticas.
     */
    public function inicio() {
        if (!isset($_SESSION['id_usuario'])) {
            return $this->redireccionar('index.php');
        }

        $idUsuario = $_SESSION['id_usuario'];
        
        // Obtener datos clave del ModeloReporte
        $estadisticas = $this->modeloReporte->obtenerConteoEstadoProyectos($idUsuario);
        $progreso = $this->modeloReporte->obtenerProgresoGlobalTareas($idUsuario);
        
        // Renderizar la vista de reportes
        $this->renderizar('report_dashboard', [
            'estadisticas' => $estadisticas,
            'progreso' => $progreso,
            'mensaje' => $this->mensaje
        ]);
    }
}