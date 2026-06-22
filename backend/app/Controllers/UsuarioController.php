<?php
declare(strict_types=1);

namespace GemMotors\Controllers;

use GemMotors\Middleware\AuthMiddleware;
use GemMotors\Middleware\RolMiddleware;
use GemMotors\Models\Usuario;
use GemMotors\Config\App;

class UsuarioController
{
    public static function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $usuarios = Usuario::findAll();

        $usuariosData = array_map(function ($usuario) {
            return [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'activo' => $usuario->activo,
                'created_at' => property_exists($usuario, 'created_at') ? $usuario->created_at : date('Y-m-d H:i:s')
            ];
        }, $usuarios);

        App::jsonResponse(true, $usuariosData, 'Usuarios obtenidos');
    }

    /**
     * Crear un nuevo usuario en el sistema
     * POST /api/usuarios
     */
    public static function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $nombre = $input['nombre'] ?? '';
        $apellido = $input['apellido'] ?? '';
        $email = $input['email'] ?? '';
        $rol = $input['rol'] ?? 'mecanico';
        $activo = isset($input['activo']) ? (bool)$input['activo'] : true;

        if (empty($nombre) || empty($email)) {
            App::jsonResponse(false, null, 'Nombre y email son requeridos', 400);
            return;
        }

        try {
            // Verificar si el correo ya existe
            $existente = Usuario::findByEmail($email);
            if ($existente !== null) {
                App::jsonResponse(false, null, 'El correo electrónico ya pertenece a un usuario existente.', 409);
                return;
            }

            // Generar contraseña temporal aleatoria (8 caracteres)
            $passwordTemporal = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            
            // Cifrar la contraseña antes de guardarla
            $hashedPassword = password_hash($passwordTemporal, PASSWORD_BCRYPT);

            $usuario = Usuario::create([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'password_hash' => $hashedPassword,
                'rol' => $rol,
                'activo' => $activo,
                'forzar_cambio_password' => true
            ]);
            
            // Simular envío de correo con credenciales
            \GemMotors\Services\NotificacionService::notificarNuevoUsuario($email, $passwordTemporal);

            App::jsonResponse(true, [
                'id' => $usuario->id, 
                'nombre' => $usuario->nombre, 
                'email' => $usuario->email, 
                'rol' => $usuario->rol, 
                'activo' => $usuario->activo,
                'forzar_cambio_password' => $usuario->forzar_cambio_password
            ], 'Usuario creado exitosamente. Se han enviado las credenciales temporales por correo.', 201);
        } catch (\Exception $e) {
            App::jsonResponse(false, null, 'Error al crear usuario: ' . $e->getMessage(), 500);
        }
    }

    public static function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $usuario = Usuario::find($id);
        if ($usuario === null) {
            App::jsonResponse(false, null, 'Usuario no encontrado', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            App::jsonResponse(false, null, 'JSON inválido', 400);
            return;
        }

        $rol = $input['rol'] ?? $usuario->rol;
        $activo = $input['activo'] ?? $usuario->activo;
        $nombre = $input['nombre'] ?? $usuario->nombre;
        $apellido = $input['apellido'] ?? $usuario->apellido;

        $usuario->update([
            'rol' => $rol,
            'activo' => $activo,
            'nombre' => $nombre,
            'apellido' => $apellido
        ]);

        App::jsonResponse(true, [
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'email' => $usuario->email,
            'rol' => $usuario->rol,
            'activo' => $usuario->activo,
            'created_at' => property_exists($usuario, 'created_at') ? $usuario->created_at : null
        ], 'Usuario actualizado');
    }

    /**
     * Obtener la carga laboral actual de cada mecánico
     * GET /api/usuarios/carga-mecanicos
     */
    public static function getCargaMecanicos(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $db = \GemMotors\Config\Database::getInstance();
        
        $query = "
            SELECT u.id, u.nombre, u.apellido, COUNT(ot.id) as ots_activas
            FROM usuarios u
            LEFT JOIN mecanico_ot mot ON u.id = mot.mecanico_id
            LEFT JOIN ordenes_trabajo ot ON mot.orden_id = ot.id AND ot.estado NOT IN ('entregado')
            WHERE u.rol = 'mecanico' AND u.activo = true
            GROUP BY u.id, u.nombre, u.apellido
            ORDER BY ots_activas ASC
        ";

        try {
            $stmt = $db->query($query);
            $carga = $stmt->fetchAll();
            App::jsonResponse(true, $carga, 'Carga de mecánicos obtenida');
        } catch (\Exception $e) {
            App::jsonResponse(false, null, 'Error al obtener carga: ' . $e->getMessage(), 500);
        }
    }
}