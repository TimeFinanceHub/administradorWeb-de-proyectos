<?php
namespace App\Controladores;

use App\Modelos\ModeloBloque;
use Exception;

class ControladorProyecto extends ControladorBase {
    protected $pdo;
    protected $mensaje = "";
    protected $modeloBloque;

    public function __construct($pdo, $mensaje = null) {
        parent::__construct($pdo, $mensaje); 
        $this->modeloBloque = new ModeloBloque($pdo);
    }

    protected function renderizar($vista, $datos = []) {
        extract($datos);
        $mensaje = $this->mensaje;
        require 'views/' . $vista . '.phtml';
    }

    protected function redireccionar($ubicacion) {
        header("Location: " . $ubicacion);
        exit;
    }

    public function inicio() {
        if (isset($_SESSION['id_usuario'])) {
            $idUsuario = $_SESSION['id_usuario'];
            
            $filtroEstado = $_GET['estado'] ?? 'todos';
            $filtroEtiqueta = $_GET['etiqueta'] ?? null;
            
            $bloques = $this->modeloBloque->obtenerCadenaPorIdUsuario($idUsuario, $filtroEstado, $filtroEtiqueta);
            $esSegura = $this->modeloBloque->esCadenaValida($idUsuario);
            
            foreach ($bloques as &$bloque) {
                $bloque['etiquetas'] = $this->modeloBloque->obtenerEtiquetasPorIdBloque($bloque['id']);
            }
            unset($bloque); 

            $todasLasEtiquetas = $this->modeloBloque->obtenerTodasLasEtiquetas();
            
            $this->renderizar('dashboard', [
                'bloques' => $bloques,
                'esSegura' => $esSegura,
                'todasLasEtiquetas' => $todasLasEtiquetas,
                'filtroEstadoActual' => $filtroEstado,
                'filtroEtiquetaActual' => $filtroEtiqueta
            ]);
        } else {
            $this->renderizar('auth');
        }
    }

    public function registrar($datosPost) {
        $hashContrasena = password_hash($datosPost['password'], PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, email, telefono, password) VALUES (?, ?, ?, ?)");
        
        try {
            $stmt->execute([$datosPost['name'], $datosPost['email'], $datosPost['phone'], $hashContrasena]);
            $this->mensaje = "Usuario registrado con éxito. Por favor inicia sesión.";
        } catch (Exception $e) { 
            if ($e->getCode() === '23000') {
                $this->mensaje = "Error: El correo electrónico ya existe.";
            } else {
                $this->mensaje = "Error de registro: " . $e->getMessage();
            }
        }
        $this->inicio();
    }

    public function iniciarSesion($datosPost) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$datosPost['email']]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($datosPost['password'], $usuario['password'])) {
            $_SESSION['id_usuario'] = $usuario['id'];
            $_SESSION['nombre_usuario'] = $usuario['nombre'];
            $this->redireccionar('index.php');
        } else {
            $this->mensaje = "Credenciales incorrectas o usuario no encontrado.";
            $this->inicio();
        }
    }

    public function cerrarSesion() {
        session_destroy();
        $this->redireccionar('index.php');
    }

    public function agregarProyecto($datosPost) {
        if (!isset($_SESSION['id_usuario'])) {
            $this->redireccionar('index.php');
        }
        
        $this->modeloBloque->agregarBloque($_SESSION['id_usuario'], $datosPost['project_data']);
        $this->mensaje = "Proyecto encriptado y guardado en la cadena.";
        
        $this->redireccionar('index.php');
    }

    public function eliminarProyecto() {
        if (!isset($_SESSION['id_usuario'])) {
            $this->redireccionar('index.php');
        }
        
        $idBloque = $_GET['id'] ?? null; 
        
        if (!$idBloque || !is_numeric($idBloque)) {
            $this->mensaje = "Error: ID de bloque no válido.";
        } else {
            $exito = $this->modeloBloque->eliminarBloqueLogico($idBloque);
            
            if ($exito) {
                $this->mensaje = "Proyecto marcado como eliminado. ¡La cadena sigue intacta!";
            } else {
                $this->mensaje = "Error al marcar el proyecto como eliminado.";
            }
        }
        
        $this->redireccionar('index.php');
    }

    public function editarMetadatos() {
        if (!isset($_SESSION['id_usuario'])) {
            return $this->redireccionar('index.php');
        }
    
        $idBloque = $_GET['id'] ?? null;
        $bloque = $this->modeloBloque->obtenerBloquePorId($idBloque);
    
        if (!$bloque || $bloque['id_usuario'] !== $_SESSION['id_usuario']) {
            $this->mensaje = "Error: Proyecto no encontrado o no autorizado.";
            return $this->redireccionar('index.php');
        }
        
        $registroDeCambios = $this->modeloBloque->obtenerRegistroCambiosPorIdBloque($idBloque); 
    
        $estadosDisponibles = ['activo', 'en_progreso', 'completado', 'archivado'];
    
        $todasLasEtiquetas = $this->modeloBloque->obtenerTodasLasEtiquetas();
        $etiquetasDelBloque = $this->modeloBloque->obtenerEtiquetasPorIdBloque($idBloque);
        $etiquetasActuales = array_column($etiquetasDelBloque, 'nombre');
        
        $cadenaEtiquetasActuales = implode(', ', $etiquetasActuales); 
        
        $this->renderizar('edit_metadata', [
            'bloque' => $bloque,
            'mensaje' => $this->mensaje,
            'todasLasEtiquetas' => $todasLasEtiquetas,
            'etiquetasActuales' => $etiquetasActuales,
            'registroDeCambios' => $registroDeCambios,
            'estadosDisponibles' => $estadosDisponibles,
            'cadenaEtiquetasActuales' => $cadenaEtiquetasActuales
        ]);
    }
    
    public function procesarEdicion() {
        if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redireccionar('index.php');
        }
    
        $idUsuario = $_SESSION['id_usuario'];
        $idBloque = $_POST['block_id'] ?? null;
        $alias = trim($_POST['alias'] ?? '');
        $estado = $_POST['status'] ?? '';
        $cadenaEtiquetas = $_POST['tags'] ?? '';
    
        if (!$idBloque || empty($alias) || empty($estado)) {
            $this->mensaje = "Error: Datos incompletos para actualizar el proyecto.";
            return $this->redireccionar("index.php?action=edit&id=" . $idBloque);
        }
        
        $bloqueActualizado = $this->modeloBloque->actualizarMetadatosBloque($idBloque, $alias, $estado, $idUsuario);
    
        $etiquetasActualizadas = $this->modeloBloque->actualizarEtiquetasDeBloque($idBloque, $cadenaEtiquetas, $idUsuario);
    
        if ($bloqueActualizado || $etiquetasActualizadas) {
            $this->mensaje = "Metadatos y Etiquetas actualizados correctamente.";
        } else {
            $this->mensaje = "Advertencia: No se detectaron cambios o hubo un error al guardar.";
        }
    
        return $this->redireccionar("index.php?action=edit&id=" . $idBloque);
    }

    public function reiniciarCadena() {
        if (!isset($_SESSION['id_usuario'])) {
            return $this->redireccionar('index.php');
        }

        $idUsuario = $_SESSION['id_usuario'];
        $fechaCreacion = date('Y-m-d H:i:s');
        
        $tablasALimpiar = [
            'bloque_etiquetas',
            'registros_de_cambios',
            'tareas',
            'etiquetas',
            'bloques',
        ];
        
        $totalFilasEliminadas = 0;
        $estadoTabla = [];

        try {
            $this->pdo->beginTransaction(); 

            foreach ($tablasALimpiar as $tabla) {
                try {
                    $stmt_delete = $this->pdo->prepare("DELETE FROM `{$tabla}`");
                    $stmt_delete->execute();
                    $filasEliminadas = $stmt_delete->rowCount();
                    $totalFilasEliminadas += $filasEliminadas;
                    
                    $estadoTabla[] = "✅ **{$tabla}**: Eliminados {$filasEliminadas} registros.";

                } catch (\PDOException $e) {
                    $this->pdo->rollBack();
                    return $this->redireccionar('index.php?message=❌ Error al eliminar filas de '.$tabla.'. Causa: '.$e->getMessage());
                }
            }
            
            $indiceGenesis = 1;
            $datosGenesis = 'Bloque Génesis Inicial';
            $hashAnteriorGenesis = str_repeat('0', 64);
            
            $hashGenesis = hash('sha256', $indiceGenesis . $fechaCreacion . $datosGenesis . $hashAnteriorGenesis); 
            
            $stmt_insert = $this->pdo->prepare("
                INSERT INTO bloques (id_usuario, indice_cadena, fecha_creacion, datos, hash_anterior, hash)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt_insert->execute([
                $idUsuario, 
                $indiceGenesis, 
                $fechaCreacion, 
                $datosGenesis, 
                $hashAnteriorGenesis, 
                $hashGenesis
            ]);

            $this->pdo->commit();
            
            $mensajeFinal = "✅ **REINICIO EXITOSO!** Cadena lógica iniciada con índice 1.";
            $mensajeFinal .= "<br>Detalle de Limpieza: " . implode("<br>- ", $estadoTabla);
            
            return $this->redireccionar('index.php?message=' . urlencode($mensajeFinal));

        } catch (\PDOException $e) { 
            $this->pdo->rollBack();
            return $this->redireccionar('index.php?message=❌ Error crítico e inesperado: ' . $e->getMessage());
        }
    }
}