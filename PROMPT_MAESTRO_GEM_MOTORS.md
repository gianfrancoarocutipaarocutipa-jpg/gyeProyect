# PROMPT MAESTRO — Sistema Web G&E Motors (SIW-GEM)
> Basado en: FD01 Informe de Factibilidad · FD02 Informe de Visión · FD03 SRS v1.0
> Stack: PHP 8+ / Vue.js 3 / PostgreSQL 15 · Arquitectura: MVC · API REST

---

## ROL Y CONTEXTO

Eres un desarrollador senior full-stack especializado en PHP y Vue.js. Tu tarea es implementar **de forma completa y funcional** el Sistema Web G&E Motors (SIW-GEM), una plataforma para el taller automotriz G&E Motors (Tacna, Perú) que automatiza el seguimiento de reparaciones y el control de almacén de repuestos con integración OBD-II.

El proyecto está dividido en **backend** (PHP 8+ con arquitectura MVC y API REST) y **frontend** (Vue.js 3 como SPA). Ambas capas se comunican exclusivamente por JSON sobre HTTPS.

---

## STACK TECNOLÓGICO OBLIGATORIO

| Capa | Tecnología |
|---|---|
| Backend (lógica de negocio) | PHP 8.2+ · Arquitectura MVC estricta · PDO |
| Frontend (interfaz) | Vue.js 3 (Composition API) · Vue Router 4 · Pinia |
| Base de datos | PostgreSQL 15 (Neon.tech en producción) |
| Autenticación | JWT manual (sin librerías externas) · bcrypt |
| Autorización | RBAC: roles `administrador`, `mecanico`, `cliente` |
| Estilos | Tailwind CSS v3 |
| Multimedia | Cloudinary (almacenamiento de fotos y videos) |
| PDF | mPDF o DomPDF (generación de boletas y diagnósticos) |
| Contenedorización | Docker + Docker Compose |
| Control de versiones | Git / GitHub |
| CI/CD | GitHub Actions |
| Hosting | Railway (backend) + Vercel (frontend) + Neon.tech (BD) |

---

## ESTRUCTURA DE DIRECTORIOS OBLIGATORIA

```
gem-motors/
├── backend/
│   ├── public/
│   │   └── index.php              ← Entry point; maneja CORS y enrutamiento
│   ├── app/
│   │   ├── Controllers/           ← Un controller por módulo
│   │   │   ├── AuthController.php
│   │   │   ├── OrdenTrabajoController.php
│   │   │   ├── ClienteController.php
│   │   │   ├── VehiculoController.php
│   │   │   ├── RepuestoController.php
│   │   │   ├── DiagnosticoController.php
│   │   │   ├── EvidenciaController.php
│   │   │   ├── PresupuestoController.php
│   │   │   ├── ReporteController.php
│   │   │   └── UsuarioController.php
│   │   ├── Models/                ← Un model por entidad del diagrama de clases
│   │   │   ├── Usuario.php
│   │   │   ├── Cliente.php
│   │   │   ├── Vehiculo.php
│   │   │   ├── OrdenTrabajo.php
│   │   │   ├── Diagnostico.php
│   │   │   ├── Repuesto.php
│   │   │   ├── AsignacionRepuesto.php
│   │   │   ├── Evidencia.php
│   │   │   ├── Presupuesto.php
│   │   │   └── MecanicoOT.php
│   │   ├── Middleware/
│   │   │   ├── AuthMiddleware.php ← Verificación JWT
│   │   │   └── RolMiddleware.php  ← Verificación de rol RBAC
│   │   └── Services/
│   │       ├── OBDService.php     ← Parsing de tramas hex ELM327
│   │       ├── CloudinaryService.php
│   │       ├── PDFService.php
│   │       └── NotificacionService.php
│   ├── config/
│   │   ├── database.php           ← Singleton PDO + PostgreSQL
│   │   └── app.php                ← Constantes globales y helpers
│   ├── routes/
│   │   └── api.php                ← Router manual por método HTTP + URI
│   ├── database/
│   │   ├── schema.sql             ← DDL completo
│   │   └── seed.sql               ← Datos de prueba
│   ├── Dockerfile
│   └── composer.json
│
├── frontend/
│   ├── src/
│   │   ├── views/                 ← Una View por módulo/ruta
│   │   │   ├── LoginView.vue
│   │   │   ├── DashboardView.vue
│   │   │   ├── OrdenesView.vue
│   │   │   ├── OrdenDetalleView.vue
│   │   │   ├── ClientesView.vue
│   │   │   ├── VehiculosView.vue
│   │   │   ├── InventarioView.vue
│   │   │   ├── DiagnosticoView.vue
│   │   │   ├── PresupuestosView.vue
│   │   │   ├── ReportesView.vue
│   │   │   ├── UsuariosView.vue
│   │   │   └── SeguimientoPublicoView.vue  ← Sin login
│   │   ├── components/            ← Componentes reutilizables
│   │   │   ├── layout/
│   │   │   │   ├── AppSidebar.vue
│   │   │   │   ├── AppTopbar.vue
│   │   │   │   └── AppNotification.vue
│   │   │   ├── ordenes/
│   │   │   │   ├── OrdenCard.vue
│   │   │   │   ├── OrdenEstadoPipeline.vue
│   │   │   │   └── OrdenForm.vue
│   │   │   ├── inventario/
│   │   │   │   ├── RepuestoForm.vue
│   │   │   │   └── StockBadge.vue
│   │   │   ├── diagnostico/
│   │   │   │   ├── OBDReader.vue
│   │   │   │   └── CodigoDTCCard.vue
│   │   │   └── shared/
│   │   │       ├── BaseModal.vue
│   │   │       ├── BaseTable.vue
│   │   │       ├── BaseButton.vue
│   │   │       └── BaseAlert.vue
│   │   ├── router/
│   │   │   └── index.js           ← Vue Router con guardias por rol
│   │   ├── stores/                ← Pinia
│   │   │   ├── auth.js
│   │   │   ├── ordenes.js
│   │   │   ├── repuestos.js
│   │   │   └── notificaciones.js
│   │   └── services/              ← Una función por endpoint
│   │       ├── api.js             ← fetch base con token
│   │       ├── authService.js
│   │       ├── ordenService.js
│   │       ├── clienteService.js
│   │       ├── repuestoService.js
│   │       ├── diagnosticoService.js
│   │       ├── evidenciaService.js
│   │       ├── presupuestoService.js
│   │       └── reporteService.js
│   ├── public/
│   ├── index.html
│   ├── vite.config.js
│   ├── tailwind.config.js
│   └── package.json
│
└── docker-compose.yml
```

---

## BASE DE DATOS — SCHEMA POSTGRESQL COMPLETO

Genera el DDL completo con las siguientes tablas (basadas en el diagrama de clases del SRS):

```sql
-- Tablas requeridas (con todas sus restricciones y relaciones):
usuarios         -- id, nombre, apellido, email, password_hash, rol CHECK(...), activo, created_at
clientes         -- id, nombre, dni_ruc, telefono, correo, codigo_seguimiento UNIQUE, created_at
vehiculos        -- id, cliente_id FK, placa UNIQUE, marca, modelo, anio, vin UNIQUE, color
ordenes_trabajo  -- id, numero_ot UNIQUE, cliente_id FK, vehiculo_id FK, mecanico_id FK(nullable),
                 --   descripcion_problema, estado CHECK(flujo secuencial), presupuesto_aprobado BOOL,
                 --   fecha_cierre, created_at
mecanico_ot      -- id, orden_id FK, mecanico_id FK, horas_trabajadas, fecha_asignacion
diagnosticos     -- id, orden_id FK, vehiculo_id FK, mecanico_id FK, tramas_hex TEXT,
                 --   codigos_falla JSONB, observaciones, created_at
repuestos        -- id, codigo_oem UNIQUE, nombre, descripcion, categoria, marca_fabricante,
                 --   stock INT CHECK(>=0), stock_minimo INT, precio_unitario NUMERIC(10,2)
asignacion_repuesto -- id, orden_id FK, repuesto_id FK, cantidad INT CHECK(>0), fecha_asignacion
                    --   UNIQUE(orden_id, repuesto_id)
evidencias       -- id, orden_id FK, tipo CHECK('foto','video'), url_cloudinary, etiqueta
                 --   CHECK('antes','durante','despues'), descripcion, inalterable BOOL DEFAULT false
presupuestos     -- id, orden_id FK UNIQUE, total NUMERIC(10,2), estado CHECK('pendiente','aprobado','rechazado'),
                 --   motivo_rechazo TEXT, fecha_emision, fecha_respuesta
```

**Regla**: el campo `estado` de `ordenes_trabajo` solo acepta transiciones secuenciales:
`diagnostico → reparacion → esperando_repuesto → control_calidad → entregado`

---

## REQUERIMIENTOS FUNCIONALES A IMPLEMENTAR

Implementa los 17 RF del SRS **en orden de prioridad por Sprint**:

### Sprint 1 — Alta Prioridad
| RF | Módulo | Descripción |
|---|---|---|
| RF-01 | Órdenes | Crear, editar, anular OT. Validar que no exista OT activa para el mismo vehículo (RN-01) |
| RF-02 | Diagnóstico | Leer/parsear tramas hex ELM327 → códigos DTC legibles. Usar simulador en dev |
| RF-03 | Almacén | CRUD repuestos por código OEM. Descuento automático de stock al asignar |
| RF-07 | Dashboard | Métricas en tiempo real: OTs por estado, stock bajo, últimas incidencias |
| RF-08 | Seguridad | Login JWT + RBAC. Roles: administrador, mecanico, cliente |
| RF-09 | Clientes | CRUD clientes + vehículos. Historial de reparaciones por cliente |

### Sprint 2 — Media/Alta Prioridad
| RF | Módulo | Descripción |
|---|---|---|
| RF-04 | Órdenes | Ciclo de estados con validación de flujo secuencial (RN-03). Notificación al cliente |
| RF-05 | Multimedia | Upload foto/video a Cloudinary. Etiqueta antes/durante/después. Inalterable post-entrega (RN-06) |
| RF-06 | Reportes | Generar PDF: diagnóstico, hoja de ruta, boleta de servicio con membrete |
| RF-10 | Almacén | Alerta automática cuando stock ≤ stock_minimo (RN-08) |
| RF-11 | Almacén | Correlación repuesto ↔ orden de trabajo (trazabilidad garantías) |
| RF-16 | Recursos | Asignar mecánicos a órdenes de trabajo |

### Sprint 3 — Media/Baja Prioridad
| RF | Módulo | Descripción |
|---|---|---|
| RF-12 | Diagnóstico | Historial completo de lecturas OBD-II por vehículo (comparación temporal) |
| RF-13 | Clientes | Portal público sin login: consultar estado por código de seguimiento único |
| RF-14 | Facturación | Generar presupuesto, enviar por correo, registrar aprobación/rechazo (RN-04) |
| RF-15 | Reportes | Reportes gerenciales: ingresos por período, productividad mecánicos, rotación repuestos |
| RF-17 | Almacén | Exportar inventario a Excel y PDF |

---

## REGLAS DE NEGOCIO (Obligatorias en código)

Implementa estas 8 reglas con validación tanto en el backend como en el frontend:

| ID | Descripción | Dónde validar |
|---|---|---|
| RN-01 | Un vehículo solo puede tener 1 OT activa simultáneamente | Model OrdenTrabajo + Controller |
| RN-02 | No se puede asignar un repuesto con stock = 0. El descuento es automático | Model AsignacionRepuesto |
| RN-03 | Los estados siguen flujo estricto. No se permiten saltos ni retrocesos | Model OrdenTrabajo::cambiarEstado() |
| RN-04 | No se puede pasar a 'reparacion' sin presupuesto aprobado | Controller OrdenTrabajo |
| RN-05 | Datos financieros solo visibles para administrador | Middleware RolMiddleware |
| RN-06 | Evidencias no eliminables una vez que la OT está en estado 'entregado' | Model Evidencia + Controller |
| RN-07 | Código OEM debe cumplir patrón alfanumérico con guión [A-Z0-9-] | Model Repuesto::validarOEM() |
| RN-08 | Notificación inmediata al admin si stock cae ≤ stock_minimo tras descuento | Service NotificacionService |

---

## API REST — ENDPOINTS COMPLETOS

Todos los endpoints siguen el formato:
- URL: `/api/{recurso}`
- Respuesta exitosa: `{ "success": true, "data": {...}, "mensaje": "..." }`
- Error: `{ "success": false, "error": "Descripción del error" }`

```
# Auth
POST   /api/auth/login          ← email + password → JWT + usuario
POST   /api/auth/registro       ← auto-registro clientes
GET    /api/auth/perfil         ← requiere JWT

# Clientes
GET    /api/clientes            ← Admin/Mecánico
POST   /api/clientes            ← Admin/Mecánico
GET    /api/clientes/{id}
PUT    /api/clientes/{id}
GET    /api/clientes/{id}/vehiculos
GET    /api/clientes/{id}/historial

# Vehículos
GET    /api/vehiculos
POST   /api/vehiculos
GET    /api/vehiculos/{id}
PUT    /api/vehiculos/{id}
GET    /api/vehiculos/{id}/diagnosticos   ← Historial OBD-II (RF-12)

# Órdenes de Trabajo
GET    /api/ordenes                        ← Filtrado por rol automático
POST   /api/ordenes
GET    /api/ordenes/{id}
PUT    /api/ordenes/{id}/estado            ← Valida flujo secuencial (RN-03)
POST   /api/ordenes/{id}/repuestos         ← Asignar + descontar stock
GET    /api/ordenes/{id}/repuestos
POST   /api/ordenes/{id}/mecanico         ← Asignar mecánico (RF-16)
GET    /api/ordenes/estadisticas          ← Dashboard Admin

# Diagnósticos
POST   /api/diagnosticos
GET    /api/diagnosticos/orden/{id}
GET    /api/diagnosticos/interpretar/{codigo}   ← Parsear código DTC
GET    /api/diagnosticos/codigos               ← Catálogo DTC completo

# Repuestos (Inventario)
GET    /api/repuestos?q={busqueda}
POST   /api/repuestos                    ← Solo Admin
GET    /api/repuestos/{id}
PUT    /api/repuestos/{id}               ← Solo Admin
DELETE /api/repuestos/{id}               ← Solo Admin
GET    /api/repuestos/oem/{codigo}
GET    /api/repuestos/stock-bajo

# Evidencias (Multimedia)
POST   /api/evidencias                   ← Subir a Cloudinary
GET    /api/evidencias/orden/{id}
DELETE /api/evidencias/{id}              ← Solo si OT no está 'entregado' (RN-06)

# Presupuestos
POST   /api/presupuestos
GET    /api/presupuestos/orden/{id}
PUT    /api/presupuestos/{id}/respuesta  ← aprobar | rechazar
GET    /api/seguimiento/{codigo}         ← Público, sin JWT (RF-13)

# Reportes
GET    /api/reportes/ingresos?desde={}&hasta={}
GET    /api/reportes/productividad?desde={}&hasta={}
GET    /api/reportes/rotacion-repuestos
GET    /api/reportes/tiempo-promedio
GET    /api/reportes/pdf/orden/{id}?tipo={diagnostico|hoja_ruta|boleta}   ← RF-06
GET    /api/reportes/excel/inventario                                       ← RF-17

# Usuarios
GET    /api/usuarios          ← Solo Admin
POST   /api/usuarios          ← Solo Admin
GET    /api/usuarios/{id}
PUT    /api/usuarios/{id}
DELETE /api/usuarios/{id}     ← Soft delete (activo = false)
```

---

## LÓGICA DEL SERVICIO OBD-II (OBDService.php)

Implementa la clase con esta lógica:

```php
class OBDService {
    // Catálogo de al menos 50 códigos DTC P0xxx, B0xxx, C0xxx, U0xxx
    private array $catalogoDTC = [...];

    // Parsear trama hexadecimal RAW a código DTC legible
    // Ej: "43 01 03 00" → ["P0103"]
    public function parsearTramaHex(string $tramaHex): array {}

    // Interpretar código DTC a descripción en español
    public function interpretarCodigo(string $codigo): array {
        return ['codigo' => $codigo, 'descripcion' => '...', 'encontrado' => bool];
    }

    // Simular respuesta de adaptador ELM327 para desarrollo/pruebas
    public function simularELM327(string $pid): string {}
}
```

---

## FRONTEND — VISTAS REQUERIDAS

### AppSidebar.vue
Menú lateral dinámico según rol:
- **Administrador**: Dashboard · Órdenes · Clientes · Vehículos · Inventario · Diagnóstico · Presupuestos · Reportes · Usuarios
- **Mecánico**: Dashboard · Mis Órdenes · Inventario · Diagnóstico
- **Cliente**: Mis Vehículos · Mis Órdenes · Seguimiento

### DashboardView.vue (RF-07)
- Grid de tarjetas stat: OTs por estado (con color por estado), repuestos con stock bajo, última actividad
- Gráfico de barras: OTs por semana (recharts o Chart.js)
- Lista de alertas: stock crítico + OTs sin mecánico asignado

### OrdenesView.vue (RF-01, RF-04)
- Tabla filtrable + paginada de órdenes (filtro por estado, mecánico, fecha)
- Pipeline visual de estados (componente `OrdenEstadoPipeline.vue`)
- Modal para crear orden: seleccionar cliente → seleccionar/registrar vehículo → describir problema
- Modal para cambiar estado con validación de flujo (RN-03) y bloqueo si no hay presupuesto (RN-04)

### OrdenDetalleView.vue
- Vista completa de una OT: datos del vehículo, cliente, mecánico asignado
- Timeline de cambios de estado con fecha, hora y usuario
- Sección de diagnósticos OBD-II registrados
- Sección de repuestos asignados con subtotales
- Galería de evidencia multimedia (fotos + reproductor video)
- Botón "Generar PDF" (diagnóstico / hoja de ruta / boleta)

### InventarioView.vue (RF-03, RF-10, RF-17)
- Tabla con búsqueda por nombre/OEM/categoría
- Badge de stock: verde (OK) / amarillo (cerca del mínimo) / rojo (crítico)
- Modal CRUD: validar formato OEM en tiempo real (RN-07)
- Botón exportar Excel/PDF

### DiagnosticoView.vue (RF-02, RF-12)
- Panel izquierdo: intérprete de código DTC (input + resultado)
- Panel derecho: tabla completa de códigos DTC del catálogo
- Formulario: registrar diagnóstico en OT específica (orden_id + código + datos RAW + observaciones)
- Sección: historial de diagnósticos de un vehículo

### SeguimientoPublicoView.vue (RF-13)
- Vista sin login, accesible por URL pública
- Input de código de seguimiento
- Muestra: estado actual del vehículo, pipeline de estados, últimas observaciones del mecánico, fotos de evidencia disponibles
- NO muestra: datos financieros, otros clientes

### ReportesView.vue (RF-15)
- Selector de tipo de reporte con filtros de fecha
- Gráficos interactivos: líneas (ingresos), barras (productividad mecánicos), dona (categorías repuestos)
- Botón exportar PDF/Excel por reporte

---

## PERFILES DE USUARIO Y RESTRICCIONES UI

| Componente/Dato | Admin | Mecánico | Cliente |
|---|---|---|---|
| Dashboard con métricas financieras | ✅ | ❌ | ❌ |
| Crear/editar órdenes | ✅ | ✅ | ❌ |
| Ver todas las órdenes | ✅ | Solo asignadas | Solo propias |
| Gestionar inventario (CRUD) | ✅ | Solo lectura | ❌ |
| Asignar repuestos a OT | ✅ | ✅ | ❌ |
| Diagnóstico OBD-II | ✅ | ✅ | ❌ |
| Cargar evidencia multimedia | ✅ | ✅ | ❌ |
| Aprobar presupuesto | ✅ | ❌ | ✅ |
| Ver reportes gerenciales | ✅ | ❌ | ❌ |
| Gestionar usuarios | ✅ | ❌ | ❌ |
| Portal de seguimiento público | ✅ | ✅ | ✅ (sin login) |

---

## REQUERIMIENTOS NO FUNCIONALES (implementar en código)

| RNF | Implementación requerida |
|---|---|
| RNF-01 Rendimiento ≤2s | Índices en FK + campos de búsqueda frecuente en PostgreSQL |
| RNF-02 Seguridad | HTTPS en producción, bcrypt para passwords, JWT con expiración 1h |
| RNF-03 Disponibilidad 99.5% | Docker health checks, retry en Railway |
| RNF-04 Responsive | Tailwind breakpoints; layout sidebar colapsable en mobile |
| RNF-05 30 concurrentes | PDO con pooling, índices en BD |
| RNF-06 MVC estricto | Ninguna lógica de negocio en Controllers (va en Models/Services) |
| RNF-07 Compatibilidad | CSS/JS sin features experimentales |
| RNF-08 Ley 29733 | Cifrado de datos sensibles, soft delete, auditoría de accesos |
| RNF-09 Usabilidad 2h | Mensajes de error descriptivos, tooltips, íconos en botones |

---

## DOCKER COMPOSE (obligatorio)

```yaml
# docker-compose.yml — generarlo completo con:
services:
  backend:   # PHP 8.2-fpm + extensiones pgsql, mbstring, gd, fileinfo
  frontend:  # Node 20 Alpine con npm run dev en dev / nginx en prod
  postgres:  # postgres:15 con volumen persistente + init scripts
  nginx:     # Reverse proxy: /api → backend:9000, / → frontend:3000
```

---

## INSTRUCCIONES DE GENERACIÓN

Cuando implementes el código, sigue este orden estricto:

1. **`database/schema.sql`** — DDL completo con todas las tablas, restricciones, índices y comentarios
2. **`database/seed.sql`** — Datos de prueba: 1 admin, 2 mecánicos, 3 clientes, 2 vehículos por cliente, 10 repuestos OEM, 3 órdenes en estados diferentes
3. **`backend/config/`** — database.php y app.php con todas las constantes
4. **`backend/app/Models/`** — Todos los modelos con métodos CRUD + reglas de negocio
5. **`backend/app/Middleware/`** — AuthMiddleware (JWT) + RolMiddleware (RBAC)
6. **`backend/app/Services/`** — OBDService + PDFService + CloudinaryService + NotificacionService
7. **`backend/app/Controllers/`** — Todos los controllers (delegan a Models/Services)
8. **`backend/routes/api.php`** — Router completo
9. **`backend/public/index.php`** — Entry point con CORS
10. **`frontend/src/services/`** — Capa de servicios API (fetch con JWT)
11. **`frontend/src/stores/`** — Pinia stores
12. **`frontend/src/router/index.js`** — Vue Router con navigation guards por rol
13. **`frontend/src/components/`** — Componentes reutilizables (BaseModal, BaseTable, etc.)
14. **`frontend/src/views/`** — Todas las vistas en orden de prioridad de Sprint
15. **`docker-compose.yml`** y **`Dockerfile`**
16. **`README.md`** — Instrucciones de instalación, variables de entorno requeridas, cómo correr localmente

---

## VARIABLES DE ENTORNO REQUERIDAS

```env
# Backend (.env)
DB_HOST=localhost
DB_PORT=5432
DB_NAME=gem_motors
DB_USER=postgres
DB_PASSWORD=

JWT_SECRET=clave_segura_minimo_32_chars
JWT_EXPIRY=3600

CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=
MAIL_PASS=

APP_ENV=development   # development | production
APP_URL=http://localhost:8000

# Frontend (.env)
VITE_API_BASE=http://localhost:8000/api
```

---

## CALIDAD DE CÓDIGO EXIGIDA

- **Principios SOLID**: responsabilidad única por clase, interfaces claras, inyección de dependencias
- **Ninguna lógica de negocio en Controllers**: los controllers solo reciben, validan HTTP y delegan
- **PSR-12** para PHP (indentación 4 espacios, tipado estricto `declare(strict_types=1)`)
- **Comentarios en español** para lógica de negocio compleja
- **Manejo de excepciones** con try/catch en toda operación de BD y servicio externo
- **Respuestas HTTP correctas**: 200, 201, 400, 401, 403, 404, 409, 500
- **Validación de entrada** en todos los endpoints antes de tocar la BD

---

## NOTAS FINALES

- El taller G&E Motors está en **Tacna, Perú**: usa formato de fecha `d/m/Y`, moneda `S/` (soles)
- El sistema debe funcionar **completamente sin OBD-II físico**: el simulador virtual debe activarse automáticamente en `APP_ENV=development`
- La generación de PDF usa membrete con logo del taller: deja un `<div class="membrete">` como placeholder
- El portal de seguimiento público (`/seguimiento/{codigo}`) debe funcionar **sin autenticación**
- Cumplir **Ley N° 29733** (Perú): soft delete en usuarios/clientes, no exponer datos personales de otros clientes, registrar intentos fallidos de login

