<?php
declare(strict_types=1);

namespace GemMotors\Models;

use GemMotors\Config\Database;

class Usuario
{
    public int $id;
    public string $nombre;
    public string $apellido;
    public string $email;
    public string $password_hash;
    public string $rol;
    public bool $activo;
    public bool $forzar_cambio_password;
    public string $created_at;

    public function __construct(
        int $id,
        string $nombre,
        string $apellido,
        string $email,
        string $password_hash,
        string $rol,
        bool $activo,
        bool $forzar_cambio_password,
        string $created_at
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->email = $email;
        $this->password_hash = $password_hash;
        $this->rol = $rol;
        $this->activo = $activo;
        $this->forzar_cambio_password = $forzar_cambio_password;
        $this->created_at = $created_at;
    }

    // Buscar usuario por ID
    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            return new self(
                (int)$row['id'],
                $row['nombre'],
                $row['apellido'],
                $row['email'],
                $row['password_hash'],
                $row['rol'],
                filter_var($row['activo'], FILTER_VALIDATE_BOOLEAN), // Adaptado para booleanos de Postgres
                filter_var($row['forzar_cambio_password'], FILTER_VALIDATE_BOOLEAN),
                $row['created_at']
            );
        }

        return null;
    }

    // Buscar usuario por email
    public static function findByEmail(string $email): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if ($row) {
            return new self(
                (int)$row['id'],
                $row['nombre'],
                $row['apellido'],
                $row['email'],
                $row['password_hash'],
                $row['rol'],
                filter_var($row['activo'], FILTER_VALIDATE_BOOLEAN), // Adaptado para booleanos de Postgres
                filter_var($row['forzar_cambio_password'], FILTER_VALIDATE_BOOLEAN),
                $row['created_at']
            );
        }

        return null;
    }

    // Listar todos los usuarios (solo para admin)
    public static function findAll(array $filters = []): array
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM usuarios ORDER BY created_at DESC');
        $rows = $stmt->fetchAll();

        $usuarios = [];
        foreach ($rows as $row) {
            $usuarios[] = new self(
                (int)$row['id'],
                $row['nombre'],
                $row['apellido'],
                $row['email'],
                $row['password_hash'],
                $row['rol'],
                filter_var($row['activo'], FILTER_VALIDATE_BOOLEAN),
                filter_var($row['forzar_cambio_password'], FILTER_VALIDATE_BOOLEAN),
                $row['created_at']
            );
        }

        return $usuarios;
    }

    // Crear un nuevo usuario
    public static function create(array $data): self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo, forzar_cambio_password) VALUES (:nombre, :apellido, :email, :password_hash, :rol, :activo, :forzar_cambio_password)');
        
        $activoBool = isset($data['activo']) ? filter_var($data['activo'], FILTER_VALIDATE_BOOLEAN) : true;
        $forzarCambio = isset($data['forzar_cambio_password']) ? filter_var($data['forzar_cambio_password'], FILTER_VALIDATE_BOOLEAN) : false;

        $stmt->execute([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'rol' => $data['rol'],
            'activo' => $activoBool ? 'true' : 'false', // En Postgres se envían cadenas booleanas nativas
            'forzar_cambio_password' => $forzarCambio ? 'true' : 'false'
        ]);

        // En PostgreSQL pasamos el nombre de la secuencia de la tabla para recuperar el ID
        $id = (int)$db->lastInsertId('usuarios_id_seq');

        return new self(
            $id,
            $data['nombre'],
            $data['apellido'],
            $data['email'],
            $data['password_hash'],
            $data['rol'],
            $activoBool,
            $forzarCambio,
            date('Y-m-d H:i:s')
        );
    }

    // Actualizar usuario
    public function update(array $data): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE usuarios SET nombre = :nombre, apellido = :apellido, email = :email, password_hash = :password_hash, rol = :rol, activo = :activo WHERE id = :id');
        
        $activoFinal = isset($data['activo']) ? filter_var($data['activo'], FILTER_VALIDATE_BOOLEAN) : $this->activo;

        $stmt->execute([
            'id' => $this->id,
            'nombre' => $data['nombre'] ?? $this->nombre,
            'apellido' => $data['apellido'] ?? $this->apellido,
            'email' => $data['email'] ?? $this->email,
            'password_hash' => $data['password_hash'] ?? $this->password_hash,
            'rol' => $data['rol'] ?? $this->rol,
            'activo' => $activoFinal ? 'true' : 'false'
        ]);

        $this->nombre = $data['nombre'] ?? $this->nombre;
        $this->apellido = $data['apellido'] ?? $this->apellido;
        $this->email = $data['email'] ?? $this->email;
        $this->password_hash = $data['password_hash'] ?? $this->password_hash;
        $this->rol = $data['rol'] ?? $this->rol;
        $this->activo = $activoFinal;
    }

    // Eliminar (soft delete) usuario
    public function delete(): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE usuarios SET activo = 'false' WHERE id = :id");
        $stmt->execute(['id' => $this->id]);
        $this->activo = false;
    }

    // Cambiar password
    public function changePassword(string $newPasswordHash): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE usuarios SET password_hash = :password_hash, forzar_cambio_password = false WHERE id = :id');
        $stmt->execute([
            'id' => $this->id,
            'password_hash' => $newPasswordHash
        ]);
        $this->password_hash = $newPasswordHash;
        $this->forzar_cambio_password = false;
    }
}
