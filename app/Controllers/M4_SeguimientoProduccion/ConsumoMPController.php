<?php
namespace App\Controllers\M4_SeguimientoProduccion;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\LoteProduccion;
use App\Models\ConsumoMP;
use App\Models\Insumo;
use App\Models\StockMP;

class ConsumoMPController extends Controller
{
    private LoteProduccion $modelLote;
    private ConsumoMP      $modelConsumo;
    private Insumo         $modelInsumo;
    private StockMP        $modelStock;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m4_seguimiento');
        $this->modelLote    = new LoteProduccion();
        $this->modelConsumo = new ConsumoMP();
        $this->modelInsumo  = new Insumo();
        $this->modelStock   = new StockMP();
    }

    // GET /m4/lote/:codigo/mp
    public function index(array $params): void
    {
        $lote = $this->modelLote->porCodigo($params['codigo']);
        if (!$lote) {
            $this->flash('error', 'Lote no encontrado.');
            $this->redirect('/m4');
        }

        $consumos = $this->modelLote->consumoMP($lote['id']);
        $insumos  = $this->modelInsumo->paraSelect('materia_prima');

        $this->render('m4_seguimiento/consumo_mp', [
            'pageTitle'  => 'MP — Lote '.$lote['codigo_lote'],
            'breadcrumb' => [
                ['label' => 'Seguimiento', 'url' => APP_URL.'/m4'],
                ['label' => 'Lote '.$lote['codigo_lote'],
                'url'   => APP_URL.'/m4/lote/'.$lote['codigo_lote']],
                ['label' => 'Materia Prima'],
            ],
            'lote'     => $lote,
            'consumos' => $consumos,
            'insumos'  => $insumos,
            'canWrite' => Auth::canWrite('m4_seguimiento')
                            && $lote['estado'] === 'en_proceso',
        ]);
    }

    // POST /m4/lote/:codigo/mp/agregar
    public function agregar(array $params): void
    {
        Auth::requireWrite('m4_seguimiento');
        $this->verifyCsrf();

        $lote = $this->modelLote->porCodigo($params['codigo']);
        if (!$lote || $lote['estado'] !== 'en_proceso') {
            $this->flash('error', 'Lote no disponible para modificaciones.');
            $this->redirect('/m4');
        }

        $stockMpId = $this->inputInt('stock_mp_id');
        $cantidad  = $this->inputFloat('cantidad_usada');

        $errores = [];
        if (!$stockMpId) $errores[] = 'Seleccione el lote de stock a usar.';
        if (!$cantidad)  $errores[] = 'Ingrese la cantidad utilizada.';

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect("/m4/lote/{$params['codigo']}/mp");
        }

        $ok = $this->modelConsumo->registrar(
            $lote['id'], $stockMpId, $cantidad, Auth::id()
        );

        if (!$ok) {
            $this->flash('error',
                'Stock insuficiente o lote de stock no disponible.');
            $this->redirect("/m4/lote/{$params['codigo']}/mp");
        }

        // Registrar en trazabilidad
        $stock = $this->modelStock->find($stockMpId);
        $this->db->execute(
            "INSERT INTO trazabilidad_eventos
            (codigo_lote, tipo_evento, descripcion, tabla_origen, registro_id, usuario_id)
            VALUES (?,?,?,?,?,?)",
            [
                $lote['codigo_lote'], 'recepcion_mp',
                "MP consumida: {$cantidad} {$stock['unidad_medida']} "
                . "del lote proveedor {$stock['lote_proveedor']}.",
                'consumo_mp_por_lote', $lote['id'], Auth::id()
            ]
        );

        $this->redirectWithSuccess("/m4/lote/{$params['codigo']}/mp",
            'Materia prima registrada correctamente.');
    }

    // POST /m4/lote/:codigo/mp/:id/eliminar
    public function eliminar(array $params): void
    {
        Auth::requireWrite('m4_seguimiento');
        $this->verifyCsrf();

        $lote = $this->modelLote->porCodigo($params['codigo']);
        if (!$lote || $lote['estado'] !== 'en_proceso') {
            $this->flash('error', 'No se puede modificar este lote.');
            $this->redirect('/m4');
        }

        $ok = $this->modelConsumo->eliminarYDevolverStock((int)$params['id']);

        if (!$ok) {
            $this->flash('error', 'No se pudo eliminar el consumo.');
        } else {
            $this->flash('success', 'Consumo eliminado y stock devuelto.');
        }

        $this->redirect("/m4/lote/{$params['codigo']}/mp");
    }
}