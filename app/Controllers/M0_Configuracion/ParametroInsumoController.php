<?php
namespace App\Controllers\M0_Configuracion;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\ParametroInsumo;
use App\Models\Insumo;

class ParametroInsumoController extends Controller
{
    private ParametroInsumo $model;
    private Insumo $modelInsumo;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m0_configuracion');
        $this->model       = new ParametroInsumo();
        $this->modelInsumo = new Insumo();
    }

    // GET /m0/insumos/:id/parametros
    public function index(array $params): void
    {
        $insumo = $this->modelInsumo->find((int)$params['id']);
        if (!$insumo) { $this->flash('error','Insumo no encontrado.'); $this->redirect('/m0/insumos'); return; }
        $parametros = $this->model->porInsumo((int)$params['id']);
        $this->render('m0_configuracion/parametros_insumo/index', [
            'pageTitle'  => 'Parámetros — ' . $insumo['descripcion'],
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Insumos','url'=>APP_URL.'/m0/insumos'],
                ['label'=>$insumo['descripcion'],'url'=>APP_URL.'/m0/insumos/'.$insumo['id']],
                ['label'=>'Parámetros'],
            ],
            'insumo'     => $insumo,
            'parametros' => $parametros,
            'tipos'      => ParametroInsumo::TIPOS,
            'canWrite'   => Auth::canWrite('m0_configuracion'),
        ]);
    }

    // GET /m0/insumos/:id/parametros/nuevo
    public function nuevo(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $insumo = $this->modelInsumo->find((int)$params['id']);
        if (!$insumo) { $this->flash('error','Insumo no encontrado.'); $this->redirect('/m0/insumos'); return; }
        $this->render('m0_configuracion/parametros_insumo/form', [
            'pageTitle'  => 'Nuevo Parámetro',
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Insumos','url'=>APP_URL.'/m0/insumos'],
                ['label'=>$insumo['descripcion'],'url'=>APP_URL.'/m0/insumos/'.$insumo['id']],
                ['label'=>'Nuevo Parámetro'],
            ],
            'insumo'    => $insumo,
            'parametro' => null,
            'tipos'     => ParametroInsumo::TIPOS,
            'accion'    => 'crear',
        ]);
    }

    // POST /m0/insumos/:id/parametros/nuevo
    public function crear(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $insumoId = (int)$params['id'];

        $data = [
            'insumo_id'     => $insumoId,
            'nombre'        => $this->input('nombre'),
            'unidad'        => $this->input('unidad'),
            'tipo_dato'     => $this->input('tipo_dato'),
            'valor_min'     => $this->floatOrNull('valor_min'),
            'valor_max'     => $this->floatOrNull('valor_max'),
            'opciones_json' => $this->input('opciones_json') ?: null,
            'obligatorio'   => isset($_POST['obligatorio']) ? 1 : 0,
            'orden_display' => $this->model->siguienteOrden($insumoId),
            'activo'        => 1,
            'creado_por'    => Auth::id(),
        ];

        $errores = $this->validar($data);

        // Mismo patrón AJAX-aware que ParametroController — sin esto,
        // un error de validación aquí quedaría invisible si el
        // formulario termina usando fetch() como el de M0/productos.
        if (!empty($errores)) {
            if ($this->isAjax()) {
                $this->jsonError(implode(' ', $errores), 422);
                return;
            }
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect("/m0/insumos/{$insumoId}/parametros/nuevo");
            return;
        }

        $this->model->create($data);

        if ($this->isAjax()) {
            $this->jsonSuccess(null, 'Parámetro creado correctamente.');
            return;
        }
        $this->redirectWithSuccess("/m0/insumos/{$insumoId}/parametros",
            'Parámetro creado correctamente.');
    }

    // GET /m0/parametros-insumo/:id/editar
    public function editar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $parametro = $this->model->find((int)$params['id']);
        if (!$parametro) { $this->flash('error','No encontrado.'); $this->redirect('/m0/insumos'); return; }
        $insumo = $this->modelInsumo->find($parametro['insumo_id']);
        $this->render('m0_configuracion/parametros_insumo/form', [
            'pageTitle'  => 'Editar Parámetro',
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Insumos','url'=>APP_URL.'/m0/insumos'],
                ['label'=>$insumo['descripcion'],'url'=>APP_URL.'/m0/insumos/'.$insumo['id']],
                ['label'=>'Editar Parámetro'],
            ],
            'insumo'    => $insumo,
            'parametro' => $parametro,
            'tipos'     => ParametroInsumo::TIPOS,
            'accion'    => 'editar',
        ]);
    }

    // POST /m0/parametros-insumo/:id/editar
    public function actualizar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id        = (int)$params['id'];
        $parametro = $this->model->find($id);
        if (!$parametro) { $this->flash('error','No encontrado.'); $this->redirect('/m0/insumos'); return; }

        $data = [
            'nombre'        => $this->input('nombre'),
            'unidad'        => $this->input('unidad'),
            'tipo_dato'     => $this->input('tipo_dato'),
            'valor_min'     => $this->floatOrNull('valor_min'),
            'valor_max'     => $this->floatOrNull('valor_max'),
            'opciones_json' => $this->input('opciones_json') ?: null,
            'obligatorio'   => isset($_POST['obligatorio']) ? 1 : 0,
        ];

        $errores = $this->validar($data);

        if (!empty($errores)) {
            if ($this->isAjax()) {
                $this->jsonError(implode(' ', $errores), 422);
                return;
            }
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect("/m0/parametros-insumo/{$id}/editar");
            return;
        }

        $this->model->update($id, $data);

        if ($this->isAjax()) {
            $this->jsonSuccess(null, 'Parámetro actualizado correctamente.');
            return;
        }
        $this->redirectWithSuccess(
            "/m0/insumos/{$parametro['insumo_id']}/parametros",
            'Parámetro actualizado correctamente.'
        );
    }

    // POST /m0/parametros-insumo/:id/eliminar
    //
    // Mismo patrón que ParametroController::eliminar() — softDelete,
    // no borrado físico, porque un parámetro puede tener resultados ya
    // registrados en recepciones anteriores (recepcion_resultados
    // referencia parametro_insumo_id) y borrarlo de verdad rompería
    // ese historial.
    public function eliminar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id        = (int)$params['id'];
        $parametro = $this->model->find($id);
        if (!$parametro) { $this->flash('error','No encontrado.'); $this->redirect('/m0/insumos'); return; }
        $this->model->softDelete($id);
        $this->redirectWithSuccess(
            "/m0/insumos/{$parametro['insumo_id']}/parametros",
            'Parámetro eliminado.'
        );
    }

    // API GET /api/parametros-insumo?insumo_id=1
    // Usado por M1 (nueva recepción) para armar el formulario dinámico
    // según el insumo seleccionado.
    public function porInsumo(): void
    {
        $insumoId = $this->inputInt('insumo_id');
        $this->jsonSuccess($this->model->porInsumo($insumoId));
    }

    private function floatOrNull(string $key): ?float
    {
        $raw = $_POST[$key] ?? null;
        if ($raw === null || trim((string)$raw) === '') {
            return null;
        }
        return (float) str_replace(',', '.', (string) $raw);
    }

    private function validar(array $data): array
    {
        $errores = [];
        if (empty($data['nombre'])) $errores[] = 'El nombre es requerido.';
        if (empty($data['unidad']) && $data['unidad'] !== '0') {
            // La unidad puede ser legítimamente una cadena vacía (ej.
            // pH no tiene unidad) — solo se exige que el campo llegue,
            // no que tenga texto.
        }
        if (!in_array($data['tipo_dato'], array_keys(ParametroInsumo::TIPOS), true)) {
            $errores[] = 'Tipo de dato inválido.';
        }
        if ($data['valor_min'] !== null && $data['valor_max'] !== null
            && $data['valor_min'] >= $data['valor_max']) {
            $errores[] = 'El valor mínimo debe ser menor al valor máximo.';
        }
        return $errores;
    }
}