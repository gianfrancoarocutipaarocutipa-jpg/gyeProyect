<?php
declare(strict_types=1);

namespace GemMotors\Models;

use GemMotors\Config\Database;

class OrdenTrabajo
{
    public int $id;
    public string $numero_ot;
    public int $cliente_id;
    public int $vehiculo_id;
    public ?int $mecanico_id;
    public string $descripcion_problema;
    public string $estado;
    public bool $presupuesto_aprobado;
    public ?string $fecha_cierre;
    public string $created_at;

    public ?int $tiempo_estimado_dias;
    public ?string $observaciones_reparacion;
    public ?string $fecha_estimada_entrega;

    // Propiedades adicionales para relaciones
    public ?int $cantidad_asignada = null;
    public ?string $fecha_asignacion = null;

    public function __construct(
        int $id,
        string $numero_ot,
        int $cliente_id,
        int $vehiculo_id,
        ?int $mecanico_id,
        string $descripcion_problema,
        string $estado,
        bool $presupuesto_aprobado,
        ?string $fecha_cierre,
        string $created_at,
        ?int $tiempo_estimado_dias = null,
        ?string $observaciones_reparacion = null,
        ?string $fecha_estimada_entrega = null
    ) {
        $this->id = $id;
        $this->numero_ot = $numero_ot;
        $this->cliente_id = $cliente_id;
        $this->vehiculo_id = $vehiculo_id;
        $this->mecanico_id = $mecanico_id;
        $this->descripcion_problema = $descripcion_problema;
        $this->estado = $estado;
        $this->presupuesto_aprobado = $presupuesto_aprobado;
        $this->fecha_cierre = $fecha_cierre;
        $this->created_at = $created_at;
        $this->tiempo_estimado_dias = $tiempo_estimado_dias;
        $this->observaciones_reparacion = $observaciones_reparacion;
        $this->fecha_estimada_entrega = $fecha_estimada_entrega;
    }

    // Buscar orden de trabajo por ID
    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM ordenes_trabajo WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Buscar orden de trabajo por número
    public static function findByNumeroOt(string $numeroOt): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM ordenes_trabajo WHERE numero_ot = :numero_ot');
        $stmt->execute(['numero_ot' => $numeroOt]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Listar órdenes de trabajo con filtros
    public static function findAll(array $filters = []): array
    {
        $db = Database::getInstance();
        $query = 'SELECT * FROM ordenes_trabajo WHERE 1=1';
        $params = [];

        if (isset($filters['cliente_id'])) {
            $query .= ' AND cliente_id = :cliente_id';
            $params[':cliente_id'] = $filters['cliente_id'];
        }

        if (isset($filters['vehiculo_id'])) {
            $query .= ' AND vehiculo_id = :vehiculo_id';
            $params[':vehiculo_id'] = $filters['vehiculo_id'];
        }

        if (isset($filters['estado'])) {
            $query .= ' AND estado = :estado';
            $params[':estado'] = $filters['estado'];
        }

        if (isset($filters['mecanico_id'])) {
            $query .= ' AND (mecanico_id = :mecanico_id OR id IN (SELECT orden_id FROM mecanico_ot WHERE mecanico_id = :mecanico_id))';
            $params[':mecanico_id'] = $filters['mecanico_id'];
        }

        $query .= ' ORDER BY created_at DESC';

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $ordenes = [];
        foreach ($rows as $row) {
            $ordenes[] = self::fromRow($row);
        }

        return $ordenes;
    }

    // Crear una nueva orden de trabajo
    public static function create(array $data): self
    {
        $db = Database::getInstance();

        // Verificar que no exista OT activa para el mismo vehículo (RN-01)
        if (isset($data['vehiculo_id'])) {
            $otActiva = self::getOtActivaPorVehiculo($data['vehiculo_id']);
            if ($otActiva !== null) {
                throw new \RuntimeException('El vehículo ya tiene una orden de trabajo activa');
            }
        }

        $presupuestoAprobado = isset($data['presupuesto_aprobado']) ? filter_var($data['presupuesto_aprobado'], FILTER_VALIDATE_BOOLEAN) : false;

        $stmt = $db->prepare('INSERT INTO ordenes_trabajo (numero_ot, cliente_id, vehiculo_id, descripcion_problema, estado, presupuesto_aprobado) VALUES (:numero_ot, :cliente_id, :vehiculo_id, :descripcion_problema, :estado, :presupuesto_aprobado)');
        $stmt->execute([
            'numero_ot' => $data['numero_ot'],
            'cliente_id' => $data['cliente_id'],
            'vehiculo_id' => $data['vehiculo_id'],
            'descripcion_problema' => $data['descripcion_problema'],
            'estado' => $data['estado'] ?? 'diagnostico',
            'presupuesto_aprobado' => $presupuestoAprobado ? 'true' : 'false'
        ]);

        $id = (int)$db->lastInsertId();

        return new self(
            $id,
            $data['numero_ot'],
            $data['cliente_id'],
            $data['vehiculo_id'],
            $data['mecanico_id'] ?? null,
            $data['descripcion_problema'],
            $data['estado'] ?? 'diagnostico',
            $presupuestoAprobado,
            null,
            date('Y-m-d H:i:s')
        );
    }

    // Actualizar orden de trabajo
    public function update(array $data): void
    {
        $db = Database::getInstance();
        $presupuestoFinal = isset($data['presupuesto_aprobado']) ? filter_var($data['presupuesto_aprobado'], FILTER_VALIDATE_BOOLEAN) : $this->presupuesto_aprobado;

        $sets = 'numero_ot = :numero_ot, cliente_id = :cliente_id, vehiculo_id = :vehiculo_id, descripcion_problema = :descripcion_problema, estado = :estado, presupuesto_aprobado = :presupuesto_aprobado, fecha_cierre = :fecha_cierre';
        $params = [
            'id' => $this->id,
            'numero_ot' => $data['numero_ot'] ?? $this->numero_ot,
            'cliente_id' => $data['cliente_id'] ?? $this->cliente_id,
            'vehiculo_id' => $data['vehiculo_id'] ?? $this->vehiculo_id,
            'descripcion_problema' => $data['descripcion_problema'] ?? $this->descripcion_problema,
            'estado' => $data['estado'] ?? $this->estado,
            'presupuesto_aprobado' => $presupuestoFinal ? 'true' : 'false',
            'fecha_cierre' => $data['fecha_cierre'] ?? $this->fecha_cierre,
        ];
        if (array_key_exists('tiempo_estimado_dias', $data) || $this->tiempo_estimado_dias !== null) {
            $sets .= ', tiempo_estimado_dias = :tiempo_estimado_dias';
            $params['tiempo_estimado_dias'] = $data['tiempo_estimado_dias'] ?? $this->tiempo_estimado_dias;
        }
        if (array_key_exists('observaciones_reparacion', $data) || $this->observaciones_reparacion !== null) {
            $sets .= ', observaciones_reparacion = :observaciones_reparacion';
            $params['observaciones_reparacion'] = $data['observaciones_reparacion'] ?? $this->observaciones_reparacion;
        }
        if (array_key_exists('fecha_estimada_entrega', $data) || $this->fecha_estimada_entrega !== null) {
            $sets .= ', fecha_estimada_entrega = :fecha_estimada_entrega';
            $params['fecha_estimada_entrega'] = $data['fecha_estimada_entrega'] ?? $this->fecha_estimada_entrega;
        }
        $stmt = $db->prepare("UPDATE ordenes_trabajo SET {$sets} WHERE id = :id");
        $stmt->execute($params);

        // Actualizar las propiedades
        $this->numero_ot = $data['numero_ot'] ?? $this->numero_ot;
        $this->cliente_id = $data['cliente_id'] ?? $this->cliente_id;
        $this->vehiculo_id = $data['vehiculo_id'] ?? $this->vehiculo_id;
        $this->descripcion_problema = $data['descripcion_problema'] ?? $this->descripcion_problema;
        $this->estado = $data['estado'] ?? $this->estado;
        $this->presupuesto_aprobado = $presupuestoFinal;
        $this->fecha_cierre = $data['fecha_cierre'] ?? $this->fecha_cierre;
        $this->tiempo_estimado_dias = $data['tiempo_estimado_dias'] ?? $this->tiempo_estimado_dias;
        $this->observaciones_reparacion = $data['observaciones_reparacion'] ?? $this->observaciones_reparacion;
        $this->fecha_estimada_entrega = $data['fecha_estimada_entrega'] ?? $this->fecha_estimada_entrega;
    }

    // Eliminar orden de trabajo
    public function delete(): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM ordenes_trabajo WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    // Cambiar estado de la orden (con validación de flujo secuencial RN-03)
    public function cambiarEstado(string $nuevoEstado): void
    {
        $flujoValido = [
            'diagnostico' => ['esperando_repuesto'],
            'esperando_repuesto' => ['reparacion'],
            'reparacion' => ['control_calidad', 'cancelado'],
            'control_calidad' => ['entregado'],
            'entregado' => [],
            'cancelado' => []
        ];

        if (!isset($flujoValido[$this->estado]) || !in_array($nuevoEstado, $flujoValido[$this->estado], true)) {
            throw new \RuntimeException("Transición de estado no válida: {$this->estado} → {$nuevoEstado}");
        }

        if ($nuevoEstado === 'control_calidad' && !$this->presupuesto_aprobado) {
            throw new \RuntimeException('No se puede completar la reparación sin presupuesto aprobado por el cliente');
        }

        $db = Database::getInstance();

        // Obtener el ID del usuario actual de la sesión (seteado por AuthMiddleware)
        $usuarioId = null;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['jwt_payload']) && isset($_SESSION['jwt_payload']['id'])) {
            $usuarioId = $_SESSION['jwt_payload']['id'];
        }

        // Insertar en historial_estados_ot
        $stmtHistorial = $db->prepare('INSERT INTO historial_estados_ot (orden_id, estado_anterior, estado_nuevo, usuario_id) VALUES (:orden_id, :estado_anterior, :estado_nuevo, :usuario_id)');
        $stmtHistorial->execute([
            'orden_id' => $this->id,
            'estado_anterior' => $this->estado,
            'estado_nuevo' => $nuevoEstado,
            'usuario_id' => $usuarioId
        ]);

        $stmt = $db->prepare('UPDATE ordenes_trabajo SET estado = :estado WHERE id = :id');
        $stmt->execute([
            'id' => $this->id,
            'estado' => $nuevoEstado
        ]);

        $this->estado = $nuevoEstado;

        // Si el estado es 'entregado', establecer fecha de cierre
        if ($nuevoEstado === 'entregado') {
            $this->fecha_cierre = date('Y-m-d H:i:s');
            $stmt = $db->prepare('UPDATE ordenes_trabajo SET fecha_cierre = :fecha_cierre WHERE id = :id');
            $stmt->execute([
                'id' => $this->id,
                'fecha_cierre' => $this->fecha_cierre
            ]);
        }
    }

    // Obtener historial de estados de la orden
    public function getHistorialEstados(): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('
            SELECT h.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido 
            FROM historial_estados_ot h 
            LEFT JOIN usuarios u ON h.usuario_id = u.id 
            WHERE h.orden_id = :orden_id 
            ORDER BY h.created_at ASC
        ');
        $stmt->execute(['orden_id' => $this->id]);
        return $stmt->fetchAll();
    }

    // Verificar si el vehículo tiene una OT activa (RN-01)
    public static function getOtActivaPorVehiculo(int $vehiculoId): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM ordenes_trabajo WHERE vehiculo_id = :vehiculo_id AND estado IN (\'diagnostico\', \'reparacion\', \'esperando_repuesto\', \'control_calidad\')');
        $stmt->execute(['vehiculo_id' => $vehiculoId]);
        $row = $stmt->fetch();

        if ($row) {
            return self::fromRow($row);
        }

        return null;
    }

    // Método auxiliar para crear objeto desde fila de BD
    public static function fromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['numero_ot'],
            $row['cliente_id'],
            $row['vehiculo_id'],
            $row['mecanico_id'] ?? null,
            $row['descripcion_problema'],
            $row['estado'],
            filter_var($row['presupuesto_aprobado'], FILTER_VALIDATE_BOOLEAN),
            $row['fecha_cierre'],
            $row['created_at'],
            isset($row['tiempo_estimado_dias']) ? (int)$row['tiempo_estimado_dias'] : null,
            $row['observaciones_reparacion'] ?? null,
            $row['fecha_estimada_entrega'] ?? null
        );
    }

    // Obtener el cliente asociado
    public function getCliente(): ?\GemMotors\Models\Cliente
    {
        return \GemMotors\Models\Cliente::find($this->cliente_id);
    }

    // Obtener el vehículo asociado
    public function getVehiculo(): ?\GemMotors\Models\Vehiculo
    {
        return \GemMotors\Models\Vehiculo::find($this->vehiculo_id);
    }

    // Obtener el mecánico asignado
    public function getMecanico(): ?\GemMotors\Models\Usuario
    {
        if ($this->mecanico_id === null) {
            return null;
        }
        return \GemMotors\Models\Usuario::find($this->mecanico_id);
    }

    // Obtener diagnósticos asociados
    public function getDiagnosticos(): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM diagnosticos WHERE orden_id = :orden_id ORDER BY created_at DESC');
        $stmt->execute(['orden_id' => $this->id]);
        $rows = $stmt->fetchAll();

        $diagnosticos = [];
        foreach ($rows as $row) {
            $diagnosticos[] = \GemMotors\Models\Diagnostico::fromRow($row);
        }

        return $diagnosticos;
    }

    // Obtener la lista de mecánicos asignados desde mecanico_ot
    public function getMecanicosAsignados(): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('
            SELECT mot.id as asignacion_id, mot.mecanico_id, u.nombre, u.apellido, mot.horas_trabajadas, mot.fecha_asignacion 
            FROM mecanico_ot mot 
            JOIN usuarios u ON mot.mecanico_id = u.id 
            WHERE mot.orden_id = :orden_id
        ');
        $stmt->execute(['orden_id' => $this->id]);
        return $stmt->fetchAll();
    }

    // Obtener repuestos asignados
    public function getRepuestosAsignados(): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('
            SELECT ar.*, r.nombre as repuesto_nombre, r.codigo_oem, r.precio_unitario 
            FROM asignacion_repuesto ar 
            JOIN repuestos r ON ar.repuesto_id = r.id 
            WHERE ar.orden_id = :orden_id
        ');
        $stmt->execute(['orden_id' => $this->id]);
        $rows = $stmt->fetchAll();

        $asignaciones = [];
        foreach ($rows as $row) {
            $asignaciones[] = [
                'id' => $row['id'],
                'repuesto_id' => $row['repuesto_id'],
                'cantidad' => $row['cantidad'],
                'fecha_asignacion' => $row['fecha_asignacion'],
                'repuesto_nombre' => $row['repuesto_nombre'],
                'codigo_oem' => $row['codigo_oem'],
                'precio_unitario' => (float)$row['precio_unitario'],
                'subtotal' => (float)$row['cantidad'] * (float)$row['precio_unitario']
            ];
        }

        return $asignaciones;
    }

    // Obtener evidencias asociadas
    public function getEvidencias(): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM evidencias WHERE orden_id = :orden_id ORDER BY created_at');
        $stmt->execute(['orden_id' => $this->id]);
        $rows = $stmt->fetchAll();

        $evidencias = [];
        foreach ($rows as $row) {
            $evidencias[] = \GemMotors\Models\Evidencia::fromRow($row);
        }

        return $evidencias;
    }

    // Obtener presupuesto asociado
    public function getPresupuesto(): ?\GemMotors\Models\Presupuesto
    {
        return \GemMotors\Models\Presupuesto::findByOrdenId($this->id);
    }

    // Calcular total de repuestos asignados
    public function getTotalRepuestos(): float
    {
        $total = 0.0;
        foreach ($this->getRepuestosAsignados() as $asignacion) {
            $total += $asignacion['subtotal'];
        }
        return $total;
    }
}