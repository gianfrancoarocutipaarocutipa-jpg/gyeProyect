<?php
declare(strict_types=1);

namespace GemMotors\Controllers;

use GemMotors\Middleware\AuthMiddleware;
use GemMotors\Middleware\RolMiddleware;
use GemMotors\Models\Vehiculo;
use GemMotors\Models\Cliente;
use GemMotors\Config\App;
use GemMotors\Services\OBDService;

class VehiculoController
{
    /**
     * Obtener lista de vehículos
     * GET /api/vehiculos
     */
    public static function index(): void
    {
        // Verificar autenticación y roles
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener filtro opcional de cliente_id (enviado por el frontend para el rol cliente)
        $clienteId = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : null;

        // Obtener todos los vehículos registrados
        $vehiculos = Vehiculo::findAll();

        // Si se proporciona cliente_id, filtramos la lista para mostrar solo lo que pertenece al cliente
        if ($clienteId !== null) {
            $vehiculos = array_filter($vehiculos, fn($v) => $v->cliente_id === $clienteId);
        }

        // Formatear respuesta incluyendo la relación con el cliente para la tabla
        $data = array_map(function($v) {
            $cliente = $v->getCliente();
            return [
                'id' => $v->id,
                'cliente_id' => $v->cliente_id,
                'placa' => $v->placa,
                'marca' => $v->marca,
                'modelo' => $v->modelo,
                'anio' => $v->anio,
                'vin' => $v->vin,
                'color' => $v->color,
                'foto_url' => $v->foto_url,
                'created_at' => $v->created_at,
                'cliente' => $cliente ? [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre
                ] : null
            ];
        }, array_values($vehiculos));

        App::jsonResponse(true, $data, 'Vehículos obtenidos');
    }

    /**
     * Obtener un vehículo específico por ID
     * GET /api/vehiculos/{id}
     */
    public static function show(int $id): void
    {
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $vehiculo = Vehiculo::find($id);
        if (!$vehiculo) {
            App::jsonResponse(false, null, 'Vehículo no encontrado', 404);
            return;
        }

        $cliente = $vehiculo->getCliente();
        $data = [
            'id' => $vehiculo->id,
            'cliente_id' => $vehiculo->cliente_id,
            'placa' => $vehiculo->placa,
            'marca' => $vehiculo->marca,
            'modelo' => $vehiculo->modelo,
            'anio' => $vehiculo->anio,
            'vin' => $vehiculo->vin,
            'color' => $vehiculo->color,
            'foto_url' => $vehiculo->foto_url,
            'created_at' => $vehiculo->created_at,
            'cliente' => $cliente ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'dni_ruc' => $cliente->dni_ruc
            ] : null
        ];

        App::jsonResponse(true, $data, 'Vehículo obtenido');
    }

    /**
     * Actualizar los datos de un vehículo
     * PUT /api/vehiculos/{id}
     */
    public static function update(int $id): void
    {
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $vehiculo = Vehiculo::find($id);
        if (!$vehiculo) {
            App::jsonResponse(false, null, 'Vehículo no encontrado', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        try {
            $vehiculo->update($input);
            App::jsonResponse(true, $vehiculo, 'Vehículo actualizado exitosamente');
        } catch (\Exception $e) {
            App::jsonResponse(false, null, 'Error al actualizar vehículo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Crear un nuevo vehículo en el sistema
     * POST /api/vehiculos
     */
    public static function create(): void
    {
        // Verificar autenticación y roles (Admin/Mecánico)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $placa = $input['placa'] ?? '';
        $marca = $input['marca'] ?? '';
        $modelo = $input['modelo'] ?? '';
        $clienteId = $input['cliente_id'] ?? 0;

        // Validación de campos obligatorios
        if (empty($placa) || empty($marca) || empty($modelo) || empty($clienteId)) {
            App::jsonResponse(false, null, 'Placa, marca, modelo y propietario son requeridos', 400);
            return;
        }

        try {
            // 1. Validar que el cliente exista
            $cliente = Cliente::find((int)$clienteId);
            if (!$cliente) {
                App::jsonResponse(false, null, 'El cliente seleccionado no existe', 404);
                return;
            }

            // 2. Intentar crear el vehículo
            $vehiculo = Vehiculo::create([
                'placa'      => strtoupper(trim($placa)),
                'marca'      => trim($marca),
                'modelo'     => trim($modelo),
                'cliente_id' => (int)$clienteId,
                'anio'       => !empty($input['anio']) ? (int)$input['anio'] : null,
                'vin'        => !empty($input['vin']) ? strtoupper(trim($input['vin'])) : null,
                'color'      => !empty($input['color']) ? trim($input['color']) : null,
                'foto_url'   => !empty($input['foto_url']) ? trim($input['foto_url']) : null
            ]);

            App::jsonResponse(true, [
                'id' => $vehiculo->id,
                'placa' => $vehiculo->placa,
                'marca' => $vehiculo->marca
            ], 'Vehículo registrado exitosamente', 201);

        } catch (\Exception $e) {
            // Capturar error de placa/VIN duplicado (Código 23505 en PostgreSQL)
            if (str_contains($e->getMessage(), '23505')) {
                App::jsonResponse(false, null, 'La placa o el VIN ya están registrados en el sistema', 409);
                return;
            }
            
            // Capturar violación de check constraint (Año inválido, etc.)
            if (str_contains($e->getMessage(), '23514')) {
                App::jsonResponse(false, null, 'El año del vehículo no es válido o no cumple con las reglas del taller', 400);
                return;
            }

            // Cualquier otro error de base de datos
            App::jsonResponse(false, null, 'Error al registrar vehículo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener el historial de diagnósticos OBD-II de un vehículo
     * GET /api/vehiculos/{id}/diagnosticos
     */
    public static function getDiagnosticos(int $id): void
    {
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $vehiculo = Vehiculo::find($id);
        if (!$vehiculo) {
            App::jsonResponse(false, null, 'Vehículo no encontrado', 404);
            return;
        }

        $diagnosticos = $vehiculo->getDiagnosticos();
        
        $data = array_map(function($item) {
            // Asegurar que tratamos los datos como objeto para acceso uniforme
            $d = (object)$item;
            
            $ordenId = isset($d->orden_id) ? (int)$d->orden_id : 0;
            $mecanicoId = isset($d->mecanico_id) ? (int)$d->mecanico_id : 0;
            $observacionesRaw = isset($d->observaciones) ? trim((string)$d->observaciones) : '';

            // Obtener el nombre del mecánico si existe
            $mecanicoNombre = 'No asignado';
            if ($mecanicoId > 0) {
                $m = \GemMotors\Models\Usuario::find($mecanicoId);
                if ($m) {
                    $mecanicoNombre = $m->nombre . ' ' . $m->apellido;
                }
            }

            // Obtener el número de OT para referencia en la tabla de historial
            $numeroOt = 'N/A';
            if ($ordenId > 0) {
                $o = \GemMotors\Models\OrdenTrabajo::find($ordenId);
                if ($o && !empty($o->numero_ot)) {
                    $numeroOt = (string)$o->numero_ot;
                }
            }

            $codigosRaw = is_string($d->codigos_falla ?? null)
                ? (json_decode($d->codigos_falla, true) ?: [])
                : ($d->codigos_falla ?? []);

            $obdService = new OBDService();
            $codigosDetalle = array_map(function (string $codigo) use ($obdService): array {
                $info = $obdService->interpretarCodigo($codigo);
                return [
                    'codigo'      => $codigo,
                    'descripcion' => $info['encontrado'] ? $info['descripcion'] : 'Descripción no disponible',
                    'tipo'        => $info['tipo'] ?: ($codigo[0] ?? ''),
                    'sistema'     => $info['sistema'] ?: 'Desconocido',
                ];
            }, $codigosRaw);

            return [
                'id'                   => $d->id ?? null,
                'orden_id'             => $ordenId,
                'numero_ot'            => $numeroOt,
                'mecanico_nombre'      => $mecanicoNombre,
                'tramas_hex'           => $d->tramas_hex ?? '',
                'codigos_falla'        => $codigosRaw,
                'codigos_falla_detalle'=> $codigosDetalle,
                'observaciones'        => $observacionesRaw !== '' ? $observacionesRaw : 'Sin observaciones',
                'fecha'                => !empty($d->created_at) ? $d->created_at : 'N/A',
                'created_at'           => $d->created_at ?? null
            ];
        }, array_values($diagnosticos));
        
        App::jsonResponse(true, array_values($data), 'Historial de diagnósticos obtenido');
    }

    /**
     * Obtener el historial completo de órdenes de trabajo de un vehículo
     * GET /api/vehiculos/{id}/historial
     */
    public static function getHistorial(int $id): void
    {
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $vehiculo = Vehiculo::find($id);
        if (!$vehiculo) {
            App::jsonResponse(false, null, 'Vehículo no encontrado', 404);
            return;
        }

        $ordenes = \GemMotors\Models\OrdenTrabajo::findAll(['vehiculo_id' => $id]);
        
        $data = [];
        foreach ($ordenes as $orden) {
            $repuestos = $orden->getRepuestosAsignados();
            $mecanicos = $orden->getMecanicosAsignados();
            
            $data[] = [
                'id' => $orden->id,
                'numero_ot' => $orden->numero_ot,
                'estado' => $orden->estado,
                'descripcion_problema' => $orden->descripcion_problema,
                'fecha_cierre' => $orden->fecha_cierre,
                'created_at' => $orden->created_at,
                'repuestos_count' => count($repuestos),
                'repuestos' => array_map(fn($r) => ['nombre' => $r->nombre, 'cantidad' => $r->cantidad_asignada], $repuestos),
                'mecanicos' => array_map(fn($m) => ['nombre' => $m->nombre . ' ' . $m->apellido], $mecanicos)
            ];
        }

        App::jsonResponse(true, $data, 'Historial de vehículo obtenido exitosamente');
    }
}