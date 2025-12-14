<?php
namespace App\Modelos;
use PDO;

class ModeloReporte {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function obtenerConteoEstadoProyectos($idUsuario) {
        $stmt = $this->pdo->prepare("
            SELECT estado, COUNT(id) as conteo 
            FROM bloques 
            WHERE id_usuario = ? 
              AND estado IN ('activo', 'en_progreso', 'completado', 'archivado') 
            GROUP BY estado
        ");
        $stmt->execute([$idUsuario]);
        
        $conteos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $todosLosEstados = ['activo', 'en_progreso', 'completado', 'archivado'];
        $estadisticasFormateadas = [];
        foreach ($todosLosEstados as $estado) {
            $estadisticasFormateadas[$estado] = $conteos[$estado] ?? 0;
        }
        
        return $estadisticasFormateadas;
    }

    public function obtenerProgresoGlobalTareas($idUsuario) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(t.id) as total_tareas, 
                SUM(CASE WHEN t.estado = 'completada' THEN 1 ELSE 0 END) as tareas_completadas
            FROM tareas t
            JOIN bloques b ON t.id_bloque = b.id
            WHERE b.id_usuario = ? 
        ");
        $stmt->execute([$idUsuario]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
        $total = $resultado['total_tareas'];
        $completadas = $resultado['tareas_completadas'];
        
        if ($total > 0) {
            $progreso = round(($completadas / $total) * 100);
        } else {
            $progreso = 0;
        }
    
        return [
            'total' => (int)$total,
            'completadas' => (int)$completadas,
            'progreso' => $progreso
        ];
    }
}