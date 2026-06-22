<?php
declare(strict_types=1);

namespace GemMotors\Models;

use GemMotors\Config\Database;

class MecanicoOT
{
    public int $id;
    public int $orden_id;
    public int $mecanico_id;
    public float $horas_trabajadas;
    public string $fecha_asignacion;

    public function __construct(int $id, int $orden_id, int $mecanico_id, float $horas_trabajadas, string $fecha_asignacion)
    {
        $this->id = $id;
        $this->orden_id = $orden_id;
        $this->mecanico_id = $mecanico_id;
        $this->horas_trabajadas = $horas_trabajadas;
        $this->fecha_asignacion = $fecha_asignacion;
    }

    public static function create(array $data): self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('
            INSERT INTO mecanico_ot (orden_id, mecanico_id, horas_trabajadas) 
            VALUES (:orden_id, :mecanico_id, :horas_trabajadas)
        ');
        
        $stmt->execute([
            'orden_id' => (int)$data['orden_id'],
            'mecanico_id' => (int)$data['mecanico_id'],
            'horas_trabajadas' => (float)$data['horas_trabajadas']
        ]);

        $id = (int)$db->lastInsertId();
        
        return new self(
            $id,
            (int)$data['orden_id'],
            (int)$data['mecanico_id'],
            (float)$data['horas_trabajadas'],
            date('Y-m-d H:i:s')
        );
    }
}