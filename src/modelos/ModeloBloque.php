<?php
namespace App\Modelos;
use PDOException;

/**
 * Clase auxiliar para definir la estructura y el comportamiento de un solo Bloque.
 */
class Bloque {
    public $indice;
    public $fechaCreacion;
    public $datos;
    public $hashAnterior;
    public $hash;

    // Los campos alias y estado son metadatos del Modelo (no participan en el hash inicial)
    public $alias = '';
    public $estado = 'activo'; 

    public function __construct($indice, $fechaCreacion, $datos, $hashAnterior = '') {
        $this->indice = $indice;
        $this->fechaCreacion = $fechaCreacion;
        $this->datos = $datos;
        $this->hashAnterior = $hashAnterior;
        $this->hash = $this->calcularHash();
    }

    // Calcula el hash criptográfico (la firma) del bloque
    public function calcularHash() {
        return hash('sha256', $this->indice . $this->hashAnterior . $this->fechaCreacion . $this->datos);
    }
}


/**
 * Clase principal del Modelo que maneja la persistencia y la lógica de la cadena.
 */
class ModeloBloque {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function obtenerCadenaPorIdUsuario($idUsuario, $filtroEstado = null, $filtroEtiqueta = null) {
        
        $sql = "SELECT b.* FROM bloques b";
        $parametros = [$idUsuario];
        $clausulaWhere = "b.id_usuario = ? AND b.estado != 'eliminado'";
    
        if ($filtroEstado && $filtroEstado !== 'todos') {
            $clausulaWhere .= " AND b.estado = ?";
            $parametros[] = $filtroEstado;
        }
    
        if ($filtroEtiqueta) {
            $sql .= " JOIN bloque_etiquetas be ON b.id = be.id_bloque";
            $sql .= " JOIN etiquetas e ON be.id_etiqueta = e.id";
            $clausulaWhere .= " AND e.nombre = ?";
            $parametros[] = $filtroEtiqueta;
        }
    
        $sql .= " WHERE " . $clausulaWhere . " ORDER BY b.indice_cadena DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obtenerUltimoBloque($idUsuario) {
        $stmt = $this->pdo->prepare("SELECT * FROM bloques WHERE id_usuario = ? ORDER BY indice_cadena DESC LIMIT 1");
        $stmt->execute([$idUsuario]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function agregarBloque($idUsuario, $datos) {
        $ultimoBloque = $this->obtenerUltimoBloque($idUsuario);

        if (!$ultimoBloque) {
            $indice = 0;
            $hashAnterior = "0"; 
        } else {
            $indice = $ultimoBloque['indice_cadena'] + 1;
            $hashAnterior = $ultimoBloque['hash'];
        }

        $fechaCreacion = date('Y-m-d H:i:s');
        $nuevoBloque = new Bloque($indice, $fechaCreacion, $datos, $hashAnterior);
        
        $sql = "INSERT INTO bloques (id_usuario, indice_cadena, fecha_creacion, datos, hash_anterior, hash, alias, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $idUsuario, 
            $nuevoBloque->indice, 
            $nuevoBloque->fechaCreacion, 
            $nuevoBloque->datos, 
            $nuevoBloque->hashAnterior, 
            $nuevoBloque->hash,
            $nuevoBloque->alias,
            $nuevoBloque->estado
        ]);
        
        return $nuevoBloque;
    }

    public function esCadenaValida($idUsuario) {
        $stmt = $this->pdo->prepare("SELECT * FROM bloques WHERE id_usuario = ? ORDER BY indice_cadena ASC");
        $stmt->execute([$idUsuario]);
        $cadena = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        for ($i = 1; $i < count($cadena); $i++) {
            $bloqueActual = $cadena[$i];
            $bloqueAnterior = $cadena[$i - 1];

            if ($bloqueActual['hash_anterior'] !== $bloqueAnterior['hash']) {
                return false;
            }

            $hashRecalculado = hash('sha256', 
                $bloqueActual['indice_cadena'] . 
                $bloqueActual['hash_anterior'] . 
                $bloqueActual['fecha_creacion'] . 
                $bloqueActual['datos']
            );
            
            if ($bloqueActual['hash'] !== $hashRecalculado) {
                return false;
            }
        }
        return true;
    }

    public function eliminarBloqueLogico($idBloque) {
        $stmt = $this->pdo->prepare("UPDATE bloques SET estado = 'eliminado' WHERE id = ?");
        return $stmt->execute([$idBloque]);
    }
    
    public function actualizarMetadatosBloque($idBloque, $alias, $estado, $idUsuario) {
    
        $bloqueAntiguo = $this->obtenerBloquePorId($idBloque);
        if (!$bloqueAntiguo || $bloqueAntiguo['id_usuario'] !== $idUsuario) {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE bloques SET alias = ?, estado = ? WHERE id = ? AND id_usuario = ?");
        $resultado = $stmt->execute([$alias, $estado, $idBloque, $idUsuario]);

        if ($resultado) {
            $cambios = [];
            
            if ($bloqueAntiguo['alias'] !== $alias) {
                $cambios[] = [
                    'nombre_campo' => 'alias', 
                    'valor_antiguo' => $bloqueAntiguo['alias'], 
                    'valor_nuevo' => $alias
                ];
            }

            if ($bloqueAntiguo['estado'] !== $estado) {
                $cambios[] = [
                    'nombre_campo' => 'estado', 
                    'valor_antiguo' => $bloqueAntiguo['estado'], 
                    'valor_nuevo' => $estado
                ];
            }

            foreach ($cambios as $cambio) {
                $this->registrarCambio($idBloque, $idUsuario, $cambio['nombre_campo'], $cambio['valor_antiguo'], $cambio['valor_nuevo']);
            }
        }

        return $resultado;
    }

    public function obtenerBloquePorId($idBloque) {
        $stmt = $this->pdo->prepare("SELECT * FROM bloques WHERE id = ? LIMIT 1");
        $stmt->execute([$idBloque]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function obtenerTodasLasEtiquetas() {
        $stmt = $this->pdo->query("SELECT * FROM etiquetas ORDER BY nombre ASC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function obtenerEtiquetasPorIdBloque($idBloque) {
        $stmt = $this->pdo->prepare("
            SELECT e.id, e.nombre 
            FROM etiquetas e 
            JOIN bloque_etiquetas be ON e.id = be.id_etiqueta 
            WHERE be.id_bloque = ?
        ");
        $stmt->execute([$idBloque]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function establecerEtiquetasDeBloque($idBloque, array $nombresEtiquetas) {
        $this->pdo->prepare("DELETE FROM bloque_etiquetas WHERE id_bloque = ?")->execute([$idBloque]);
        
        if (empty($nombresEtiquetas)) {
            return true;
        }
    
        foreach ($nombresEtiquetas as $nombreEtiqueta) {
            $nombreEtiqueta = trim($nombreEtiqueta);
            if (empty($nombreEtiqueta)) continue;
    
            try {
                $stmt = $this->pdo->prepare("INSERT INTO etiquetas (nombre) VALUES (?)");
                $stmt->execute([$nombreEtiqueta]);
                $idEtiqueta = $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $stmt = $this->pdo->prepare("SELECT id FROM etiquetas WHERE nombre = ?");
                    $stmt->execute([$nombreEtiqueta]);
                    $idEtiqueta = $stmt->fetchColumn();
                } else {
                    throw $e;
                }
            }
            
            $stmt = $this->pdo->prepare("INSERT INTO bloque_etiquetas (id_bloque, id_etiqueta) VALUES (?, ?)");
            $stmt->execute([$idBloque, $idEtiqueta]);
        }
    
        return true;
    }
    
    public function obtenerTareasPorIdBloque($idBloque) {
        $stmt = $this->pdo->prepare("SELECT * FROM tareas WHERE id_bloque = ? ORDER BY fecha_creacion ASC");
        $stmt->execute([$idBloque]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function agregarTarea($idBloque, $descripcion) {
        $stmt = $this->pdo->prepare("INSERT INTO tareas (id_bloque, descripcion) VALUES (?, ?)");
        return $stmt->execute([$idBloque, $descripcion]);
    }
    
    public function actualizarEstadoTarea($idTarea, $nuevoEstado) {
        $stmt = $this->pdo->prepare("UPDATE tareas SET estado = ? WHERE id = ?");
        return $stmt->execute([$nuevoEstado, $idTarea]);
    }
    
    public function eliminarTarea($idTarea) {
        $stmt = $this->pdo->prepare("DELETE FROM tareas WHERE id = ?");
        return $stmt->execute([$idTarea]);
    }
    
    private function registrarCambio($idBloque, $idUsuario, $nombreCampo, $valorAntiguo, $valorNuevo) {
        $stmt = $this->pdo->prepare("
            INSERT INTO registros_de_cambios (id_bloque, id_usuario, nombre_campo, valor_antiguo, valor_nuevo) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $idBloque, 
            $idUsuario, 
            $nombreCampo, 
            $valorAntiguo, 
            $valorNuevo
        ]);
    }
    
    public function obtenerRegistroCambiosPorIdBloque($idBloque) {
        $stmt = $this->pdo->prepare("
            SELECT 
                rc.*, 
                u.nombre as nombre_usuario
            FROM registros_de_cambios rc
            JOIN usuarios u ON rc.id_usuario = u.id
            WHERE rc.id_bloque = ? 
            ORDER BY rc.fecha_creacion DESC
        ");
        $stmt->execute([$idBloque]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function actualizarEtiquetasDeBloque($idBloque, $cadenaEtiquetas, $idUsuario) {
        if (empty($cadenaEtiquetas)) {
            return $this->pdo->prepare("DELETE FROM bloque_etiquetas WHERE id_bloque = ?")->execute([$idBloque]);
        }
    
        $nombresEtiquetas = array_map('trim', explode(',', strtolower($cadenaEtiquetas)));
        $nombresEtiquetas = array_unique(array_filter($nombresEtiquetas));
    
        try {
            $this->pdo->beginTransaction();
    
            $stmtDelete = $this->pdo->prepare("DELETE FROM bloque_etiquetas WHERE id_bloque = ?");
            $stmtDelete->execute([$idBloque]);
    
            foreach ($nombresEtiquetas as $nombreEtiqueta) {
                if (empty($nombreEtiqueta)) continue;
    
                $idEtiqueta = $this->obtenerOCrearEtiqueta($nombreEtiqueta);
    
                $stmtInsert = $this->pdo->prepare("
                    INSERT INTO bloque_etiquetas (id_bloque, id_etiqueta) VALUES (?, ?)
                ");
                $stmtInsert->execute([$idBloque, $idEtiqueta]);
            }
    
            $this->pdo->commit();
            return true;
    
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    
    private function obtenerOCrearEtiqueta($nombreEtiqueta) {
        $stmt = $this->pdo->prepare("SELECT id FROM etiquetas WHERE nombre = ?");
        $stmt->execute([$nombreEtiqueta]);
        $etiqueta = $stmt->fetch(\PDO::FETCH_ASSOC);
    
        if ($etiqueta) {
            return $etiqueta['id'];
        } else {
            $stmtInsert = $this->pdo->prepare("INSERT INTO etiquetas (nombre) VALUES (?)");
            $stmtInsert->execute([$nombreEtiqueta]);
            return $this->pdo->lastInsertId();
        }
    }
    
}
?>