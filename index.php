<?php
ob_start();
session_start();

// 1. Cargar Composer Autoloader
require_once 'vendor/autoload.php';

// 2. Cargar Configuración y Conexión a DB
require_once 'config/db.php';

// Importar las clases de los controladores
use App\Controllers\ProjectController;
use App\Controllers\TaskController;
use App\Controllers\ReportController;
use App\Controllers\MediaController;

// 4. Determinar la Acción (Routing Básico)
$action = $_GET['action'] ?? 'home';

// 5. Determinar qué Controlador y qué Método usar
$controllerInstance = null;
$method = '';

// --- Lógica para Tareas (Módulo G013) ---
if (strpos($action, 'task') !== false) {
    $controllerInstance = new TaskController($pdo);
    
    switch ($action) {
        case 'tasks':
            $method = 'showTasks';
            break;
        case 'add_task':
            $method = 'add';
            break;
        case 'update_task_status':
            $method = 'updateStatus';
            break;
        case 'delete_task': // <--- ¡Esta línea ahora está protegida!
            $method = 'delete';
            break;
        default:
            // Si es una acción de tarea desconocida, volver al dashboard
            $method = 'index'; 
            $controllerInstance = new ProjectController($pdo); 
            break;
    }
} elseif ($action === 'reportes') { 
    $controllerInstance = new ReportController($pdo);
    $method = 'index';
} elseif ($action === 'upload_media' || $action === 'process_upload' || $action === 'delete_media') { // <-- NUEVO BLOQUE MEDIA HUB
    $controllerInstance = new MediaController($pdo);
    
    switch ($action) {
        case 'upload_media':
            $method = 'showUploadForm'; // Muestra la vista con el formulario
            break;
        case 'process_upload':
            $method = 'upload'; // Maneja la subida POST
            break;
        case 'delete_media': // <--- ¡DEBE ESTAR ESTO!
            $method = 'deleteFile'; // <-- O el nombre del método que uses
            break;
        default:
            // Asegurarse de manejar el caso por defecto si la acción no coincide
            $method = 'showUploadForm'; 
            break;
    }
// --- Lógica para Proyectos y Autenticación (Módulo G012/Auth) ---
} else {
    $controllerInstance = new ProjectController($pdo);
    
    switch ($action) {
        case 'logout':
            $method = 'logout';
            break;
        case 'register':
        case 'login':
            // Estas acciones dependen del método POST, que se maneja dentro del call_user_func
            $method = $action;
            break;
        case 'add_project':
            $method = 'addProject';
            break;
        case 'delete':
            $method = 'softDelete';
            break;
        case 'edit':
            $method = 'editMetadata';
            break;
        case 'update_metadata':
            $method = 'processEdit';
            break;
        case 'reset_chain':
            $method = 'resetChain';
            break;
        default:
            $method = 'index'; // 'home' o acción por defecto
            break;
    }
}


if ($controllerInstance && $method && method_exists($controllerInstance, $method)) {

$params = [];
// Asignar los parámetros para los métodos que los necesiten
if ($method === 'register' || $method === 'login' || $method === 'addProject' || $method === 'upload') {
$params[] = $_POST;
}

 // 1. Llamar al método del controlador
 call_user_func_array([$controllerInstance, $method], $params);
    
    // 2. FORZAR LA TERMINACIÓN
    // Si la llamada no terminó con un redirect/exit() (lo cual es el caso si el header falla),
    // terminamos aquí para evitar caer en el fallback del ProjectController::index().
    exit(); // <--- ¡AÑADE ESTA LÍNEA CRÍTICA!
    
} else {
// Si no se encuentra el controlador/método, forzar al inicio de sesión o dashboard
$controllerInstance = new ProjectController($pdo);
$controllerInstance->index();
}
