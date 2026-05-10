<?php
namespace App\Models;

use App\Core\Model;

class Usuario extends Model
{
    protected string $table    = 'usuarios';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'rol_id','nombre','apellidos','cargo','email','password_hash','activo'
    ];

    public function todosConRol(): array
    {
        return $this->query(
            "SELECT u.*, r.nombre AS rol_nombre
            FROM usuarios u
            JOIN roles r ON r.id = u.rol_id
            ORDER BY r.id, u.nombre"
        );
    }

    public function emailExiste(string $email, ?int $exceptoId = null): bool
    {
        if ($exceptoId) return $this->exists('email = ? AND id != ?', [$email, $exceptoId]);
        return $this->exists('email = ?', [$email]);
    }

    public function roles(): array
    {
        return $this->db->fetchAll("SELECT id, nombre FROM roles ORDER BY id");
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }
}