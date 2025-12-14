<?php
namespace App\Controllers;

// No necesitamos un MediaModel por ahora, ya que la lógica de DB es simple y la pondremos aquí.

class MediaController extends BaseController {
    
    /**
     * Muestra el formulario para subir archivos.
     * Vinculado a un Block ID específico de R012.
     */
    public function showUploadForm() {
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('index.php');
        }

        $blockId = $_GET['block_id'] ?? null;

        if (!$blockId) {
             // Redirigir si no se proporciona un ID de bloque válido.
             return $this->redirect('index.php?message=ID de proyecto R012 requerido.');
        }

        // Obtener la lista de archivos ya subidos para este bloque (para la vista)
        $mediaFiles = $this->getFilesByBlock($blockId, $_SESSION['user_id']);
        
        // Renderizar la vista de subida de archivos
        $this->render('media_upload', [
            'block_id' => $blockId,
            'media_files' => $mediaFiles,
            'message' => $this->message
        ]);
    }

    /**
 * Maneja la solicitud POST para subir un archivo.
 */
public function upload() {
    // EL BLOQUE DE DEBUG ANTERIOR HA SIDO ELIMINADO PARA PERMITIR LA EJECUCIÓN NORMAL.
    
    if (!isset($_SESSION['user_id'])) {
        return $this->redirect('index.php');
    }

    $userId = $_SESSION['user_id'];
    $blockId = $_POST['block_id'] ?? null;
    $file = $_FILES['media_file'] ?? null;
    
    // 1. CHEQUEO BÁSICO DEL ARCHIVO Y EL ID
    if (!$blockId || !$file || $file['error'] !== UPLOAD_ERR_OK) {
        $error_code = $file['error'] ?? 'N/A';
        // UPLOAD_ERR_INI_SIZE (1) o UPLOAD_ERR_FORM_SIZE (2) indican que el archivo es demasiado grande
        if ($error_code == 1 || $error_code == 2) {
             return $this->redirect('index.php?action=upload_media&block_id=' . $blockId . '&message=Error: Archivo demasiado grande o excede el límite de PHP.');
        }
        return $this->redirect('index.php?action=upload_media&block_id=' . $blockId . '&message=Error: Faltan datos o subida fallida (Código: ' . $error_code . ').');
    }

    // --- 2. VALIDACIÓN DE TIPO DE ARCHIVO ---
    $allowedMimeTypes = ['image/png', 'video/mp4'];
    if (!in_array($file['type'], $allowedMimeTypes)) {
        return $this->redirect('index.php?action=upload_media&block_id=' . $blockId . '&message=Error: Tipo de archivo no permitido. Solo PNG y MP4.');
    }
    
    // --- 3. PREPARACIÓN DE LA RUTA Y NOMBRE ---
    $uploadDir = 'uploads/'; 
    
    // Intentar crear el directorio si no existe. 
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        return $this->redirect('index.php?action=upload_media&block_id=' . $blockId . '&message=Error: No se pudo crear el directorio de subida. Verifica los permisos.');
    }

    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid($blockId . '_') . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;

    // --- 4. MOVER EL ARCHIVO ---
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        
        // --- 5. REGISTRAR EN LA BASE DE DATOS ---
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO media_files (block_id, user_id, file_name, file_path, mime_type) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $success = $stmt->execute([
                $blockId, 
                $userId, 
                $file['name'], 
                $filePath,     
                $file['type']
            ]);
            
            if ($success) {
                return $this->redirect('index.php?action=upload_media&block_id=' . $blockId . '&message=✅ Archivo subido y registrado con éxito.');
            } else {
                 return $this->redirect('index.php?action=upload_media&block_id=' . $blockId . '&message=Error: Archivo subido al servidor, pero falló el registro en la base de datos.');
            }
        } catch (\PDOException $e) {
             return $this->redirect('index.php?action=upload_media&block_id=' . $blockId . '&message=Error PDO: Falló el registro de DB. ' . $e->getMessage());
        }
        
    } else {
        // --- 6. FALLO AL MOVER ---
        return $this->redirect('index.php?action=upload_media&block_id=' . $blockId . '&message=Error crítico: Falló al mover el archivo temporal. Esto puede ser un error de permisos o ruta de servidor.');
    }
}
    
    /**
     * Función interna para obtener archivos subidos para la vista.
     */
    private function getFilesByBlock($blockId, $userId) {
 $stmt = $this->pdo->prepare("
 SELECT id, file_name, file_path, mime_type, uploaded_at
FROM media_files
WHERE block_id = ? AND user_id = ?
 ORDER BY uploaded_at DESC
 ");
 $stmt->execute([$blockId, $userId]);
return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    public function deleteFile() {
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('index.php');
        }

        $mediaId = $_GET['id'] ?? null;
        $blockId = $_GET['block_id'] ?? null;
        $redirectUrl = 'index.php?action=upload_media&block_id=' . $blockId;

        if (!$mediaId || !$blockId) {
            return $this->redirect($redirectUrl . '&message=Error: ID de archivo o bloque no proporcionado.');
        }

        try {
            // 1. Obtener la ruta del archivo y verificar propiedad
            $stmt = $this->pdo->prepare("
                SELECT file_path FROM media_files WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$mediaId, $_SESSION['user_id']]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                return $this->redirect($redirectUrl . '&message=Error: Archivo no encontrado o no autorizado.');
            }

            $filePath = $file['file_path'];

            // 2. Eliminar el registro de la base de datos
            $stmt = $this->pdo->prepare("
                DELETE FROM media_files WHERE id = ?
            ");
            $stmt->execute([$mediaId]);

            // 3. Eliminar el archivo del disco si existe
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return $this->redirect($redirectUrl . '&message=🗑️ Archivo eliminado con éxito.');

        } catch (\PDOException $e) {
            return $this->redirect($redirectUrl . '&message=Error PDO al eliminar: ' . $e->getMessage());
        }
    }
}