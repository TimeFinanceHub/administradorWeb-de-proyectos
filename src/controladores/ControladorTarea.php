<?php
namespace App\Controladores;

use App\Modelos\ModeloBloque;

class ControladorTarea extends ControladorBase {
    
    protected $modeloBloque;

    public function __construct($pdo, $mensaje = null) {
        parent::__construct($pdo, $mensaje);
        $this->modeloBloque = new ModeloBloque($pdo);
    }

    public function mostrarTareas() {
        if (!isset($_SESSION['id_usuario'])) {
            return $this->redireccionar('index.php');
        }

        $idBloque = $_GET['block_id'] ?? null;
        
        $bloque = $this->modeloBloque->obtenerBloquePorId($idBloque);
        
        if (!$bloque || $bloque['id_usuario'] !== $_SESSION['id_usuario']) {
            $this->mensaje = "Error: Proyecto no encontrado o no autorizado.";
            return $this->redireccionar('index.php');
        }

        $tareas = $this->modeloBloque->obtenerTareasPorIdBloque($idBloque);

        $this->renderizar('project_tasks', [
            'bloque' => $bloque,
            'tareas' => $tareas,
            'opcionesEstado' => ['pendiente', 'en_progreso', 'completada']
        ]);
    }
    
    public function agregar() {
        if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redireccionar('index.php');
        }
        
        $idBloque = $_POST['block_id'] ?? null;
        $descripcion = trim($_POST['description'] ?? '');

        if (!$idBloque || empty($descripcion)) {
            $this->mensaje = "Error: Datos de tarea incompletos.";
            return $this->redireccionar("index.php?action=tasks&block_id=" . $idBloque);
        }

        if ($this->modeloBloque->agregarTarea($idBloque, $descripcion)) {
            $this->mensaje = "Tarea añadida correctamente.";
        } else {
            $this->mensaje = "Error al añadir la tarea.";
        }
        
        return $this->redireccionar("index.php?action=tasks&block_id=" . $idBloque);
    }
    
    public function actualizarEstado() {
        if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redireccionar('index.php');
        }

        $idTarea = $_POST['task_id'] ?? null;
        $nuevoEstado = $_POST['status'] ?? null;
        $idBloque = $_POST['block_id'] ?? null;

        if (!$idTarea || !$nuevoEstado || !$idBloque) {
            $this->mensaje = "Error: Datos de actualización incompletos.";
            return $this->redireccionar("index.php?action=tasks&block_id=" . $idBloque);
        }
        
        if ($this->modeloBloque->actualizarEstadoTarea($idTarea, $nuevoEstado)) {
            $this->mensaje = "Estado de tarea actualizado.";
        } else {
            $this->mensaje = "Error al actualizar el estado.";
        }
        
        return $this->redireccionar("index.php?action=tasks&block_id=" . $idBloque);
    }

    public function eliminar() {
        if (!isset($_SESSION['id_usuario'])) {
            return $this->redireccionar('index.php');
        }
    
        $idTarea = $_GET['task_id'] ?? null;
        $idBloque = $_GET['block_id'] ?? null;
    
        if (!$idTarea || !$idBloque) {
            $this->mensaje = "Error: ID de tarea o bloque no especificado.";
            return $this->redireccionar("index.php?action=tasks&block_id=" . $idBloque);
        }
        
        if ($this->modeloBloque->eliminarTarea($idTarea)) {
            $this->mensaje = "Tarea eliminada correctamente.";
        } else {
            $this->mensaje = "Error al eliminar la tarea.";
        }
        
        return $this->redireccionar("index.php?action=tasks&block_id=" . $idBloque);
    }
}