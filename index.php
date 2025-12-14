<?php
ob_start();
session_start();

// 1. Cargar Composer Autoloader
require_once 'vendor/autoload.php';

// 2. Cargar Configuración y Conexión a DB
require_once 'config/db.php';

// Importar las clases de los controladores
use App\Controladores\ControladorProyecto;
use App\Controladores\ControladorTarea;
use App\Controladores\ControladorReporte;
use App\Controladores\ControladorMedia;

// 4. Determinar la Acción (Routing Básico)
$accion = $_GET['action'] ?? 'inicio';

// 5. Determinar qué Controlador y qué Método usar
$instanciaControlador = null;
$metodo = '';

// --- Lógica para Tareas ---
if (strpos($accion, 'task') !== false) {
    $instanciaControlador = new ControladorTarea($pdo);
    
    switch ($accion) {
        case 'tasks':
            $metodo = 'mostrarTareas';
            break;
        case 'add_task':
            $metodo = 'agregar';
            break;
        case 'update_task_status':
            $metodo = 'actualizarEstado';
            break;
        case 'delete_task':
            $metodo = 'eliminar';
            break;
        default:
            $metodo = 'inicio'; 
            $instanciaControlador = new ControladorProyecto($pdo); 
            break;
    }
} elseif ($accion === 'reportes') { 
    $instanciaControlador = new ControladorReporte($pdo);
    $metodo = 'inicio';
} elseif ($accion === 'upload_media' || $accion === 'process_upload' || $accion === 'delete_media') {
    $instanciaControlador = new ControladorMedia($pdo);
    
    switch ($accion) {
        case 'upload_media':
            $metodo = 'mostrarFormularioSubida';
            break;
        case 'process_upload':
            $metodo = 'subir';
            break;
        case 'delete_media':
            $metodo = 'eliminarArchivo';
            break;
        default:
            $metodo = 'mostrarFormularioSubida'; 
            break;
    }
// --- Lógica para Proyectos y Autenticación ---
} else {
    $instanciaControlador = new ControladorProyecto($pdo);
    
    switch ($accion) {
        case 'logout':
            $metodo = 'cerrarSesion';
            break;
        case 'register':
            $metodo = 'registrar';
            break;
        case 'login':
            $metodo = 'iniciarSesion';
            break;
        case 'add_project':
            $metodo = 'agregarProyecto';
            break;
        case 'delete':
            $metodo = 'eliminarProyecto';
            break;
        case 'edit':
            $metodo = 'editarMetadatos';
            break;
        case 'update_metadata':
            $metodo = 'procesarEdicion';
            break;
        case 'reset_chain':
            $metodo = 'reiniciarCadena';
            break;
        default:
            $metodo = 'inicio';
            break;
    }
}


if ($instanciaControlador && $metodo && method_exists($instanciaControlador, $metodo)) {

    $parametros = [];
    if ($metodo === 'registrar' || $metodo === 'iniciarSesion' || $metodo === 'agregarProyecto' || $metodo === 'subir') {
        $parametros[] = $_POST;
    }

    call_user_func_array([$instanciaControlador, $metodo], $parametros);
    
    exit();
    
} else {
    $instanciaControlador = new ControladorProyecto($pdo);
    $instanciaControlador->inicio();
}
