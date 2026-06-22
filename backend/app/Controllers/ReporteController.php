<?php
declare(strict_types=1);

namespace GemMotors\Controllers;

use GemMotors\Middleware\AuthMiddleware;
use GemMotors\Middleware\RolMiddleware;
use GemMotors\Config\App;
use GemMotors\Models\Usuario;
use GemMotors\Models\Cliente;
use GemMotors\Models\Vehiculo;
use GemMotors\Models\OrdenTrabajo;
use GemMotors\Models\Diagnostico;
use GemMotors\Models\Repuesto;
use GemMotors\Models\Presupuesto;
use GemMotors\Models\MecanicoOT;

class ReporteController
{
    /**
     * Obtener reporte de ingresos por período
     * GET /api/reportes/ingresos?desde={}&hasta={}
     */
    public static function getIngresos(): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos (solo admin)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $desde = $_GET['desde'] ?? date('Y-m-01'); // Primer día del mes actual por defecto
        $hasta = $_GET['hasta'] ?? date('Y-m-d'); // Hoy por defecto

        // Validar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            App::jsonResponse(false, null, 'Formato de fecha inválido. Use YYYY-MM-DD', 400);
            return;
        }

        $db = \GemMotors\Config\Database::getInstance();

        // Sumar totales de presupuestos aprobados en el período
        $stmt = $db->prepare('
            SELECT COALESCE(SUM(p.total), 0) as total_ingresos
            FROM presupuestos p
            JOIN ordenes_trabajo o ON p.orden_id = o.id
            WHERE p.estado = \'aprobado\'
            AND p.fecha_respuesta BETWEEN :desde AND :hasta
        ');
        $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
        $totalIngresos = (float)$stmt->fetchColumn();

        // También podríamos desglosar por tipo de servicio, etc.
        // Pero por ahora devolvemos el total

        App::jsonResponse(true, [
            'desde' => $desde,
            'hasta' => $hasta,
            'total_ingresos' => $totalIngresos,
            'moneda' => App::CURRENCY
        ], 'Reporte de ingresos obtenido');
    }

    /**
     * Obtener reporte de productividad de mecánicos por período
     * GET /api/reportes/productividad?desde={}&hasta={}
     */
    public static function getProductividad(): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos (solo admin)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $desde = $_GET['desde'] ?? date('Y-m-01'); // Primer día del mes actual por defecto
        $hasta = $_GET['hasta'] ?? date('Y-m-d'); // Hoy por defecto

        // Validar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            App::jsonResponse(false, null, 'Formato de fecha inválido. Use YYYY-MM-DD', 400);
            return;
        }

        $db = \GemMotors\Config\Database::getInstance();

        // Obtener productividad por mecánico (horas trabajadas y órdenes completadas)
        $stmt = $db->prepare('
            SELECT 
                u.id,
                u.nombre,
                u.apellido,
                COUNT(DISTINCT mo.orden_id) as ordenes_atendidas,
                COALESCE(SUM(mo.horas_trabajadas), 0) as horas_trabajadas
            FROM mecanico_ot mo
            JOIN usuarios u ON mo.mecanico_id = u.id
            JOIN ordenes_trabajo o ON mo.orden_id = o.id
            WHERE o.estado = \'entregado\'
            AND o.fecha_cierre BETWEEN :desde AND :hasta
            GROUP BY u.id, u.nombre, u.apellido
            ORDER BY horas_trabajadas DESC
        ');
        $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
        $productividad = $stmt->fetchAll();

        App::jsonResponse(true, [
            'desde' => $desde,
            'hasta' => $hasta,
            'productividad' => $productividad
        ], 'Reporte de productividad obtenido');
    }

    /**
     * Obtener reporte de rotación de repuestos
     * GET /api/reportes/rotacion-repuestos
     */
    public static function getRotacionRepuestos(): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos (solo admin)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $db = \GemMotors\Config\Database::getInstance();

        // Obtener repuestos más utilizados (basado en asignaciones)
        $stmt = $db->query('
            SELECT 
                r.id,
                r.codigo_oem,
                r.nombre,
                r.marca_fabricante,
                COUNT(ar.id) as veces_asignado,
                SUM(ar.cantidad) as total_unidades,
                r.stock as stock_actual,
                r.stock_minimo as stock_minimo
            FROM asignacion_repuesto ar
            JOIN repuestos r ON ar.repuesto_id = r.id
            GROUP BY r.id, r.codigo_oem, r.nombre, r.marca_fabricante, r.stock, r.stock_minimo
            ORDER BY veces_asignado DESC, total_unidades DESC
        ');
        $rotacion = $stmt->fetchAll();

        App::jsonResponse(true, [
            'rotacion_repuestos' => $rotacion
        ], 'Reporte de rotación de repuestos obtenido');
    }

    /**
     * Obtener reporte de tiempo promedio de reparación
     * GET /api/reportes/tiempo-promedio
     */
    public static function getTiempoPromedio(): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos (solo admin)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $db = \GemMotors\Config\Database::getInstance();

        // Calcular tiempo promedio entre creación y cierre de órdenes entregadas
        $stmt = $db->prepare('
            SELECT 
                AVG(EXTRACT(EPOCH FROM (fecha_cierre - created_at)) / 3600) as horas_promedio,
                COUNT(*) as total_ordenes
            FROM ordenes_trabajo
            WHERE estado = \'entregado\'
            AND fecha_cierre IS NOT NULL
        ');
        $result = $stmt->execute();
        $tiempoPromedio = $stmt->fetch();

        App::jsonResponse(true, [
            'horas_promedio' => round((float)$tiempoPromedio['horas_promedio'] ?? 0, 2),
            'total_ordenes' => (int)$tiempoPromedio['total_ordenes'],
            'descripcion' => 'Tiempo promedio en horas para completar órdenes entregadas'
        ], 'Reporte de tiempo promedio obtenido');
    }

    /**
     * Generar PDF de reporte (diagnóstico, hoja de ruta o boleta)
     * GET /api/reportes/pdf/orden/{id}?tipo={diagnostico|hoja_ruta|boleta}
     */
    public static function generarPdf(int $id, string $tipo): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Validar tipo
        $tiposPermitidos = ['diagnostico', 'hoja_ruta', 'boleta'];
        if (!in_array($tipo, $tiposPermitidos, true)) {
            App::jsonResponse(false, null, 'Tipo inválido. Use: diagnostico, hoja_ruta o boleta', 400);
            return;
        }

        // Verificar que la orden existe
        $orden = \GemMotors\Models\OrdenTrabajo::find($id);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        try {
            // Generar PDF según el tipo
            switch ($tipo) {
                case 'diagnostico':
                    $pdfContent = \GemMotors\Services\PDFService::generarDiagnostico($id);
                    break;
                case 'hoja_ruta':
                    $pdfContent = \GemMotors\Services\PDFService::generarHojaDeRuta($id);
                    break;
                case 'boleta':
                    $pdfContent = \GemMotors\Services\PDFService::generarBoletaDeServicio($id);
                    break;
                default:
                    App::jsonResponse(false, null, 'Tipo no soportado', 400);
                    return;
            }

            // Devolver PDF como descarga
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . $orden->numero_ot . '.pdf"');
            echo base64_decode($pdfContent);
            exit;
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * Generar y enviar PDF de reporte por correo
     * POST /api/reportes/pdf/orden/{id}/enviar?tipo={diagnostico|hoja_ruta|boleta}
     */
    public static function enviarPdfCorreo(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $tipo = $_GET['tipo'] ?? '';

        $tiposPermitidos = ['diagnostico', 'hoja_ruta', 'boleta'];
        if (!in_array($tipo, $tiposPermitidos, true)) {
            App::jsonResponse(false, null, 'Tipo inválido. Use: diagnostico, hoja_ruta o boleta', 400);
            return;
        }

        $orden = \GemMotors\Models\OrdenTrabajo::find($id);
        if ($orden === null) {
            App::jsonResponse(false, null, 'Orden de trabajo no encontrada', 404);
            return;
        }

        try {
            // Simulamos generar el PDF primero (para validar que funciona)
            switch ($tipo) {
                case 'diagnostico':
                    $pdfContent = \GemMotors\Services\PDFService::generarDiagnostico($id);
                    $tipoPdfNombre = 'diagnostico';
                    break;
                case 'hoja_ruta':
                    $pdfContent = \GemMotors\Services\PDFService::generarHojaDeRuta($id);
                    $tipoPdfNombre = 'hoja_ruta';
                    break;
                case 'boleta':
                    $pdfContent = \GemMotors\Services\PDFService::generarBoletaDeServicio($id);
                    $tipoPdfNombre = 'boleta_servicio';
                    break;
                default:
                    App::jsonResponse(false, null, 'Tipo no soportado', 400);
                    return;
            }

            // Llamar al servicio para enviar por correo
            $enviado = \GemMotors\Services\NotificacionService::enviarPdfCliente($id, $tipoPdfNombre);

            if ($enviado) {
                App::jsonResponse(true, null, 'PDF generado y enviado por correo exitosamente');
            } else {
                App::jsonResponse(false, null, 'El PDF fue generado pero hubo un problema al enviarlo por correo', 500);
            }
        } catch (\Exception $e) {
            App::jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * Exportar inventario a Excel
     * GET /api/reportes/excel/inventario
     */
    public static function exportarExcelInventario(): void
    {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        // Verificar autenticación y permisos (solo admin)
        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        // Para simplificar, vamos a devolver un CSV en lugar de Excel real
        // En un sistema real, usaríamos una biblioteca como PhpSpreadsheet
        
        $db = \GemMotors\Config\Database::getInstance();
        $stmt = $db->query('
            SELECT 
                codigo_oem,
                nombre,
                descripcion,
                categoria,
                marca_fabricante,
                stock,
                stock_minimo,
                precio_unitario
            FROM repuestos
            ORDER BY nombre
        ');
        $repuestos = $stmt->fetchAll();

        // Generar CSV (Compatible con Excel)
        $output = fopen('php://output', 'w');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=inventario_gem_motors.csv');
        
        // Agregar BOM UTF-8 para que Excel reconozca los acentos y caracteres especiales
        fputs($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados usando punto y coma
        fputcsv($output, ['Código OEM', 'Nombre', 'Descripción', 'Categoría', 'Marca Fabricante', 'Stock', 'Stock Mínimo', 'Precio Unitario'], ';');
        
        // Datos usando punto y coma
        foreach ($repuestos as $repuesto) {
            fputcsv($output, [
                $repuesto['codigo_oem'],
                $repuesto['nombre'],
                $repuesto['descripcion'] ?? '',
                $repuesto['categoria'] ?? '',
                $repuesto['marca_fabricante'] ?? '',
                $repuesto['stock'],
                $repuesto['stock_minimo'],
                number_format((float)$repuesto['precio_unitario'], 2)
            ], ';');
        }
        
        fclose($output);
        exit;
    }

    /**
     * Exportar inventario a PDF
     * GET /api/reportes/pdf/inventario
     */
    public static function exportarPdfInventario(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            App::jsonResponse(false, null, 'Método no permitido', 405);
            return;
        }

        AuthMiddleware::requireAuth();
        RolMiddleware::checkRole();

        $db = \GemMotors\Config\Database::getInstance();
        $stmt = $db->query('
            SELECT * FROM repuestos ORDER BY nombre
        ');
        $repuestos = $stmt->fetchAll();

        try {
            $pdfContent = \GemMotors\Services\PDFService::generarInventarioPdf($repuestos);
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="inventario_gem_motors.pdf"');
            echo base64_decode($pdfContent);
            exit;
        } catch (\Exception $e) {
            App::jsonResponse(false, null, 'Error al generar PDF: ' . $e->getMessage(), 500);
        }
    }
}