<?php
declare(strict_types=1);

namespace GemMotors\Models;

use GemMotors\Config\Database;

class Diagnostico
{
    public int $id;
    public int $orden_id;
    public int $vehiculo_id;
    public ?int $mecanico_id;
    public ?string $tramas_hex;
    public ?array $codigos_falla;
    public ?string $observaciones;
    public string $created_at;

    public function __construct(
        int $id,
        int $orden_id,
        int $vehiculo_id,
        ?int $mecanico_id,
        ?string $tramas_hex,
        ?array $codigos_falla,
        ?string $observaciones,
        string $created_at
    ) {
        $this->id = $id;
        $this->orden_id = $orden_id;
        $this->vehiculo_id = $vehiculo_id;
        $this->mecanico_id = $mecanico_id;
        $this->tramas_hex = $tramas_hex;
        $this->codigos_falla = $codigos_falla;
        $this->observaciones = $observaciones;
        $this->created_at = $created_at;
    }

    // Buscar diagnóstico por ID
    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM diagnosticos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Listar diagnósticos por orden de trabajo
    public static function findByOrdenId(int $ordenId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM diagnosticos WHERE orden_id = :orden_id ORDER BY created_at DESC');
        $stmt->execute(['orden_id' => $ordenId]);
        $rows = $stmt->fetchAll();

        $diagnosticos = [];
        foreach ($rows as $row) {
            $diagnosticos[] = self::fromRow($row);
        }

        return $diagnosticos;
    }

    // Listar diagnósticos por vehículo
    public static function findByVehiculoId(int $vehiculoId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM diagnosticos WHERE vehiculo_id = :vehiculo_id ORDER BY created_at DESC');
        $stmt->execute(['vehiculo_id' => $vehiculoId]);
        $rows = $stmt->fetchAll();

        $diagnosticos = [];
        foreach ($rows as $row) {
            $diagnosticos[] = self::fromRow($row);
        }

        return $diagnosticos;
    }

    // Crear un nuevo diagnóstico
    public static function create(array $data): self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO diagnosticos (orden_id, vehiculo_id, mecanico_id, tramas_hex, codigos_falla, observaciones) VALUES (:orden_id, :vehiculo_id, :mecanico_id, :tramas_hex, :codigos_falla, :observaciones)');
        $stmt->execute([
            'orden_id' => $data['orden_id'],
            'vehiculo_id' => $data['vehiculo_id'],
            'mecanico_id' => $data['mecanico_id'] ?? null,
            'tramas_hex' => $data['tramas_hex'] ?? null,
            'codigos_falla' => $data['codigos_falla'] ? json_encode($data['codigos_falla'], JSON_UNESCAPED_UNICODE) : null,
            'observaciones' => $data['observaciones'] ?? null
        ]);

        $id = (int)$db->lastInsertId();

        return new self(
            $id,
            $data['orden_id'],
            $data['vehiculo_id'],
            $data['mecanico_id'] ?? null,
            $data['tramas_hex'] ?? null,
            $data['codigos_falla'] ?? null,
            $data['observaciones'] ?? null,
            date('Y-m-d H:i:s')
        );
    }

    // Actualizar diagnóstico
    public function update(array $data): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE diagnosticos SET orden_id = :orden_id, vehiculo_id = :vehiculo_id, mecanico_id = :mecanico_id, tramas_hex = :tramas_hex, codigos_falla = :codigos_falla, observaciones = :observaciones WHERE id = :id');
        $stmt->execute([
            'id' => $this->id,
            'orden_id' => $data['orden_id'] ?? $this->orden_id,
            'vehiculo_id' => $data['vehiculo_id'] ?? $this->vehiculo_id,
            'mecanico_id' => $data['mecanico_id'] ?? $this->mecanico_id,
            'tramas_hex' => $data['tramas_hex'] ?? $this->tramas_hex,
            'codigos_falla' => $data['codigos_falla'] !== null ? json_encode($data['codigos_falla'], JSON_UNESCAPED_UNICODE) : $this->codigos_falla,
            'observaciones' => $data['observaciones'] ?? $this->observaciones
        ]);

        // Actualizar las propiedades
        $this->orden_id = $data['orden_id'] ?? $this->orden_id;
        $this->vehiculo_id = $data['vehiculo_id'] ?? $this->vehiculo_id;
        $this->mecanico_id = $data['mecanico_id'] ?? $this->mecanico_id;
        $this->tramas_hex = $data['tramas_hex'] ?? $this->tramas_hex;
        $this->codigos_falla = $data['codigos_falla'] ?? $this->codigos_falla;
        $this->observaciones = $data['observaciones'] ?? $this->observaciones;
    }

    // Eliminar diagnóstico
    public function delete(): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM diagnosticos WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    // Método auxiliar para crear objeto desde fila de BD
    public static function fromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['orden_id'],
            $row['vehiculo_id'],
            $row['mecanico_id'] ?? null,
            $row['tramas_hex'],
            $row['codigos_falla'] ? json_decode($row['codigos_falla'], true) : null,
            $row['observaciones'],
            $row['created_at']
        );
    }

    // Obtener la orden de trabajo asociada
    public function getOrdenTrabajo(): ?\GemMotors\Models\OrdenTrabajo
    {
        return \GemMotors\Models\OrdenTrabajo::find($this->orden_id);
    }

    // Obtener el vehículo asociado
    public function getVehiculo(): ?\GemMotors\Models\Vehiculo
    {
        return \GemMotors\Models\Vehiculo::find($this->vehiculo_id);
    }

    // Obtener el mecánico asociado
    public function getMecanico(): ?\GemMotors\Models\Usuario
    {
        if ($this->mecanico_id === null) {
            return null;
        }
        return \GemMotors\Models\Usuario::find($this->mecanico_id);
    }
}