<?php
namespace App\Controllers;
// controllers/TaskController.php

use App\Models\BlockModel;

class TaskController extends BaseController {
    
    protected $blockModel;

    public function __construct($pdo, $message = null) {
        parent::__construct($pdo, $message);
        $this->blockModel = new BlockModel($pdo); // Necesitamos acceder a BlockModel para vincular tareas
    }

    // Muestra la vista de tareas para un bloque específico
    public function showTasks() {
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('index.php');
        }

        $blockId = $_GET['block_id'] ?? null;
        
        // 1. Obtener el bloque (para mostrar el alias y verificar pertenencia)
        $block = $this->blockModel->getBlockById($blockId);
        
        if (!$block || $block['user_id'] !== $_SESSION['user_id']) {
            $this->message = "Error: Proyecto no encontrado o no autorizado.";
            return $this->redirect('index.php');
        }

        // 2. Obtener todas las tareas asociadas a este bloque
        $tasks = $this->blockModel->getTasksByBlockId($blockId);

        // 3. Renderizar la vista específica de tareas
        $this->render('project_tasks', [
            'block' => $block,
            'tasks' => $tasks,
            'status_options' => ['pending', 'in_progress', 'completed']
        ]);
    }
    
    // Procesa el formulario para agregar una nueva tarea
    public function add() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('index.php');
        }
        
        $blockId = $_POST['block_id'] ?? null;
        $description = trim($_POST['description'] ?? '');

        if (!$blockId || empty($description)) {
            $this->message = "Error: Datos de tarea incompletos.";
            return $this->redirect("index.php?action=tasks&block_id=" . $blockId);
        }

        if ($this->blockModel->addTask($blockId, $description)) {
            $this->message = "Tarea añadida correctamente.";
        } else {
            $this->message = "Error al añadir la tarea.";
        }
        
        return $this->redirect("index.php?action=tasks&block_id=" . $blockId);
    }
    
    // Procesa la actualización del estado de una tarea
    public function updateStatus() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('index.php');
        }

        $taskId = $_POST['task_id'] ?? null;
        $newStatus = $_POST['status'] ?? null;
        $blockId = $_POST['block_id'] ?? null;

        if (!$taskId || !$newStatus || !$blockId) {
            $this->message = "Error: Datos de actualización incompletos.";
            return $this->redirect("index.php?action=tasks&block_id=" . $blockId);
        }
        
        if ($this->blockModel->updateTaskStatus($taskId, $newStatus)) {
            $this->message = "Estado de tarea actualizado.";
        } else {
            $this->message = "Error al actualizar el estado.";
        }
        
        return $this->redirect("index.php?action=tasks&block_id=" . $blockId);
    }
    // Procesa la eliminación de una tarea.
    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('index.php');
        }
    
        // Capturamos los IDs desde la URL (método GET)
        $taskId = $_GET['task_id'] ?? null;
        $blockId = $_GET['block_id'] ?? null; // Necesario para redirigir correctamente
    
        if (!$taskId || !$blockId) {
            $this->message = "Error: ID de tarea o bloque no especificado.";
            return $this->redirect("index.php?action=tasks&block_id=" . $blockId);
        }
        
        if ($this->blockModel->deleteTask($taskId)) {
            $this->message = "Tarea eliminada correctamente. [R012-G013]";
        } else {
            $this->message = "Error al eliminar la tarea.";
        }
        
        // Redirigir de vuelta a la lista de tareas de ese bloque
        return $this->redirect("index.php?action=tasks&block_id=" . $blockId);
    }
}