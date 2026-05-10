<?php
namespace App\Controllers\M0_Configuracion;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\ParametroProceso;
use App\Models\Producto;

class ParametroController extends Controller
{
    private ParametroProceso $model;
    private Producto $modelProducto;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m0_configuracion');
        $this->model         = new ParametroProceso();
        $this->modelProducto = new Producto();
    }

    public function index(array $params): void
    {
        $producto = $this->modelProducto->conDetalle((int)$params['id']);
        if (!$producto) { $this->flash('error','Producto no encontrado.'); $this->redirect('/m0/productos'); }
        $parametros = $this->model->porProductoAgrupado((int)$params['id']);
        $this->render('m0_configuracion/parametros/index', [
            'pageTitle'  => 'Parámetros — ' . $producto['nombre'],
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Productos','url'=>APP_URL.'/m0/productos'],
                ['label'=>$producto['nombre'],'url'=>APP_URL.'/m0/productos/'.$producto['id']],
                ['label'=>'Parámetros'],
            ],
            'producto'   => $producto,
            'parametros' => $parametros,
            'etapas'     => ParametroProceso::ETAPAS,
            'tipos'      => ParametroProceso::TIPOS,
            'canWrite'   => Auth::canWrite('m0_configuracion'),
        ]);
    }

    public function nuevo(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $producto = $this->modelProducto->find((int)$params['id']);
        if (!$producto) { $this->flash('error','Producto no encontrado.'); $this->redirect('/m0/productos'); }
        $this->render('m0_configuracion/parametros/form', [
            'pageTitle'  => 'Nuevo Parámetro',
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Productos','url'=>APP_URL.'/m0/productos'],
                ['label'=>$producto['nombre'],'url'=>APP_URL.'/m0/productos/'.$producto['id']],
                ['label'=>'Nuevo Parámetro'],
            ],
            'producto'  => $producto,
            'parametro' => null,
            'etapas'    => ParametroProceso::ETAPAS,
            'tipos'     => ParametroProceso::TIPOS,
            'accion'    => 'crear',
        ]);
    }

    public function crear(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $productoId = (int)$params['id'];

        $etapa = $this->input('etapa');
        $data  = [
            'producto_id'      => $productoId,
            'etapa'            => $etapa,
            'nombre'           => $this->input('nombre'),
            'unidad'           => $this->input('unidad'),
            'tipo_dato'        => $this->input('tipo_dato'),
            'valor_nominal'    => $this->inputFloat('valor_nominal') ?: null,
            'valor_min'        => $this->inputFloat('valor_min') ?: null,
            'valor_max'        => $this->inputFloat('valor_max') ?: null,
            'es_variable_spc'  => isset($_POST['es_variable_spc']) ? 1 : 0,
            'tamanio_subgrupo' => $this->inputInt('tamanio_subgrupo') ?: null,
            'opciones_json'    => $this->input('opciones_json') ?: null,
            'obligatorio'      => isset($_POST['obligatorio']) ? 1 : 0,
            'orden_display'    => $this->model->siguienteOrden($productoId, $etapa),
            'activo'           => 1,
            'creado_por'       => Auth::id(),
        ];

        $errores = [];
        if (empty($data['etapa']))  $errores[] = 'Seleccione la etapa.';
        if (empty($data['nombre'])) $errores[] = 'El nombre es requerido.';
        if (empty($data['unidad'])) $errores[] = 'La unidad es requerida.';

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect("/m0/productos/{$productoId}/parametros/nuevo");
        }

        $this->model->create($data);
        $this->redirectWithSuccess("/m0/productos/{$productoId}/parametros",
            'Parámetro creado correctamente.');
    }

    public function editar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $parametro = $this->model->find((int)$params['id']);
        if (!$parametro) { $this->flash('error','No encontrado.'); $this->redirect('/m0/productos'); }
        $producto = $this->modelProducto->find($parametro['producto_id']);
        $this->render('m0_configuracion/parametros/form', [
            'pageTitle'  => 'Editar Parámetro',
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Productos','url'=>APP_URL.'/m0/productos'],
                ['label'=>$producto['nombre'],'url'=>APP_URL.'/m0/productos/'.$producto['id']],
                ['label'=>'Editar Parámetro'],
            ],
            'producto'  => $producto,
            'parametro' => $parametro,
            'etapas'    => ParametroProceso::ETAPAS,
            'tipos'     => ParametroProceso::TIPOS,
            'accion'    => 'editar',
        ]);
    }

    public function actualizar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id        = (int)$params['id'];
        $parametro = $this->model->find($id);
        if (!$parametro) { $this->flash('error','No encontrado.'); $this->redirect('/m0/productos'); }

        $this->model->update($id, [
            'etapa'            => $this->input('etapa'),
            'nombre'           => $this->input('nombre'),
            'unidad'           => $this->input('unidad'),
            'tipo_dato'        => $this->input('tipo_dato'),
            'valor_nominal'    => $this->inputFloat('valor_nominal') ?: null,
            'valor_min'        => $this->inputFloat('valor_min') ?: null,
            'valor_max'        => $this->inputFloat('valor_max') ?: null,
            'es_variable_spc'  => isset($_POST['es_variable_spc']) ? 1 : 0,
            'tamanio_subgrupo' => $this->inputInt('tamanio_subgrupo') ?: null,
            'opciones_json'    => $this->input('opciones_json') ?: null,
            'obligatorio'      => isset($_POST['obligatorio']) ? 1 : 0,
        ]);

        $this->redirectWithSuccess(
            "/m0/productos/{$parametro['producto_id']}/parametros",
            'Parámetro actualizado correctamente.'
        );
    }

    public function eliminar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id        = (int)$params['id'];
        $parametro = $this->model->find($id);
        if (!$parametro) { $this->flash('error','No encontrado.'); $this->redirect('/m0/productos'); }
        $this->model->softDelete($id);
        $this->redirectWithSuccess(
            "/m0/productos/{$parametro['producto_id']}/parametros",
            'Parámetro eliminado.'
        );
    }

    public function porProducto(): void
    {
        $productoId = $this->inputInt('producto_id');
        $this->jsonSuccess($this->model->paraFormulario($productoId));
    }
}