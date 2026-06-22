<?php

declare(strict_types=1);



namespace GemMotors\Controllers;



use GemMotors\Middleware\AuthMiddleware;

use GemMotors\Middleware\RolMiddleware;

use GemMotors\Models\Usuario;

use GemMotors\Config\App;



class AuthController

{

    /**

     * Iniciar sesión

     * POST /api/auth/login

     */

    public static function login(): void

    {

        // Inicializar configuraciones dinámicas de la app

        App::init();



        // Verificar método HTTP

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            App::jsonResponse(false, null, 'Método no permitido', 405);

            return;

        }



        // Obtener datos del cuerpo de la solicitud

        $input = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            App::jsonResponse(false, null, 'JSON inválido', 400);

            return;

        }



        $email = $input['email'] ?? '';

        $password = $input['password'] ?? '';



        if (empty($email) || empty($password)) {

            App::jsonResponse(false, null, 'Email y contraseña son requeridos', 400);

            return;

        }



        // Buscar usuario por email

        $usuario = Usuario::findByEmail($email);

        if ($usuario === null) {

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

            \GemMotors\Services\NotificacionService::registrarIntentoFallidoLogin($email, $ipAddress);

           

            App::jsonResponse(false, null, 'Credenciales inválidas', 401);

            return;

        }



        // Verificar contraseña con el hash de la base de datos

        if (!password_verify($password, $usuario->password_hash)) {

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

            \GemMotors\Services\NotificacionService::registrarIntentoFallidoLogin($email, $ipAddress);

           

            App::jsonResponse(false, null, 'Credenciales inválidas', 401);

            return;

        }



        // Verificar que el usuario esté activo
        if (!$usuario->activo) {
            App::jsonResponse(false, null, 'Cuenta desactivada', 403);
            return;
        }

        // Generar token JWT usando las propiedades estáticas corregidas
        $tokenPayload = [
            'id' => $usuario->id,
            'email' => $usuario->email,
            'rol' => $usuario->rol,
            'requires_password_change' => $usuario->forzar_cambio_password,
            'iat' => time(),
            'exp' => time() + (App::$jwtExpiry ?? 3600)
        ];



        $jwt = self::generateJWT($tokenPayload, App::$jwtSecret ?? 'clave_segura_minimo_32_chars');



        // Registrar acceso exitoso (Ley 29733)

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

        \GemMotors\Services\NotificacionService::registrarAccesoExitoso($usuario->id, $ipAddress);



        // Devolver token y datos del usuario (sin contraseña)
        $isForced = $usuario->forzar_cambio_password;
        App::jsonResponse(true, [
            'token' => $jwt,
            'requires_password_change' => $isForced,
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'email' => $usuario->email,
                'rol' => $usuario->rol
            ]
        ], $isForced ? 'Debe cambiar su contraseña' : 'Inicio de sesión exitoso');

    }



    /**

     * Registrar nuevo usuario (solo para clientes)

     * POST /api/auth/registro

     */

    public static function registro(): void

    {

        App::init();



        // Verificar método HTTP

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            App::jsonResponse(false, null, 'Método no permitido', 405);

            return;

        }



        // Obtener datos del cuerpo de la solicitud

        $input = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            App::jsonResponse(false, null, 'JSON inválido', 400);

            return;

        }



        $nombre = $input['nombre'] ?? '';

        $apellido = $input['apellido'] ?? '';

        $email = $input['email'] ?? '';

        $password = $input['password'] ?? '';

        $dni_ruc = $input['dni_ruc'] ?? '';

        $telefono = $input['telefono'] ?? '';



        // Validar campos requeridos

        if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($dni_ruc)) {

            App::jsonResponse(false, null, 'Nombre, apellido, email, contraseña y DNI/RUC son requeridos', 400);

            return;

        }



        // Verificar si el email ya existe

        $usuarioExistente = Usuario::findByEmail($email);

        if ($usuarioExistente !== null) {

            App::jsonResponse(false, null, 'El email ya está registrado', 409);

            return;

        }



        // Verificar si el DNI/RUC ya existe (para clientes)

        $clienteExistente = \GemMotors\Models\Cliente::findByDniRuc($dni_ruc);

        if ($clienteExistente !== null) {

            App::jsonResponse(false, null, 'El DNI/RUC ya está registrado', 409);

            return;

        }



        // Hashear la contraseña

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);



        // Crear usuario

        $usuario = Usuario::create([

            'nombre' => $nombre,

            'apellido' => $apellido,

            'email' => $email,

            'password_hash' => $passwordHash,

            'rol' => 'cliente',

            'activo' => true

        ]);



        // Crear cliente asociado

        $cliente = \GemMotors\Models\Cliente::create([

            'nombre' => $nombre . ' ' . $apellido,

            'dni_ruc' => $dni_ruc,

            'telefono' => $telefono,

            'correo' => $email,

            'codigo_seguimiento' => self::generarCodigoSeguimiento()

        ]);



        // Generar token JWT para login automático

        $tokenPayload = [

            'id' => $usuario->id,

            'email' => $usuario->email,

            'rol' => $usuario->rol,

            'iat' => time(),

            'exp' => time() + (App::$jwtExpiry ?? 3600)

        ];



        $jwt = self::generateJWT($tokenPayload, App::$jwtSecret ?? 'clave_segura_minimo_32_chars');


        // Guardar en sesión y liberar el bloqueo inmediatamente para peticiones concurrentes
        $_SESSION['jwt_payload'] = $tokenPayload;
        session_write_close();

        // Registrar acceso exitoso (Ley 29733)

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

        \GemMotors\Services\NotificacionService::registrarAccesoExitoso($usuario->id, $ipAddress);



        // Devolver token y datos del usuario

        App::jsonResponse(true, [

            'token' => $jwt,

            'usuario' => [

                'id' => $usuario->id,

                'nombre' => $usuario->nombre,

                'apellido' => $usuario->apellido,

                'email' => $usuario->email,

                'rol' => $usuario->rol

            ]

        ], 'Registro exitoso');

    }



    /**

     * Obtener perfil del usuario autenticado

     * GET /api/auth/perfil

     */

    public static function perfil(): void

    {

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

            App::jsonResponse(false, null, 'Método no permitido', 405);

            return;

        }



        AuthMiddleware::requireAuth();

        RolMiddleware::checkRole();



        $payload = $_SESSION['jwt_payload'] ?? null;

        if ($payload === null) {

            App::jsonResponse(false, null, 'Token inválido', 401);

            return;

        }



        $usuario = Usuario::find($payload['id']);

        if ($usuario === null) {

            App::jsonResponse(false, null, 'Usuario no encontrado', 404);

            return;

        }



        App::jsonResponse(true, [

            'id' => $usuario->id,

            'nombre' => $usuario->nombre,

            'apellido' => $usuario->apellido,

            'email' => $usuario->email,

            'rol' => $usuario->rol,

            'activo' => $usuario->activo

        ], 'Perfil obtenido');

    }



    /**
     * Cambiar la contraseña forzada o de forma manual
     * PUT /api/auth/cambiar-password
     */
    public static function cambiarPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Validar token temporal
        AuthMiddleware::requireAuth();

        $payload = $_SESSION['jwt_payload'] ?? null;
        if ($payload === null) {
            App::jsonResponse(false, null, 'Token inválido', 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $passwordActual = $input['password_actual'] ?? '';
        $nuevoPassword = $input['password_nuevo'] ?? '';

        if (empty($passwordActual) || empty($nuevoPassword)) {
            App::jsonResponse(false, null, 'Ambas contraseñas son requeridas', 400);
            return;
        }

        $usuario = Usuario::find($payload['id']);
        if ($usuario === null) {
            App::jsonResponse(false, null, 'Usuario no encontrado', 404);
            return;
        }

        if (!password_verify($passwordActual, $usuario->password_hash)) {
            App::jsonResponse(false, null, 'Contraseña actual incorrecta', 401);
            return;
        }

        $newHash = password_hash($nuevoPassword, PASSWORD_DEFAULT);
        $usuario->changePassword($newHash);

        // Generar nuevo token completo sin el flag
        $tokenPayload = [
            'id' => $usuario->id,
            'email' => $usuario->email,
            'rol' => $usuario->rol,
            'iat' => time(),
            'exp' => time() + (App::$jwtExpiry ?? 3600)
        ];
        
        $jwt = self::generateJWT($tokenPayload, App::$jwtSecret ?? 'clave_segura_minimo_32_chars');
        
        $_SESSION['jwt_payload'] = $tokenPayload;
        session_write_close();

        App::jsonResponse(true, [
            'token' => $jwt,
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->rol
            ]
        ], 'Contraseña actualizada exitosamente');
    }

    /**

     * Genera un token JWT

     */

    private static function generateJWT(array $payload, string $secret): string

    {

        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);

        $payloadJson = json_encode($payload);



        $headerB64 = self::base64UrlEncode($header);

        $payloadB64 = self::base64UrlEncode($payloadJson);



        $signature = hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $secret, true);

        $signatureB64 = self::base64UrlEncode($signature);



        return $headerB64 . '.' . $payloadB64 . '.' . $signatureB64;

    }



    /**

     * Codifica en Base64 URL safe

     */

    private static function base64UrlEncode(string $input): string

    {

        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');

    }



    /**

     * Genera un código de seguimiento único para clientes

     */

    private static function generarCodigoSeguimiento(): string

    {

        $timestamp = substr((string)time(), -6);

        $random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);

        return 'GEM' . $timestamp . $random;

    }

} 

