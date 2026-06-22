# G&E Motors - Backend API

API REST para el Sistema de Órdenes de Trabajo del Taller Automotriz G&E Motors.

## Requisitos

- Docker Desktop 4.0+
- Docker Compose 2.0+

## Variables de Entorno

Crear el archivo `.env` en la raíz del backend con las siguientes variables:

```env
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=gem_motors
DB_USERNAME=gem_user
DB_PASSWORD=gem_password

# Cloudinary (para evidencias multimedia)
CLOUDINARY_CLOUD_NAME=tu_cloud_name
CLOUDINARY_UPLOAD_PRESET=tu_upload_preset

# JWT Secret (para la autenticación)
JWT_SECRET=tu_jwt_secret_aqui
```

## Instalación y Ejecución

### Levantar el entorno con Docker

```bash
docker-compose up -d --build
```

Esto iniciará:
- **backend**: PHP 8.2-fpm en el puerto 8000
- **postgres**: PostgreSQL 15 con los scripts de inicialización

### Verificar los contenedores

```bash
docker-compose ps
```

### Ver logs en tiempo real

```bash
docker-compose logs -f
```

### Detener los servicios

```bash
docker-compose down
```

## Credenciales de Prueba

El script `seed.sql` crea los siguientes usuarios:

| Email | Password | Rol |
|-------|----------|-----|
| admin@gemmotors.com | 123456 | administrador |
| juan.perez@gemmotors.com | 123456 | mecanico |
| maria.gonzalez@gemmotors.com | 123456 | mecanico |

Clientes de prueba:
- **Carlos Lopez** - Código: GEM001
- **Ana Martinez** - Código: GEM002
- **Luis Ramirez** - Código: GEM003

## API Endpoints

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/clientes` | GET | Listar clientes |
| `/api/ordenes` | GET | Listar órdenes |
| `/api/ordenes/{id}/estado` | POST | Cambiar estado de orden |
| `/seguimiento/{codigo}` | GET | Consulta pública por código |

## Puertos

- **API Backend**: http://localhost:8000/api
- **PostgreSQL**: localhost:5432

## Volúmenes Persistentes

Los datos de PostgreSQL se almacenan en el volumen `postgres_data` para persistir entre reinicios.