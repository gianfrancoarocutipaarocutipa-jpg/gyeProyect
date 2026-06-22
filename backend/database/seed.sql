-- Datos de prueba: 1 admin, 2 mecánicos, 3 clientes, 2 vehículos por cliente, 10 repuestos OEM, 3 órdenes en estados diferentes

-- Insertar usuarios (password para todos: 123456)
INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo) VALUES
('Administrador', 'Sistema', 'admin@gemmotors.com', '$2y$10$ziB09Yx96PBaVerglFUkUOrGGhuQ3QoCzaC5mD/h.8qxWgvhloLzS', 'administrador', TRUE),
('Juan', 'Perez', 'juan.perez@gemmotors.com', '$2y$10$ziB09Yx96PBaVerglFUkUOrGGhuQ3QoCzaC5mD/h.8qxWgvhloLzS', 'mecanico', TRUE),
('Maria', 'Gonzalez', 'maria.gonzalez@gemmotors.com', '$2y$10$ziB09Yx96PBaVerglFUkUOrGGhuQ3QoCzaC5mD/h.8qxWgvhloLzS', 'mecanico', TRUE)
ON CONFLICT (email) DO NOTHING;

-- Insertar clientes
INSERT INTO clientes (nombre, dni_ruc, telefono, correo, codigo_seguimiento) VALUES
('Carlos Lopez', '12345678', '987654321', 'carlos.lopez@email.com', 'GEM001'),
('Ana Martinez', '87654321', '123456789', 'ana.martinez@email.com', 'GEM002'),
('Luis Ramirez', '11223344', '555555555', 'luis.ramirez@email.com', 'GEM003')
ON CONFLICT (codigo_seguimiento) DO NOTHING;

-- Insertar vehículos (2 por cliente)
INSERT INTO vehiculos (cliente_id, placa, marca, modelo, anio, vin, color, foto_url) VALUES
(1, 'ABC123', 'Toyota', 'Corolla', 2020, '1HGCM82633A004352', 'Blanco', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=800&q=80'),
(1, 'XYZ789', 'Honda', 'Civic', 2019, '2HGFC2F59KH500001', 'Negro', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=800&q=80'),
(2, 'DEF456', 'Chevrolet', 'Onix', 2021, '3GNCJKSB0JS555555', 'Rojo', 'https://images.unsplash.com/photo-1542282088-fe8426682b8f?auto=format&fit=crop&w=800&q=80'),
(2, 'GHI012', 'Hyundai', 'Accent', 2020, 'KMHCJ4ACxAU000000', 'Azul', 'https://images.unsplash.com/photo-1503376710356-69865111a33a?auto=format&fit=crop&w=800&q=80'),
(3, 'JKL345', 'Kia', 'Rio', 2022, 'KNDJC2AU4N7000001', 'Verde', 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&w=800&q=80'),
(3, 'MNO678', 'Nissan', 'Versa', 2021, '3N1CN7APxNL800000', 'Gris', NULL)
ON CONFLICT (placa) DO NOTHING;

-- Insertar repuestos OEM (10 piezas)
INSERT INTO repuestos (codigo_oem, nombre, descripcion, categoria, marca_fabricante, stock, stock_minimo, precio_unitario) VALUES
('FILT-001', 'Filtro de Aceite', 'Filtro de aceite sintético', 'Filtros', 'Bosch', 15, 5, 25.50),
('FILT-002', 'Filtro de Aire', 'Filtro de aire de alta eficiencia', 'Filtros', 'Mann-Filter', 20, 5, 18.90),
('FILT-003', 'Filtro de Combustible', 'Filtro de combustible para inyección', 'Filtros', 'Bosch', 8, 3, 22.00),
('PAST-001', 'Pastillas de Freno Delanteras', 'Pastillas de freno ceramicas', 'Frenos', 'Brembo', 12, 4, 65.00),
('PAST-002', 'Zapatas de Freno Traseras', 'Zapatas de freno para tambor', 'Frenos', 'TRW', 18, 5, 45.00),
('AMOR-001', 'Amortiguador Delantero Izq', 'Amortiguador de gas presión', 'Suspensión', 'Kayaba', 6, 2, 85.00),
('AMOR-002', 'Amortiguador Delantero Der', 'Amortiguador de gas presión', 'Suspensión', 'Kayaba', 6, 2, 85.00),
('LUC-001', 'Bombillo H7', 'Bombillo halógeno faro bajo', 'Iluminación', 'Philips', 30, 10, 12.00),
('LUC-002', 'Bombillo 9005', 'Bombillo halógeno faro alto', 'Iluminación', 'Philips', 25, 8, 15.00),
('BAT-001', 'Bateria 60Ah', 'Bateria sin mantenimiento 60Ah', 'Electricidad', 'Varta', 10, 3, 120.00)
ON CONFLICT (codigo_oem) DO NOTHING;

-- Insertar órdenes de trabajo en diferentes estados
INSERT INTO ordenes_trabajo (numero_ot, cliente_id, vehiculo_id, descripcion_problema, estado, presupuesto_aprobado) VALUES
('OT001', 1, 1, 'Vehículo presenta pérdida de potencia y humo negro en escape', 'diagnostico', FALSE)
ON CONFLICT (numero_ot) DO NOTHING;

INSERT INTO ordenes_trabajo (numero_ot, cliente_id, vehiculo_id, descripcion_problema, estado, presupuesto_aprobado) VALUES
('OT002', 2, 2, 'Ruido en dirección al girar, posible problema en rótulas', 'reparacion', TRUE)
ON CONFLICT (numero_ot) DO NOTHING;

INSERT INTO ordenes_trabajo (numero_ot, cliente_id, vehiculo_id, descripcion_problema, estado, presupuesto_aprobado, fecha_cierre) VALUES
('OT003', 3, 3, 'Mantenimiento preventivo: cambio de aceite y filtros', 'entregado', TRUE, CURRENT_TIMESTAMP)
ON CONFLICT (numero_ot) DO NOTHING;

-- Asignar mecánicos a órdenes (mecánico_ot)
INSERT INTO mecanico_ot (orden_id, mecanico_id, horas_trabajadas)
SELECT 2, 2, 3.5 WHERE EXISTS (SELECT 1 FROM ordenes_trabajo WHERE id = 2)
ON CONFLICT (orden_id, mecanico_id) DO NOTHING;

INSERT INTO mecanico_ot (orden_id, mecanico_id, horas_trabajadas)
SELECT 3, 3, 1.5 WHERE EXISTS (SELECT 1 FROM ordenes_trabajo WHERE id = 3)
ON CONFLICT (orden_id, mecanico_id) DO NOTHING;

-- Insertar diagnósticos de ejemplo
INSERT INTO diagnosticos (orden_id, vehiculo_id, mecanico_id, tramas_hex, codigos_falla, observaciones)
SELECT 1, 1, 2, '43 01 03 00 41 01 0C 00', '{"P0103": "Voltaje alto en circuito del sensor MAP"}', 'Sensor MAP con señal intermitente'
WHERE NOT EXISTS (SELECT 1 FROM diagnosticos WHERE orden_id = 1);

INSERT INTO diagnosticos (orden_id, vehiculo_id, mecanico_id, tramas_hex, codigos_falla, observaciones)
SELECT 2, 2, 2, '43 01 0A 00', '{"P010A": "Circuito del sensor de temperatura del aire de admisión"}', 'Sensor de temperatura defectuoso'
WHERE NOT EXISTS (SELECT 1 FROM diagnosticos WHERE orden_id = 2);

-- Insertar asignación de repuestos a órdenes
INSERT INTO asignacion_repuesto (orden_id, repuesto_id, cantidad)
SELECT 2, 1, 1 WHERE EXISTS (SELECT 1 FROM ordenes_trabajo WHERE id = 2)
ON CONFLICT (orden_id, repuesto_id) DO NOTHING;

INSERT INTO asignacion_repuesto (orden_id, repuesto_id, cantidad)
SELECT 2, 2, 1 WHERE EXISTS (SELECT 1 FROM ordenes_trabajo WHERE id = 2)
ON CONFLICT (orden_id, repuesto_id) DO NOTHING;

INSERT INTO asignacion_repuesto (orden_id, repuesto_id, cantidad)
SELECT 2, 3, 1 WHERE EXISTS (SELECT 1 FROM ordenes_trabajo WHERE id = 2)
ON CONFLICT (orden_id, repuesto_id) DO NOTHING;

INSERT INTO asignacion_repuesto (orden_id, repuesto_id, cantidad)
SELECT 3, 4, 2 WHERE EXISTS (SELECT 1 FROM ordenes_trabajo WHERE id = 3)
ON CONFLICT (orden_id, repuesto_id) DO NOTHING;

-- Insertar evidencias de ejemplo
INSERT INTO evidencias (orden_id, tipo, url_cloudinary, etiqueta, descripcion)
SELECT 2, 'foto', 'https://res.cloudinary.com/demo/image/upload/v1234567890/antes_freno.jpg', 'antes', 'Estado inicial de las pastillas de freno'
WHERE NOT EXISTS (SELECT 1 FROM evidencias WHERE url_cloudinary = 'https://res.cloudinary.com/demo/image/upload/v1234567890/antes_freno.jpg');

INSERT INTO evidencias (orden_id, tipo, url_cloudinary, etiqueta, descripcion)
SELECT 2, 'foto', 'https://res.cloudinary.com/demo/image/upload/v1234567891/durante_freno.jpg', 'durante', 'Proceso de reemplazo de pastillas'
WHERE NOT EXISTS (SELECT 1 FROM evidencias WHERE url_cloudinary = 'https://res.cloudinary.com/demo/image/upload/v1234567891/durante_freno.jpg');

INSERT INTO evidencias (orden_id, tipo, url_cloudinary, etiqueta, descripcion)
SELECT 2, 'foto', 'https://res.cloudinary.com/demo/image/upload/v1234567892/despues_freno.jpg', 'despues', 'Estado final tras reemplazo'
WHERE NOT EXISTS (SELECT 1 FROM evidencias WHERE url_cloudinary = 'https://res.cloudinary.com/demo/image/upload/v1234567892/despues_freno.jpg');

-- Insertar presupuestos
INSERT INTO presupuestos (orden_id, total, estado, motivo_rechazo)
SELECT 2, 185.50, 'aprobado', NULL WHERE EXISTS (SELECT 1 FROM ordenes_trabajo WHERE id = 2)
ON CONFLICT (orden_id) DO NOTHING;

INSERT INTO presupuestos (orden_id, total, estado, motivo_rechazo)
SELECT 3, 45.00, 'aprobado', NULL WHERE EXISTS (SELECT 1 FROM ordenes_trabajo WHERE id = 3)
ON CONFLICT (orden_id) DO NOTHING;