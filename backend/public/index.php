<?php
declare(strict_types=1);

// Permitir solicitudes CORS desde el frontend
$allowedOrigin = rtrim(getenv('FRONTEND_URL') ?: 'http://localhost:5173', '/');
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Si es una petición de preflight (OPTIONS), respondemos 200 inmediatamente y salimos
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sesión para persistencia interna si la requieres
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. CARGAR EL AUTOLOADER DE COMPOSER (Indispensable para que encuentre tus Modelos y Middlewares)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// 2. INICIALIZAR CONFIGURACIÓN (Cargar .env y constantes)
// Esto garantiza que el JWT_SECRET esté disponible para los Middlewares
\GemMotors\Config\App::init();

// 2. Cargar el enrutador principal de la API
require_once __DIR__ . '/../routes/api.php';