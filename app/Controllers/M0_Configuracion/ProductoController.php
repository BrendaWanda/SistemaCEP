<?php
// =============================================================================
//  SIACEP — M0: Controlador de Productos
//  Archivo: app/Controllers/M0_Configuracion/ProductoController.php
// =============================================================================

namespace App\Controllers\M0_Configuracion;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Producto;
use App\Models\LineaProduccion;
use App\Models\ParametroProceso;

class ProductoController extends Controller
{
    private Producto        $model;
    private LineaProduccion $modelLinea;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m0_configuracion');
        $this->model      = new Producto();
        $this->modelLinea = new LineaProduccion();
    }

    // GET /m0/productos
    public function index(): void
    {
        $productos = $this->model->todosConLinea();
        $lineas    = $this->modelLinea->paraSelect();

        $this->render('m0_configuracion/productos/index', [
            'pageTitle'  => 'Productos',
            'breadcrumb' => [
                ['label' => 'Configuración', 'url' => APP_URL . '/m0/lineas'],
                ['label' => 'Productos'],
            ],
            'productos' => $productos,
            'lineas'    => $lineas,
            'canWrite'  => Auth::canWrite('m0_configuracion'),
        ]);
    }

    // GET /m0/productos/:id
    public function ver(array $params): void
    {
        $producto = $this->model->conDetalle((int)$params['id']);
        if (!$producto) {
            $this->flash('error', 'Producto no encontrado.');
            $this->redirect('/m0/productos');
        }

        $modelParam  = new ParametroProceso();
        $parametros  = $modelParam->porProductoAgrupado((int)$params['id']);

        $this->render('m0_configuracion/productos/ver', [
            'pageTitle'  => $producto['nombre'],
            'breadcrumb' => [
                ['label' => 'Configuración', 'url' => APP_URL . '/m0/lineas'],
                ['label' => 'Productos', 'url' => APP_URL . '/m0/productos'],
                ['label' => $producto['nombre']],
            ],
            'producto'  => $producto,
            'parametros'=> $parametros,
            'etapas'    => ParametroProceso::ETAPAS,
            'canWrite'  => Auth::canWrite('m0_configuracion'),
        ]);
    }

    // GET /m0/productos/nuevo
    public function nuevo(): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->render('m0_configuracion/productos/form', [
            'pageTitle'  => 'Nuevo Producto',
            'breadcrumb' => [
                ['label' => 'Configuración', 'url' => APP_URL . '/m0/lineas'],
                ['label' => 'Productos', 'url' => APP_URL . '/m0/productos'],
                ['label' => 'Nuevo'],
            ],
            'producto' => null,
            'lineas'   => $this->modelLinea->paraSelect(),
            'accion'   => 'crear',
        ]);
    }

    // POST /m0/productos/nuevo
    public function crear(): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();

        $data = $this->construirData();
        $errores = $this->validar($data);

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/m0/productos/nuevo');
        }

        $data['creado_por'] = Auth::id();
        $data['activo']     = 1;

        // Calcular LSE/LIE automáticamente
        if ($data['peso_nominal_g'] && $data['tolerancia_pct']) {
            $limites = $this->model->calcularLimites(
                (float)$data['peso_nominal_g'],
                (float)$data['tolerancia_pct']
            );
            $data = array_merge($data, $limites);
        }

        $id = $this->model->create($data);
        $this->redirectWithSuccess(
            "/m0/productos/{$id}",
            "Producto '{$data['nombre']}' creado. Ahora configure sus parámetros."
        );
    }

    // GET /m0/productos/:id/editar
    public function editar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $producto = $this->model->find((int)$params['id']);
        if (!$producto) {
            $this->flash('error', 'Producto no encontrado.');
            $this->redirect('/m0/productos');
        }

        $this->render('m0_configuracion/productos/form', [
            'pageTitle'  => 'Editar: ' . $producto['nombre'],
            'breadcrumb' => [
                ['label' => 'Configuración', 'url' => APP_URL . '/m0/lineas'],
                ['label' => 'Productos', 'url' => APP_URL . '/m0/productos'],
                ['label' => 'Editar'],
            ],
            'producto' => $producto,
            'lineas'   => $this->modelLinea->paraSelect(),
            'accion'   => 'editar',
        ]);
    }

    // POST /m0/productos/:id/editar
    public function actualizar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id = (int)$params['id'];

        $data = $this->construirData();
        $errores = $this->validar($data, $id);

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect("/m0/productos/{$id}/editar");
        }

        if ($data['peso_nominal_g'] && $data['tolerancia_pct']) {
            $limites = $this->model->calcularLimites(
                (float)$data['peso_nominal_g'],
                (float)$data['tolerancia_pct']
            );
            $data = array_merge($data, $limites);
        }

        $this->model->update($id, $data);
        $this->redirectWithSuccess("/m0/productos/{$id}",
            'Producto actualizado correctamente.');
    }

    // POST /m0/productos/:id/toggle
    public function toggleActivo(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id      = (int)$params['id'];
        $producto = $this->model->find($id);
        if (!$producto) $this->jsonError('No encontrado', 404);
        $nuevo = $producto['activo'] ? 0 : 1;
        $this->model->update($id, ['activo' => $nuevo]);
        $msg = $nuevo ? 'Producto activado.' : 'Producto desactivado.';
        if ($this->isAjax()) $this->jsonSuccess(['activo' => $nuevo], $msg);
        $this->redirectWithSuccess('/m0/productos', $msg);
    }

    // API GET /api/productos-por-linea?linea_id=1
    public function porLinea(): void
    {
        $lineaId = $this->inputInt('linea_id');
        $productos = $this->model->porLinea($lineaId);
        $this->jsonSuccess($productos);
    }

    // ── Helpers privados ─────────────────────────────────────────────────────
    private function construirData(): array
    {
        return [
            'linea_id'               => $this->inputInt('linea_id'),
            'codigo'                 => strtoupper(trim($this->input('codigo'))),
            'nombre'                 => $this->input('nombre'),
            'descripcion'            => $this->input('descripcion'),
            'lote_contrato'          => $this->input('lote_contrato'),
            'item_dbc'               => $this->input('item_dbc'),
            'peso_nominal_g'         => $this->inputFloat('peso_nominal_g') ?: null,
            'tolerancia_pct'         => $this->inputFloat('tolerancia_pct') ?: null,
            'unidades_por_receta'    => $this->inputInt('unidades_por_receta') ?: null,
            'unidades_por_bolsa'     => $this->inputInt('unidades_por_bolsa') ?: null,
            'bolsas_por_caja'        => $this->inputInt('bolsas_por_caja') ?: null,
            'unidades_por_caja'      => $this->inputInt('unidades_por_caja') ?: null,
            'peso_caja_kg'           => $this->inputFloat('peso_caja_kg') ?: null,
            'vida_util_dias'         => $this->inputInt('vida_util_dias') ?: null,
            'temperatura_conserv_min'=> $this->inputFloat('temperatura_conserv_min') ?: null,
            'temperatura_conserv_max'=> $this->inputFloat('temperatura_conserv_max') ?: null,
            'temperatura_entrega_max'=> $this->inputFloat('temperatura_entrega_max') ?: null,
            'ref_color'              => $this->input('ref_color'),
            'ref_olor'               => $this->input('ref_olor'),
            'ref_sabor'              => $this->input('ref_sabor'),
            'ref_textura'            => $this->input('ref_textura'),
            'ref_apariencia'         => $this->input('ref_apariencia'),
            'ref_humedad_max_pct'    => $this->inputFloat('ref_humedad_max_pct') ?: null,
            'ref_ph_min'             => $this->inputFloat('ref_ph_min') ?: null,
            'ref_ph_max'             => $this->inputFloat('ref_ph_max') ?: null,
        ];
    }

    private function validar(array $data, ?int $exceptoId = null): array
    {
        $errores = [];
        if (!$data['linea_id'])  $errores[] = 'Seleccione una línea de producción.';
        if (empty($data['codigo'])) $errores[] = 'El código es requerido.';
        if (empty($data['nombre'])) $errores[] = 'El nombre es requerido.';
        if ($this->model->codigoExiste($data['codigo'], $exceptoId)) {
            $errores[] = "El código '{$data['codigo']}' ya existe.";
        }
        if ($data['peso_nominal_g'] && $data['peso_nominal_g'] <= 0) {
            $errores[] = 'El peso nominal debe ser mayor a 0.';
        }
        return $errores;
    }
}