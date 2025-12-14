<?php
namespace App\Controladores;

class ControladorMedia extends ControladorBase {
    
    public function mostrarFormularioSubida() {
        if (!isset($_SESSION['id_usuario'])) {
            return $this->redireccionar('index.php');
        }

        $idBloque = $_GET['block_id'] ?? null;

        if (!$idBloque) {
             return $this->redireccionar('index.php?message=ID de proyecto requerido.');
        }

        $archivosMedia = $this->obtenerArchivosPorBloque($idBloque, $_SESSION['id_usuario']);
        
        $this->renderizar('media_upload', [
            'block_id' => $idBloque,
            'media_files' => $archivosMedia,
            'message' => $this->mensaje
        ]);
    }

    public function subir() {
        if (!isset($_SESSION['id_usuario'])) {
            return $this->redireccionar('index.php');
        }

        $idUsuario = $_SESSION['id_usuario'];
        $idBloque = $_POST['block_id'] ?? null;
        $archivo = $_FILES['media_file'] ?? null;
        
        if (!$idBloque || !$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
            $codigoError = $archivo['error'] ?? 'N/A';
            if ($codigoError == 1 || $codigoError == 2) {
                 return $this->redireccionar('index.php?action=upload_media&block_id=' . $idBloque . '&message=Error: Archivo demasiado grande o excede el límite de PHP.');
            }
            return $this->redireccionar('index.php?action=upload_media&block_id=' . $idBloque . '&message=Error: Faltan datos o subida fallida (Código: ' . $codigoError . ').');
        }

        $tiposMimePermitidos = ['image/png', 'video/mp4'];
        if (!in_array($archivo['type'], $tiposMimePermitidos)) {
            return $this->redireccionar('index.php?action=upload_media&block_id=' . $idBloque . '&message=Error: Tipo de archivo no permitido. Solo PNG y MP4.');
        }
        
        $directorioSubida = 'uploads/'; 
        
        if (!is_dir($directorioSubida) && !mkdir($directorioSubida, 0777, true)) {
            return $this->redireccionar('index.php?action=upload_media&block_id=' . $idBloque . '&message=Error: No se pudo crear el directorio de subida. Verifica los permisos.');
        }

        $extensionArchivo = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombreArchivo = uniqid($idBloque . '_') . '.' . $extensionArchivo;
        $rutaArchivo = $directorioSubida . $nombreArchivo;

        if (move_uploaded_file($archivo['tmp_name'], $rutaArchivo)) {
            
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO archivos_media (id_bloque, id_usuario, nombre_archivo, ruta_archivo, tipo_mime) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $exito = $stmt->execute([
                    $idBloque, 
                    $idUsuario, 
                    $archivo['name'], 
                    $rutaArchivo,     
                    $archivo['type']
                ]);
                
                if ($exito) {
                    return $this->redireccionar('index.php?action=upload_media&block_id=' . $idBloque . '&message=✅ Archivo subido y registrado con éxito.');
                } else {
                     return $this->redireccionar('index.php?action=upload_media&block_id=' . $idBloque . '&message=Error: Archivo subido al servidor, pero falló el registro en la base de datos.');
                }
            } catch (\PDOException $e) {
                 return $this->redireccionar('index.php?action=upload_media&block_id=' . $idBloque . '&message=Error PDO: Falló el registro de DB. ' . $e->getMessage());
            }
            
        } else {
            return $this->redireccionar('index.php?action=upload_media&block_id=' . $idBloque . '&message=Error crítico: Falló al mover el archivo temporal. Esto puede ser un error de permisos o ruta de servidor.');
        }
    }
    
    private function obtenerArchivosPorBloque($idBloque, $idUsuario) {
        $stmt = $this->pdo->prepare("
            SELECT id, nombre_archivo, ruta_archivo, tipo_mime, subido_en
            FROM archivos_media
            WHERE id_bloque = ? AND id_usuario = ?
            ORDER BY subido_en DESC
        ");
        $stmt->execute([$idBloque, $idUsuario]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function eliminarArchivo() {
        if (!isset($_SESSION['id_usuario'])) {
            return $this->redireccionar('index.php');
        }

        $idMedia = $_GET['id'] ?? null;
        $idBloque = $_GET['block_id'] ?? null;
        $urlRedireccion = 'index.php?action=upload_media&block_id=' . $idBloque;

        if (!$idMedia || !$idBloque) {
            return $this->redireccionar($urlRedireccion . '&message=Error: ID de archivo o bloque no proporcionado.');
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT ruta_archivo FROM archivos_media WHERE id = ? AND id_usuario = ?
            ");
            $stmt->execute([$idMedia, $_SESSION['id_usuario']]);
            $archivo = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$archivo) {
                return $this->redireccionar($urlRedireccion . '&message=Error: Archivo no encontrado o no autorizado.');
            }

            $rutaArchivo = $archivo['ruta_archivo'];

            $stmt = $this->pdo->prepare("
                DELETE FROM archivos_media WHERE id = ?
            ");
            $stmt->execute([$idMedia]);

            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }

            return $this->redireccionar($urlRedireccion . '&message=🗑️ Archivo eliminado con éxito.');

        } catch (\PDOException $e) {
            return $this->redireccionar($urlRedireccion . '&message=Error PDO al eliminar: ' . $e->getMessage());
        }
    }
}