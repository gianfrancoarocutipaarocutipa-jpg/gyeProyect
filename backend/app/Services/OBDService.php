<?php
declare(strict_types=1);

namespace GemMotors\Services;

class OBDService
{
    /**
     * Catálogo de códigos DTC (al menos 50 códigos P0xxx, B0xxx, C0xxx, U0xxx)
     * En un sistema real, esto vendría de una base de datos o archivo externo
     */
    public array $catalogoDTC = [
        // P0xxx - Powertrain
        'P0000' => ['descripcion' => 'Fallos aleatorios', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0100' => ['descripcion' => 'Circuito del Sensor de Volumen/Aire de Admisión', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0101' => ['descripcion' => 'Rango/Rendimiento del Sensor de Volumen/Aire de Admisión', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0102' => ['descripcion' => 'Entrada Baja del Sensor de Volumen/Aire de Admisión', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0103' => ['descripcion' => 'Entrada Alta del Sensor de Volumen/Aire de Admisión', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0104' => ['descripcion' => 'Intermitente del Sensor de Volumen/Aire de Admisión', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0110' => ['descripcion' => 'Circuito del Sensor de Temperatura de Admisión', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0111' => ['descripcion' => 'Rango/Rendimiento del Sensor de Temperatura de Admisión', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0112' => ['descripcion' => 'Entrada Baja del Sensor de Temperatura de Admisión', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0113' => ['descripcion' => 'Entrada Alta del Sensor de Temperatura de Admisión', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0114' => ['descripcion' => 'Intermitente del Sensor de Temperatura de Admisión', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0120' => ['descripcion' => 'Circuito del Sensor/Interruptor de Posición del Acelerador', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0121' => ['descripcion' => 'Rango/Rendimiento del Sensor/Interruptor de Posición del Acelerador', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0122' => ['descripcion' => 'Entrada Baja del Sensor/Interruptor de Posición del Acelerador', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0123' => ['descripcion' => 'Entrada Alta del Sensor/Interruptor de Posición del Acelerador', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0124' => ['descripcion' => 'Intermitente del Sensor/Interruptor de Posición del Acelerador', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0130' => ['descripcion' => 'Circuito del Sensor de Oxígeno (Banco 1, Sensor 1)', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0131' => ['descripcion' => 'Entrada Baja del Sensor de Oxígeno (Banco 1, Sensor 1)', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0132' => ['descripcion' => 'Entrada Alta del Sensor de Oxígeno (Banco 1, Sensor 1)', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0133' => ['descripcion' => 'Respuesta Lenta del Sensor de Oxígeno (Banco 1, Sensor 1)', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0134' => ['descripcion' => 'Sin Actividad del Sensor de Oxígeno (Banco 1, Sensor 1)', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0135' => ['descripcion' => 'Fallos en el Calentador del Sensor de Oxígeno (Banco 1, Sensor 1)', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0171' => ['descripcion' => 'Sistema demasiado pobre (Banco 1)', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0172' => ['descripcion' => 'Sistema demasiado rico (Banco 1)', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0174' => ['descripcion' => 'Sistema demasiado pobre (Banco 2)', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0175' => ['descripcion' => 'Sistema demasiado rico (Banco 2)', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0200' => ['descripcion' => 'Fallos en los Inyectores de Combustible', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0201' => ['descripcion' => 'Circuito del Inyector de Combustible - Cilindro 1', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0202' => ['descripcion' => 'Circuito del Inyector de Combustible - Cilindro 2', 'tipo' => 'P', 'sistema' => 'Combustible'],
        'P0300' => ['descripcion' => 'Fallos Aleatorios/Múltiples en los Cilindros Detectados', 'tipo' => 'P', 'sistema' => 'Encendido'],
        'P0301' => ['descripcion' => 'Fallo en el Cilindro 1', 'tipo' => 'P', 'sistema' => 'Encendido'],
        'P0302' => ['descripcion' => 'Fallo en el Cilindro 2', 'tipo' => 'P', 'sistema' => 'Encendido'],
        'P0303' => ['descripcion' => 'Fallo en el Cilindro 3', 'tipo' => 'P', 'sistema' => 'Encendido'],
        'P0304' => ['descripcion' => 'Fallo en el Cilindro 4', 'tipo' => 'P', 'sistema' => 'Encendido'],
        'P0400' => ['descripcion' => 'Flujo en el Sistema de Recirculación de Gases de Escape', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0401' => ['descripcion' => 'Flujo Insuficiente en el Sistema de Recirculación de Gases de Escape', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0420' => ['descripcion' => 'Eficiencia por debajo del umbral del Catalizador (Banco 1)', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0440' => ['descripcion' => 'Fallos en el Sistema de Control de Emisiones Evaporativas', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0442' => ['descripcion' => 'Fuga Pequeña Detectada en el Sistema de Control de Emisiones Evaporativas', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0455' => ['descripcion' => 'Fuga Grande Detectada en el Sistema de Control de Emisiones Evaporativas', 'tipo' => 'P', 'sistema' => 'Control de Emisiones'],
        'P0500' => ['descripcion' => 'Fallos en el Sensor de Velocidad del Vehículo', 'tipo' => 'P', 'sistema' => 'Control de Velocidad en Rutina'],
        'P0505' => ['descripcion' => 'Fallos en la Unidad de Control de Ralentí', 'tipo' => 'P', 'sistema' => 'Control de Velocidad en Rutina'],
        'P0562' => ['descripcion' => 'Voltaje del Sistema Bajo', 'tipo' => 'P', 'sistema' => 'Eléctrico'],
        'P0563' => ['descripcion' => 'Voltaje del Sistema Alto', 'tipo' => 'P', 'sistema' => 'Eléctrico'],
        'P0600' => ['descripcion' => 'Fallos en la Comunicación Serial de Control de Transmisión', 'tipo' => 'P', 'sistema' => 'Computadora de Transmisión'],
        'P0603' => ['descripcion' => 'Error de Memoria Keep Alive (KAM)', 'tipo' => 'P', 'sistema' => 'Computadora de Transmisión'],
        
        // B0xxx - Cuerpo
        'B0000' => ['descripcion' => 'Fallos Aleatorios en el Sistema de Carrocería', 'tipo' => 'B', 'sistema' => 'Carrocería'],
        'B0010' => ['descripcion' => 'Fallos en el Circuito de la Lámpara de Interior', 'tipo' => 'B', 'sistema' => 'Iluminación'],
        'B0020' => ['descripcion' => 'Fallos en el Circuito del Lavaparabrisas Delantero', 'tipo' => 'B', 'sistema' => 'Limpieza'],
        'B0030' => ['descripcion' => 'Fallos en el Circuito del Desembopador Trasero', 'tipo' => 'B', 'sistema' => 'Desembopado'],
        'B0040' => ['descripcion' => 'Fallos en el Circuito del Bloqueo Central de Puertas', 'tipo' => 'B', 'sistema' => 'Acceso'],
        'B0050' => ['descripcion' => 'Fallos en el Circuito del Control de Asientos Eléctricos', 'tipo' => 'B', 'sistema' => 'Asientos'],
        'B0060' => ['descripcion' => 'Fallos en el Circuito del Climatizador', 'tipo' => 'B', 'sistema' => 'Climatización'],
        'B0070' => ['descripcion' => 'Fallos en el Circuito del Sistema de Audio', 'tipo' => 'B', 'sistema' => 'Entretenimiento'],
        'B0080' => ['descripcion' => 'Fallos en el Circuito del Sistema de Navegación', 'tipo' => 'B', 'sistema' => 'Navegación'],
        'B0090' => ['descripcion' => 'Fallos en el Circuito del Sistema de Telemática', 'tipo' => 'B', 'sistema' => 'Telemática'],
        
        // C0xxx - Chasis
        'C0000' => ['descripcion' => 'Fallos Aleatorios en el Sistema de Chasis', 'tipo' => 'C', 'sistema' => 'Chasis'],
        'C0010' => ['descripcion' => 'Fallos en el Sistema de Frenos Antibloqueo (ABS)', 'tipo' => 'C', 'sistema' => 'Frenos'],
        'C0020' => ['descripcion' => 'Fallos en el Sistema de Control de Estabilidad Electrónica (ESC)', 'tipo' => 'C', 'sistema' => 'Estabilidad'],
        'C0030' => ['descripcion' => 'Fallos en el Sistema de Control de Tracción (TCS)', 'tipo' => 'C', 'sistema' => 'Tracción'],
        'C0040' => ['descripcion' => 'Fallos en el Sistema de Soporte de Estacionamiento', 'tipo' => 'C', 'sistema' => 'Estacionamiento'],
        'C0050' => ['descripcion' => 'Fallos en el Sistema de Iluminación Exterior', 'tipo' => 'C', 'sistema' => 'Iluminación'],
        'C0060' => ['descripcion' => 'Fallos en el Sistema de Parabrisas y Limpiaparabrisas', 'tipo' => 'C', 'sistema' => 'Visibilidad'],
        'C0070' => ['descripcion' => 'Fallos en el Sistema de Neumáticos (Presión, Desgaste)', 'tipo' => 'C', 'sistema' => 'Neumáticos'],
        'C0080' => ['descripcion' => 'Fallos en el Sistema de Dirección Asistida', 'tipo' => 'C', 'sistema' => 'Dirección'],
        'C0090' => ['descripcion' => 'Fallos en el Sistema de Suspensión', 'tipo' => 'C', 'sistema' => 'Suspensión'],
        
        // U0xxx - Red de Comunicación
        'U0000' => ['descripcion' => 'Fallos Aleatorios en la Red de Comunicación', 'tipo' => 'U', 'sistema' => 'Comunicación'],
        'U0010' => ['descripcion' => 'Fallos en el Bus CAN de Alta Velocidad', 'tipo' => 'U', 'sistema' => 'Comunicación'],
        'U0020' => ['descripcion' => 'Fallos en el Bus CAN de Baja Velocidad', 'tipo' => 'U', 'sistema' => 'Comunicación'],
        'U0030' => ['descripcion' => 'Fallos en el Bus de Comunicación de Línea Simple', 'tipo' => 'U', 'sistema' => 'Comunicación'],
        'U0040' => ['descripcion' => 'Fallos en el Bus de Comunicación de Punto a Punto', 'tipo' => 'U', 'sistema' => 'Comunicación'],
        'U0050' => ['descripcion' => 'Fallos en el Bus de Comunicación de Anillo', 'tipo' => 'U', 'sistema' => 'Comunicación'],
        'U0060' => ['descripcion' => 'Fallos en el Bus de Comunicación de Múltiples Maestros', 'tipo' => 'U', 'sistema' => 'Comunicación'],
        'U0070' => ['descripcion' => 'Fallos en el Bus de Comunicación de Maestros Múltiples', 'tipo' => 'U', 'sistema' => 'Comunicación'],
        'U0080' => ['descripcion' => 'Fallos en el Bus de Comunicación de Esclavos Múltiples', 'tipo' => 'U', 'sistema' => 'Comunicación'],
        'U0090' => ['descripcion' => 'Fallos en el Bus de Comunicación de Red Híbrida', 'tipo' => 'U', 'sistema' => 'Comunicación']
    ];

    /**
     * Parsear trama hexadecimal RAW a código DTC legible
     * Soporta tramas con múltiples códigos. Ejemplo: "43 01 03 01 0A 00" → ["P0103", "P010A"]
     */
    public function parsearTramaHex(string $tramaHex): array
    {
        $tramaHex = str_replace(' ', '', strtoupper(trim($tramaHex)));
        
        if (empty($tramaHex)) {
            return [];
        }
        
        $codigos = [];
        
        // El modo 03/07 responde con 43 o 47.
        // Los códigos DTC ocupan 2 bytes cada uno después de los bytes de servicio.
        if (strpos($tramaHex, '43') === 0) {
            $data = substr($tramaHex, 2); // Quitar el '43'
            
            // Iterar de 4 en 4 caracteres (2 bytes por DTC)
            for ($i = 0; $i <= strlen($data) - 4; $i += 4) {
                $byte1 = substr($data, $i, 2);
                $byte2 = substr($data, $i + 2, 2);
                
                if ($byte1 === '00' && $byte2 === '00') continue;

                // En OBD-II, el primer dígito se codifica en el primer nibble
                // 0=P, 1=P, 4=B, 8=C, C=U (simplificado para el catálogo actual)
                $prefix = 'P';
                $val = hexdec($byte1 . $byte2);
                $codigo = $prefix . sprintf('%04X', $val & 0x3FFF); // Aplicar máscara estándar
                
                $codigos[] = $codigo;
            }
        }
        
        return $codigos;
    }

    /**
     * Interpretar código DTC a descripción en español
     */
    public function interpretarCodigo(string $codigo): array
    {
        $codigo = strtoupper(trim($codigo));
        
        if (array_key_exists($codigo, $this->catalogoDTC)) {
            $info = $this->catalogoDTC[$codigo];
            return [
                'codigo' => $codigo,
                'descripcion' => $info['descripcion'],
                'tipo' => $info['tipo'],
                'sistema' => $info['sistema'],
                'encontrado' => true
            ];
        }
        
        return [
            'codigo' => $codigo,
            'descripcion' => 'Código no encontrado en el catálogo',
            'tipo' => '',
            'sistema' => '',
            'encontrado' => false
        ];
    }

    /**
     * Simular respuesta de adaptador ELM327 para desarrollo/pruebas
     */
    public function simularELM327(string $pid): string
    {
        // Simulador básico de respuestas ELM327 para desarrollo
        // En un sistema real, esto se comunicaría con un adaptador físico
        
        $simulaciones = [
            '01' => '41 0C 8A 08',  // RPM simulado
            '02' => '41 05 80',     // Temperatura de refrigerante simulada
            '03' => '41 04 8A',     // Carga del motor simulada
            '04' => '41 0B 5A',     // Posición del acelerador simulada
            '05' => '41 0D 1A',     // Velocidad del vehículo simulada
            '06' => '43 01 03 00',  // Código de falla P0103 simulado
            '07' => '43 01 0A 00',  // Código de falla P010A simulado
        ];
        
        return $simulaciones[$pid] ?? 'NO DATA';
    }
}