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
            return;
        }

        // Se obtiene el registro ANTES de eliminarlo, para poder armar
        // un mensaje de trazabilidad útil (después ya no existiría).
        $consumo = $this->db->fetchOne(
            "SELECT c.*, i.descripcion AS insumo_nombre
            FROM consumo_mp_por_lote c
            JOIN insumos i ON i.id = c.insumo_id
            WHERE c.id = ?",
            [(int)$params['id']]
        );

        $ok = $this->modelConsumo->eliminarYDevolverStock((int)$params['id']);

        if (!$ok) {
            $this->flash('error', 'No se pudo eliminar el consumo.');
            $this->redirect("/m4/lote/{$params['codigo']}/mp");
            return;
        }

        // Hallazgo de auditoría: esta era la única acción de escritura en
        // los 3 controllers de M4 que no dejaba rastro en trazabilidad —
        // se podía agregar y luego quitar MP sin que quedara registro de
        // la reversión, dejando el historial del lote incompleto.
        if ($consumo) {
            $this->db->execute(
                "INSERT INTO trazabilidad_eventos
                (codigo_lote, tipo_evento, descripcion, tabla_origen, registro_id, usuario_id)
                VALUES (?,?,?,?,?,?)",
                [
                    $lote['codigo_lote'], 'recepcion_mp',
                    "MP revertida: {$consumo['cantidad_usada']} {$consumo['unidad_medida']} "
                    . "de {$consumo['insumo_nombre']} (lote proveedor {$consumo['lote_proveedor']}) "
                    . "— consumo eliminado y stock devuelto.",
                    'consumo_mp_por_lote', (int)$params['id'], Auth::id()
                ]
            );
        }

        $this->flash('success', 'Consumo eliminado y stock devuelto.');
        $this->redirect("/m4/lote/{$params['codigo']}/mp");
    }
}