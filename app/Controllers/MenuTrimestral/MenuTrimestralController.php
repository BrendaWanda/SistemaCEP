<?php
namespace App\Controllers\MenuTrimestral;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\MenuTrimestral as MenuTrimestralModel;

class MenuTrimestralController extends Controller
{
    private MenuTrimestralModel $model;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m7_dashboard');
        $this->model = new MenuTrimestralModel();
    }

    // GET /menu-trimestral
    public function index(): void
    {
        $menus = $this->model->todosConStats();

        $this->render('menu_trimestral/index', [
            'pageTitle'  => 'Menú Trimestral',
            'breadcrumb' => [['label' => 'Menú Trimestral']],
            'menus'      => $menus,
            'trimestres' => MenuTrimestralModel::TRIMESTRES,
            'niveles'    => MenuTrimestralModel::NIVELES,
            'canWrite'   => Auth::canWrite('m0_configuracion'),
        ]);
    }

    // GET /menu-trimestral/:id
    public function ver(array $params): void
    {
        $menu = $this->model->conDetalle((int)$params['id']);
        if (!$menu) {
            $this->flash('error', 'Menú no encontrado.');
            $this->redirect('/menu-trimestral');
        }

        $this->render('menu_trimestral/ver', [
            'pageTitle'  => 'Menú — '.$menu['nombre'],
            'breadcrumb' => [
                ['label'=>'Menú Trimestral','url'=>APP_URL.'/menu-trimestral'],
                ['label'=>$menu['nombre']],
            ],
            'menu'      => $menu,
            'trimestres'=> MenuTrimestralModel::TRIMESTRES,
        ]);
    }

    // GET /menu-trimestral/nuevo
    public function nuevo(): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->render('menu_trimestral/form', [
            'pageTitle'  => 'Nuevo Menú Trimestral',
            'breadcrumb' => [
                ['label'=>'Menú Trimestral','url'=>APP_URL.'/menu-trimestral'],
                ['label'=>'Nuevo'],
            ],
            'menu'       => null,
            'trimestres' => MenuTrimestralModel::TRIMESTRES,
            'niveles'    => MenuTrimestralModel::NIVELES,
            'fecha_hoy'  => date('Y-m-d'),
            'anio_actual'=> (int)date('Y'),
            'accion'     => 'crear',
        ]);
    }

    // POST /menu-trimestral/nuevo
    public function guardar(): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();

        $nombre     = $this->input('nombre');
        $trimestre  = $this->inputInt('trimestre');
        $anio       = $this->inputInt('anio') ?: (int)date('Y');
        $fechaInicio= $this->input('fecha_inicio');
        $fechaFin   = $this->input('fecha_fin');

        $errores = [];
        if (empty($nombre))    $errores[] = 'El nombre es requerido.';
        if (!$trimestre)       $errores[] = 'Seleccione el trimestre.';
        if (empty($fechaInicio)) $errores[] = 'La fecha de inicio es requerida.';
        if (empty($fechaFin))  $errores[] = 'La fecha de fin es requerida.';

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/menu-trimestral/nuevo');
        }

        $id = $this->model->create([
            'anio'           => $anio,
            'trimestre'      => $trimestre,
            'nombre'         => $nombre,
            'fecha_inicio'   => $fechaInicio,
            'fecha_fin'      => $fechaFin,
            'nivel'          => $this->input('nivel') ?: 'Todos los niveles',
            'recibido_de'    => $this->input('recibido_de'),
            'fecha_recepcion'=> $this->input('fecha_recepcion') ?: null,
            'obs'            => $this->input('obs'),
            'activo'         => 1,
            'creado_por'     => Auth::id(),
        ]);

        $this->redirectWithSuccess("/menu-trimestral/{$id}",
            'Menú trimestral registrado correctamente.');
    }

    // GET /menu-trimestral/:id/editar
    public function editar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $menu = $this->model->find((int)$params['id']);
        if (!$menu) {
            $this->flash('error', 'Menú no encontrado.');
            $this->redirect('/menu-trimestral');
        }
        $this->render('menu_trimestral/form', [
            'pageTitle'  => 'Editar Menú Trimestral',
            'breadcrumb' => [
                ['label'=>'Menú Trimestral','url'=>APP_URL.'/menu-trimestral'],
                ['label'=>'Editar'],
            ],
            'menu'       => $menu,
            'trimestres' => MenuTrimestralModel::TRIMESTRES,
            'niveles'    => MenuTrimestralModel::NIVELES,
            'fecha_hoy'  => date('Y-m-d'),
            'anio_actual'=> (int)date('Y'),
            'accion'     => 'editar',
        ]);
    }

    // POST /menu-trimestral/:id/editar
    public function actualizar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id = (int)$params['id'];

        $this->model->update($id, [
            'anio'           => $this->inputInt('anio') ?: (int)date('Y'),
            'trimestre'      => $this->inputInt('trimestre'),
            'nombre'         => $this->input('nombre'),
            'fecha_inicio'   => $this->input('fecha_inicio'),
            'fecha_fin'      => $this->input('fecha_fin'),
            'nivel'          => $this->input('nivel'),
            'recibido_de'    => $this->input('recibido_de'),
            'fecha_recepcion'=> $this->input('fecha_recepcion') ?: null,
            'obs'            => $this->input('obs'),
            'activo'         => isset($_POST['activo']) ? 1 : 0,
        ]);

        $this->redirectWithSuccess("/menu-trimestral/{$id}",
            'Menú actualizado correctamente.');
    }
}