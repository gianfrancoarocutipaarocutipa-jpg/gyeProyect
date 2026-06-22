<?php
declare(strict_types=1);

namespace GemMotors\Models;

use GemMotors\Config\Database;

class Cliente
{
    public int $id;
    public string $nombre;
    public string $dni_ruc;
    public ?string $telefono;
    public ?string $correo;
    public string $codigo_seguimiento;
    public string $created_at;

    public function __construct(int $id, string $nombre, string $dni_ruc, ?string $telefono, ?string $correo, string $codigo_seguimiento, string $created_at)
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->dni_ruc = $dni_ruc;
        $this->telefono = $telefono;
        $this->correo = $correo;
        $this->codigo_seguimiento = $codigo_seguimiento;
        $this->created_at = $created_at;
    }

    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM clientes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    public static function findAll(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM clientes ORDER BY nombre ASC');
        $rows = $stmt->fetchAll();
        return array_map([self::class, 'fromRow'], $rows);
    }

    /**
     * Busca un cliente por su número de documento (DNI o RUC)
     */
    public static function findByDniRuc(string $dni_ruc): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM clientes WHERE dni_ruc = :dni_ruc');
        $stmt->execute(['dni_ruc' => $dni_ruc]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    public static function create(array $data): self
    {
        $db = Database::getInstance();
        
        // Generar código único de seguimiento (Ej: GEM-A1B2C3D4)
        $codigo = 'GEM-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $stmt = $db->prepare('
            INSERT INTO clientes (nombre, dni_ruc, telefono, correo, codigo_seguimiento) 
            VALUES (:nombre, :dni_ruc, :telefono, :correo, :codigo)
        ');
        
        $stmt->execute([
            'nombre'   => $data['nombre'],
            'dni_ruc'  => $data['dni_ruc'],
            'telefono' => $data['telefono'] ?? null,
            'correo'   => $data['correo'] ?? null,
            'codigo'   => $codigo
        ]);

        $id = (int)$db->lastInsertId();
        return self::find($id);
    }

    public function update(array $data): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('
            UPDATE clientes 
            SET nombre = :nombre, dni_ruc = :dni_ruc, telefono = :telefono, correo = :correo 
            WHERE id = :id
        ');
        $stmt->execute([
            'id'       => $this->id,
            'nombre'   => $data['nombre'] ?? $this->nombre,
            'dni_ruc'  => $data['dni_ruc'] ?? $this->dni_ruc,
            'telefono' => $data['telefono'] ?? $this->telefono,
            'correo'   => $data['correo'] ?? $this->correo
        ]);
    }

    public function getHistorial(): array
    {
        $db = Database::getInstance();
        // Obtener órdenes con información de vehículos
        $stmt = $db->prepare('
            SELECT o.*, v.marca, v.modelo, v.placa 
            FROM ordenes_trabajo o
            JOIN vehiculos v ON o.vehiculo_id = v.id
            WHERE o.cliente_id = :id 
            ORDER BY o.created_at DESC
        ');
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int)$row['id'],
            $row['nombre'],
            $row['dni_ruc'],
            $row['telefono'],
            $row['correo'],
            $row['codigo_seguimiento'],
            $row['created_at']
        );
    }
}