<?php
namespace App\Controllers\M1_RecepcionMP;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Proveedor;

class ProveedorController extends Controller
{
    private Proveedor $model;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m1_recepcion_mp');
        $this->model = new Proveedor();
    }

    public function index(): void
    {
        $proveedores = $this->model->todosActivos();
        $this->render('m1_recepcion_mp/proveedores/index', [
            'pageTitle'  => 'Proveedores',
            'breadcrumb' => [
                ['label' => 'Recepción MP', 'url' => APP_URL.'/m1'],
                ['label' => 'Proveedores'],
            ],
            'proveedores' => $proveedores,
            'canWrite'    => Auth::canWrite('m1_recepcion_mp'),
        ]);
    }

    public function nuevo(): void
    {
        Auth::requireWrite('m1_recepcion_mp');
        $this->render('m1_recepcion_mp/proveedores/form', [
            'pageTitle'  => 'Nuevo Proveedor',
            'breadcrumb' => [
                ['label' => 'Recepción MP', 'url' => APP_URL.'/m1'],
                ['label' => 'Proveedores',  'url' => APP_URL.'/m1/proveedores'],
                ['label' => 'Nuevo'],
            ],
            'proveedor' => null,
            'accion'    => 'crear',
        ]);
    }

    public function crear(): void
    {
        Auth::requireWrite('m1_recepcion_mp');
        $this->verifyCsrf();

        $nombre = $this->input('nombre');
        $errores = [];
        if (empty($nombre)) $errores[] = 'El nombre es requerido.';
        if ($this->model->nombreExiste($nombre)) {
            $errores[] = "El proveedor '{$nombre}' ya existe.";
        }

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/m1/proveedores/nuevo');
        }

        $this->model->create([
            'nombre'             => $nombre,
            'procedencia'        => $this->input('procedencia'),
            'telefono'           => $this->input('telefono'),
            'email'              => $this->input('email'),
            'registro_sanitario' => $this->input('registro_sanitario'),
            'activo'             => 1,
            'creado_por'         => Auth::id(),
        ]);

        $this->redirectWithSuccess('/m1/proveedores',
            "Proveedor '{$nombre}' creado correctamente.");
    }

    public function editar(array $params): void
    {
        Auth::requireWrite('m1_recepcion_mp');
        $proveedor = $this->model->find((int)$params['id']);
        if (!$proveedor) {
            $this->flash('error', 'Proveedor no encontrado.');
            $this->redirect('/m1/proveedores');
        }
        $this->render('m1_recepcion_mp/proveedores/form', [
            'pageTitle'  => 'Editar Proveedor',
            'breadcrumb' => [
                ['label' => 'Recepción MP', 'url' => APP_URL.'/m1'],
                ['label' => 'Proveedores',  'url' => APP_URL.'/m1/proveedores'],
                ['label' => 'Editar'],
            ],
            'proveedor' => $proveedor,
            'accion'    => 'editar',
        ]);
    }

    public function actualizar(array $params): void
    {
        Auth::requireWrite('m1_recepcion_mp');
        $this->verifyCsrf();
        $id     = (int)$params['id'];
        $nombre = $this->input('nombre');

        $errores = [];
        if (empty($nombre)) $errores[] = 'El nombre es requerido.';
        if ($this->model->nombreExiste($nombre, $id)) {
            $errores[] = "El nombre '{$nombre}' ya está en uso.";
        }

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect("/m1/proveedores/{$id}/editar");
        }

        $this->model->update($id, [
            'nombre'             => $nombre,
            'procedencia'        => $this->input('procedencia'),
            'telefono'           => $this->input('telefono'),
            'email'              => $this->input('email'),
            'registro_sanitario' => $this->input('registro_sanitario'),
        ]);

        $this->redirectWithSuccess('/m1/proveedores',
            'Proveedor actualizado correctamente.');
    }
}