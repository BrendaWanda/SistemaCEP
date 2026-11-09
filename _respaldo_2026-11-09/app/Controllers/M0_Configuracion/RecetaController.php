<?php
namespace App\Controllers\M0_Configuracion;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Receta;
use App\Models\Producto;
use App\Models\Insumo;

class RecetaController extends Controller
{
    private Receta   $model;
    private Producto $modelProducto;
    private Insumo   $modelInsumo;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m0_configuracion');
        $this->model         = new Receta();
        $this->modelProducto = new Producto();
        $this->modelInsumo   = new Insumo();
    }

    public function index(): void
    {
        $recetas = $this->model->todasConProducto();
        $this->render('m0_configuracion/recetas/index', [
            'pageTitle'  => 'Recetas (BOM)',
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Recetas'],
            ],
            'recetas'  => $recetas,
            'canWrite' => Auth::canWrite('m0_configuracion'),
        ]);
    }

    public function ver(array $params): void
    {
        $receta = $this->model->conIngredientes((int)$params['id']);
        if (!$receta) {
            $this->flash('error','Receta no encontrada.');
            $this->redirect('/m0/recetas');
        }
        $this->render('m0_configuracion/recetas/ver', [
            'pageTitle'  => $receta['nombre'],
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Recetas','url'=>APP_URL.'/m0/recetas'],
                ['label'=>$receta['nombre']],
            ],
            'receta'   => $receta,
            'canWrite' => Auth::canWrite('m0_configuracion'),
        ]);
    }

    public function nueva(): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->render('m0_configuracion/recetas/form', [
            'pageTitle'  => 'Nueva Receta',
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Recetas','url'=>APP_URL.'/m0/recetas'],
                ['label'=>'Nueva'],
            ],
            'receta'    => null,
            'productos' => $this->modelProducto->todosConLinea(),
            'insumos'   => $this->modelInsumo->paraSelect(),
            'accion'    => 'crear',
        ]);
    }

    public function crear(): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();

        $productoId = $this->inputInt('producto_id');
        $nombre     = $this->input('nombre');
        $desc       = $this->input('descripcion');
        $insumoIds  = $_POST['insumo_id']  ?? [];
        $cantidades = $_POST['cantidad']   ?? [];
        $unidades   = $_POST['unidad']     ?? [];
        $criticos   = $_POST['es_critico'] ?? [];

        $errores = [];
        if (!$productoId)      $errores[] = 'Seleccione un producto.';
        if (empty($nombre))    $errores[] = 'El nombre es requerido.';
        if (empty($insumoIds)) $errores[] = 'Agregue al menos un ingrediente.';

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/m0/recetas/nueva');
        }

        $version  = $this->model->siguienteVersion($productoId);

        // Hallazgo de auditoría: antes la receta se creaba y los
        // ingredientes se insertaban en un loop suelto, sin transacción.
        // Si un ingrediente fallaba a mitad del loop (ej. insumo_id
        // inválido), quedaba una receta guardada con una lista de
        // ingredientes incompleta y sin ningún aviso.
        $recetaId = $this->db->transaction(function($db) use (
            $productoId, $version, $nombre, $desc, $insumoIds, $cantidades, $unidades, $criticos
        ) {
            $recetaId = $this->model->create([
                'producto_id' => $productoId,
                'version'     => $version,
                'nombre'      => $nombre,
                'descripcion' => $desc,
                'vigente'     => 1,
                'creado_por'  => Auth::id(),
            ]);

            foreach ($insumoIds as $i => $insumoId) {
                if (empty($insumoId) || empty($cantidades[$i])) continue;
                $db->execute(
                    "INSERT INTO receta_insumos
                    (receta_id, insumo_id, cantidad, unidad_medida, es_critico)
                    VALUES (?, ?, ?, ?, ?)",
                    [
                        $recetaId,
                        (int)$insumoId,
                        (float)str_replace(',', '.', $cantidades[$i]),
                        $unidades[$i] ?? 'kg',
                        isset($criticos[$i]) ? 1 : 0,
                    ]
                );
            }

            return $recetaId;
        });

        $this->redirectWithSuccess("/m0/recetas/{$recetaId}",
            "Receta '{$nombre}' v{$version} creada correctamente.");
    }

    public function editar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $receta = $this->model->conIngredientes((int)$params['id']);
        if (!$receta) {
            $this->flash('error','Receta no encontrada.');
            $this->redirect('/m0/recetas');
        }

        // Hallazgo de auditoría: Insumo::paraSelect() solo trae insumos
        // activos. Si un ingrediente de ESTA receta usa un insumo que
        // luego fue desactivado, su <option> no existía en la lista y el
        // <select> quedaba sin selección — al guardar sin darse cuenta,
        // ese ingrediente se borraba silenciosamente (ver RecetaController
        // ::actualizar(), que descarta filas con insumo_id vacío). Se
        // agregan aquí, marcados como "(INACTIVO)", los insumos ya usados
        // en la receta aunque estén desactivados — para NUEVOS
        // ingredientes solo se puede elegir entre los activos, que es lo
        // correcto.
        $insumos = $this->modelInsumo->paraSelect();
        foreach ($receta['ingredientes'] as $ing) {
            $insId = (int)$ing['insumo_id'];
            if (!isset($insumos[$insId])) {
                $insInactivo = $this->modelInsumo->find($insId);
                if ($insInactivo) {
                    $insumos[$insId] = $insInactivo['codigo'] . ' — '
                        . $insInactivo['descripcion'] . ' (INACTIVO)';
                }
            }
        }

        $this->render('m0_configuracion/recetas/form', [
            'pageTitle'  => 'Editar: '.$receta['nombre'],
            'breadcrumb' => [
                ['label'=>'Configuración','url'=>APP_URL.'/m0/lineas'],
                ['label'=>'Recetas','url'=>APP_URL.'/m0/recetas'],
                ['label'=>'Editar'],
            ],
            'receta'    => $receta,
            'productos' => $this->modelProducto->todosConLinea(),
            'insumos'   => $insumos,
            'accion'    => 'editar',
        ]);
    }

    public function actualizar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id = (int)$params['id'];

        $insumoIds  = $_POST['insumo_id']  ?? [];
        $cantidades = $_POST['cantidad']   ?? [];
        $unidades   = $_POST['unidad']     ?? [];
        $criticos   = $_POST['es_critico'] ?? [];

        // Hallazgo de auditoría: "borrar todo y reinsertar" (patrón para
        // reemplazar la lista completa de ingredientes) es exactamente el
        // caso donde la falta de transacción es más peligrosa — si el
        // DELETE tenía éxito pero un INSERT posterior fallaba, la receta
        // quedaba con CERO ingredientes en vez de la lista original.
        $this->db->transaction(function($db) use (
            $id, $insumoIds, $cantidades, $unidades, $criticos
        ) {
            $this->model->update($id, [
                'nombre'      => $this->input('nombre'),
                'descripcion' => $this->input('descripcion'),
                'vigente'     => isset($_POST['vigente']) ? 1 : 0,
            ]);

            $db->execute("DELETE FROM receta_insumos WHERE receta_id = ?", [$id]);

            foreach ($insumoIds as $i => $insumoId) {
                if (empty($insumoId) || empty($cantidades[$i])) continue;
                $db->execute(
                    "INSERT INTO receta_insumos
                    (receta_id, insumo_id, cantidad, unidad_medida, es_critico)
                    VALUES (?, ?, ?, ?, ?)",
                    [
                        $id,
                        (int)$insumoId,
                        (float)str_replace(',', '.', $cantidades[$i]),
                        $unidades[$i] ?? 'kg',
                        isset($criticos[$i]) ? 1 : 0,
                    ]
                );
            }
        });

        $this->redirectWithSuccess("/m0/recetas/{$id}", 'Receta actualizada correctamente.');
    }

    // POST /m0/recetas/:id/eliminar ← NUEVO
    public function eliminar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id     = (int)$params['id'];
        $receta = $this->model->find($id);

        if (!$receta) {
            $this->flash('error', 'Receta no encontrada.');
            $this->redirect('/m0/recetas');
        }

        // Verificar que no tenga lotes asociados
        $tieneUso = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM lotes_produccion WHERE receta_id = ?", [$id]
        );
        if ($tieneUso > 0) {
            $this->flash('error',
                "No se puede eliminar '{$receta['nombre']}' — tiene {$tieneUso} lote(s) asociado(s). Márquela como obsoleta en su lugar.");
            $this->redirect('/m0/recetas');
        }

        $nombre = $receta['nombre'];
        $this->db->transaction(function($db) use ($id) {
            $db->execute("DELETE FROM receta_insumos WHERE receta_id = ?", [$id]);
            $this->model->delete($id);
        });
        $this->redirectWithSuccess('/m0/recetas',
            "Receta '{$nombre}' eliminada correctamente.");
    }

    // API GET /api/recetas-por-producto?producto_id=1
    public function porProducto(): void
    {
        $productoId = $this->inputInt('producto_id');
        if (!$productoId) { $this->jsonSuccess([]); return; }

        $recetas = $this->db->fetchAll(
            "SELECT id, CONCAT(nombre, ' v', version) AS label
            FROM recetas
            WHERE producto_id = ? AND vigente = 1
            ORDER BY version DESC",
            [$productoId]
        );

        $this->jsonSuccess(array_column($recetas, 'label', 'id'));
    }
}