<?php
declare(strict_types=1);

// Configuración de CORS para permitir peticiones desde el frontend (Vite)
$allowedOrigin = getenv('FRONTEND_URL') ?: 'http://localhost:5173';
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Manejo de peticiones preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Define la ruta base
$basePath = '/api';

// Obtener la URI y método HTTP
$uri = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 1. Quitar cualquier query string (?param=valor)
if (($pos = strpos($uri, '?')) !== false) {
    $uri = substr($uri, 0, $pos);
}

// 2. Si Nginx redirigió incluyendo el index.php en la URI, lo removemos
if (strpos($uri, '/index.php') === 0) {
    $uri = substr($uri, 10);
}

// 3. Remover el prefijo /api si existe al inicio de la cadena de forma segura
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

// Normalizar la URI (eliminar slashes iniciales y finales)
$uri = trim($uri, '/');

// Dividir la URI en segmentos
$segments = array_filter(explode('/', $uri), 'strlen');

// Función para llamar a los controladores de forma dinámica (Soporta métodos estáticos y de instancia)
function callController(string $controller, string $method, array $params = []): void
{
    // Convertir nombre del controlador a clase completa
    $class = "GemMotors\\Controllers\\{$controller}";
    
    // Verificar que la clase existe mediante el Autoloader
    if (!class_exists($class)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Controlador no encontrado: ' . $controller
        ]);
        return;
    }
    
    // Verificar que el método existe
    if (!method_exists($class, $method)) {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido: ' . $method
        ]);
        return;
    }
    
    // Ejecutar de forma compatible (Estático o Instancia)
    $reflection = new ReflectionMethod($class, $method);
    if ($reflection->isStatic()) {
        call_user_func_array([$class, $method], $params);
    } else {
        $instance = new $class();
        call_user_func_array([$instance, $method], $params);
    }
}

// -------------------------------------------------------------------------
// RUTAS DEL SISTEMA (Compatibles con/sin barra inicial interna)
// -------------------------------------------------------------------------

// Rutas de Autenticación
if (preg_match('#auth/login#', $uri) && $method === 'POST') {
    callController('AuthController', 'login');
} elseif (preg_match('#auth/registro#', $uri) && $method === 'POST') {
    callController('AuthController', 'registro');
} elseif (preg_match('#auth/perfil#', $uri) && $method === 'GET') {
    callController('AuthController', 'perfil');
} elseif (preg_match('#auth/cambiar-password#', $uri) && $method === 'PUT') {
    callController('AuthController', 'cambiarPassword');
}

// Rutas de Clientes
elseif (preg_match('#clientes/(\d+)/historial#', $uri, $matches) && $method === 'GET') {
    // GET /api/clientes/{id}/historial — debe ir antes de la ruta genérica {id}
    callController('ClienteController', 'getHistorial', [(int)$matches[1]]);
} elseif (preg_match('#clientes/(\d+)/vehiculos#', $uri, $matches) && $method === 'GET') {
    // GET /api/clientes/{id}/vehiculos
    callController('ClienteController', 'getVehiculos', [(int)$matches[1]]);
} elseif (preg_match('#clientes/(\d+)$#', $uri, $matches) && $method === 'GET') {
    // GET /api/clientes/{id}
    callController('ClienteController', 'show', [(int)$matches[1]]);
} elseif (preg_match('#clientes$#', $uri) && $method === 'GET') {
    // GET /api/clientes
    callController('ClienteController', 'index');
} elseif (preg_match('#clientes$#', $uri) && $method === 'POST') {
    // POST /api/clientes
    callController('ClienteController', 'create');
} elseif (preg_match('#clientes/(\d+)$#', $uri, $matches) && $method === 'PUT') {
    // PUT /api/clientes/{id}
    callController('ClienteController', 'update', [(int)$matches[1]]);
}

// Rutas de Vehículos
elseif (preg_match('#vehiculos/(\d+)/diagnosticos#', $uri, $matches) && $method === 'GET') {
    // GET /api/vehiculos/{id}/diagnosticos — debe ir antes de la ruta genérica {id}
    callController('VehiculoController', 'getDiagnosticos', [(int)$matches[1]]);
} elseif (preg_match('#vehiculos/(\d+)/historial#', $uri, $matches) && $method === 'GET') {
    // GET /api/vehiculos/{id}/historial
    callController('VehiculoController', 'getHistorial', [(int)$matches[1]]);
} elseif (preg_match('#vehiculos/(\d+)$#', $uri, $matches) && $method === 'GET') {
    // GET /api/vehiculos/{id}
    callController('VehiculoController', 'show', [(int)$matches[1]]);
} elseif (preg_match('#vehiculos$#', $uri) && $method === 'GET') {
    // GET /api/vehiculos
    callController('VehiculoController', 'index');
} elseif (preg_match('#vehiculos$#', $uri) && $method === 'POST') {
    // POST /api/vehiculos
    callController('VehiculoController', 'create');
} elseif (preg_match('#vehiculos/(\d+)$#', $uri, $matches) && $method === 'PUT') {
    // PUT /api/vehiculos/{id}
    callController('VehiculoController', 'update', [(int)$matches[1]]);
}

// Rutas de Órdenes de Trabajo
elseif (preg_match('#ordenes(?:/(.*))?#', $uri, $matches) && $method === 'GET') {
    if (isset($matches[1]) && is_numeric($matches[1])) {
        // GET /api/ordenes/{id}
        callController('OrdenTrabajoController', 'show', [(int)$matches[1]]);
    } elseif (isset($matches[1]) && $matches[1] === 'repuestos' && preg_match('#ordenes/(\d+)/repuestos#', $uri, $repMatches)) {
        // GET /api/ordenes/{id}/repuestos
        callController('OrdenTrabajoController', 'getRepuestos', [(int)$repMatches[1]]);
    } elseif (isset($matches[1]) && $matches[1] === 'estadisticas') {
        // GET /api/ordenes/estadisticas
        callController('OrdenTrabajoController', 'getEstadisticas');
    } elseif (isset($matches[1]) && $matches[1] === 'por-semana') {
        // GET /api/ordenes/por-semana
        callController('OrdenTrabajoController', 'getPorSemana');
    } else {
        // GET /api/ordenes
        callController('OrdenTrabajoController', 'index');
    }
} elseif (preg_match('#ordenes$#', $uri) && $method === 'POST') {
    // POST /api/ordenes
    callController('OrdenTrabajoController', 'create');
} elseif (preg_match('#ordenes/(\d+)/detalle-reparacion$#', $uri, $matches) && $method === 'PUT') {
    // PUT /api/ordenes/{id}/detalle-reparacion
    callController('OrdenTrabajoController', 'actualizarDetalleReparacion', [(int)$matches[1]]);
} elseif (preg_match('#ordenes/(\d+)$#', $uri, $matches) && $method === 'PUT') {
    // PUT /api/ordenes/{id}
    callController('OrdenTrabajoController', 'update', [(int)$matches[1]]);
} elseif (preg_match('#ordenes/(\d+)/estado$#', $uri, $matches) && $method === 'PUT') {
    // PUT /api/ordenes/{id}/estado
    callController('OrdenTrabajoController', 'cambiarEstado', [(int)$matches[1]]);
} elseif (preg_match('#ordenes/(\d+)/repuestos#', $uri, $matches) && $method === 'POST') {
    // POST /api/ordenes/{id}/repuestos
    callController('OrdenTrabajoController', 'asignarRepuestos', [(int)$matches[1]]);
} elseif (preg_match('#ordenes/(\d+)/mecanico/(\d+)/horas#', $uri, $matches) && $method === 'PUT') {
    // PUT /api/ordenes/{id}/mecanico/{mecanico_id}/horas
    callController('OrdenTrabajoController', 'actualizarHorasMecanico', [(int)$matches[1], (int)$matches[2]]);
} elseif (preg_match('#ordenes/(\d+)/mecanico#', $uri, $matches) && $method === 'POST') {
    // POST /api/ordenes/{id}/mecanico
    callController('OrdenTrabajoController', 'asignarMecanico', [(int)$matches[1]]);
}

// Rutas de Diagnósticos
elseif (preg_match('#diagnosticos(?:/(.*))?#', $uri, $matches) && $method === 'GET') {
    if (isset($matches[1]) && $matches[1] === 'codigos') {
        // GET /api/diagnosticos/codigos
        callController('DiagnosticoController', 'getCodigos');
    } elseif (isset($matches[1]) && preg_match('#diagnosticos/interpretar/(.+)#', $uri, $interpMatches)) {
        // GET /api/diagnosticos/interpretar/{codigo}
        callController('DiagnosticoController', 'interpretarCodigo', [$interpMatches[1]]);
    } elseif (isset($matches[1]) && preg_match('#diagnosticos/orden/(\d+)#', $uri, $ordMatches)) {
        // GET /api/diagnosticos/orden/{id}
        callController('DiagnosticoController', 'getByOrden', [(int)$ordMatches[1]]);
    }
} elseif (preg_match('#diagnosticos(?:/(.*))?#', $uri, $matches) && $method === 'POST') {
    if (isset($matches[1]) && $matches[1] === 'parsear-trama') {
        // POST /api/diagnosticos/parsear-trama
        callController('DiagnosticoController', 'parsearTrama');
    } else {
        // POST /api/diagnosticos
        callController('DiagnosticoController', 'create');
    }
}

// Rutas de Repuestos e Inventario
elseif (preg_match('#repuestos/(\d+)/historial#', $uri, $matches) && $method === 'GET') {
    callController('RepuestoController', 'getHistorialConsumo', [(int)$matches[1]]);
} elseif (preg_match('#repuestos(?:/(.*))?#', $uri, $matches) && $method === 'GET') {
    if (isset($matches[1]) && $matches[1] === 'oem' && preg_match('#repuestos/oem/(.+)#', $uri, $oemMatches)) {
        // GET /api/repuestos/oem/{codigo}
        callController('RepuestoController', 'getByOem', [$oemMatches[1]]);
    } elseif (isset($matches[1]) && $matches[1] === 'stock-bajo') {
        // GET /api/repuestos/stock-bajo
        callController('RepuestoController', 'getStockBajo');
    } else {
        // GET /api/repuestos
        callController('RepuestoController', 'index');
    }
} elseif (preg_match('#repuestos$#', $uri) && $method === 'POST') {
    // POST /api/repuestos
    callController('RepuestoController', 'create');
} elseif (preg_match('#repuestos/(\d+)#', $uri, $matches) && $method === 'PUT') {
    // PUT /api/repuestos/{id}
    callController('RepuestoController', 'update', [(int)$matches[1]]);
} elseif (preg_match('#repuestos/(\d+)#', $uri, $matches) && $method === 'DELETE') {
    // DELETE /api/repuestos/{id}
    callController('RepuestoController', 'delete', [(int)$matches[1]]);
}

// Rutas de Evidencias
elseif (preg_match('#evidencias(?:/(.*))?#', $uri, $matches) && $method === 'GET') {
    if (isset($matches[1]) && preg_match('#evidencias/orden/(\d+)#', $uri, $ordMatches)) {
        // GET /api/evidencias/orden/{id}
        callController('EvidenciaController', 'getByOrden', [(int)$ordMatches[1]]);
    }
} elseif (preg_match('#evidencias$#', $uri) && $method === 'POST') {
    // POST /api/evidencias
    callController('EvidenciaController', 'create');
} elseif (preg_match('#evidencias/(\d+)#', $uri, $matches) && $method === 'DELETE') {
    // DELETE /api/evidencias/{id}
    callController('EvidenciaController', 'delete', [(int)$matches[1]]);
} elseif (preg_match('#evidencias/(\d+)#', $uri, $matches) && $method === 'PUT') {
    // PUT /api/evidencias/{id}
    callController('EvidenciaController', 'update', [(int)$matches[1]]);
}

// Rutas de Presupuestos
elseif (preg_match('#^presupuestos(?:/(.*))?#', $uri, $matches) && $method === 'GET') {
    if (isset($matches[1]) && preg_match('#^presupuestos/orden/(\d+)#', $uri, $ordMatches)) {
        // GET /api/presupuestos/orden/{id}
        callController('PresupuestoController', 'getByOrden', [(int)$ordMatches[1]]);
    }
} elseif (preg_match('#^presupuestos$#', $uri) && $method === 'POST') {
    // POST /api/presupuestos
    callController('PresupuestoController', 'create');
} elseif (preg_match('#^presupuestos/(\d+)/respuesta#', $uri, $matches) && $method === 'PUT') {
    // PUT /api/presupuestos/{id}/respuesta
    callController('PresupuestoController', 'responder', [(int)$matches[1]]);
}

// Rutas de Reportes
elseif (preg_match('#reportes/ingresos#', $uri) && $method === 'GET') {
    callController('ReporteController', 'getIngresos');
} elseif (preg_match('#reportes/productividad#', $uri) && $method === 'GET') {
    callController('ReporteController', 'getProductividad');
} elseif (preg_match('#reportes/rotacion-repuestos#', $uri) && $method === 'GET') {
    callController('ReporteController', 'getRotacionRepuestos');
} elseif (preg_match('#reportes/tiempo-promedio#', $uri) && $method === 'GET') {
    callController('ReporteController', 'getTiempoPromedio');
} elseif (preg_match('#reportes/pdf/orden/(\d+)/enviar$#', $uri, $matches) && $method === 'POST') {
    callController('ReporteController', 'enviarPdfCorreo', [(int)$matches[1]]);
} elseif (preg_match('#reportes/pdf/orden/(\d+)#', $uri, $matches) && $method === 'GET') {
    callController('ReporteController', 'generarPdf', [(int)$matches[1], $_GET['tipo'] ?? '']);
} elseif (preg_match('#reportes/pdf/inventario#', $uri) && $method === 'GET') {
    callController('ReporteController', 'exportarPdfInventario');
} elseif (preg_match('#reportes/excel/inventario#', $uri) && $method === 'GET') {
    callController('ReporteController', 'exportarExcelInventario');
}

// Rutas de Usuarios
elseif (preg_match('#usuarios/carga-mecanicos#', $uri) && $method === 'GET') {
    callController('UsuarioController', 'getCargaMecanicos');
} elseif (preg_match('#usuarios(?:/(.*))?#', $uri, $matches) && $method === 'GET') {
    if (empty($matches[1])) {
        callController('UsuarioController', 'index');
    }
} elseif (preg_match('#usuarios$#', $uri) && $method === 'POST') {
    // POST /api/usuarios
    callController('UsuarioController', 'create');
} elseif (preg_match('#usuarios/(\d+)#', $uri, $matches) && $method === 'PUT') {
    callController('UsuarioController', 'update', [(int)$matches[1]]);
}

// Rutas de Seguimiento Público (sin autenticación - Ley 29733)
elseif (preg_match('#seguimiento/([^/]+)$#', $uri, $matches) && $method === 'GET') {
    callController('SeguimientoController', 'track', [$matches[1]]);
} elseif (preg_match('#seguimiento/([^/]+)/presupuestos/(\d+)/respuesta$#', $uri, $matches) && $method === 'PUT') {
    callController('SeguimientoController', 'responderPresupuesto', [$matches[1], (int)$matches[2]]);
} elseif (preg_match('#seguimiento/([^/]+)/confirmar-reparacion$#', $uri, $matches) && $method === 'PUT') {
    callController('SeguimientoController', 'confirmarReparacion', [$matches[1]]);
} elseif (preg_match('#seguimiento/([^/]+)/vehiculo-recibido$#', $uri, $matches) && $method === 'PUT') {
    callController('SeguimientoController', 'vehiculoRecibido', [$matches[1]]);
}

// Si ninguna ruta coincidió, devolver 404 detallado
else {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Ruta no encontrada',
        'uri_recibida' => $uri,
        'metodo_recibido' => $method
    ]);
}