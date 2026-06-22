<?php
declare(strict_types=1);

namespace GemMotors\Controllers;

use GemMotors\Middleware\AuthMiddleware;
use GemMotors\Middleware\RolMiddleware;
use GemMotors\Models\Evidencia;
use GemMotors\Models\OrdenTrabajo;
use GemMotors\Config\App;

class EvidenciaController
{
    /**
     * Subir evidencia (foto o video) a Cloudinary
     * POST /api/evidencias
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
        $tipo = $input['tipo'] ?? ''; // 'foto' o 'video'
        $urlCloudinary = $input['url_cloudinary'] ?? '';
        $etiqueta = $input['etiqueta'] ?? ''; // 'antes', 'durante', 'despues'
        $descripcion = $input['descripcion'] ?? null;

        // Validar campos requeridos
        if (empty($ordenId) || empty($tipo) || empty($urlCloudinary) || empty($etiqueta)) {
            App::jsonResponse(false, null, 'ID de orden, tipo, URL de Cloudinary y etiqueta son requeridos', 400);
            return;
        }

        // Validar tipo
        if (!in_array($tipo, ['foto', 'video'], true)) {
            App::jsonResponse(false, null, 'Tipo debe ser "foto" o "video"', 400);
            return;
        }

        // Validar etiqueta
        if (!in_array($etiqueta, ['antes', 'durante', 'despues'], true)) {
            App::jsonResponse(false, null, 'Etiqueta debe ser "antes", "durante" o "despues"', 400);
            return;
        }

        // Verificar que la orden existe
        $orden = OrdenTrabajo::find($ordenId);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        // RN-06: Verificar que la OT no esté en estado 'entregado' antes de permitir upload
        // Esta validación también se hace en el modelo, pero la duplicamos aquí para una respuesta más temprana
        if ($orden->estado === 'entregado') {
            App::jsonResponse(false, null, 'No se puede subir evidencia cuando la orden está en estado "entregado"', 403);
            return;
        }

        try {
            // Crear evidencia
            $evidencia = Evidencia::create([
                'orden_id' => $ordenId,
                'tipo' => $tipo,
                'url_cloudinary' => $urlCloudinary,
                'etiqueta' => $etiqueta,
                'descripcion' => $descripcion,
                'inalterable' => false // Por defecto, se puede alterar hasta que se entregue
            ]);

            // Devolver respuesta
            App::jsonResponse(true, [
                'id' => $evidencia->id,
                'orden_id' => $evidencia->orden_id,
                'tipo' => $evidencia->tipo,
                'url_cloudinary' => $evidencia->url_cloudinary,
                'etiqueta' => $evidencia->etiqueta,
                'descripcion' => $evidencia->descripcion,
                'inalterable' => $evidencia->inalterable,
                'created_at' => $evidencia->created_at
            ], 'Evidencia subida exitosamente', 201);
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Obtener evidencias por orden de trabajo
     * GET /api/evidencias/orden/{id}
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

        // Verificar que la orden existe
        $orden = OrdenTrabajo::find($id);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        // Obtener evidencias
        $evidencias = Evidencia::findByOrdenId($id);

        // Formatear respuesta
        $evidenciasData = [];
        foreach ($evidencias as $evidencia) {
            $evidenciasData[] = [
                'id' => $evidencia->id,
                'orden_id' => $evidencia->orden_id,
                'tipo' => $evidencia->tipo,
                'url_cloudinary' => $evidencia->url_cloudinary,
                'etiqueta' => $evidencia->etiqueta,
                'descripcion' => $evidencia->descripcion,
                'inalterable' => $evidencia->inalterable,
                'created_at' => $evidencia->created_at
            ];
        }

        App::jsonResponse(true, $evidenciasData, 'Evidencias obtenidas');
    }

    /**
     * Eliminar evidencia
     * DELETE /api/evidencias/{id}
     */
    public static function delete(int $id): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener evidencia
        $evidencia = Evidencia::find($id);
        if ($evidencia === null) {
            App::jsonResponse(false, null, 'Evidencia no encontrada', 404);
            return;
        }

        try {
            // Eliminar evidencia (el modelo ya valida RN-06)
            $evidencia->delete();

            // Devolver respuesta
            App::jsonResponse(true, null, 'Evidencia eliminada exitosamente');
        } catch (\RuntimeException $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Actualizar evidencia (ej. descripción)
     * PUT /api/evidencias/{id}
     */
    public static function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $evidencia = Evidencia::find($id);
        if ($evidencia === null) {
            App::jsonResponse(false, null, 'Evidencia no encontrada', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        try {
            $evidencia->update($input);
            App::jsonResponse(true, [
                'id' => $evidencia->id,
                'descripcion' => $evidencia->descripcion,
                'etiqueta' => $evidencia->etiqueta
            ], 'Evidencia actualizada exitosamente');
        } catch (\RuntimeException $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }
}