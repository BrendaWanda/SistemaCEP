<?php
namespace App\Controllers\M0_Configuracion;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Insumo;

class InsumoController extends Controller
{
    private Insumo $model;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m0_configuracion');
        $this->model = new Insumo();
    }

    public function index(): void
    {
        $insumos = $this->model->todosConStock();
        $this->render('m0_configuracion/insumos/index', [
            'pageTitle'  => 'Catálogo de Insumos',
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Insumos'],
            ],
            'insumos'  => $insumos,
            'tipos'    => Insumo::TIPOS,
            'canWrite' => Auth::canWrite('m0_configuracion'),
        ]);
    }

    public function nuevo(): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->render('m0_configuracion/insumos/form', [
            'pageTitle'  => 'Nuevo Insumo',
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Insumos','url'=>APP_URL.'/m0/insumos'],
                ['label'=>'Nuevo'],
            ],
            'insumo' => null,
            'tipos'  => Insumo::TIPOS,
            'accion' => 'crear',
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
            $this->redirect('/m0/insumos/nuevo');
        }
        $data['creado_por'] = Auth::id();
        $data['activo']     = 1;
        $this->model->create($data);
        $this->redirectWithSuccess('/m0/insumos', "Insumo '{$data['descripcion']}' creado correctamente.");
    }

    public function editar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $insumo = $this->model->find((int)$params['id']);
        if (!$insumo) { $this->flash('error','Insumo no encontrado.'); $this->redirect('/m0/insumos'); }
        $this->render('m0_configuracion/insumos/form', [
            'pageTitle'  => 'Editar: '.$insumo['descripcion'],
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Insumos','url'=>APP_URL.'/m0/insumos'],
                ['label'=>'Editar'],
            ],
            'insumo' => $insumo,
            'tipos'  => Insumo::TIPOS,
            'accion' => 'editar',
        ]);
    }

    public function actualizar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id      = (int)$params['id'];
        $data    = $this->buildData();
        $errores = $this->validar($data, $id);
        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect("/m0/insumos/{$id}/editar");
        }
        $this->model->update($id, $data);
        $this->redirectWithSuccess('/m0/insumos', 'Insumo actualizado correctamente.');
    }

    private function buildData(): array
    {
        return [
            'codigo'                 => strtoupper(trim($this->input('codigo'))),
            'tipo'                   => $this->input('tipo'),
            'descripcion'            => $this->input('descripcion'),
            'unidad_medida'          => $this->input('unidad_medida'),
            'esp_sabor_olor'         => $this->input('esp_sabor_olor'),
            'esp_color'              => $this->input('esp_color'),
            'esp_descripcion_fisica' => $this->input('esp_descripcion_fisica'),
            'esp_humedad_max'        => $this->inputFloat('esp_humedad_max') ?: null,
            'esp_densidad_min'       => $this->inputFloat('esp_densidad_min') ?: null,
            'esp_densidad_max'       => $this->inputFloat('esp_densidad_max') ?: null,
            'esp_ph_min'             => $this->inputFloat('esp_ph_min') ?: null,
            'esp_ph_max'             => $this->inputFloat('esp_ph_max') ?: null,
            'esp_gluten_min'         => $this->inputFloat('esp_gluten_min') ?: null,
            'esp_impurezas_max'      => $this->inputFloat('esp_impurezas_max') ?: null,
            'vida_util_referencia'   => $this->input('vida_util_referencia'),
        ];
    }

    private function validar(array $data, ?int $exceptoId = null): array
    {
        $errores = [];
        if (empty($data['codigo']))       $errores[] = 'El código es requerido.';
        if (empty($data['tipo']))         $errores[] = 'Seleccione el tipo.';
        if (empty($data['descripcion']))  $errores[] = 'La descripción es requerida.';
        if (empty($data['unidad_medida'])) $errores[] = 'La unidad de medida es requerida.';
        if ($this->model->codigoExiste($data['codigo'], $exceptoId)) {
            $errores[] = "El código '{$data['codigo']}' ya existe.";
        }
        return $errores;
    }
}