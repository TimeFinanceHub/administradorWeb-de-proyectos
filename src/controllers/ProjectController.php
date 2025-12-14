<?php
namespace App\Controllers;

use App\Models\BlockModel;

// 2. Extender de BaseController
class ProjectController extends BaseController {
    protected $pdo;
    protected $message = "";
    protected $blockModel;

    public function __construct($pdo, $message = null) {
        // Llamada al constructor de BaseController
        parent::__construct($pdo, $message); 
        $this->blockModel = new BlockModel($pdo);
    }

    // --- FUNCIONES DE UTILIDAD ---
    
    // Función para renderizar una vista y pasarle datos
    protected function render($view, $data = []) {
        // Extraemos los datos para que las variables estén disponibles en la vista
        extract($data); 
        
        // La variable $message también debe estar disponible en la vista
        $message = $this->message;
        
        // Incluimos la vista (el HTML)
        require 'views/' . $view . '.phtml';
    }

    protected function redirect($location) {
        header("Location: $location");
        exit;
    }

    // --- RUTAS PRINCIPALES ---

    public function index() {
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        // Capturar filtros de la URL (si existen)
        $statusFilter = $_GET['status'] ?? 'all';
        $tagFilter = $_GET['tag'] ?? null;
        
        // 1. Obtener datos filtrados
        $blocks = $this->blockModel->getChainByUserId($userId, $statusFilter, $tagFilter);
        $isSecure = $this->blockModel->isValidChain($userId);
        
        // 2. Inyectar las etiquetas en cada bloque para la visualización en la interfaz
        foreach ($blocks as &$block) {
            $block['tags'] = $this->blockModel->getTagsByBlockId($block['id']);
        }
        unset($block); 

        // 3. Obtener todas las etiquetas existentes para el select del filtro
        $allTags = $this->blockModel->getAllTags();
        
        // 4. Renderizar la vista del Dashboard
        $this->render('dashboard', [
            'blocks' => $blocks,
            'isSecure' => $isSecure,
            'allTags' => $allTags,            // <-- NUEVO: Todas las tags para el filtro
            'currentStatusFilter' => $statusFilter, // <-- NUEVO: Estado actual seleccionado
            'currentTagFilter' => $tagFilter    // <-- NUEVO: Tag actual seleccionado
        ]);
    } else {
        $this->render('auth');
    }
}

    // --- GESTIÓN DE AUTENTICACIÓN ---

    public function register($postData) {
        // Lógica de Registro (movida desde index.php)
        $hashPass = password_hash($postData['password'], PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
        
        try {
            $stmt->execute([$postData['name'], $postData['email'], $postData['phone'], $hashPass]);
            $this->message = "Usuario registrado con éxito. Por favor inicia sesión.";
        } catch (Exception $e) { 
            // 23000 es el código de error para UNIQUE constraint violation (email duplicado)
            if ($e->getCode() === '23000') {
                $this->message = "Error: El correo electrónico ya existe.";
            } else {
                $this->message = "Error de registro: " . $e->getMessage();
            }
        }
        $this->index(); // Vuelve a mostrar la vista de autenticación con el mensaje
    }

    public function login($postData) {
        // Lógica de Login (movida desde index.php)
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$postData['email']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($postData['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $this->redirect('index.php'); // Redirecciona para limpiar el POST
        } else {
            $this->message = "Credenciales incorrectas o usuario no encontrado.";
            $this->index(); // Vuelve a mostrar la vista de autenticación con el error
        }
    }

    public function logout() {
        session_destroy();
        $this->redirect('index.php');
    }

    // --- GESTIÓN DE PROYECTOS (BLOQUES) ---
    
    public function addProject($postData) {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('index.php');
        }
        
        // Lógica de creación de Bloque (movida desde index.php)
        $this->blockModel->addBlock($_SESSION['user_id'], $postData['project_data']);
        $this->message = "Proyecto encriptado y guardado en la cadena.";
        
        $this->redirect('index.php'); // Redirecciona para evitar reenvío de formulario
    }

    // Nota: Aquí agregaremos los métodos updateMetadata y softDelete en la siguiente fase
    
    public function softDelete() {
    if (!isset($_SESSION['user_id'])) {
        $this->redirect('index.php');
    }
    
    // Obtenemos el ID del bloque a eliminar desde la URL (index.php?action=delete&id=123)
    $blockId = $_GET['id'] ?? null; 
    
    if (!$blockId || !is_numeric($blockId)) {
        $this->message = "Error: ID de bloque no válido.";
    } else {
        // Llamada al Modelo para ejecutar la Eliminación Suave
        $success = $this->blockModel->softDeleteBlock($blockId);
        
        if ($success) {
            $this->message = "Proyecto marcado como eliminado. ¡La cadena sigue intacta!";
        } else {
            $this->message = "Error al marcar el proyecto como eliminado.";
        }
    }
    
    $this->redirect('index.php'); // Redirecciona al dashboard
}

    // En controllers/ProjectController.php

    public function editMetadata() {
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('index.php');
        }
    
        $blockId = $_GET['id'] ?? null;
        $block = $this->blockModel->getBlockById($blockId);
    
        if (!$block || $block['user_id'] !== $_SESSION['user_id']) {
            $this->message = "Error: Proyecto no encontrado o no autorizado.";
            return $this->redirect('index.php');
        }
        
        // 1. Obtener la bitácora de cambios (Módulo G014)
        $changelog = $this->blockModel->getChangeLogByBlockId($blockId); 
    
        // 2. Definir los estados disponibles (¡CORRECCIÓN! Estaba faltando esta definición)
        $available_statuses = ['active', 'in_progress', 'completed', 'archived'];
    
        // 3. Obtener etiquetas para el formulario
        $allTags = $this->blockModel->getAllTags();
        $blockTags = $this->blockModel->getTagsByBlockId($blockId);
        $currentTags = array_column($blockTags, 'name');
        
        // 4. Crear la cadena de tags actual para el textarea (¡CORRECCIÓN! Estaba faltando esta definición)
        // Nota: La vista espera $current_tags_string, no $currentTags (que es un array)
        $current_tags_string = implode(', ', $currentTags); 
        
    
        $this->render('edit_metadata', [
            'block' => $block,
            'message' => $this->message,
            'allTags' => $allTags,
            'currentTags' => $currentTags,
            'changelog' => $changelog,
            'available_statuses' => $available_statuses,     // <--- ¡NUEVO!
            'current_tags_string' => $current_tags_string    // <--- ¡NUEVO!
        ]);
    }
    
    /**
     * Procesa los datos del formulario de edición de metadata y etiquetas.
     */
    public function processEdit() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('index.php');
        }
    
        $userId = $_SESSION['user_id'];
        $blockId = $_POST['block_id'] ?? null;
        $alias = trim($_POST['alias'] ?? '');
        $status = $_POST['status'] ?? '';
        $tagsString = $_POST['tags'] ?? '';
    
        // 1. Validar datos mínimos
        if (!$blockId || empty($alias) || empty($status)) {
            $this->message = "Error: Datos incompletos para actualizar el proyecto.";
            return $this->redirect("index.php?action=edit&id=" . $blockId);
        }
        
        // 2. Actualizar metadata del bloque (Alias y Status)
        // El método updateBlockMetadata ahora también maneja el log G014
        $blockUpdated = $this->blockModel->updateBlockMetadata($blockId, $alias, $status, $userId);
    
        // 3. Actualizar Etiquetas
        $tagsUpdated = $this->blockModel->updateBlockTags($blockId, $tagsString, $userId);
    
        if ($blockUpdated || $tagsUpdated) {
            $this->message = "Metadatos y Etiquetas actualizados correctamente. (G014 Loggeado)";
        } else {
            // Podría ser que no hubo cambios, o que falló la actualización
            $this->message = "Advertencia: No se detectaron cambios o hubo un error al guardar.";
        }
    
        // Redirigir de vuelta a la vista de edición para ver los cambios y el log
        return $this->redirect("index.php?action=edit&id=" . $blockId);
    }

    public function resetChain() {
    if (!isset($_SESSION['user_id'])) {
        return $this->redirect('index.php');
    }

    $userId = $_SESSION['user_id'];
    $timestamp = date('Y-m-d H:i:s');
    
    // Tablas a vaciar (DELETE FROM), excluyendo 'users'.
    // Orden: hijas/dependientes primero (por si hay FOREIGN KEYS).
    $tablesToClean = [
        'block_tags',   // Tablas de relación
        'changelogs',   // Logs de cambios
        'tasks',         // Tareas
        'tags',         // Si quieres limpiar los tags creados por el usuario
        'blocks',       // La cadena principal (debe ser la última)
    ];
    
    $totalRowsDeleted = 0;
    $tableStatus = [];

    try {
        $this->pdo->beginTransaction(); 

        // --- 1. LIMPIEZA DE TABLAS (Usando sólo DELETE FROM) ---
        foreach ($tablesToClean as $table) {
            
            try {
                // ELIMINAR FILAS: Utilizamos DELETE FROM para vaciar la tabla.
                $stmt_delete = $this->pdo->prepare("DELETE FROM `{$table}`");
                $stmt_delete->execute();
                $deletedRows = $stmt_delete->rowCount();
                $totalRowsDeleted += $deletedRows;
                
                $tableStatus[] = "✅ **{$table}**: Eliminados {$deletedRows} registros.";

            } catch (\PDOException $e) {
                // Si falla el DELETE (por Foreign Key o permiso), abortamos.
                $this->pdo->rollBack();
                return $this->redirect('index.php?message=❌ Error al eliminar filas de '.$table.'. Causa: '.$e->getMessage());
            }
        }
        
        // --- 2. CREAR EL NUEVO BLOQUE GÉNESIS (Reinicio Lógico) ---
        
        $genesis_index = 1; // <--- ¡Esto reinicia la cadena lógica!
        $genesis_data = 'Bloque Génesis Inicial';
        $genesis_previous_hash = str_repeat('0', 64);
        
        // Generamos el hash SÓLO con los datos que tenemos
        $genesis_hash = hash('sha256', $genesis_index . $timestamp . $genesis_data . $genesis_previous_hash); 
        
        $stmt_insert = $this->pdo->prepare("
            INSERT INTO blocks (user_id, index_in_chain, timestamp, data, previous_hash, hash)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt_insert->execute([
            $userId, 
            $genesis_index, 
            $timestamp, 
            $genesis_data, 
            $genesis_previous_hash, 
            $genesis_hash
        ]);

        $this->pdo->commit();
        
        // --- 3. GENERAR MENSAJE FINAL DETALLADO ---
        $finalMessage = "✅ **REINICIO EXITOSO!** Cadena lógica iniciada con índice 1. (El ID físico de las tablas no se pudo reiniciar)";
        $finalMessage .= "<br>Detalle de Limpieza: " . implode("<br>- ", $tableStatus);
        
        return $this->redirect('index.php?message=' . urlencode($finalMessage));

    } catch (\PDOException $e) { 
        $this->pdo->rollBack();
        return $this->redirect('index.php?message=❌ Error crítico e inesperado: ' . $e->getMessage());
    }
}
    
    
}