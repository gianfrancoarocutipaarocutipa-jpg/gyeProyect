<?php
declare(strict_types=1);

namespace GemMotors\Services;

use GemMotors\Config\App;
use Dompdf\Dompdf;
use Dompdf\Options;

class PDFService
{
    /**
     * Inicializa Dompdf con opciones predeterminadas
     */
    private static function initDompdf(): Dompdf
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        return $dompdf;
    }

    /**
     * Genera un PDF a partir de una vista HTML
     */
    private static function renderView(string $viewPath, array $data): string
    {
        extract($data);
        ob_start();
        require __DIR__ . '/../Views/pdf/' . $viewPath;
        return ob_get_clean();
    }

    /**
     * Genera un PDF de diagnóstico para una orden de trabajo
     * @param int $ordenId ID de la orden de trabajo
     * @return string Contenido del PDF generado (raw bytes)
     */
    public static function generarDiagnostico(int $ordenId): string
    {
        $orden = \GemMotors\Models\OrdenTrabajo::find($ordenId);
        if ($orden === null) {
            throw new \InvalidArgumentException('Orden de trabajo no encontrada');
        }
        
        $dompdf = self::initDompdf();
        $html = self::renderView('diagnostico.php', [
            'orden' => $orden,
            'cliente' => $orden->getCliente(),
            'vehiculo' => $orden->getVehiculo(),
            'diagnosticos' => $orden->getDiagnosticos()
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Retornar base64 para mantener la compatibilidad con el frontend actual
        return base64_encode($dompdf->output());
    }

    /**
     * Genera un PDF de hoja de ruta para una orden de trabajo
     */
    public static function generarHojaDeRuta(int $ordenId): string
    {
        $orden = \GemMotors\Models\OrdenTrabajo::find($ordenId);
        if ($orden === null) {
            throw new \InvalidArgumentException('Orden de trabajo no encontrada');
        }
        
        $dompdf = self::initDompdf();
        $html = self::renderView('hoja_ruta.php', [
            'orden' => $orden,
            'vehiculo' => $orden->getVehiculo(),
            'mecanico' => $orden->getMecanico(),
            'repuestos' => $orden->getRepuestosAsignados()
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return base64_encode($dompdf->output());
    }

    /**
     * Genera un PDF de boleta de servicio para una orden de trabajo
     */
    public static function generarBoletaDeServicio(int $ordenId): string
    {
        $orden = \GemMotors\Models\OrdenTrabajo::find($ordenId);
        if ($orden === null) {
            throw new \InvalidArgumentException('Orden de trabajo no encontrada');
        }
        
        $mecanicos = $orden->getMecanicosAsignados();
        $costoManoObra = 0.0;
        foreach ($mecanicos as $m) {
            $costoManoObra += ($m['horas_trabajadas'] * 25.0); // Tarifa base por hora
        }
        if ($costoManoObra == 0.0) {
            $costoManoObra = 50.0; // Tarifa mínima si no hay horas
        }
        
        $totalRepuestos = $orden->getTotalRepuestos();
        $total = $totalRepuestos + $costoManoObra;

        $dompdf = self::initDompdf();
        $html = self::renderView('boleta_servicio.php', [
            'orden' => $orden,
            'cliente' => $orden->getCliente(),
            'vehiculo' => $orden->getVehiculo(),
            'repuestos' => $orden->getRepuestosAsignados(),
            'costoManoObra' => $costoManoObra,
            'total' => $total
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return base64_encode($dompdf->output());
    }

    /**
     * Genera un PDF del inventario de repuestos
     */
    public static function generarInventarioPdf(array $repuestos): string
    {
        $dompdf = self::initDompdf();
        $html = self::renderView('inventario_pdf.php', [
            'repuestos' => $repuestos,
            'fecha' => date('d/m/Y H:i')
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape'); // Mejor landscape para tablas anchas
        $dompdf->render();
        
        return base64_encode($dompdf->output());
    }
}