<?php
namespace App\Controllers\Usuarios;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Usuario;

class UsuarioController extends Controller
{
    private Usuario $model;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('usuarios');
        $this->model = new Usuario();
    }

    public function index(): void
    {
        $usuarios = $this->model->todosConRol();
        $this->render('usuarios/index', [
            'pageTitle'  => 'Usuarios del sistema',
            'breadcrumb' => [['label'=>'Usuarios']],
            'usuarios'   => $usuarios,
            'canWrite'   => Auth::canWrite('usuarios'),
        ]);
    }

    public function nuevo(): void
    {
        Auth::requireWrite('usuarios');
        $this->render('usuarios/form', [
            'pageTitle'  => 'Nuevo Usuario',
            'breadcrumb' => [
                ['label'=>'Usuarios','url'=>APP_URL.'/usuarios'],
                ['label'=>'Nuevo'],
            ],
            'usuario' => null,
            'roles'   => $this->model->roles(),
            'accion'  => 'crear',
        ]);
    }

    public function crear(): void
    {
        Auth::requireWrite('usuarios');
        $this->verifyCsrf();

        $nombre    = $this->input('nombre');
        $apellidos = $this->input('apellidos');
        $cargo     = $this->input('cargo');
        $email     = strtolower(trim($this->input('email')));
        $rolId     = $this->inputInt('rol_id');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['password_confirm'] ?? '';

        $errores = [];
        if (empty($nombre))        $errores[] = 'El nombre es requerido.';
        if (empty($email))         $errores[] = 'El correo es requerido.';
        if (!$rolId)               $errores[] = 'Seleccione un rol.';
        if (strlen($password) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($password !== $confirm) $errores[] = 'Las contraseñas no coinciden.';
        if ($this->model->emailExiste($email)) {
            $errores[] = "El correo '{$email}' ya está registrado.";
        }

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/usuarios/nuevo');
        }

        $this->model->create([
            'rol_id'        => $rolId,
            'nombre'        => $nombre,
            'apellidos'     => $apellidos,
            'cargo'         => $cargo,
            'email'         => $email,
            'password_hash' => $this->model->hashPassword($password),
            'activo'        => 1,
        ]);

        $this->redirectWithSuccess('/usuarios', "Usuario '{$nombre}' creado correctamente.");
    }

    public function editar(array $params): void
    {
        Auth::requireWrite('usuarios');
        $usuario = $this->model->find((int)$params['id']);
        if (!$usuario) { $this->flash('error','Usuario no encontrado.'); $this->redirect('/usuarios'); }
        $this->render('usuarios/form', [
            'pageTitle'  => 'Editar: '.$usuario['nombre'],
            'breadcrumb' => [
                ['label'=>'Usuarios','url'=>APP_URL.'/usuarios'],
                ['label'=>'Editar'],
            ],
            'usuario' => $usuario,
            'roles'   => $this->model->roles(),
            'accion'  => 'editar',
        ]);
    }

    public function actualizar(array $params): void
    {
        Auth::requireWrite('usuarios');
        $this->verifyCsrf();
        $id      = (int)$params['id'];
        $usuario = $this->model->find($id);
        if (!$usuario) { $this->flash('error','No encontrado.'); $this->redirect('/usuarios'); }

        $email   = strtolower(trim($this->input('email')));
        $errores = [];
        if (empty($this->input('nombre'))) $errores[] = 'El nombre es requerido.';
        if (empty($email))                 $errores[] = 'El correo es requerido.';
        if ($this->model->emailExiste($email, $id)) {
            $errores[] = 'El correo ya está en uso por otro usuario.';
        }

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect("/usuarios/{$id}/editar");
        }

        $data = [
            'rol_id'    => $this->inputInt('rol_id'),
            'nombre'    => $this->input('nombre'),
            'apellidos' => $this->input('apellidos'),
            'cargo'     => $this->input('cargo'),
            'email'     => $email,
        ];

        // Solo cambia contraseña si se ingresó una nueva
        $newPass = $_POST['password'] ?? '';
        if (!empty($newPass)) {
            if (strlen($newPass) < 8) {
                $this->flash('error', 'La contraseña debe tener al menos 8 caracteres.');
                $this->redirect("/usuarios/{$id}/editar");
            }
            $data['password_hash'] = $this->model->hashPassword($newPass);
        }

        $this->model->update($id, $data);
        $this->redirectWithSuccess('/usuarios', 'Usuario actualizado correctamente.');
    }

    public function toggleActivo(array $params): void
    {
        Auth::requireWrite('usuarios');
        $this->verifyCsrf();
        $id = (int)$params['id'];

        if ($id === Auth::id()) {
            $this->flash('error', 'No puede desactivar su propia cuenta.');
            $this->redirect('/usuarios');
        }

        $usuario = $this->model->find($id);
        if (!$usuario) { $this->flash('error','No encontrado.'); $this->redirect('/usuarios'); }

        $nuevo = $usuario['activo'] ? 0 : 1;
        $this->model->update($id, ['activo' => $nuevo]);
        $msg = $nuevo ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.';
        $this->redirectWithSuccess('/usuarios', $msg);
    }
}