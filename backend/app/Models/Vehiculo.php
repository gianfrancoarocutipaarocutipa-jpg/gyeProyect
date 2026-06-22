<?php
declare(strict_types=1);

namespace GemMotors\Models;

use GemMotors\Config\Database;

class Vehiculo
{
    public int $id;
    public int $cliente_id;
    public string $placa;
    public string $marca;
    public string $modelo; 
    public ?int $anio; // Ahora puede ser nulo
    public ?string $vin;
    public ?string $color;
    public ?string $foto_url;
    public string $created_at;

    public function __construct(
        int $id,
        int $cliente_id,
        string $placa,
        string $marca,
        string $modelo, 
        ?int $anio, // Ahora puede ser nulo
        ?string $vin,
        ?string $color,
        ?string $foto_url,
        string $created_at
    ) {
        $this->id = $id;
        $this->cliente_id = $cliente_id;
        $this->placa = $placa;
        $this->marca = $marca;
        $this->modelo = $modelo;
        $this->anio = $anio;
        $this->vin = $vin;
        $this->color = $color;
        $this->foto_url = $foto_url;
        $this->created_at = $created_at;
    }

    // Buscar vehículo por ID
    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM vehiculos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Buscar vehículo por placa
    public static function findByPlaca(string $placa): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM vehiculos WHERE placa = :placa');
        $stmt->execute(['placa' => $placa]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Buscar vehículo por VIN
    public static function findByVin(string $vin): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM vehiculos WHERE vin = :vin');
        $stmt->execute(['vin' => $vin]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Listar todos los vehículos
    public static function findAll(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM vehiculos ORDER BY placa');
        $rows = $stmt->fetchAll();

        $vehiculos = [];
        foreach ($rows as $row) {
            $vehiculos[] = self::fromRow($row);
        }

        return $vehiculos;
    }

    // Crear un nuevo vehículo
    public static function create(array $data): self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO vehiculos (cliente_id, placa, marca, modelo, anio, vin, color, foto_url) VALUES (:cliente_id, :placa, :marca, :modelo, :anio, :vin, :color, :foto_url)');
        $stmt->execute([
            'cliente_id' => $data['cliente_id'],
            'placa' => $data['placa'],
            'marca' => $data['marca'],
            'modelo' => $data['modelo'],
            'anio' => $data['anio'],
            'vin' => $data['vin'] ?? null,
            'color' => $data['color'] ?? null,
            'foto_url' => $data['foto_url'] ?? null
        ]);

        $id = (int)$db->lastInsertId();

        return new self(
            $id,
            $data['cliente_id'],
            $data['placa'],
            $data['marca'],
            $data['modelo'],
            $data['anio'],
            $data['vin'] ?? null,
            $data['color'] ?? null,
            $data['foto_url'] ?? null,
            date('Y-m-d H:i:s')
        );
    }

    // Actualizar vehículo
    public function update(array $data): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE vehiculos SET cliente_id = :cliente_id, placa = :placa, marca = :marca, modelo = :modelo, anio = :anio, vin = :vin, color = :color, foto_url = :foto_url WHERE id = :id');
        $stmt->execute([
            'id' => $this->id,
            'cliente_id' => $data['cliente_id'] ?? $this->cliente_id,
            'placa' => $data['placa'] ?? $this->placa,
            'marca' => $data['marca'] ?? $this->marca,
            'modelo' => $data['modelo'] ?? $this->modelo,
            'anio' => $data['anio'] ?? $this->anio,
            'vin' => $data['vin'] ?? $this->vin,
            'color' => $data['color'] ?? $this->color,
            'foto_url' => $data['foto_url'] ?? $this->foto_url
        ]);

        // Actualizar las propiedades
        $this->cliente_id = $data['cliente_id'] ?? $this->cliente_id;
        $this->placa = $data['placa'] ?? $this->placa;
        $this->marca = $data['marca'] ?? $this->marca;
        $this->modelo = $data['modelo'] ?? $this->modelo;
        $this->anio = $data['anio'] ?? $this->anio;
        $this->vin = $data['vin'] ?? $this->vin;
        $this->color = $data['color'] ?? $this->color;
        $this->foto_url = $data['foto_url'] ?? $this->foto_url;
    }

    // Eliminar vehículo
    public function delete(): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM vehiculos WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    // Método auxiliar para crear objeto desde fila de BD
    public static function fromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['cliente_id'],
            $row['placa'],
            $row['marca'],
            $row['modelo'],
            $row['anio'] !== null ? (int)$row['anio'] : null, // Manejar anio como nulo si viene de la BD
            $row['vin'],
            $row['color'],
            $row['foto_url'] ?? null,
            $row['created_at']
        );
    }

    // Obtener el cliente asociado al vehículo
    public function getCliente(): ?\GemMotors\Models\Cliente
    {
        return \GemMotors\Models\Cliente::find($this->cliente_id);
    }

    // Obtener órdenes de trabajo asociadas al vehículo
    public function getOrdenesTrabajo(): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM ordenes_trabajo WHERE vehiculo_id = :vehiculo_id ORDER BY created_at DESC');
        $stmt->execute(['vehiculo_id' => $this->id]);
        $rows = $stmt->fetchAll();

        $ordenes = [];
        foreach ($rows as $row) {
            $ordenes[] = \GemMotors\Models\OrdenTrabajo::fromRow($row);
        }

        return $ordenes;
    }

    // Obtener diagnósticos asociados al vehículo
    public function getDiagnosticos(): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM diagnosticos WHERE vehiculo_id = :vehiculo_id ORDER BY created_at DESC');
        $stmt->execute(['vehiculo_id' => $this->id]);
        $rows = $stmt->fetchAll();

        $diagnosticos = [];
        foreach ($rows as $row) {
            $diagnosticos[] = \GemMotors\Models\Diagnostico::fromRow($row);
        }

        return $diagnosticos;
    }
}