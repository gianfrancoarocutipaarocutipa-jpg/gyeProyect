<?php
declare(strict_types=1);

namespace GemMotors\Models;

use GemMotors\Config\Database;

class Repuesto
{
    public int $id;
    public string $codigo_oem;
    public string $nombre;
    public ?string $descripcion;
    public ?string $categoria;
    public ?string $marca_fabricante;
    public int $stock;
    public int $stock_minimo;
    public float $precio_unitario;
    public string $created_at;

    public function __construct(
        int $id,
        string $codigo_oem,
        string $nombre,
        ?string $descripcion,
        ?string $categoria,
        ?string $marca_fabricante,
        int $stock,
        int $stock_minimo,
        float $precio_unitario,
        string $created_at
    ) {
        $this->id = $id;
        $this->codigo_oem = $codigo_oem;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->categoria = $categoria;
        $this->marca_fabricante = $marca_fabricante;
        $this->stock = $stock;
        $this->stock_minimo = $stock_minimo;
        $this->precio_unitario = $precio_unitario;
        $this->created_at = $created_at;
    }

    // Buscar repuesto por ID
    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM repuestos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Buscar repuesto por código OEM
    public static function findByCodigoOem(string $codigoOem): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM repuestos WHERE codigo_oem = :codigo_oem');
        $stmt->execute(['codigo_oem' => $codigoOem]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Listar todos los repuestos con filtros
    public static function findAll(array $filters = []): array
    {
        $db = Database::getInstance();
        $query = 'SELECT * FROM repuestos WHERE 1=1';
        $params = [];

        if (isset($filters['busqueda'])) {
            $query .= ' AND (nombre LIKE :busqueda OR codigo_oem LIKE :busqueda OR categoria LIKE :busqueda)';
            $params[':busqueda'] = '%' . $filters['busqueda'] . '%';
        }

        if (isset($filters['stock_bajo']) && $filters['stock_bajo'] === true) {
            $query .= ' AND stock <= stock_minimo';
        }

        $query .= ' ORDER BY nombre';

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $repuestos = [];
        foreach ($rows as $row) {
            $repuestos[] = self::fromRow($row);
        }

        return $repuestos;
    }

    // Crear un nuevo repuesto
    public static function create(array $data): self
    {
        // Validar formato OEM (RN-07)
        if (!self::validarOEM($data['codigo_oem'])) {
            throw new \InvalidArgumentException('Formato de código OEM inválido. Debe ser alfanumérico con guiones.');
        }

        $stockMinimo = (isset($data['stock_minimo']) && $data['stock_minimo'] !== null && $data['stock_minimo'] !== '') ? (int)$data['stock_minimo'] : 1;

        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO repuestos (codigo_oem, nombre, descripcion, categoria, marca_fabricante, stock, stock_minimo, precio_unitario) VALUES (:codigo_oem, :nombre, :descripcion, :categoria, :marca_fabricante, :stock, :stock_minimo, :precio_unitario)');
        $stmt->execute([
            'codigo_oem' => $data['codigo_oem'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'categoria' => $data['categoria'] ?? null,
            'marca_fabricante' => $data['marca_fabricante'] ?? null,
            'stock' => $data['stock'],
            'stock_minimo' => $stockMinimo,
            'precio_unitario' => $data['precio_unitario']
        ]);

        $id = (int)$db->lastInsertId();

        return new self(
            $id,
            $data['codigo_oem'],
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['categoria'] ?? null,
            $data['marca_fabricante'] ?? null,
            $data['stock'],
            $stockMinimo,
            (float)$data['precio_unitario'],
            date('Y-m-d H:i:s')
        );
    }

    // Actualizar repuesto
    public function update(array $data): void
    {
        // Validar formato OEM si se está actualizando (RN-07)
        if (isset($data['codigo_oem']) && !self::validarOEM($data['codigo_oem'])) {
            throw new \InvalidArgumentException('Formato de código OEM inválido. Debe ser alfanumérico con guiones.');
        }

        $stockMinimo = (isset($data['stock_minimo']) && $data['stock_minimo'] !== null && $data['stock_minimo'] !== '') 
            ? (int)$data['stock_minimo'] 
            : (array_key_exists('stock_minimo', $data) ? 1 : $this->stock_minimo);

        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE repuestos SET codigo_oem = :codigo_oem, nombre = :nombre, descripcion = :descripcion, categoria = :categoria, marca_fabricante = :marca_fabricante, stock = :stock, stock_minimo = :stock_minimo, precio_unitario = :precio_unitario WHERE id = :id');
        $stmt->execute([
            'id' => $this->id,
            'codigo_oem' => $data['codigo_oem'] ?? $this->codigo_oem,
            'nombre' => $data['nombre'] ?? $this->nombre,
            'descripcion' => $data['descripcion'] ?? $this->descripcion,
            'categoria' => $data['categoria'] ?? $this->categoria,
            'marca_fabricante' => $data['marca_fabricante'] ?? $this->marca_fabricante,
            'stock' => $data['stock'] ?? $this->stock,
            'stock_minimo' => $stockMinimo,
            'precio_unitario' => $data['precio_unitario'] ?? $this->precio_unitario
        ]);

        // Actualizar las propiedades
        $this->codigo_oem = $data['codigo_oem'] ?? $this->codigo_oem;
        $this->nombre = $data['nombre'] ?? $this->nombre;
        $this->descripcion = $data['descripcion'] ?? $this->descripcion;
        $this->categoria = $data['categoria'] ?? $this->categoria;
        $this->marca_fabricante = $data['marca_fabricante'] ?? $this->marca_fabricante;
        $this->stock = $data['stock'] ?? $this->stock;
        $this->stock_minimo = $stockMinimo;
        $this->precio_unitario = $data['precio_unitario'] ?? $this->precio_unitario;
    }

    // Eliminar repuesto
    public function delete(): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM repuestos WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    // Método auxiliar para crear objeto desde fila de BD
    public static function fromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['codigo_oem'],
            $row['nombre'],
            $row['descripcion'],
            $row['categoria'],
            $row['marca_fabricante'],
            (int)$row['stock'],
            (int)$row['stock_minimo'],
            (float)$row['precio_unitario'],
            $row['created_at']
        );
    }

    // Validar formato OEM: alfanumérico con guiones (RN-07)
    public static function validarOEM(string $codigo): bool
    {
        return preg_match('/^[A-Z0-9-]+$/', $codigo) === 1;
    }

    // Asignar repuesto a una orden de trabajo (con validación de stock RN-02)
    public static function asignarAOrden(int $ordenId, int $repuestoId, int $cantidad): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Verificar stock disponible
            $repuesto = self::find($repuestoId);
            if ($repuesto === null) {
                throw new \RuntimeException('Repuesto no encontrado');
            }

            if ($repuesto->stock < $cantidad) {
                throw new \RuntimeException('Stock insuficiente');
            }

            if ($cantidad <= 0) {
                throw new \RuntimeException('La cantidad debe ser mayor a cero');
            }

            // Verificar que no exista ya esta asignación (evitar duplicados)
            $stmt = $db->prepare('SELECT COUNT(*) FROM asignacion_repuesto WHERE orden_id = :orden_id AND repuesto_id = :repuesto_id');
            $stmt->execute(['orden_id' => $ordenId, 'repuesto_id' => $repuestoId]);
            $count = (int)$stmt->fetchColumn();

            if ($count > 0) {
                throw new \RuntimeException('El repuesto ya está asignado a esta orden');
            }

            // Insertar asignación
            $stmt = $db->prepare('INSERT INTO asignacion_repuesto (orden_id, repuesto_id, cantidad) VALUES (:orden_id, :repuesto_id, :cantidad)');
            $stmt->execute([
                'orden_id' => $ordenId,
                'repuesto_id' => $repuestoId,
                'cantidad' => $cantidad
            ]);

            // Actualizar stock (descontar automáticamente RN-02)
            $nuevoStock = $repuesto->stock - $cantidad;
            $stmt = $db->prepare('UPDATE repuestos SET stock = :stock WHERE id = :id');
            $stmt->execute([
                'id' => $repuestoId,
                'stock' => $nuevoStock
            ]);

            // Verificar si el stock cayó por debajo o es igual al mínimo para notificar (RN-08)
            if ($nuevoStock <= $repuesto->stock_minimo) {
                // Generar notificación al administrador y registrarla en log o enviar email
                \GemMotors\Services\NotificacionService::notificarStockBajo(
                    $repuesto->nombre, 
                    $nuevoStock, 
                    $repuesto->stock_minimo
                );
            }

            // Acumular el costo en el presupuesto (RN-12)
            $costoTotal = $repuesto->precio_unitario * $cantidad;
            $stmt = $db->prepare('SELECT id, total FROM presupuestos WHERE orden_id = :orden_id');
            $stmt->execute(['orden_id' => $ordenId]);
            $presupuestoRow = $stmt->fetch();

            if ($presupuestoRow) {
                // Actualizar total
                $nuevoTotal = (float)$presupuestoRow['total'] + $costoTotal;
                $stmt = $db->prepare('UPDATE presupuestos SET total = :total WHERE id = :id');
                $stmt->execute([
                    'total' => $nuevoTotal,
                    'id' => $presupuestoRow['id']
                ]);
            } else {
                // Crear presupuesto
                $stmt = $db->prepare('INSERT INTO presupuestos (orden_id, total, estado) VALUES (:orden_id, :total, :estado)');
                $stmt->execute([
                    'orden_id' => $ordenId,
                    'total' => $costoTotal,
                    'estado' => 'pendiente'
                ]);
            }

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // Obtener órdenes de trabajo donde está asignado este repuesto
    public function getOrdenesTrabajo(): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('
            SELECT ot.*, ar.cantidad, ar.fecha_asignacion 
            FROM asignacion_repuesto ar 
            JOIN ordenes_trabajo ot ON ar.orden_id = ot.id 
            WHERE ar.repuesto_id = :repuesto_id
        ');
        $stmt->execute(['repuesto_id' => $this->id]);
        $rows = $stmt->fetchAll();

        $ordenes = [];
        foreach ($rows as $row) {
            $orden = \GemMotors\Models\OrdenTrabajo::fromRow($row);
            $orden->cantidad_asignada = (int)$row['cantidad'];
            $orden->fecha_asignacion = $row['fecha_asignacion'];
            $ordenes[] = $orden;
        }

        return $ordenes;
    }
}