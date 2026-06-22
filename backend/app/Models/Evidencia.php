<?php
declare(strict_types=1);

namespace GemMotors\Models;

use GemMotors\Config\Database;

class Evidencia
{
    public int $id;
    public int $orden_id;
    public string $tipo; // 'foto' o 'video'
    public string $url_cloudinary;
    public string $etiqueta; // 'antes', 'durante', 'despues'
    public ?string $descripcion;
    public bool $inalterable;
    public string $created_at;

    public function __construct(
        int $id,
        int $orden_id,
        string $tipo,
        string $url_cloudinary,
        string $etiqueta,
        ?string $descripcion,
        bool $inalterable,
        string $created_at
    ) {
        $this->id = $id;
        $this->orden_id = $orden_id;
        $this->tipo = $tipo;
        $this->url_cloudinary = $url_cloudinary;
        $this->etiqueta = $etiqueta;
        $this->descripcion = $descripcion;
        $this->inalterable = $inalterable;
        $this->created_at = $created_at;
    }

    // Buscar evidencia por ID
    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM evidencias WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Listar evidencias por orden de trabajo
    public static function findByOrdenId(int $ordenId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM evidencias WHERE orden_id = :orden_id ORDER BY created_at');
        $stmt->execute(['orden_id' => $ordenId]);
        $rows = $stmt->fetchAll();

        $evidencias = [];
        foreach ($rows as $row) {
            $evidencias[] = self::fromRow($row);
        }

        return $evidencias;
    }

    // Crear una nueva evidencia
    public static function create(array $data): self
    {
        $db = Database::getInstance();
        $inalterable = isset($data['inalterable']) ? filter_var($data['inalterable'], FILTER_VALIDATE_BOOLEAN) : false;

        $stmt = $db->prepare('INSERT INTO evidencias (orden_id, tipo, url_cloudinary, etiqueta, descripcion, inalterable) VALUES (:orden_id, :tipo, :url_cloudinary, :etiqueta, :descripcion, :inalterable)');
        $stmt->execute([
            'orden_id' => $data['orden_id'],
            'tipo' => $data['tipo'],
            'url_cloudinary' => $data['url_cloudinary'],
            'etiqueta' => $data['etiqueta'],
            'descripcion' => $data['descripcion'] ?? null,
            'inalterable' => $inalterable ? 'true' : 'false'
        ]);

        $id = (int)$db->lastInsertId();

        return new self(
            $id,
            $data['orden_id'],
            $data['tipo'],
            $data['url_cloudinary'],
            $data['etiqueta'],
            $data['descripcion'] ?? null,
            $inalterable,
            date('Y-m-d H:i:s')
        );
    }

    // Actualizar evidencia
    public function update(array $data): void
    {
        // RN-06: No se pueden modificar evidencias si la OT está en estado 'entregado'
        // Esta validación se hace en el controller, pero añadimos una capa extra aquí
        $orden = \GemMotors\Models\OrdenTrabajo::find($this->orden_id);
        if ($orden !== null && $orden->estado === 'entregado') {
            throw new \RuntimeException('No se puede modificar evidencia cuando la orden está entregada');
        }

        $inalterableFinal = isset($data['inalterable']) ? filter_var($data['inalterable'], FILTER_VALIDATE_BOOLEAN) : $this->inalterable;

        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE evidencias SET orden_id = :orden_id, tipo = :tipo, url_cloudinary = :url_cloudinary, etiqueta = :etiqueta, descripcion = :descripcion, inalterable = :inalterable WHERE id = :id');
        $stmt->execute([
            'id' => $this->id,
            'orden_id' => $data['orden_id'] ?? $this->orden_id,
            'tipo' => $data['tipo'] ?? $this->tipo,
            'url_cloudinary' => $data['url_cloudinary'] ?? $this->url_cloudinary,
            'etiqueta' => $data['etiqueta'] ?? $this->etiqueta,
            'descripcion' => $data['descripcion'] ?? $this->descripcion,
            'inalterable' => $inalterableFinal ? 'true' : 'false'
        ]);

        // Actualizar las propiedades
        $this->orden_id = $data['orden_id'] ?? $this->orden_id;
        $this->tipo = $data['tipo'] ?? $this->tipo;
        $this->url_cloudinary = $data['url_cloudinary'] ?? $this->url_cloudinary;
        $this->etiqueta = $data['etiqueta'] ?? $this->etiqueta;
        $this->descripcion = $data['descripcion'] ?? $this->descripcion;
        $this->inalterable = $inalterableFinal;
    }

    // Eliminar evidencia
    public function delete(): void
    {
        // RN-06: No se pueden eliminar evidencias si la OT está en estado 'entregado'
        $orden = \GemMotors\Models\OrdenTrabajo::find($this->orden_id);
        if ($orden !== null && $orden->estado === 'entregado') {
            throw new \RuntimeException('No se puede eliminar evidencia cuando la orden está entregada');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM evidencias WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    // Método auxiliar para crear objeto desde fila de BD
    public static function fromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['orden_id'],
            $row['tipo'],
            $row['url_cloudinary'],
            $row['etiqueta'],
            $row['descripcion'],
            filter_var($row['inalterable'], FILTER_VALIDATE_BOOLEAN),
            $row['created_at']
        );
    }

    // Obtener la orden de trabajo asociada
    public function getOrdenTrabajo(): ?\GemMotors\Models\OrdenTrabajo
    {
        return \GemMotors\Models\OrdenTrabajo::find($this->orden_id);
    }
}