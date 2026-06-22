<?php
declare(strict_types=1);

namespace GemMotors\Config;

class App
{
    // Propiedades dinámicas de la aplicación basadas en el entorno
    public static ?string $jwtSecret = null;
    public static ?int $jwtExpiry = null;
    public static ?string $appEnv = null;
    public static ?string $appUrl = null;
    public static ?string $uploadDir = null;

    // Constantes verdaderas (Valores estáticos puros)
    const DATE_FORMAT = 'd/m/Y';
    const CURRENCY = 'S/';

    // Inicializador para cargar variables de entorno de forma válida en PHP
    public static function init(): void
    {
        if (self::$jwtSecret === null) {
            self::$jwtSecret = $_ENV['JWT_SECRET'] ?? 'clave_segura_minimo_32_chars';
            self::$jwtExpiry = (int)($_ENV['JWT_EXPIRY'] ?? 28800);
            self::$appEnv = $_ENV['APP_ENV'] ?? 'development';
            self::$appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
            self::$uploadDir = __DIR__ . '/../public/uploads'; // Ajustado a la nueva estructura externa
        }
    }

    // Helper para generar respuestas JSON
    public static function jsonResponse(bool $success, $data = null, string $mensaje = '', int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'data'    => $data,
            'mensaje' => $mensaje,
        ]);
        exit;
    }

    // Helper para validar JWT
    public static function validateToken(string $token): array|false
    {
        self::init(); // Asegura la carga de las variables
        try {
            $secret = self::$jwtSecret;
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                return false;
            }

            list($headerB64, $payloadB64, $signature) = $tokenParts;

            // Decodificar header y payload usando el prefijo self::
            $header = json_decode(self::base64_url_decode($headerB64), true);
            $payload = json_decode(self::base64_url_decode($payloadB64), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            // Verificar expiración
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }

            // Verificar firma usando las variables corregidas con su $ correspondiente
            $base64UrlHeader = $headerB64;
            $base64UrlPayload = $payloadB64;
            $signatureCalculated = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $secret, true);
            $signatureCalculatedB64 = self::base64_url_encode($signatureCalculated);

            if (!hash_equals($signatureCalculatedB64, $signature)) {
                return false;
            }

            return $payload;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Funciones auxiliares para Base64 URL safe
    private static function base64_url_decode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    private static function base64_url_encode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }
}