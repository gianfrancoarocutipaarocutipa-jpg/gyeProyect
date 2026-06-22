<?php
declare(strict_types=1);

namespace GemMotors\Models;

use GemMotors\Config\Database;

class AsignacionRepuesto
{
    public int $id;
    public int $orden_id;
    public int $repuesto_id;
    public int $cantidad;
    public string $fecha_asignacion;

    public function __construct(
        int $id,
        int $orden_id,
        int $repuesto_id,
        int $cantidad,
        string $fecha_asignacion
    ) {
        $this->id = $id;
        $this->orden_id = $orden_id;
        $this->repuesto_id = $repuesto_id;
        $this->cantidad = $cantidad;
        $this->fecha_asignacion = $fecha_asignacion;
    }

    // Buscar asignación por ID
    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM asignacion_repuesto WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Listar asignaciones por orden de trabajo
    public static function findByOrdenId(int $ordenId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM asignacion_repuesto WHERE orden_id = :orden_id ORDER BY fecha_asignacion');
        $stmt->execute(['orden_id' => $ordenId]);
        $rows = $stmt->fetchAll();

        $asignaciones = [];
        foreach ($rows as $row) {
            $asignaciones[] = self::fromRow($row);
        }

        return $asignaciones;
    }

    // Listar asignaciones por repuesto
    public static function findByRepuestoId(int $repuestoId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM asignacion_repuesto WHERE repuesto_id = :repuesto_id ORDER BY fecha_asignacion');
        $stmt->execute(['repuesto_id' => $repuestoId]);
        $rows = $stmt->fetchAll();

        $asignaciones = [];
        foreach ($rows as $row) {
            $asignaciones[] = self::fromRow($row);
        }

        return $asignaciones;
    }

    // Crear una nueva asignación (normalmente se hace a través del modelo Repuesto::asignarAOrden)
    public static function create(array $data): self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO asignacion_repuesto (orden_id, repuesto_id, cantidad) VALUES (:orden_id, :repuesto_id, :cantidad)');
        $stmt->execute([
            'orden_id' => $data['orden_id'],
            'repuesto_id' => $data['repuesto_id'],
            'cantidad' => $data['cantidad']
        ]);

        $id = (int)$db->lastInsertId();

        return new self(
            $id,
            $data['orden_id'],
            $data['repuesto_id'],
            $data['cantidad'],
            date('Y-m-d H:i:s')
        );
    }

    // Actualizar asignación
    public function update(array $data): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE asignacion_repuesto SET orden_id = :orden_id, repuesto_id = :repuesto_id, cantidad = :cantidad WHERE id = :id');
        $stmt->execute([
            'id' => $this->id,
            'orden_id' => $data['orden_id'] ?? $this->orden_id,
            'repuesto_id' => $data['repuesto_id'] ?? $this->repuesto_id,
            'cantidad' => $data['cantidad'] ?? $this->cantidad
        ]);

        // Actualizar las propiedades
        $this->orden_id = $data['orden_id'] ?? $this->orden_id;
        $this->repuesto_id = $data['repuesto_id'] ?? $this->repuesto_id;
        $this->cantidad = $data['cantidad'] ?? $this->cantidad;
    }

    // Eliminar asignación (con reposición de stock)
    public function delete(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Obtener información de la asignación antes de eliminar
            $repuesto = \GemMotors\Models\Repuesto::find($this->repuesto_id);
            
            // Eliminar la asignación
            $stmt = $db->prepare('DELETE FROM asignacion_repuesto WHERE id = :id');
            $stmt->execute(['id' => $this->id]);

            // Devolver el stock (opcional, depende de las reglas de negocio)
            // En algunos sistemas, no se devuelve el stock cuando se elimina una asignación
            // Pero para mantener consistencia, vamos a asumir que sí se devuelve
            if ($repuesto !== null) {
                $nuevoStock = $repuesto->stock + $this->cantidad;
                $stmt = $db->prepare('UPDATE repuestos SET stock = :stock WHERE id = :id');
                $stmt->execute([
                    'id' => $this->repuesto_id,
                    'stock' => $nuevoStock
                ]);
            }

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // Método auxiliar para crear objeto desde fila de BD
    public static function fromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['orden_id'],
            $row['repuesto_id'],
            (int)$row['cantidad'],
            $row['fecha_asignacion']
        );
    }

    // Obtener la orden de trabajo asociada
    public function getOrdenTrabajo(): ?\GemMotors\Models\OrdenTrabajo
    {
        return \GemMotors\Models\OrdenTrabajo::find($this->orden_id);
    }

    // Obtener el repuesto asociado
    public function getRepuesto(): ?\GemMotors\Models\Repuesto
    {
        return \GemMotors\Models\Repuesto::find($this->repuesto_id);
    }
}