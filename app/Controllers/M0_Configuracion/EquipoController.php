<?php
namespace App\Controllers\M0_Configuracion;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Equipo;
use App\Models\LineaProduccion;

class EquipoController extends Controller
{
    private Equipo $model;
    private LineaProduccion $modelLinea;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m0_configuracion');
        $this->model      = new Equipo();
        $this->modelLinea = new LineaProduccion();
    }

    public function index(): void
    {
        $equipos = $this->model->todosConEstado();
        $alertas = $this->model->alertasCalibración();
        $this->render('m0_configuracion/equipos/index', [
            'pageTitle'  => 'Equipos y Maquinaria',
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Equipos'],
            ],
            'equipos'  => $equipos,
            'alertas'  => $alertas,
            'tipos'    => Equipo::TIPOS,
            'canWrite' => Auth::canWrite('m0_configuracion'),
        ]);
    }

    public function nuevo(): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->render('m0_configuracion/equipos/form', [
            'pageTitle'  => 'Nuevo Equipo',
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Equipos','url'=>APP_URL.'/m0/equipos'],
                ['label'=>'Nuevo'],
            ],
            'equipo'  => null,
            'lineas'  => $this->modelLinea->paraSelect(),
            'tipos'   => Equipo::TIPOS,
            'accion'  => 'crear',
        ]);
    }

    public function crear(): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $data    = $this->buildData();
        $errores = $this->validar($data);
        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/m0/equipos/nuevo');
        }
        $data['creado_por'] = Auth::id();
        $this->model->create($data);
        $this->redirectWithSuccess('/m0/equipos', "Equipo '{$data['nombre']}' creado correctamente.");
    }

    public function editar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $equipo = $this->model->find((int)$params['id']);
        if (!$equipo) { $this->flash('error','Equipo no encontrado.'); $this->redirect('/m0/equipos'); }
        $this->render('m0_configuracion/equipos/form', [
            'pageTitle'  => 'Editar: '.$equipo['nombre'],
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Equipos','url'=>APP_URL.'/m0/equipos'],
                ['label'=>'Editar'],
            ],
            'equipo'  => $equipo,
            'lineas'  => $this->modelLinea->paraSelect(),
            'tipos'   => Equipo::TIPOS,
            'accion'  => 'editar',
        ]);
    }

    public function actualizar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id      = (int)$params['id'];
        $data    = $this->buildData();
        $errores = $this->validar($data);
        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect("/m0/equipos/{$id}/editar");
        }
        $this->model->update($id, $data);
        $this->redirectWithSuccess('/m0/equipos', 'Equipo actualizado correctamente.');
    }

    private function buildData(): array
    {
        return [
            'linea_id'               => $this->inputInt('linea_id'),
            'codigo'                 => strtoupper(trim($this->input('codigo'))),
            'nombre'                 => $this->input('nombre'),
            'tipo'                   => $this->input('tipo'),
            'marca'                  => $this->input('marca'),
            'modelo'                 => $this->input('modelo'),
            'serie'                  => $this->input('serie'),
            'requiere_calibracion'   => isset($_POST['requiere_calibracion']) ? 1 : 0,
            'frecuencia_calibr_dias' => $this->inputInt('frecuencia_calibr_dias') ?: null,
            'frecuencia_mant_dias'   => $this->inputInt('frecuencia_mant_dias') ?: null,
            'observaciones'          => $this->input('observaciones'),
            'activo'                 => 1,
        ];
    }

    private function validar(array $data): array
    {
        $errores = [];
        if (!$data['linea_id'])     $errores[] = 'Seleccione la línea de producción.';
        if (empty($data['codigo'])) $errores[] = 'El código es requerido.';
        if (empty($data['nombre'])) $errores[] = 'El nombre es requerido.';
        if (empty($data['tipo']))   $errores[] = 'Seleccione el tipo de equipo.';
        return $errores;
    }
}