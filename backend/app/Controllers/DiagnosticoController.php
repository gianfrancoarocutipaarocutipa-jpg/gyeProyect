<?php
declare(strict_types=1);

namespace GemMotors\Controllers;

use GemMotors\Middleware\AuthMiddleware;
use GemMotors\Middleware\RolMiddleware;
use GemMotors\Models\Diagnostico;
use GemMotors\Models\OrdenTrabajo;
use GemMotors\Models\Vehiculo;
use GemMotors\Models\Usuario;
use GemMotors\Models\Repuesto;
use GemMotors\Config\App;
use GemMotors\Services\OBDService;
use GemMotors\Services\NotificacionService;

class DiagnosticoController
{
    /**
     * Crear un nuevo diagnóstico
     * POST /api/diagnosticos
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
        $vehiculoId = $input['vehiculo_id'] ?? 0;
        $mecanicoId = $input['mecanico_id'] ?? 0;
        $tramasHex = $input['tramas_hex'] ?? '';
        $observaciones = $input['observaciones'] ?? '';
        $kilometraje = $input['kilometraje'] ?? null;
        // Códigos enviados directamente por el frontend (ej. B/C/U del simulador que el parser hex no extrae)
        $codigosDirectos = is_array($input['codigos_directos'] ?? null) ? $input['codigos_directos'] : [];

        // Validar campos mínimos requeridos
        if (empty($ordenId) || empty($tramasHex)) {
            App::jsonResponse(false, null, 'ID de orden y tramas hex son requeridos', 400);
            return;
        }

        // Verificar que la orden existe
        $orden = OrdenTrabajo::find($ordenId);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        // Robustez: Si no viene el vehiculo_id, lo tomamos de la orden
        if (empty($vehiculoId)) {
            $vehiculoId = $orden->vehiculo_id;
        } else {
            // Si viene, validamos que coincida con la orden por seguridad
            if ((int)$vehiculoId !== (int)$orden->vehiculo_id) {
                App::jsonResponse(false, null, 'El vehículo no corresponde a la orden de trabajo', 400);
                return;
            }
        }

        // Identificar al mecánico (usar el enviado o el usuario autenticado)
        if (!empty($mecanicoId)) {
            $mecanico = Usuario::find($mecanicoId);
            if ($mecanico === null) {
                App::jsonResponse(false, null, 'Mecánico no encontrado', 404);
                return;
            }
        } else {
            $mecanicoId = $_SESSION['jwt_payload']['id'] ?? null;
        }

        try {
            // Si el frontend envía los códigos ya validados (vistos por el usuario), usarlos directo.
            // Solo parsear el hex como fallback cuando no vienen códigos del frontend.
            $obdService = new OBDService();
            $codigosFalla = !empty($codigosDirectos)
                ? array_values(array_unique($codigosDirectos))
                : $obdService->parsearTramaHex($tramasHex);

            // Crear diagnóstico
            $diagnostico = Diagnostico::create([
                'orden_id' => $ordenId,
                'vehiculo_id' => $vehiculoId,
                'mecanico_id' => $mecanicoId,
                'tramas_hex' => $tramasHex,
                'codigos_falla' => $codigosFalla, // Guardar los códigos parseados
                'observaciones' => $observaciones,
                'kilometraje' => $kilometraje
            ]);

            // Avanzar la orden a esperando_repuesto para que se asignen mecánicos y repuestos.
            $orden->cambiarEstado('esperando_repuesto');

            NotificacionService::notificarCambioEstado($orden->id, 'esperando_repuesto');

            // Devolver respuesta
            App::jsonResponse(true, [
                'id' => $diagnostico->id,
                'orden_id' => $diagnostico->orden_id,
                'vehiculo_id' => $diagnostico->vehiculo_id,
                'codigos_falla' => $diagnostico->codigos_falla,
                'tramas_hex' => $diagnostico->tramas_hex,
                'observaciones' => $diagnostico->observaciones,
                'created_at' => $diagnostico->created_at
            ], 'Diagnóstico creado exitosamente', 201);
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Obtener diagnósticos por orden de trabajo
     * GET /api/diagnosticos/orden/{id}
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

        // Obtener diagnósticos
        $diagnosticos = Diagnostico::findByOrdenId($id);

        // Formatear respuesta
        $diagnosticosData = [];
        foreach ($diagnosticos as $diagnostico) {
            $diagnosticosData[] = [
                'id' => $diagnostico->id,
                'orden_id' => $diagnostico->orden_id,
                'vehiculo_id' => $diagnostico->vehiculo_id,
                'mecanico_id' => $diagnostico->mecanico_id,
                'tramas_hex' => $diagnostico->tramas_hex,
                'codigos_falla' => $diagnostico->codigos_falla,
                'observaciones' => $diagnostico->observaciones,
                'created_at' => $diagnostico->created_at
            ];
        }

        App::jsonResponse(true, $diagnosticosData, 'Diagnósticos obtenidos');
    }

    /**
     * Interpretar código DTC
     * GET /api/diagnosticos/interpretar/{codigo}
     */
    public static function interpretarCodigo(string $codigo): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Este endpoint es público (no requiere autenticación) según RF-12
        // Pero verificamos opcionalmente si hay token
        AuthMiddleware::optionalAuth();
        // No verificamos roles porque es público

        // Validar código
        if (empty($codigo)) {
            App::jsonResponse(false, null, 'Código DTC es requerido', 400);
            return;
        }

        // Interpretar código usando el servicio OBD
        $obdService = new OBDService();
        $resultado = $obdService->interpretarCodigo($codigo);

        // Devolver respuesta
        if ($resultado['encontrado']) {
            App::jsonResponse(true, $resultado, 'Código interpretado');
        } else {
            App::jsonResponse(false, null, 'Código DTC no encontrado', 404);
        }
    }

    /**
     * Obtener catálogo completo de códigos DTC
     * GET /api/diagnosticos/codigos
     */
    public static function getCodigos(): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Este endpoint es público (no requiere autenticación) según RF-12
        // Pero verificamos opcionalmente si hay token
        AuthMiddleware::optionalAuth();
        // No verificamos roles porque es público

        // Obtener catálogo del servicio OBD
        $obdService = new OBDService();
        $catalogo = $obdService->catalogoDTC;

        // Formatear respuesta para incluir solo códigos y descripciones
        $codigosData = [];
        foreach ($catalogo as $codigo => $info) {
            $codigosData[] = [
                'codigo' => $codigo,
                'descripcion' => $info['descripcion'],
                'tipo' => $info['tipo'],
                'sistema' => $info['sistema']
            ];
        }

        App::jsonResponse(true, $codigosData, 'Catálogo de códigos DTC obtenido');
    }

    /**
     * Parsear una trama hex y devolver detalles de los códigos encontrados
     * POST /api/diagnosticos/parsear-trama
     */
    public static function parsearTrama(): void
    {
        AuthMiddleware::requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $trama = $input['trama_hex'] ?? '';

        if (empty($trama)) {
            App::jsonResponse(false, null, 'Trama hexadecimal requerida', 400);
            return;
        }

        $obdService = new OBDService();
        $codigos = $obdService->parsearTramaHex($trama);
        
        $detalles = [];
        foreach ($codigos as $codigo) {
            $interpretacion = $obdService->interpretarCodigo($codigo);
            if ($interpretacion['encontrado']) {
                $detalles[] = [
                    'codigo' => $codigo,
                    'descripcion' => $interpretacion['descripcion'],
                    'tipo' => $interpretacion['tipo'],
                    'sistema' => $interpretacion['sistema']
                ];
            }
        }

        App::jsonResponse(true, $detalles, 'Trama parseada');
    }
}