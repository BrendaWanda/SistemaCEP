<?php
// =============================================================================
//  SIACEP — Controlador de autenticación
//  Archivo: app/Controllers/Auth/AuthController.php
// =============================================================================

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;

class AuthController extends Controller
{
    // ── GET /auth/login ───────────────────────────────────────────────────────
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->renderPlain('auth/login', []);
    }

    // ── POST /auth/login ──────────────────────────────────────────────────────
    public function login(): void
    {
        $this->verifyCsrf();

        $email    = $this->input('email');
        $password = $_POST['password'] ?? ''; // No sanitizar password antes de verificar

        if (empty($email) || empty($password)) {
            $this->flash('error', 'Ingrese su correo y contraseña.');
            $this->redirect('/auth/login');
        }

        if (Auth::login($email, $password)) {
            // Redirigir a la URL que intentaba acceder, o al dashboard
            $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
            unset($_SESSION['redirect_after_login']);
            $this->redirect($redirect);
        } else {
            $this->flash('error', 'Correo o contraseña incorrectos.');
            $this->redirect('/auth/login');
        }
    }

    // ── GET /auth/logout ──────────────────────────────────────────────────────
    public function logout(): void
    {
        Auth::logout();
        $this->flash('info', 'Sesión cerrada correctamente.');
        $this->redirect('/auth/login');
    }

    // ── GET /auth/perfil ──────────────────────────────────────────────────────
    public function perfil(): void
    {
        Auth::require();
        $usuario = $this->db->fetchOne(
            "SELECT id, nombre, apellidos, cargo, email, rol_id FROM usuarios WHERE id = ?",
            [Auth::id()]
        );
        $this->render('auth/perfil', compact('usuario'));
    }

    // ── POST /auth/perfil ─────────────────────────────────────────────────────
    public function actualizarPerfil(): void
    {
        Auth::require();
        $this->verifyCsrf();

        $nombre    = $this->input('nombre');
        $apellidos = $this->input('apellidos');
        $cargo     = $this->input('cargo');

        if (empty($nombre)) {
            $this->flash('error', 'El nombre es requerido.');
            $this->redirect('/auth/perfil');
        }

        $this->db->execute(
            "UPDATE usuarios SET nombre = ?, apellidos = ?, cargo = ? WHERE id = ?",
            [$nombre, $apellidos, $cargo, Auth::id()]
        );

        // Actualizar la sesión
        $_SESSION['user']['nombre']    = $nombre;
        $_SESSION['user']['apellidos'] = $apellidos;
        $_SESSION['user']['cargo']     = $cargo;

        $this->flash('success', 'Perfil actualizado correctamente.');
        $this->redirect('/auth/perfil');
    }

    // ── POST /auth/password ───────────────────────────────────────────────────
    public function cambiarPassword(): void
    {
        Auth::require();
        $this->verifyCsrf();

        $actual  = $_POST['password_actual']  ?? '';
        $nueva   = $_POST['password_nueva']   ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if (empty($actual) || empty($nueva)) {
            $this->flash('error', 'Complete todos los campos.');
            $this->redirect('/auth/perfil');
        }

        if ($nueva !== $confirm) {
            $this->flash('error', 'Las contraseñas nuevas no coinciden.');
            $this->redirect('/auth/perfil');
        }

        if (strlen($nueva) < 8) {
            $this->flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            $this->redirect('/auth/perfil');
        }

        $user = $this->db->fetchOne(
            "SELECT password_hash FROM usuarios WHERE id = ?",
            [Auth::id()]
        );

        if (!$user || !password_verify($actual, $user['password_hash'])) {
            $this->flash('error', 'La contraseña actual es incorrecta.');
            $this->redirect('/auth/perfil');
        }

        $nuevoHash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->execute(
            "UPDATE usuarios SET password_hash = ? WHERE id = ?",
            [$nuevoHash, Auth::id()]
        );

        $this->flash('success', 'Contraseña actualizada correctamente.');
        $this->redirect('/auth/perfil');
    }
}