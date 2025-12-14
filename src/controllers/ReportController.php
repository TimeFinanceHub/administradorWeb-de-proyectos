<?php
namespace App\Controllers;

// controllers/ReportController.php

use App\Models\ReportModel; 

class ReportController extends BaseController {
    
    private $reportModel;

    public function __construct($pdo, $message = null) {
        // Llama al constructor de BaseController para inicializar $pdo y $message
        parent::__construct($pdo, $message);
        $this->reportModel = new ReportModel($pdo);
    }

    /**
     * Muestra el dashboard de reportes y estadísticas.
     */
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('index.php');
        }

        $userId = $_SESSION['user_id'];
        
        // Obtener datos clave del ReportModel
        $stats = $this->reportModel->getProjectStatusCounts($userId);
        $progress = $this->reportModel->getGlobalTaskProgress($userId);
        
        // Renderizar la vista de reportes
        $this->render('report_dashboard', [
            'stats' => $stats,
            'progress' => $progress,
            'message' => $this->message
        ]);
    }
}