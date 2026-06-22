<?php
declare(strict_types=1);

namespace GemMotors\Middleware;

use GemMotors\Config\App;

class AuthMiddleware
{
    /**
     * Verifica la presencia y validez del token JWT en los encabezados
     * @return array|false Payload del token si es válido, false si no lo es
     */
    public static function verify(): array|false
    {
        // Asegurar que las configuraciones (como JWT_SECRET) estén cargadas
        App::init();

        $authHeader = null;

        // 1. Intentar mediante getallheaders (sensible a mayúsculas/minúsculas)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $headers['AUTHORIZATION'] ?? null;
        }

        // 2. Intentar mediante variables de servidor (Nginx/Apache)
        if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (!$authHeader) {
            return false;
        }

        // Extraer el token (formato: Bearer token)
        if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return false;
        }

        $token = $matches[1];

        // Validar el token usando el helper de App
        $payload = App::validateToken($token);

        return $payload;
    }

    /**
     * Middleware que requiere autenticación
     * @throws \Exception Si no hay token o es inválido
     */
    public static function requireAuth(): void
    {
        $payload = self::verify();

        if ($payload === false) {
            App::jsonResponse(false, null, 'Token de autenticación requerido o inválido', 401);
            exit;
        }

        // Hacer el payload disponible para el controller a través de la sesión
        $_SESSION['jwt_payload'] = $payload;

        // Liberar el bloqueo de sesión inmediatamente
        session_write_close();
    }

    /**
     * Middleware opcional: si hay token válido, lo valida y lo hace disponible
     * No requiere autenticación, pero si hay token lo verifica
     */
    public static function optionalAuth(): void
    {
        $payload = self::verify();
        if ($payload !== false) {
            $_SESSION['jwt_payload'] = $payload;
            session_write_close();
        }
    }
}