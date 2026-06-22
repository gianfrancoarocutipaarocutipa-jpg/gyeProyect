<?php
declare(strict_types=1);

namespace GemMotors\Middleware;

use GemMotors\Config\App;
use GemMotors\Middleware\AuthMiddleware;

class RolMiddleware
{
    /**
     * Define los permisos por rol para diferentes recursos y acciones
     * Formato: [recurso][acción] => [roles permitidos]
     */
    private static array $permisos = [
        // Usuarios
        'usuarios' => [
            'GET' => ['administrador'],
            'POST' => ['administrador'],
            'PUT' => ['administrador'],
            'DELETE' => ['administrador']
        ],
        
        // Clientes
        'clientes' => [
            'GET' => ['administrador', 'mecanico'],
            'POST' => ['administrador', 'mecanico'],
            'PUT' => ['administrador', 'mecanico'],
            'DELETE' => ['administrador'] // Soft delete solo para admin
        ],

        'clientes/vehiculos' => [
            'GET' => ['administrador', 'mecanico']
        ],

        'clientes/historial' => [
            'GET' => ['administrador', 'mecanico']
        ],
        
        // Vehículos
        'vehiculos' => [
            'GET' => ['administrador', 'mecanico', 'cliente'],
            'POST' => ['administrador', 'mecanico'],
            'PUT' => ['administrador', 'mecanico'],
            'DELETE' => ['administrador']
        ],
        
        // Diagnósticos de un vehículo (RF-12)
        'vehiculos/diagnosticos' => [
            'GET' => ['administrador', 'mecanico', 'cliente']
        ],
        
        // Órdenes de trabajo
        'ordenes' => [
            'GET' => ['administrador', 'mecanico', 'cliente'],
            'POST' => ['administrador', 'mecanico'],
            'PUT' => ['administrador', 'mecanico'],
            'DELETE' => ['administrador']
        ],
        
        // Estados de órdenes (endpoint especial)
        'ordenes/estado' => [
            'PUT' => ['administrador', 'mecanico']
        ],
        
        // Asignar repuestos a órdenes
        'ordenes/repuestos' => [
            'POST' => ['administrador', 'mecanico'],
            'GET' => ['administrador', 'mecanico']
        ],
        
        // Asignar mecánicos a órdenes y actualizar horas
        'ordenes/mecanico' => [
            'POST' => ['administrador'],
            'PUT' => ['administrador', 'mecanico']
        ],
        
        // Estadísticas de órdenes (Dashboard)
        'ordenes/estadisticas' => [
            'GET' => ['administrador', 'mecanico']
        ],

        'ordenes/por-semana' => [
            'GET' => ['administrador', 'mecanico']
        ],
        
        // Diagnósticos
        'diagnosticos' => [
            'POST' => ['administrador', 'mecanico'],
            'GET' => ['administrador', 'mecanico', 'cliente']
        ],
        
        // Interpretar código DTC
        'diagnosticos/interpretar' => [
            'GET' => ['administrador', 'mecanico']
        ],
        
        // Catálogo DTC
        'diagnosticos/codigos' => [
            'GET' => ['administrador', 'mecanico', 'cliente']
        ],
        
        // Repuestos (Inventario)
        'repuestos' => [
            'GET' => ['administrador', 'mecanico', 'cliente'],
            'POST' => ['administrador'],
            'PUT' => ['administrador'],
            'DELETE' => ['administrador'],
        ],

        'repuestos/oem' => [
            'GET' => ['administrador', 'mecanico', 'cliente']
        ],

        'repuestos/stock-bajo' => [
            'GET' => ['administrador', 'mecanico']
        ],
        
        // Evidencias (Multimedia)
        'evidencias' => [
            'POST' => ['administrador', 'mecanico'],
            'GET' => ['administrador', 'mecanico', 'cliente'],
            'DELETE' => ['administrador', 'mecanico'] // Solo si OT no está 'entregado' (RN-06)
        ],
        
        // Presupuestos
        'presupuestos' => [
            'POST' => ['administrador', 'mecanico'],
            'GET' => ['administrador', 'mecanico', 'cliente'],
            'PUT' => ['administrador'] // aprobar | rechazar
        ],
        
        // Seguimiento público (sin autenticación requerida)
        'seguimiento' => [
            'GET' => ['administrador', 'mecanico', 'cliente', 'publico']
        ],
        
        // Reportes
        'reportes/ingresos' => [
            'GET' => ['administrador']
        ],
        'reportes/productividad' => [
            'GET' => ['administrador']
        ],
        'reportes/rotacion-repuestos' => [
            'GET' => ['administrador']
        ],
        'reportes/tiempo-promedio' => [
            'GET' => ['administrador']
        ],
        'reportes/pdf' => [
            'GET' => ['administrador', 'mecanico']
        ],
        'reportes/excel' => [
            'GET' => ['administrador']
        ],
        
        // Auth (público)
        'auth' => [
            'POST' => ['publico'], // login y registro
            'GET' => ['administrador', 'mecanico', 'cliente'] // perfil
        ]
    ];

    /**
     * Extrae el recurso y la acción de la URI y método HTTP
     * @param string $uri URI de la petición (ej: /api/clientes/123)
     * @param string $method Método HTTP (GET, POST, PUT, DELETE)
     * @return array|null ['recurso' => string, 'accion' => string, 'id' => int|null] o null si no coincide
     */
    private static function parseUri(string $uri, string $method): ?array
    {
        // Eliminar el query string (?param=valor) para que no interfiera con la identificación del recurso
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rtrim($uri, '/');

        // Quitar el prefijo /api/
        $path = preg_replace('#^/api/#', '', $uri);
        
        // Dividir por segmentos
        $segments = array_filter(explode('/', $path), 'strlen');
        
        if (empty($segments)) {
            return null;
        }
        
        $recurso = $segments[0];
        $id = null;
        $accion = null;
        
        // Manejar rutas con ID
        if (count($segments) >= 2 && is_numeric($segments[1])) {
            $id = (int)$segments[1];
            
            // Rutas especiales con ID
            if (count($segments) >= 3) {
                $accion = $segments[2];
                
                // Rutas como /api/ordenes/{id}/estado
                if (in_array($accion, ['estado', 'repuestos', 'mecanico', 'diagnosticos', 'historial', 'vehiculos'])) {
                    $recurso .= '/' . $accion;
                    $accion = null; // La acción está en el recurso ahora
                }
                
                // Rutas como /api/presupuestos/{id}/respuesta
                if ($accion === 'respuesta') {
                    $recurso .= '/' . $accion;
                    $accion = null;
                }
            }
        }
        // Manejar rutas especiales sin ID
        elseif (count($segments) >= 2) {
            $posibleAccion = $segments[1];
            
            // Generalizar la detección de sub-recursos para recursos conocidos
            $subRecursosPermitidos = [
                'diagnosticos' => ['interpretar', 'codigos'],
                'repuestos' => ['oem', 'stock-bajo'],
                'ordenes' => ['estadisticas', 'por-semana']
            ];

            if (isset($subRecursosPermitidos[$recurso]) && in_array($posibleAccion, $subRecursosPermitidos[$recurso])) {
                $recurso .= '/' . $posibleAccion;
                $accion = null;
            }

            // Rutas como /api/repuestos/oem/{codigo}
            // Rutas como /api/reportes/{tipo}
            if ($recurso === 'reportes' && in_array($posibleAccion, ['ingresos', 'productividad', 'rotacion-repuestos', 'tiempo-promedio'])) {
                $recurso .= '/' . $posibleAccion;
                $accion = null;
            }
            
            // Rutas como /api/reportes/pdf/{tipo}
            if ($recurso === 'reportes' && $posibleAccion === 'pdf') {
                $recurso .= '/' . $posibleAccion;
                $accion = null;
            }
            
            // Rutas como /api/reportes/excel/{tipo}
            if ($recurso === 'reportes' && $posibleAccion === 'excel') {
                $recurso .= '/' . $posibleAccion;
                $accion = null;
            }
        }
        
        // Si no se especificó una acción especial, usar el método HTTP como acción
        if ($accion === null) {
            $accion = strtolower($method);
        }
        
        return [
            'recurso' => $recurso,
            'accion' => $accion,
            'id' => $id
        ];
    }

    /**
     * Verifica si el rol del usuario tiene permiso para acceder al recurso
     * @param string $rol Rol del usuario (desde el token JWT)
     * @param string $recurso Recurso solicitado
     * @param string $accion Acción solicitada (GET, POST, etc.)
     * @return boolean True si tiene permiso, False en caso contrario
     */
    public static function tienePermiso(string $rol, string $recurso, string $accion): bool
    {
        // Normalizar el recurso para buscar en permisos
        $recursoNormalizado = strtolower($recurso);
        $accionNormalizada = strtoupper($accion);
        
        // Verificar si existe una regla para este recurso y acción
        if (isset(self::$permisos[$recursoNormalizado][$accionNormalizada])) {
            $rolesPermitidos = self::$permisos[$recursoNormalizado][$accionNormalizada];
            
            // El rol 'publico' es especial para rutas sin autenticación
            if ($rol === 'publico' || in_array('publico', $rolesPermitidos, true)) {
                return true;
            }
            
            return in_array($rol, $rolesPermitidos, true);
        }
        
        // Si no hay regla específica, denegar acceso por defecto (principio de menor privilegio)
        return false;
    }

    /**
     * Middleware que verifica los permisos de rol
     * Asume que AuthMiddleware ya se ejecutó y validó el JWT
     * @throws \Exception Si el usuario no tiene permiso para el recurso
     */
    public static function checkRole(): void
    {
        // Obtener el payload del JWT (establecido por AuthMiddleware)
        if (empty($_SESSION['jwt_payload'])) {
            // Esto no debería pasar si AuthMiddleware se ejecutó correctamente
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Token de autenticación requerido'
            ]);
            exit;
        }
        
        $payload = $_SESSION['jwt_payload'];
        $rol = $payload['rol'] ?? '';
        
        if (empty($rol)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Token inválido: falta información de rol'
            ]);
            exit;
        }
        
        // Obtener URI y método de la petición
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Parsear la URI para obtener recurso y acción
        $parsed = self::parseUri($uri, $method);
        
        if ($parsed === null) {
            // Ruta no reconocida, dejar que el router maneje el 404
            return;
        }
        
        $recurso = $parsed['recurso'];
        $accion = $parsed['accion'];
        
        // Verificar permisos
        if (!self::tienePermiso($rol, $recurso, $accion)) {
            App::jsonResponse(false, null, 'No tiene permisos para realizar esta acción', 403);
            exit;
        }
    }
}