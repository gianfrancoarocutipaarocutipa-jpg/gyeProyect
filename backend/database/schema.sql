-- Tablas requeridas (con todas sus restricciones y relaciones):

CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL CHECK (rol IN ('administrador', 'mecanico', 'cliente')),
    activo BOOLEAN DEFAULT TRUE,
    forzar_cambio_password BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clientes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    dni_ruc VARCHAR(20) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    correo VARCHAR(255),
    codigo_seguimiento VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE vehiculos (
    id SERIAL PRIMARY KEY,
    cliente_id INTEGER NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    placa VARCHAR(20) UNIQUE NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    anio INTEGER NOT NULL CHECK (anio > 1900 AND anio <= EXTRACT(YEAR FROM CURRENT_DATE) + 1),
    vin VARCHAR(17) UNIQUE,
    color VARCHAR(30),
    foto_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_cliente_id ON vehiculos (cliente_id);

CREATE TABLE ordenes_trabajo (
    id SERIAL PRIMARY KEY,
    numero_ot VARCHAR(30) UNIQUE NOT NULL,
    cliente_id INTEGER NOT NULL REFERENCES clientes(id) ON DELETE RESTRICT,
    vehiculo_id INTEGER NOT NULL REFERENCES vehiculos(id) ON DELETE RESTRICT,
    mecanico_id INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
    descripcion_problema TEXT NOT NULL,
    estado VARCHAR(30) NOT NULL CHECK (estado IN ('diagnostico', 'reparacion', 'esperando_repuesto', 'control_calidad', 'entregado', 'cancelado')),
    presupuesto_aprobado BOOLEAN DEFAULT FALSE,
    fecha_cierre TIMESTAMP NULL,
    tiempo_estimado_dias INTEGER,
    observaciones_reparacion TEXT,
    fecha_estimada_entrega DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ordenes_cliente ON ordenes_trabajo (cliente_id);
CREATE INDEX idx_ordenes_vehiculo ON ordenes_trabajo (vehiculo_id);
CREATE INDEX idx_ordenes_estado ON ordenes_trabajo (estado);

CREATE TABLE mecanico_ot (
    id SERIAL PRIMARY KEY,
    orden_id INTEGER NOT NULL REFERENCES ordenes_trabajo(id) ON DELETE CASCADE,
    mecanico_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    horas_trabajadas DECIMAL(5,2) DEFAULT 0,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(orden_id, mecanico_id)
);

CREATE TABLE diagnosticos (
    id SERIAL PRIMARY KEY,
    orden_id INTEGER NOT NULL REFERENCES ordenes_trabajo(id) ON DELETE CASCADE,
    vehiculo_id INTEGER NOT NULL REFERENCES vehiculos(id) ON DELETE CASCADE,
    mecanico_id INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
    tramas_hex TEXT,
    codigos_falla JSONB,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_diagnosticos_orden ON diagnosticos (orden_id);
CREATE INDEX idx_diagnosticos_vehiculo ON diagnosticos (vehiculo_id);

CREATE TABLE repuestos (
    id SERIAL PRIMARY KEY,
    codigo_oem VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(50),
    marca_fabricante VARCHAR(100),
    stock INTEGER NOT NULL CHECK (stock >= 0),
    stock_minimo INTEGER NOT NULL DEFAULT 0,
    precio_unitario NUMERIC(10,2) NOT NULL CHECK (precio_unitario >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_repuestos_oem ON repuestos (codigo_oem);

CREATE TABLE asignacion_repuesto (
    id SERIAL PRIMARY KEY,
    orden_id INTEGER NOT NULL REFERENCES ordenes_trabajo(id) ON DELETE CASCADE,
    repuesto_id INTEGER NOT NULL REFERENCES repuestos(id) ON DELETE CASCADE,
    cantidad INTEGER NOT NULL CHECK (cantidad > 0),
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(orden_id, repuesto_id)
);

CREATE INDEX idx_asignacion_orden ON asignacion_repuesto (orden_id);
CREATE INDEX idx_asignacion_repuesto ON asignacion_repuesto (repuesto_id);

CREATE TABLE evidencias (
    id SERIAL PRIMARY KEY,
    orden_id INTEGER NOT NULL REFERENCES ordenes_trabajo(id) ON DELETE CASCADE,
    tipo VARCHAR(10) NOT NULL CHECK (tipo IN ('foto', 'video')),
    url_cloudinary VARCHAR(500) NOT NULL,
    etiqueta VARCHAR(20) NOT NULL CHECK (etiqueta IN ('antes', 'durante', 'despues')),
    descripcion TEXT,
    inalterable BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_evidencias_orden ON evidencias (orden_id);

CREATE TABLE presupuestos (
    id SERIAL PRIMARY KEY,
    orden_id INTEGER NOT NULL REFERENCES ordenes_trabajo(id) ON DELETE CASCADE UNIQUE,
    total NUMERIC(10,2) NOT NULL CHECK (total >= 0),
    estado VARCHAR(20) NOT NULL CHECK (estado IN ('pendiente', 'aprobado', 'rechazado')),
    motivo_rechazo TEXT,
    fecha_emision TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta TIMESTAMP NULL
);

CREATE INDEX idx_presupuestos_orden ON presupuestos (orden_id);
CREATE INDEX idx_presupuestos_estado ON presupuestos (estado);

-- Índices adicionales para rendimiento
CREATE INDEX idx_ordenes_trabajo_cliente_estado ON ordenes_trabajo (cliente_id, estado);
CREATE INDEX idx_ordenes_trabajo_vehiculo_estado ON ordenes_trabajo (vehiculo_id, estado);

CREATE TABLE historial_estados_ot (
    id SERIAL PRIMARY KEY,
    orden_id INTEGER NOT NULL REFERENCES ordenes_trabajo(id) ON DELETE CASCADE,
    estado_anterior VARCHAR(30) NOT NULL,
    estado_nuevo VARCHAR(30) NOT NULL,
    usuario_id INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_historial_orden ON historial_estados_ot (orden_id);