-- Idempotent migration: run on every startup to keep existing DBs in sync

ALTER TABLE ordenes_trabajo ADD COLUMN IF NOT EXISTS tiempo_estimado_dias INTEGER;
ALTER TABLE ordenes_trabajo ADD COLUMN IF NOT EXISTS observaciones_reparacion TEXT;
ALTER TABLE ordenes_trabajo ADD COLUMN IF NOT EXISTS fecha_estimada_entrega DATE;

-- Update estado check constraint to include cancelado
DO $$
BEGIN
    ALTER TABLE ordenes_trabajo DROP CONSTRAINT IF EXISTS ordenes_trabajo_estado_check;
    ALTER TABLE ordenes_trabajo ADD CONSTRAINT ordenes_trabajo_estado_check
        CHECK (estado IN ('diagnostico', 'reparacion', 'esperando_repuesto', 'control_calidad', 'entregado', 'cancelado'));
EXCEPTION
    WHEN others THEN NULL;
END $$;
