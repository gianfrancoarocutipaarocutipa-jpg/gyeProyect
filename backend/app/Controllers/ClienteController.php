<?php
declare(strict_types=1);

namespace GemMotors\Controllers;

use GemMotors\Middleware\AuthMiddleware;
use GemMotors\Middleware\RolMiddleware;
use GemMotors\Models\Cliente;
use GemMotors\Config\App;

class ClienteController
{
    public static function index(): void
    {
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $clientes = Cliente::findAll();
        App::jsonResponse(true, $clientes, 'Clientes obtenidos');
    }

    public static function show(int $id): void
    {
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $cliente = Cliente::find($id);
        if (!$cliente) {
            App::jsonResponse(false, null, 'Cliente no encontrado', 404);
            return;
        }
        App::jsonResponse(true, $cliente, 'Cliente obtenido');
    }

    public static function create(): void
    {
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['nombre']) || empty($input['dni_ruc'])) {
            App::jsonResponse(false, null, 'Nombre y DNI/RUC son requeridos', 400);
            return;
        }

        try {
            $cliente = Cliente::create($input);
            App::jsonResponse(true, $cliente, 'Cliente creado exitosamente', 201);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '23505')) {
                App::jsonResponse(false, null, 'El DNI/RUC ya está registrado', 409);
                return;
            }
            App::jsonResponse(false, null, 'Error al crear cliente: ' . $e->getMessage(), 500);
        }
    }

    public static function update(int $id): void
    {
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $cliente = Cliente::find($id);
        if (!$cliente) {
            App::jsonResponse(false, null, 'Cliente no encontrado', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        try {
            $cliente->update($input);
            App::jsonResponse(true, $cliente, 'Cliente actualizado correctamente');
        } catch (\Exception $e) {
            App::jsonResponse(false, null, 'Error al actualizar: ' . $e->getMessage(), 500);
        }
    }

    public static function getHistorial(int $id): void
    {
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $cliente = Cliente::find($id);
        if (!$cliente) {
            App::jsonResponse(false, null, 'Cliente no encontrado', 404);
            return;
        }

        $db = \GemMotors\Config\Database::getInstance();
        $stmt = $db->prepare('
            SELECT 
                o.id,
                o.numero_ot,
                o.descripcion_problema,
                o.estado,
                o.fecha_cierre,
                o.created_at,
                v.marca AS vehiculo_marca,
                v.modelo AS vehiculo_modelo,
                v.placa AS vehiculo_placa
            FROM ordenes_trabajo o
            LEFT JOIN vehiculos v ON o.vehiculo_id = v.id
            WHERE o.cliente_id = :id
            ORDER BY o.created_at DESC
        ');
        $stmt->execute(['id' => $id]);
        $rows = $stmt->fetchAll();

        $data = array_map(function($row) {
            return [
                'id'                   => (int)$row['id'],
                'numero_ot'            => $row['numero_ot'] ?? 'N/A',
                'descripcion_problema' => $row['descripcion_problema'] ?? '',
                'estado'               => $row['estado'] ?? 'Sin estado',
                'fecha_cierre'         => $row['fecha_cierre'],
                'created_at'           => $row['created_at'],
                'vehiculo'             => [
                    'marca'  => $row['vehiculo_marca'] ?? '',
                    'modelo' => $row['vehiculo_modelo'] ?? '',
                    'placa'  => $row['vehiculo_placa'] ?? ''
                ]
            ];
        }, $rows);

        App::jsonResponse(true, $data, 'Historial obtenido');
    }
}