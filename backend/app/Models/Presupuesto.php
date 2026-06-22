<?php
declare(strict_types=1);

namespace GemMotors\Models;

use GemMotors\Config\Database;

class Presupuesto
{
    public int $id;
    public int $orden_id;
    public float $total;
    public string $estado; // 'pendiente', 'aprobado', 'rechazado'
    public ?string $motivo_rechazo;
    public string $fecha_emision;
    public ?string $fecha_respuesta;

    public function __construct(
        int $id,
        int $orden_id,
        float $total,
        string $estado,
        ?string $motivo_rechazo,
        string $fecha_emision,
        ?string $fecha_respuesta
    ) {
        $this->id = $id;
        $this->orden_id = $orden_id;
        $this->total = $total;
        $this->estado = $estado;
        $this->motivo_rechazo = $motivo_rechazo;
        $this->fecha_emision = $fecha_emision;
        $this->fecha_respuesta = $fecha_respuesta;
    }

    // Buscar presupuesto por ID
    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM presupuestos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Buscar presupuesto por ID de orden de trabajo
    public static function findByOrdenId(int $ordenId): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM presupuestos WHERE orden_id = :orden_id');
        $stmt->execute(['orden_id' => $ordenId]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Crear un nuevo presupuesto
    public static function create(array $data): self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO presupuestos (orden_id, total, estado, motivo_rechazo) VALUES (:orden_id, :total, :estado, :motivo_rechazo)');
        $stmt->execute([
            'orden_id' => $data['orden_id'],
            'total' => $data['total'],
            'estado' => $data['estado'] ?? 'pendiente',
            'motivo_rechazo' => $data['motivo_rechazo'] ?? null
        ]);

        $id = (int)$db->lastInsertId();

        return new self(
            $id,
            $data['orden_id'],
            (float)$data['total'],
            $data['estado'] ?? 'pendiente',
            $data['motivo_rechazo'] ?? null,
            date('Y-m-d H:i:s'),
            null
        );
    }

    // Actualizar presupuesto
    public function update(array $data): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE presupuestos SET orden_id = :orden_id, total = :total, estado = :estado, motivo_rechazo = :motivo_rechazo WHERE id = :id');
        $stmt->execute([
            'id' => $this->id,
            'orden_id' => $data['orden_id'] ?? $this->orden_id,
            'total' => $data['total'] ?? $this->total,
            'estado' => $data['estado'] ?? $this->estado,
            'motivo_rechazo' => $data['motivo_rechazo'] ?? $this->motivo_rechazo
        ]);

        // Actualizar las propiedades
        $this->orden_id = $data['orden_id'] ?? $this->orden_id;
        $this->total = $data['total'] ?? $this->total;
        $this->estado = $data['estado'] ?? $this->estado;
        $this->motivo_rechazo = $data['motivo_rechazo'] ?? $this->motivo_rechazo;
    }

    // Actualizar respuesta del presupuesto (aprobar o rechazar)
    public function responder(string $estado, ?string $motivoRechazo = null): void
    {
        // Validar que el estado sea válido
        if (!in_array($estado, ['aprobado', 'rechazado'], true)) {
            throw new \InvalidArgumentException('Estado de presupuesto inválido');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE presupuestos SET estado = :estado, motivo_rechazo = :motivo_rechazo, fecha_respuesta = :fecha_respuesta WHERE id = :id');
        $stmt->execute([
            'id' => $this->id,
            'estado' => $estado,
            'motivo_rechazo' => $motivoRechazo,
            'fecha_respuesta' => date('Y-m-d H:i:s')
        ]);

        // Actualizar las propiedades
        $this->estado = $estado;
        $this->motivo_rechazo = $motivoRechazo;
        $this->fecha_respuesta = date('Y-m-d H:i:s');
    }

    // Eliminar presupuesto
    public function delete(): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM presupuestos WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    // Método auxiliar para crear objeto desde fila de BD
    public static function fromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['orden_id'],
            (float)$row['total'],
            $row['estado'],
            $row['motivo_rechazo'],
            $row['fecha_emision'],
            $row['fecha_respuesta']
        );
    }

    // Obtener la orden de trabajo asociada
    public function getOrdenTrabajo(): ?\GemMotors\Models\OrdenTrabajo
    {
        return \GemMotors\Models\OrdenTrabajo::find($this->orden_id);
    }
}