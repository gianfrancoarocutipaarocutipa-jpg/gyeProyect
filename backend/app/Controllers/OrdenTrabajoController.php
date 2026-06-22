<?php
declare(strict_types=1);

namespace GemMotors\Controllers;

use GemMotors\Middleware\AuthMiddleware;
use GemMotors\Middleware\RolMiddleware;
use GemMotors\Models\OrdenTrabajo;
use GemMotors\Models\Cliente;
use GemMotors\Models\Vehiculo;
use GemMotors\Models\Usuario;
use GemMotors\Models\Presupuesto;
use GemMotors\Config\App;
use GemMotors\Services\PDFService;
use GemMotors\Services\NotificacionService;

class OrdenTrabajoController
{
    /**
     * Obtener lista de órdenes de trabajo con filtros
     * GET /api/ordenes
     */
    public static function index(): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener filtros de query params
        $filters = [];
        if (!empty($_GET['cliente_id'])) {
            $filters['cliente_id'] = (int)$_GET['cliente_id'];
        }
        if (!empty($_GET['vehiculo_id'])) {
            $filters['vehiculo_id'] = (int)$_GET['vehiculo_id'];
        }
        if (!empty($_GET['estado'])) {
            $filters['estado'] = $_GET['estado'];
        }
        if (!empty($_GET['mecanico_id'])) {
            $filters['mecanico_id'] = (int)$_GET['mecanico_id'];
        }

        // Forzar filtro si el usuario es mecánico
        $payload = $_SESSION['jwt_payload'] ?? [];
        if (($payload['rol'] ?? '') === 'mecanico') {
            $filters['mecanico_id'] = (int)($payload['id'] ?? 0);
        }

        // Obtener órdenes
        $ordenes = OrdenTrabajo::findAll($filters);

        // Formatear respuesta
        $ordenesData = [];
        foreach ($ordenes as $orden) {
            $presupuesto = $orden->getPresupuesto();
            $ordenesData[] = [
                'id' => $orden->id,
                'numero_ot' => $orden->numero_ot,
                'cliente_id' => $orden->cliente_id,
                'vehiculo_id' => $orden->vehiculo_id,
                'mecanico_id' => $orden->mecanico_id,
                'descripcion_problema' => $orden->descripcion_problema,
                'estado' => $orden->estado,
                'presupuesto_aprobado' => $orden->presupuesto_aprobado,
                'presupuesto_id' => $presupuesto ? $presupuesto->id : null,
                'fecha_cierre' => $orden->fecha_cierre,
                'created_at' => $orden->created_at,
                'cliente' => [
                    'id' => $orden->getCliente()->id,
                    'nombre' => $orden->getCliente()->nombre,
                    'codigo_seguimiento' => $orden->getCliente()->codigo_seguimiento
                ],
                'vehiculo' => [
                    'id' => $orden->getVehiculo()->id,
                    'marca' => $orden->getVehiculo()->marca,
                    'modelo' => $orden->getVehiculo()->modelo,
                    'placa' => $orden->getVehiculo()->placa
                ]
            ];
        }

        App::jsonResponse(true, $ordenesData, 'Órdenes obtenidas');
    }

    /**
     * Crear una nueva orden de trabajo
     * POST /api/ordenes
     */
    public static function create(): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener datos del cuerpo de la solicitud
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $numeroOt = $input['numero_ot'] ?? '';
        $clienteId = $input['cliente_id'] ?? 0;
        $vehiculoId = $input['vehiculo_id'] ?? 0;
        $descripcionProblema = $input['descripcion_problema'] ?? '';
        $estado = $input['estado'] ?? 'diagnostico';

        // Validar campos requeridos
        if (empty($numeroOt) || empty($clienteId) || empty($vehiculoId) || empty($descripcionProblema)) {
            App::jsonResponse(false, null, 'Número OT, cliente ID, vehículo ID y descripción son requeridos', 400);
            return;
        }

        // Verificar que el cliente existe
        $cliente = Cliente::find($clienteId);
        if ($cliente === null) {
            App::jsonResponse(false, null, 'Cliente no encontrado', 404);
            return;
        }

        // Verificar que el vehículo existe
        $vehiculo = Vehiculo::find($vehiculoId);
        if ($vehiculo === null) {
            App::jsonResponse(false, null, 'Vehículo no encontrado', 404);
            return;
        }

        // Verificar que el vehículo pertenece al cliente
        if ($vehiculo->cliente_id !== $clienteId) {
            App::jsonResponse(false, null, 'El vehículo no pertenece al cliente especificado', 400);
            return;
        }

        // Verificar si el número OT ya existe
        $ordenExistente = OrdenTrabajo::findByNumeroOt($numeroOt);
        if ($ordenExistente !== null) {
            App::jsonResponse(false, null, 'El número de orden de trabajo ya existe', 409);
            return;
        }

        try {
            // Crear orden de trabajo (el modelo ya verifica RN-01)
            $orden = OrdenTrabajo::create([
                'numero_ot' => $numeroOt,
                'cliente_id' => $clienteId,
                'vehiculo_id' => $vehiculoId,
                'descripcion_problema' => $descripcionProblema,
                'estado' => $estado
            ]);

            // Notificar cambio de estado (si se especifica uno diferente al predeterminado)
            if ($estado !== 'diagnostico') {
                NotificacionService::notificarCambioEstado($orden->id, $estado);
            }

            // Devolver respuesta
            App::jsonResponse(true, [
                'id' => $orden->id,
                'numero_ot' => $orden->numero_ot,
                'cliente_id' => $orden->cliente_id,
                'vehiculo_id' => $orden->vehiculo_id,
                'descripcion_problema' => $orden->descripcion_problema,
                'estado' => $orden->estado,
                'presupuesto_aprobado' => $orden->presupuesto_aprobado,
                'created_at' => $orden->created_at
            ], 'Orden de trabajo creada exitosamente', 201);
        } catch (\RuntimeException $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Obtener una orden de trabajo por ID
     * GET /api/ordenes/{id}
     */
    public static function show(int $id): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener orden
        $orden = OrdenTrabajo::find($id);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        // Devolver respuesta con datos adicionales
        App::jsonResponse(true, [
            'id' => $orden->id,
            'numero_ot' => $orden->numero_ot,
            'cliente' => [
                'id' => $orden->getCliente()->id,
                'nombre' => $orden->getCliente()->nombre,
                'dni_ruc' => $orden->getCliente()->dni_ruc,
                'codigo_seguimiento' => $orden->getCliente()->codigo_seguimiento
            ],
            'vehiculo' => [
                'id' => $orden->getVehiculo()->id,
                'placa' => $orden->getVehiculo()->placa,
                'marca' => $orden->getVehiculo()->marca,
                'modelo' => $orden->getVehiculo()->modelo
            ],
            'mecanico' => $orden->getMecanico() ? [
                'id' => $orden->getMecanico()->id,
                'nombre' => $orden->getMecanico()->nombre,
                'apellido' => $orden->getMecanico()->apellido
            ] : null,
            'mecanicos_asignados' => $orden->getMecanicosAsignados(),
            'repuestos_asignados' => $orden->getRepuestosAsignados(),
            'descripcion_problema' => $orden->descripcion_problema,
            'estado' => $orden->estado,
            'presupuesto_aprobado' => $orden->presupuesto_aprobado,
            'fecha_cierre' => $orden->fecha_cierre,
            'created_at' => $orden->created_at
        ], 'Orden obtenida');
    }

    /**
     * Actualizar una orden de trabajo (descripción, etc.)
     * PUT /api/ordenes/{id}
     */
    public static function update(int $id): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener orden
        $orden = OrdenTrabajo::find($id);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        // Obtener datos del cuerpo de la solicitud
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $numeroOt = $input['numero_ot'] ?? $orden->numero_ot;
        $descripcionProblema = $input['descripcion_problema'] ?? $orden->descripcion_problema;

        // Validar campos requeridos
        if (empty($numeroOt) || empty($descripcionProblema)) {
            App::jsonResponse(false, null, 'Número OT y descripción son requeridos', 400);
            return;
        }

        // Verificar si el número OT ya existe en otra orden
        if ($numeroOt !== $orden->numero_ot) {
            $ordenExistente = OrdenTrabajo::findByNumeroOt($numeroOt);
            if ($ordenExistente !== null && $ordenExistente->id !== $id) {
                App::jsonResponse(false, null, 'El número de orden de trabajo ya existe', 409);
                return;
            }
        }

        // Actualizar orden
        $orden->update([
            'numero_ot' => $numeroOt,
            'descripcion_problema' => $descripcionProblema
        ]);

        // Devolver respuesta
        App::jsonResponse(true, [
            'id' => $orden->id,
            'numero_ot' => $orden->numero_ot,
            'cliente_id' => $orden->cliente_id,
            'vehiculo_id' => $orden->vehiculo_id,
            'descripcion_problema' => $orden->descripcion_problema,
            'estado' => $orden->estado,
            'presupuesto_aprobado' => $orden->presupuesto_aprobado,
            'fecha_cierre' => $orden->fecha_cierre,
            'created_at' => $orden->created_at
        ], 'Orden actualizada exitosamente');
    }

    /**
     * Cambiar estado de una orden de trabajo
     * PUT /api/ordenes/{id}/estado
     */
    public static function cambiarEstado(int $id): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener orden
        $orden = OrdenTrabajo::find($id);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        // Obtener datos del cuerpo de la solicitud
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $nuevoEstado = $input['estado'] ?? '';

        if (empty($nuevoEstado)) {
            App::jsonResponse(false, null, 'Estado es requerido', 400);
            return;
        }

        // Validar estados permitidos
        $estadosPermitidos = ['diagnostico', 'reparacion', 'esperando_repuesto', 'control_calidad', 'entregado'];
        if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
            App::jsonResponse(false, null, 'Estado inválido', 400);
            return;
        }

        try {
            // Cambiar estado (el modelo ya valida RN-03 y RN-04)
            $orden->cambiarEstado($nuevoEstado);

            // Notificar cambio de estado
            NotificacionService::notificarCambioEstado($orden->id, $nuevoEstado);

            // Devolver respuesta
            App::jsonResponse(true, [
                'id' => $orden->id,
                'numero_ot' => $orden->numero_ot,
                'estado' => $orden->estado,
                'fecha_cierre' => $orden->fecha_cierre
            ], 'Estado actualizado exitosamente');
        } catch (\RuntimeException $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Asignar repuestos a una orden de trabajo
     * POST /api/ordenes/{id}/repuestos
     */
    public static function asignarRepuestos(int $id): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener orden
        $orden = OrdenTrabajo::find($id);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        if ($orden->estado !== 'esperando_repuesto') {
            App::jsonResponse(false, null, 'Solo se pueden asignar repuestos cuando la orden está en estado Esperando Repuesto', 400);
            return;
        }

        // Obtener datos del cuerpo de la solicitud
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $repuestoId = $input['repuesto_id'] ?? 0;
        $cantidad = $input['cantidad'] ?? 0;

        // Validar campos requeridos
        if (empty($repuestoId) || empty($cantidad)) {
            App::jsonResponse(false, null, 'ID de repuesto y cantidad son requeridos', 400);
            return;
        }

        try {
            // Asignar repuesto (el modelo ya valida RN-02 y RN-08)
            \GemMotors\Models\Repuesto::asignarAOrden($orden->id, $repuestoId, $cantidad);

            // Notificar asignación (opcional)
            // NotificacionService::notificarAsignacionRepuesto($orden->id, $repuestoId, $cantidad);

            // Devolver respuesta
            App::jsonResponse(true, null, 'Repuesto asignado exitosamente', 201);
        } catch (\RuntimeException $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        } catch (\InvalidArgumentException $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Obtener repuestos asignados a una orden de trabajo
     * GET /api/ordenes/{id}/repuestos
     */
    public static function getRepuestos(int $id): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener orden
        $orden = OrdenTrabajo::find($id);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        // Obtener repuestos asignados
        $repuestos = $orden->getRepuestosAsignados();

        // Devolver respuesta
        App::jsonResponse(true, $repuestos, 'Repuestos obtenidos');
    }

    /**
     * Asignar mecánico a una orden de trabajo
     * POST /api/ordenes/{id}/mecanico
     */
    public static function asignarMecanico(int $id): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos (solo admin puede asignar mecánicos)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener orden
        $orden = OrdenTrabajo::find($id);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        if ($orden->estado !== 'esperando_repuesto') {
            App::jsonResponse(false, null, 'Solo se pueden asignar mecánicos cuando la orden está en estado Esperando Repuesto', 400);
            return;
        }

        // Obtener datos del cuerpo de la solicitud
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $mecanicoId = $input['mecanico_id'] ?? 0;
        $horasTrabajadas = $input['horas_trabajadas'] ?? 0.0;

        // Validar campos requeridos
        if (empty($mecanicoId)) {
            App::jsonResponse(false, null, 'ID de mecánico es requerido', 400);
            return;
        }

        // Verificar que el mecánico existe y tiene rol de mecánico
        $mecanico = Usuario::find($mecanicoId);
        if ($mecanico === null) {
            App::jsonResponse(false, null, 'Mecánico no encontrado', 404);
            return;
        }

        if ($mecanico->rol !== 'mecanico') {
            App::jsonResponse(false, null, 'El usuario especificado no tiene rol de mecánico', 400);
            return;
        }

        try {
            // Crear asignación
            $asignacion = \GemMotors\Models\MecanicoOT::create([
                'orden_id' => $orden->id,
                'mecanico_id' => $mecanicoId,
                'horas_trabajadas' => $horasTrabajadas
            ]);

            // Actualizar el mecánico responsable en la orden principal
            $orden->update(['mecanico_id' => $mecanicoId]);

            // Notificar asignación
            NotificacionService::notificarAsignacionMecanico($orden->id, $mecanicoId);

            // Devolver respuesta
            App::jsonResponse(true, [
                'id' => $asignacion->id,
                'orden_id' => $asignacion->orden_id,
                'mecanico_id' => $asignacion->mecanico_id,
                'horas_trabajadas' => $asignacion->horas_trabajadas,
                'fecha_asignacion' => $asignacion->fecha_asignacion
            ], 'Mecánico asignado exitosamente', 201);
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Actualizar horas trabajadas por un mecánico
     * PUT /api/ordenes/{id}/mecanico/{mecanico_id}/horas
     */
    public static function actualizarHorasMecanico(int $id, int $mecanicoId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();
        // Puede ser admin o el propio mecánico (en un entorno más estricto validaríamos el ID del mecánico)
        
        $orden = OrdenTrabajo::find($id);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $horasTrabajadas = isset($input['horas_trabajadas']) ? (float)$input['horas_trabajadas'] : null;
        if ($horasTrabajadas === null || $horasTrabajadas < 0) {
            App::jsonResponse(false, null, 'Horas trabajadas es requerido y debe ser positivo', 400);
            return;
        }

        try {
            $db = \GemMotors\Config\Database::getInstance();
            $stmt = $db->prepare('UPDATE mecanico_ot SET horas_trabajadas = :horas WHERE orden_id = :orden_id AND mecanico_id = :mecanico_id');
            $stmt->execute([
                'horas' => $horasTrabajadas,
                'orden_id' => $orden->id,
                'mecanico_id' => $mecanicoId
            ]);

            if ($stmt->rowCount() === 0) {
                // Verificar si existe la relación primero
                $check = $db->prepare('SELECT id FROM mecanico_ot WHERE orden_id = ? AND mecanico_id = ?');
                $check->execute([$orden->id, $mecanicoId]);
                if (!$check->fetch()) {
                    App::jsonResponse(false, null, 'El mecánico no está asignado a esta orden', 404);
                    return;
                }
            }

            App::jsonResponse(true, null, 'Horas actualizadas exitosamente');
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * Obtener estadísticas de órdenes para dashboard
     * GET /api/ordenes/estadisticas
     */
    public static function getEstadisticas(): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos (solo admin)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $db = \GemMotors\Config\Database::getInstance();

        // Contar órdenes por estado
$stmt = $db->query('
            SELECT estado, COUNT(*) as total 
            FROM ordenes_trabajo 
            GROUP BY estado
        ');
        $estadoCounts = $stmt->fetchAll();

        // Ordenes sin mecánico asignado
$stmt = $db->query('
            SELECT COUNT(*) as total 
            FROM ordenes_trabajo 
            WHERE mecanico_id IS NULL 
            AND estado IN (\'diagnostico\', \'reparacion\', \'esperando_repuesto\', \'control_calidad\')
        ');
        $sinMecanico = (int)$stmt->fetchColumn();

        // Ordenes de hoy
$stmt = $db->query('
            SELECT COUNT(*) as total 
            FROM ordenes_trabajo 
            WHERE DATE(created_at) = CURRENT_DATE
        ');
        $hoy = (int)$stmt->fetchColumn();

        // Ordenes de esta semana
$stmt = $db->query('
            SELECT COUNT(*) as total 
            FROM ordenes_trabajo 
            WHERE created_at >= DATE_TRUNC(\'week\', CURRENT_DATE)
        ');
        $semana = (int)$stmt->fetchColumn();

        App::jsonResponse(true, [
            'por_estado' => $estadoCounts,
            'sin_mecanico' => $sinMecanico,
            'hoy' => $hoy,
            'semana' => $semana
        ], 'Estadísticas obtenidas');
    }

    /**
     * Actualizar detalles de reparación y transicionar a control_calidad
     * PUT /api/ordenes/{id}/detalle-reparacion
     */
    public static function actualizarDetalleReparacion(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $orden = OrdenTrabajo::find($id);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        if ($orden->estado !== 'reparacion') {
            App::jsonResponse(false, null, 'La orden debe estar en estado Reparación', 400);
            return;
        }

        if (!$orden->presupuesto_aprobado) {
            App::jsonResponse(false, null, 'El presupuesto debe estar aprobado para completar la reparación', 400);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $tiempoEstimadoDias = isset($input['tiempo_estimado_dias']) ? (int)$input['tiempo_estimado_dias'] : null;
        $observacionesReparacion = $input['observaciones_reparacion'] ?? null;
        $fechaEstimadaEntrega = $input['fecha_estimada_entrega'] ?? null;

        if (empty($tiempoEstimadoDias) || $tiempoEstimadoDias < 1) {
            App::jsonResponse(false, null, 'Tiempo estimado de reparación es requerido (mínimo 1 día)', 400);
            return;
        }

        try {
            $orden->update([
                'tiempo_estimado_dias' => $tiempoEstimadoDias,
                'observaciones_reparacion' => $observacionesReparacion,
                'fecha_estimada_entrega' => $fechaEstimadaEntrega
            ]);
            $orden->cambiarEstado('control_calidad');
            NotificacionService::notificarCambioEstado($orden->id, 'control_calidad');

            App::jsonResponse(true, [
                'estado' => $orden->estado,
                'tiempo_estimado_dias' => $orden->tiempo_estimado_dias,
                'fecha_estimada_entrega' => $orden->fecha_estimada_entrega
            ], 'Reparación completada, orden en Control de Calidad');
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Obtener conteo de órdenes de los últimos 7 días
     * GET /api/ordenes/por-semana
     */
    public static function getPorSemana(): void
    {
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $db = \GemMotors\Config\Database::getInstance();
        
        // Consulta para obtener el conteo de los últimos 7 días (compatible con Postgres)
        $query = "
            SELECT 
                COUNT(o.id) as total
            FROM (
                SELECT current_date - i AS day
                FROM generate_series(0, 6) i
            ) d
            LEFT JOIN ordenes_trabajo o ON date(o.created_at) = d.day
            GROUP BY d.day
            ORDER BY d.day ASC
        ";
        
        try {
            $stmt = $db->query($query);
            $data = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            App::jsonResponse(true, array_map('intval', $data), 'Estadísticas semanales obtenidas');
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 500);
        }
    }
}