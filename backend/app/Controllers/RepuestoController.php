<?php
declare(strict_types=1);

namespace GemMotors\Controllers;

use GemMotors\Middleware\AuthMiddleware;
use GemMotors\Middleware\RolMiddleware;
use GemMotors\Models\Repuesto;
use GemMotors\Config\App;

class RepuestoController
{
    /**
     * Obtener lista de repuestos con filtros
     * GET /api/repuestos
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
        if (!empty($_GET['q'])) {
            $filters['busqueda'] = $_GET['q'];
        }
        if (!empty($_GET['stock_bajo']) && $_GET['stock_bajo'] === 'true') {
            $filters['stock_bajo'] = true;
        }

        // Obtener repuestos
        $repuestos = Repuesto::findAll($filters);

        // Formatear respuesta
        $repuestosData = [];
        foreach ($repuestos as $repuesto) {
            $repuestosData[] = [
                'id' => $repuesto->id,
                'codigo_oem' => $repuesto->codigo_oem,
                'nombre' => $repuesto->nombre,
                'descripcion' => $repuesto->descripcion,
                'categoria' => $repuesto->categoria,
                'marca_fabricante' => $repuesto->marca_fabricante,
                'stock' => $repuesto->stock,
                'stock_minimo' => $repuesto->stock_minimo,
                'precio_unitario' => (float)$repuesto->precio_unitario,
                'created_at' => $repuesto->created_at
            ];
        }

        App::jsonResponse(true, $repuestosData, 'Repuestos obtenidos');
    }

    /**
     * Crear un nuevo repuesto
     * POST /api/repuestos
     */
    public static function create(): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos (solo admin)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener datos del cuerpo de la solicitud
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $codigoOem = $input['codigo_oem'] ?? '';
        $nombre = $input['nombre'] ?? '';
        $descripcion = $input['descripcion'] ?? null;
        $categoria = $input['categoria'] ?? null;
        $marcaFabricante = $input['marca_fabricante'] ?? null;
        $stock = isset($input['stock']) && $input['stock'] !== '' ? (int)$input['stock'] : 0;
        $stockMinimo = isset($input['stock_minimo']) && $input['stock_minimo'] !== '' ? (int)$input['stock_minimo'] : null;
        $precioUnitario = $input['precio_unitario'] ?? 0.0;

        // Validar campos requeridos
        if (empty($codigoOem) || empty($nombre)) {
            App::jsonResponse(false, null, 'Código OEM y nombre son requeridos', 400);
            return;
        }

        try {
            // Crear repuesto (el modelo ya valida RN-07)
            $repuesto = Repuesto::create([
                'codigo_oem' => $codigoOem,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'categoria' => $categoria,
                'marca_fabricante' => $marcaFabricante,
                'stock' => $stock,
                'stock_minimo' => $stockMinimo,
                'precio_unitario' => $precioUnitario
            ]);

            // Devolver respuesta
            App::jsonResponse(true, [
                'id' => $repuesto->id,
                'codigo_oem' => $repuesto->codigo_oem,
                'nombre' => $repuesto->nombre,
                'descripcion' => $repuesto->descripcion,
                'categoria' => $repuesto->categoria,
                'marca_fabricante' => $repuesto->marca_fabricante,
                'stock' => $repuesto->stock,
                'stock_minimo' => $repuesto->stock_minimo,
                'precio_unitario' => (float)$repuesto->precio_unitario,
                'created_at' => $repuesto->created_at
            ], 'Repuesto creado exitosamente', 201);
        } catch (\InvalidArgumentException $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Obtener un repuesto por ID
     * GET /api/repuestos/{id}
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

        // Obtener repuesto
        $repuesto = Repuesto::find($id);
        if ($repuesto === null) {
            App::jsonResponse(false, null, 'Repuesto no encontrado', 404);
            return;
        }

        // Devolver respuesta
        App::jsonResponse(true, [
            'id' => $repuesto->id,
            'codigo_oem' => $repuesto->codigo_oem,
            'nombre' => $repuesto->nombre,
            'descripcion' => $repuesto->descripcion,
            'categoria' => $repuesto->categoria,
            'marca_fabricante' => $repuesto->marca_fabricante,
            'stock' => $repuesto->stock,
            'stock_minimo' => $repuesto->stock_minimo,
            'precio_unitario' => (float)$repuesto->precio_unitario,
            'created_at' => $repuesto->created_at
        ], 'Repuesto obtenido');
    }

    /**
     * Actualizar un repuesto
     * PUT /api/repuestos/{id}
     */
    public static function update(int $id): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos (solo admin)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener repuesto
        $repuesto = Repuesto::find($id);
        if ($repuesto === null) {
            App::jsonResponse(false, null, 'Repuesto no encontrado', 404);
            return;
        }

        // Obtener datos del cuerpo de la solicitud
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $nombre = $input['nombre'] ?? $repuesto->nombre;
        $descripcion = $input['descripcion'] ?? $repuesto->descripcion;
        $categoria = $input['categoria'] ?? $repuesto->categoria;
        $marcaFabricante = $input['marca_fabricante'] ?? $repuesto->marca_fabricante;
        $stock = isset($input['stock']) && $input['stock'] !== '' ? (int)$input['stock'] : $repuesto->stock;
        $stockMinimo = isset($input['stock_minimo']) && $input['stock_minimo'] !== '' ? (int)$input['stock_minimo'] : (array_key_exists('stock_minimo', $input) ? null : $repuesto->stock_minimo);
        $precioUnitario = $input['precio_unitario'] ?? $repuesto->precio_unitario;

        // Validar campos requeridos
        if (empty($nombre)) {
            App::jsonResponse(false, null, 'Nombre es requerido', 400);
            return;
        }

        try {
            // Actualizar repuesto (el modelo ya valida RN-07 si se cambia codigo_oem)
            $repuesto->update([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'categoria' => $categoria,
                'marca_fabricante' => $marcaFabricante,
                'stock' => $stock,
                'stock_minimo' => $stockMinimo,
                'precio_unitario' => $precioUnitario
            ]);

            // Devolver respuesta
            App::jsonResponse(true, [
                'id' => $repuesto->id,
                'codigo_oem' => $repuesto->codigo_oem,
                'nombre' => $repuesto->nombre,
                'descripcion' => $repuesto->descripcion,
                'categoria' => $repuesto->categoria,
                'marca_fabricante' => $repuesto->marca_fabricante,
                'stock' => $repuesto->stock,
                'stock_minimo' => $repuesto->stock_minimo,
                'precio_unitario' => (float)$repuesto->precio_unitario,
                'created_at' => $repuesto->created_at
            ], 'Repuesto actualizado exitosamente');
        } catch (\InvalidArgumentException $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Eliminar un repuesto
     * DELETE /api/repuestos/{id}
     */
    public static function delete(int $id): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos (solo admin)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener repuesto
        $repuesto = Repuesto::find($id);
        if ($repuesto === null) {
            App::jsonResponse(false, null, 'Repuesto no encontrado', 404);
            return;
        }

        try {
            // Eliminar repuesto
            $repuesto->delete();

            // Devolver respuesta
            App::jsonResponse(true, null, 'Repuesto eliminado exitosamente');
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * Obtener el historial de consumo de un repuesto
     * GET /api/repuestos/{id}/historial
     */
    public static function getHistorialConsumo(int $id): void
    {
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $repuesto = Repuesto::find($id);
        if (!$repuesto) {
            App::jsonResponse(false, null, 'Repuesto no encontrado', 404);
            return;
        }

        $ordenes = $repuesto->getOrdenesTrabajo();
        
        $data = array_map(function($orden) {
            return [
                'orden_id' => $orden->id,
                'numero_ot' => $orden->numero_ot,
                'estado' => $orden->estado,
                'cantidad' => $orden->cantidad_asignada,
                'fecha_asignacion' => $orden->fecha_asignacion
            ];
        }, $ordenes);

        App::jsonResponse(true, $data, 'Historial de consumo obtenido');
    }

    /**
     * Obtener repuesto por código OEM
     * GET /api/repuestos/oem/{codigo}
     */
    public static function getByOem(string $codigo): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Validar código
        if (empty($codigo)) {
            App::jsonResponse(false, null, 'Código OEM es requerido', 400);
            return;
        }

        // Obtener repuesto
        $repuesto = Repuesto::findByCodigoOem($codigo);
        if ($repuesto === null) {
            App::jsonResponse(false, null, 'Repuesto no encontrado', 404);
            return;
        }

        // Devolver respuesta
        App::jsonResponse(true, [
            'id' => $repuesto->id,
            'codigo_oem' => $repuesto->codigo_oem,
            'nombre' => $repuesto->nombre,
            'descripcion' => $repuesto->descripcion,
            'categoria' => $repuesto->categoria,
            'marca_fabricante' => $repuesto->marca_fabricante,
            'stock' => $repuesto->stock,
            'stock_minimo' => $repuesto->stock_minimo,
            'precio_unitario' => (float)$repuesto->precio_unitario,
            'created_at' => $repuesto->created_at
        ], 'Repuesto obtenido');
    }

    /**
     * Obtener lista de repuestos con stock bajo
     * GET /api/repuestos/stock-bajo
     */
    public static function getStockBajo(): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Obtener repuestos con stock bajo
        $repuestos = Repuesto::findAll(['stock_bajo' => true]);

        // Formatear respuesta
        $repuestosData = [];
        foreach ($repuestos as $repuesto) {
            $repuestosData[] = [
                'id' => $repuesto->id,
                'codigo_oem' => $repuesto->codigo_oem,
                'nombre' => $repuesto->nombre,
                'descripcion' => $repuesto->descripcion,
                'categoria' => $repuesto->categoria,
                'marca_fabricante' => $repuesto->marca_fabricante,
                'stock' => $repuesto->stock,
                'stock_minimo' => $repuesto->stock_minimo,
                'precio_unitario' => (float)$repuesto->precio_unitario,
                'created_at' => $repuesto->created_at
            ];
        }

        App::jsonResponse(true, $repuestosData, 'Repuestos con stock bajo obtenidos');
    }
}