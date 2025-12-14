<?php
namespace App\Models;
use PDO;
// models/ReportModel.php

class ReportModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // En models/ReportModel.php

    public function getProjectStatusCounts($userId) {
        $stmt = $this->pdo->prepare("
            SELECT status, COUNT(id) as count 
            FROM blocks 
            WHERE user_id = ? 
              AND status IN ('active', 'in_progress', 'completed', 'archived') 
            GROUP BY status
        ");
        $stmt->execute([$userId]);
        
        // ... (restante lógica de formateo)
        $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $all_statuses = ['active', 'in_progress', 'completed', 'archived'];
        $formatted_stats = [];
        foreach ($all_statuses as $status) {
            $formatted_stats[$status] = $counts[$status] ?? 0;
        }
        
        return $formatted_stats;
    }

    public function getGlobalTaskProgress($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(t.id) as total_tasks, 
                SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
            FROM tasks t
            JOIN blocks b ON t.block_id = b.id
            WHERE b.user_id = ? 
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // ... (restante lógica de cálculo de porcentaje)
        $total = $result['total_tasks'];
        $completed = $result['completed_tasks'];
        
        if ($total > 0) {
            $progress = round(($completed / $total) * 100);
        } else {
            $progress = 0;
        }
    
        return [
            'total' => (int)$total,
            'completed' => (int)$completed,
            'progress' => $progress
        ];
    }
}