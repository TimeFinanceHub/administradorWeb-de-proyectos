<?php
namespace App\Models;
use PDOException;

// models/BlockModel.php

/**
 * Clase auxiliar para definir la estructura y el comportamiento de un solo Bloque.
 */
class Block {
    public $index;
    public $timestamp;
    public $data;
    public $previousHash;
    public $hash;

    // Los campos alias y status son metadata del Modelo (no participan en el hash inicial)
    // Se agregan aquí para consistencia con el Model.
    public $alias = '';
    public $status = 'active'; 

    public function __construct($index, $timestamp, $data, $previousHash = '') {
        $this->index = $index;
        $this->timestamp = $timestamp;
        $this->data = $data;
        $this->previousHash = $previousHash;
        $this->hash = $this->calculateHash();
    }

    // Calcula el hash criptográfico (la firma) del bloque
    public function calculateHash() {
        return hash('sha256', $this->index . $this->previousHash . $this->timestamp . $this->data);
    }
}


/**
 * Clase principal del Modelo que maneja la persistencia y la lógica de la cadena.
 */
class BlockModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getChainByUserId($userId, $statusFilter = null, $tagFilter = null) {
        
        $sql = "SELECT b.* FROM blocks b";
        $params = [$userId];
        $where = "b.user_id = ? AND b.status != 'deleted'";
    
        // 1. Filtrar por ESTADO (status)
        if ($statusFilter && $statusFilter !== 'all') {
            $where .= " AND b.status = ?";
            $params[] = $statusFilter;
        }
    
        // 2. Filtrar por ETIQUETA (tag) - Requiere JOIN con la tabla pivote
        if ($tagFilter) {
            // Necesitamos unir la tabla blocks con la tabla block_tags y tags
            $sql .= " JOIN block_tags bt ON b.id = bt.block_id";
            $sql .= " JOIN tags t ON bt.tag_id = t.id";
            $where .= " AND t.name = ?";
            $params[] = $tagFilter;
        }
    
        $sql .= " WHERE " . $where . " ORDER BY b.block_index DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene el último bloque, necesario para calcular el nuevo 'previous_hash'
    public function getLatestBlock($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM blocks WHERE user_id = ? ORDER BY block_index DESC LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Añade un nuevo bloque a la cadena (Operación CREATE)
    public function addBlock($userId, $data) {
        $lastBlock = $this->getLatestBlock($userId);

        // Lógica de Bloque Génesis (Index 0) o Bloque Normal
        if (!$lastBlock) {
            $index = 0;
            $previousHash = "0"; 
        } else {
            $index = $lastBlock['block_index'] + 1;
            $previousHash = $lastBlock['hash'];
        }

        $timestamp = date('Y-m-d H:i:s');
        $newBlock = new Block($index, $timestamp, $data, $previousHash);
        
        // El Alias inicial es una cadena vacía, Status es 'active'
        $sql = "INSERT INTO blocks (user_id, block_index, timestamp, data, previous_hash, hash, alias, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $userId, 
            $newBlock->index, 
            $newBlock->timestamp, 
            $newBlock->data, 
            $newBlock->previousHash, 
            $newBlock->hash,
            $newBlock->alias, // Nuevo campo
            $newBlock->status // Nuevo campo
        ]);
        
        return $newBlock;
    }

    // Valida la integridad de la cadena
    public function isValidChain($userId) {
        // Obtenemos TODOS los bloques (incluidos los eliminados suavemente) para la validación
        $stmt = $this->pdo->prepare("SELECT * FROM blocks WHERE user_id = ? ORDER BY block_index ASC");
        $stmt->execute([$userId]);
        $chain = $stmt->fetchAll(PDO::FETCH_ASSOC);

        for ($i = 1; $i < count($chain); $i++) {
            $currentBlock = $chain[$i];
            $previousBlock = $chain[$i - 1];

            // 1. Verificar si el hash previo coincide
            if ($currentBlock['previous_hash'] !== $previousBlock['hash']) {
                return false; // Cadena rota
            }

            // 2. Recalcular el hash para ver si hubo manipulación
            $recalculatedHash = hash('sha256', 
                $currentBlock['block_index'] . 
                $currentBlock['previous_hash'] . 
                $currentBlock['timestamp'] . 
                $currentBlock['data']
            );
            
            if ($currentBlock['hash'] !== $recalculatedHash) {
                return false; // Datos originales manipulados
            }
        }
        return true;
    }

    /** * MÉTODOS DE EXPANSIÓN (CRUD - Futura implementación)
     * Estos se definirán completamente en la siguiente fase.
     */
     
    // Soft Delete (Actualiza el estado a 'deleted')
    public function softDeleteBlock($blockId) {
        $stmt = $this->pdo->prepare("UPDATE blocks SET status = 'deleted' WHERE id = ?");
        return $stmt->execute([$blockId]);
    }
    
    public function updateBlockMetadata($blockId, $alias, $status, $userId) {
    
    // 1. OBTENER DATOS ANTIGUOS para el LOG (G014)
    $oldBlock = $this->getBlockById($blockId);
    if (!$oldBlock || $oldBlock['user_id'] !== $userId) {
        return false;
    }

    // 2. REALIZAR LA ACTUALIZACIÓN EN LA TABLA BLOCKS
    $stmt = $this->pdo->prepare("UPDATE blocks SET alias = ?, status = ? WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$alias, $status, $blockId, $userId]);

    // 3. LOGUEAR CAMBIOS (G014)
    if ($result) {
        $changes = [];
        
        // Loguear cambio de Alias
        if ($oldBlock['alias'] !== $alias) {
            $changes[] = [
                'field_name' => 'alias', 
                'old_value' => $oldBlock['alias'], 
                'new_value' => $alias
            ];
        }

        // Loguear cambio de Status
        if ($oldBlock['status'] !== $status) {
            $changes[] = [
                'field_name' => 'status', 
                'old_value' => $oldBlock['status'], 
                'new_value' => $status
            ];
        }

        foreach ($changes as $change) {
            $this->logChange($blockId, $userId, $change['field_name'], $change['old_value'], $change['new_value']);
        }
    }

    return $result;
}

    // Obtener un bloque específico (para la edición de metadata)
    public function getBlockById($blockId) {
        $stmt = $this->pdo->prepare("SELECT * FROM blocks WHERE id = ? LIMIT 1");
        $stmt->execute([$blockId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // --- GESTIÓN DE ETIQUETAS (TAGS) ---

    // 1. Obtener todas las etiquetas existentes
    public function getAllTags() {
        $stmt = $this->pdo->query("SELECT * FROM tags ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 2. Obtener etiquetas de un bloque específico
    public function getTagsByBlockId($blockId) {
        $stmt = $this->pdo->prepare("
            SELECT t.id, t.name 
            FROM tags t 
            JOIN block_tags bt ON t.id = bt.tag_id 
            WHERE bt.block_id = ?
        ");
        $stmt->execute([$blockId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Guardar/asociar etiquetas a un bloque
    public function setBlockTags($blockId, array $tagNames) {
        // 3a. Eliminar asociaciones existentes (para un guardado limpio)
        $this->pdo->prepare("DELETE FROM block_tags WHERE block_id = ?")->execute([$blockId]);
        
        if (empty($tagNames)) {
            return true;
        }
    
        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;
    
            // 3b. Insertar la etiqueta si no existe, o obtener su ID si ya existe
            try {
                // Intentamos insertarla primero (para nuevas etiquetas)
                $stmt = $this->pdo->prepare("INSERT INTO tags (name) VALUES (?)");
                $stmt->execute([$tagName]);
                $tagId = $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                // Si la inserción falla (etiqueta duplicada), obtenemos el ID existente
                if ($e->getCode() === '23000') { // Código para UNIQUE constraint violation
                    $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
                    $stmt->execute([$tagName]);
                    $tagId = $stmt->fetchColumn();
                } else {
                    // Manejar otro error
                    throw $e;
                }
            }
            
            // 3c. Asociar el bloque con la etiqueta en la tabla pivote
            $stmt = $this->pdo->prepare("INSERT INTO block_tags (block_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$blockId, $tagId]);
        }
    
        return true;
    }
    
    // --- GESTIÓN DE TAREAS (TASKS) / MÓDULO G013 ---

    // Obtiene todas las tareas asociadas a un bloque específico.
    public function getTasksByBlockId($blockId) {
        $stmt = $this->pdo->prepare("SELECT * FROM tasks WHERE block_id = ? ORDER BY created_at ASC");
        $stmt->execute([$blockId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Añade una nueva tarea a un bloque.
    public function addTask($blockId, $description) {
        $stmt = $this->pdo->prepare("INSERT INTO tasks (block_id, description) VALUES (?, ?)");
        return $stmt->execute([$blockId, $description]);
    }
    
    // Actualiza el estado de una tarea existente.
    public function updateTaskStatus($taskId, $newStatus) {
        $stmt = $this->pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        return $stmt->execute([$newStatus, $taskId]);
    }
    
    // Elimina (borrado físico) una tarea.
    public function deleteTask($taskId) {
        $stmt = $this->pdo->prepare("DELETE FROM tasks WHERE id = ?");
        return $stmt->execute([$taskId]);
    }
    
    /**
     * Registra un cambio en la tabla changelogs (Módulo G014).
     */
    private function logChange($blockId, $userId, $fieldName, $oldValue, $newValue) {
        $stmt = $this->pdo->prepare("
            INSERT INTO changelogs (block_id, user_id, field_name, old_value, new_value) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $blockId, 
            $userId, 
            $fieldName, 
            $oldValue, 
            $newValue
        ]);
    }
    
    /**
     * Obtiene la bitácora de cambios para un bloque específico.
     */
    public function getChangeLogByBlockId($blockId) {
        // CORRECCIÓN CLAVE: u.user_name ha sido cambiado a u.name
        $stmt = $this->pdo->prepare("
            SELECT 
                c.*, 
                u.name as user_name /* <-- Corregido: Obtenemos 'name' y le damos el alias 'user_name' */
            FROM changelogs c
            JOIN users u ON c.user_id = u.id
            WHERE c.block_id = ? 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$blockId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Actualiza la lista de etiquetas asociadas a un bloque.
     */
    public function updateBlockTags($blockId, $tagsString, $userId) {
        if (empty($tagsString)) {
            // Si no hay etiquetas, simplemente elimina las existentes y termina
            return $this->pdo->prepare("DELETE FROM block_tags WHERE block_id = ?")->execute([$blockId]);
        }
    
        // 1. Parsear la cadena de etiquetas
        $tagNames = array_map('trim', explode(',', strtolower($tagsString)));
        $tagNames = array_unique(array_filter($tagNames));
    
        try {
            $this->pdo->beginTransaction();
    
            // 2. Eliminar todas las etiquetas actuales del bloque
            $stmtDelete = $this->pdo->prepare("DELETE FROM block_tags WHERE block_id = ?");
            $stmtDelete->execute([$blockId]);
    
            // 3. Re-insertar las etiquetas
            foreach ($tagNames as $tagName) {
                if (empty($tagName)) continue;
    
                // Asegurar que la etiqueta exista en la tabla 'tags' (o insertarla)
                $tagId = $this->getOrCreateTag($tagName);
    
                // Insertar la relación en 'block_tags'
                $stmtInsert = $this->pdo->prepare("
                    INSERT INTO block_tags (block_id, tag_id) VALUES (?, ?)
                ");
                $stmtInsert->execute([$blockId, $tagId]);
            }
    
            $this->pdo->commit();
            return true;
    
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            // En un entorno de producción, esto debería loguearse, no mostrarse al usuario.
            // echo "Error al actualizar etiquetas: " . $e->getMessage(); 
            return false;
        }
    }
    
    private function getOrCreateTag($tagName) {
        $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$tagName]);
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($tag) {
            return $tag['id'];
        } else {
            $stmtInsert = $this->pdo->prepare("INSERT INTO tags (name) VALUES (?)");
            $stmtInsert->execute([$tagName]);
            return $this->pdo->lastInsertId();
        }
    }
    
}
?>