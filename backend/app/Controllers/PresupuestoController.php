<?php
declare(strict_types=1);

namespace GemMotors\Controllers;

use GemMotors\Middleware\AuthMiddleware;
use GemMotors\Middleware\RolMiddleware;
use GemMotors\Models\Presupuesto;
use GemMotors\Models\OrdenTrabajo;
use GemMotors\Config\App;
use GemMotors\Services\NotificacionService;

class PresupuestoController
{
    /**
     * Crear un nuevo presupuesto para una orden de trabajo
     * POST /api/presupuestos
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

        $ordenId = $input['orden_id'] ?? 0;
        $total = $input['total'] ?? 0.0;

        // Validar campos requerizados
        if (empty($ordenId)) {
            App::jsonResponse(false, null, 'ID de orden de trabajo es requerido', 400);
            return;
        }

        // Verificar que la orden existe
        $orden = OrdenTrabajo::find($ordenId);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        // Verificar que no existe ya un presupuesto para esta orden (un solo presupuesto por orden)
        $presupuestoExistente = Presupuesto::findByOrdenId($ordenId);
        if ($presupuestoExistente !== null) {
            App::jsonResponse(false, null, 'Ya existe un presupuesto para esta orden de trabajo', 409);
            return;
        }

        try {
            // Crear presupuesto
            $presupuesto = Presupuesto::create([
                'orden_id' => $ordenId,
                'total' => $total,
                'estado' => 'pendiente' // Por defecto, empieza como pendiente
            ]);

            // Devolver respuesta
            App::jsonResponse(true, [
                'id' => $presupuesto->id,
                'orden_id' => $presupuesto->orden_id,
                'total' => (float)$presupuesto->total,
                'estado' => $presupuesto->estado,
                'motivo_rechazo' => $presupuesto->motivo_rechazo,
                'fecha_emision' => $presupuesto->fecha_emision,
                'fecha_respuesta' => $presupuesto->fecha_respuesta
            ], 'Presupuesto creado exitosamente', 201);
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Obtener presupuesto por ID de orden de trabajo
     * GET /api/presupuestos/orden/{id}
     */
    public static function getByOrden(int $id): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener presupuesto
        $presupuesto = Presupuesto::findByOrdenId($id);
        if ($presupuesto === null) {
            App::jsonResponse(false, null, 'Presupuesto no encontrado para esta orden', 404);
            return;
        }

        // Devolver respuesta
        App::jsonResponse(true, [
            'id' => $presupuesto->id,
            'orden_id' => $presupuesto->orden_id,
            'total' => (float)$presupuesto->total,
            'estado' => $presupuesto->estado,
            'motivo_rechazo' => $presupuesto->motivo_rechazo,
            'fecha_emision' => $presupuesto->fecha_emision,
            'fecha_respuesta' => $presupuesto->fecha_respuesta
        ], 'Presupuesto obtenido');
    }

    /**
     * Actualizar respuesta del presupuesto (aprobar o rechazar)
     * PUT /api/presupuestos/{id}/respuesta
     */
    public static function responder(int $id): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos (solo admin puede aprobar/rechazar)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener presupuesto
        $presupuesto = Presupuesto::find($id);
        if ($presupuesto === null) {
            App::jsonResponse(false, null, 'Presupuesto no encontrado', 404);
            return;
        }

        // Obtener datos del cuerpo de la solicitud
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $estado = $input['estado'] ?? ''; // 'aprobado' o 'rechazado'
        $motivoRechazo = $input['motivo_rechazo'] ?? null;

        // Validar estado
        if (empty($estado)) {
            App::jsonResponse(false, null, 'Estado es requerido', 400);
            return;
        }

        if (!in_array($estado, ['aprobado', 'rechazado'], true)) {
            App::jsonResponse(false, null, 'Estado debe ser "aprobado" o "rechazado"', 400);
            return;
        }

        // Si es rechazo, el motivo es requerido
        if ($estado === 'rechazado' && empty($motivoRechazo)) {
            App::jsonResponse(false, null, 'Motivo de rechazo es requerido cuando el estado es "rechazado"', 400);
            return;
        }

        try {
            // Actualizar respuesta del presupuesto
            $presupuesto->responder($estado, $motivoRechazo);

            // Notificar respuesta
            NotificacionService::notificarRespuestaPresupuesto($presupuesto->id, $estado, $motivoRechazo);

            // Devolver respuesta
            App::jsonResponse(true, [
                'id' => $presupuesto->id,
                'orden_id' => $presupuesto->orden_id,
                'total' => (float)$presupuesto->total,
                'estado' => $presupuesto->estado,
                'motivo_rechazo' => $presupuesto->motivo_rechazo,
                'fecha_emision' => $presupuesto->fecha_emision,
                'fecha_respuesta' => $presupuesto->fecha_respuesta
            ], 'Presupuesto actualizado exitosamente');
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }
}