<?php
declare(strict_types=1);

namespace GemMotors\Services;

use GemMotors\Config\App;

/**
 * Servicio de notificaciones para alertas de stock bajo y otros eventos
 * En un sistema real, esto podría integrarse con correo, SMS, o push notifications
 */
class NotificacionService
{
    /**
     * Envía una notificación de stock bajo al administrador
     * @param string $nombreRepuesto Nombre del repuesto con stock bajo
     * @param int $stockActual Stock actual del repuesto
     * @param int $stockMinimo Stock mínimo configurado
     * @return boolean True si la notificación se envió correctamente
     */
    public static function notificarStockBajo(string $nombreRepuesto, int $stockActual, int $stockMinimo): bool
    {
        $mensaje = "ALERTA DE STOCK BAJO: El repuesto '{$nombreRepuesto}' tiene un stock de {$stockActual} unidades, " .
                  "que está por debajo o igual al mínimo permitido de {$stockMinimo} unidades.";
        
        // Simular envío de correo electrónico al administrador
        try {
            // Simulamos la llamada a una función de envío de correo (ej. mail() o PHPMailer)
            $to = "admin@gem-motors.com";
            $subject = "Alerta de Stock Crítico: {$nombreRepuesto}";
            
            // Registramos el éxito del envío simulado
            error_log("[" . date('Y-m-d H:i:s') . "] EMAIL ENVIADO a {$to}: {$subject} - {$mensaje}");
            
        } catch (\Exception $e) {
            // En caso de fallo en el envío, registramos el error pero no detenemos el proceso (Flujo Alternativo 5a)
            error_log("[" . date('Y-m-d H:i:s') . "] ERROR ENVIO EMAIL: " . $e->getMessage());
        }
        
        // También registramos la alerta en el log general del sistema
        error_log("[" . date('Y-m-d H:i:s') . "] NOTIFICACION: {$mensaje}");
        
        return true;
    }

    /**
     * Envía una notificación cuando se asigna un mecánico a una orden de trabajo
     * @param int $ordenId ID de la orden de trabajo
     * @param int $mecanicoId ID del mecánico asignado
     * @return boolean True si la notificación se envió correctamente
     */
    public static function notificarAsignacionMecanico(int $ordenId, int $mecanicoId): bool
    {
        $orden = \GemMotors\Models\OrdenTrabajo::find($ordenId);
        $mecanico = \GemMotors\Models\Usuario::find($mecanicoId);
        
        if ($orden === null || $mecanico === null) {
            return false;
        }
        
        $mensaje = "NOTIFICACIÓN: El mecánico {$mecanico->nombre} {$mecanico->apellido} ha sido asignado a la " .
                  "orden de trabajo {$orden->numero_ot}.";
        
        error_log("[" . date('Y-m-d H:i:s') . "] NOTIFICACION: {$mensaje}");
        
        return true;
    }

    /**
     * Envía una notificación cuando se cambia el estado de una orden de trabajo
     * @param int $ordenId ID de la orden de trabajo
     * @param string $nuevoEstado Nuevo estado de la orden
     * @return boolean True si la notificación se envió correctamente
     */
    public static function notificarCambioEstado(int $ordenId, string $nuevoEstado): bool
    {
        $orden = \GemMotors\Models\OrdenTrabajo::find($ordenId);
        
        if ($orden === null) {
            return false;
        }
        
        $mensaje = "NOTIFICACIÓN: La orden de trabajo {$orden->numero_ot} ha cambiado a estado: {$nuevoEstado}.";
        
        error_log("[" . date('Y-m-d H:i:s') . "] NOTIFICACION: {$mensaje}");
        
        // En un sistema real, aquí también enviaríamos una notificación al cliente
        // si el sistema lo configuró así
        
        return true;
    }

    /**
     * Envía una notificación cuando se aprueba o rechaza un presupuesto
     * @param int $presupuestoId ID del presupuesto
     * @param string $estado Nuevo estado ('aprobado' o 'rechazado')
     * @param string|null $motivo Motivo de rechazo (si aplica)
     * @return boolean True si la notificación se envió correctamente
     */
    public static function notificarRespuestaPresupuesto(int $presupuestoId, string $estado, ?string $motivo = null): bool
    {
        $presupuesto = \GemMotors\Models\Presupuesto::find($presupuestoId);
        
        if ($presupuesto === null) {
            return false;
        }
        
        $orden = $presupuesto->getOrdenTrabajo();
        if ($orden === null) {
            return false;
        }
        
        if ($estado === 'aprobado') {
            $mensaje = "NOTIFICACIÓN: El presupuesto para la orden de trabajo {$orden->numero_ot} ha sido APROBADO.";
        } else {
            $mensaje = "NOTIFICACIÓN: El presupuesto para la orden de trabajo {$orden->numero_ot} ha sido RECHAZADO.";
            if ($motivo !== null) {
                $mensaje .= " Motivo: {$motivo}";
            }
        }
        
        error_log("[" . date('Y-m-d H:i:s') . "] NOTIFICACION: {$mensaje}");
        
        return true;
    }

    /**
     * Registra un intento fallido de login (para cumplimiento de Ley 29733)
     * @param string $email Email utilizado en el intento de login
     * @param string $ipAddress Dirección IP del intento
     * @return boolean True si se registró correctamente
     */
    public static function registrarIntentoFallidoLogin(string $email, string $ipAddress): bool
    {
        $mensaje = "INTENTO FALLIDO DE LOGIN: Email='{$email}', IP='{$ipAddress}'";
        
        error_log("[" . date('Y-m-d H:i:s') . "] SEGURIDAD: {$mensaje}");
        
        // En un sistema real, guardaríamos esto en una tabla de auditoría de seguridad
        // Para detectar ataques de fuerza bruta, etc.
        
        return true;
    }

    /**
     * Registra un acceso exitoso al sistema (para cumplimiento de Ley 29733)
     * @param int $usuarioId ID del usuario que accedió
     * @param string $ipAddress Dirección IP del acceso
     * @return boolean True si se registró correctamente
     */
    public static function registrarAccesoExitoso(int $usuarioId, string $ipAddress): bool
    {
        $usuario = \GemMotors\Models\Usuario::find($usuarioId);
        
        if ($usuario === null) {
            return false;
        }
        
        $mensaje = "ACCESO EXITOSO: Usuario='{$usuario->email}', Rol='{$usuario->rol}', IP='{$ipAddress}'";
        
        error_log("[" . date('Y-m-d H:i:s') . "] AUDITORIA: {$mensaje}");
        
        return true;
    }

    /**
     * Simula el envío de correo electrónico con credenciales temporales
     * @param string $email Email del nuevo usuario
     * @param string $passwordTemporal Contraseña generada automáticamente
     * @return boolean True si se simuló correctamente
     */
    public static function notificarNuevoUsuario(string $email, string $passwordTemporal): bool
    {
        $mensaje = "NUEVO USUARIO CREADO. Sus credenciales temporales son: Email='{$email}', Contraseña='{$passwordTemporal}'. Deberá cambiar su contraseña al iniciar sesión.";
        
        // Simular envío de correo electrónico
        try {
            $to = $email;
            $subject = "Bienvenido a G&E Motors - Credenciales de acceso";
            
            // Registramos el éxito del envío simulado
            error_log("[" . date('Y-m-d H:i:s') . "] EMAIL ENVIADO a {$to}: {$subject} - {$mensaje}");
        } catch (\Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] ERROR ENVIO EMAIL: " . $e->getMessage());
            return false;
        }
        
        return true;
    }

    /**
     * Simula el envío de un PDF por correo electrónico al cliente
     * @param int $ordenId ID de la orden
     * @param string $tipoPdf Tipo de reporte (diagnostico, hoja_ruta, boleta_servicio)
     * @return boolean True si se simuló correctamente
     */
    public static function enviarPdfCliente(int $ordenId, string $tipoPdf): bool
    {
        $orden = \GemMotors\Models\OrdenTrabajo::find($ordenId);
        if ($orden === null) {
            return false;
        }

        $cliente = $orden->getCliente();
        if ($cliente === null || empty($cliente->email)) {
            // Si el cliente no tiene email, no se puede enviar
            error_log("[" . date('Y-m-d H:i:s') . "] ERROR ENVIO EMAIL: El cliente no tiene correo electrónico configurado.");
            return false;
        }

        $nombres = [
            'diagnostico' => 'Diagnóstico Automotriz (OBD-II)',
            'hoja_ruta' => 'Hoja de Ruta',
            'boleta_servicio' => 'Boleta de Servicio Electrónica'
        ];
        $nombreReporte = $nombres[$tipoPdf] ?? 'Reporte';

        $mensaje = "Se adjunta el documento '{$nombreReporte}' correspondiente a la orden de trabajo {$orden->numero_ot}.";
        
        // Simular envío de correo electrónico con adjunto
        try {
            $to = $cliente->email;
            $subject = "G&E Motors - Su {$nombreReporte}";
            
            // Registramos el éxito del envío simulado
            error_log("[" . date('Y-m-d H:i:s') . "] EMAIL ENVIADO a {$to}: {$subject} - {$mensaje} (PDF Adjunto: {$tipoPdf}_{$orden->numero_ot}.pdf)");
        } catch (\Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] ERROR ENVIO EMAIL: " . $e->getMessage());
            return false;
        }
        
        return true;
    }
}