<?php
namespace App\Controllers\M2_RegistroProceso;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\RegistroPesos;
use App\Models\SesionRegistro;

class PesosController extends Controller
{
    private RegistroPesos  $model;
    private SesionRegistro $modelSesion;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m2_registro_proceso');
        $this->model       = new RegistroPesos();
        $this->modelSesion = new SesionRegistro();
    }

    // POST /m2/sesion/:id/pesos
    public function guardar(array $params): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $sesionId = (int)$params['id'];
        $sesion   = $this->modelSesion->find($sesionId);

        if (!$sesion || $sesion['estado'] !== 'en_proceso') {
            $this->jsonError('Sesión no disponible.', 400);
        }

        // Recoger los 10 pesos
        $pesos = [];
        for ($i = 1; $i <= 10; $i++) {
            $key = 'peso_'.str_pad($i, 2, '0', STR_PAD_LEFT);
            $val = $_POST[$key] ?? '';
            $pesos[$key] = ($val !== '') ? (float)$val : null;
        }

        // Obtener límites de control vigentes para el producto
        // ✅ CORRECTO
        $productoId = $this->db->fetchScalar(
            "SELECT producto_id FROM lotes_produccion WHERE id = ?",
            [$sesion['lote_id']]
        );

        // Intentar obtener límites calculados, si no usar DBC
        $limites = $this->db->fetchOne(
            "SELECT ucl_xbar, lcl_xbar, cl_xbar
            FROM spc_limites_control
            WHERE producto_id = ? AND vigente = 1
            ORDER BY calculado_en DESC LIMIT 1",
            [$productoId]
        );

        if (!$limites) {
            // Usar LSE/LIE del producto como límites provisionales
            $producto = $this->db->fetchOne(
                "SELECT peso_nominal_g, lse_g, lie_g
                FROM productos WHERE id = ?",
                [$productoId]
            );
            $limites = [
                'ucl_xbar' => (float)($producto['lse_g'] ?? 0),
                'lcl_xbar' => (float)($producto['lie_g'] ?? 0),
                'cl_xbar'  => (float)($producto['peso_nominal_g'] ?? 0),
            ];
        }

        $data = array_merge($pesos, [
            'sesion_id'        => $sesionId,
            'hora'             => $this->input('hora') ?: date('H:i:s'),
            'etapa'            => $this->input('etapa') ?: 'formado_boleado',
            'operario_id'      => $this->inputInt('operario_id') ?: null,
            'observaciones'    => $this->input('observaciones'),
            'registrado_por_id'=> Auth::id(),
        ]);

        $id = $this->model->registrarSubgrupo($data, $sesionId, $limites);

        // Obtener el registro guardado para devolver al cliente
        $registro = $this->model->find($id);

        // Si hay señal, guardar en spc_senales_detectadas
        if ($registro['fuera_de_control']) {
            $this->db->execute(
                "INSERT INTO spc_senales_detectadas
                (lote_id, sesion_id, registro_peso_id, producto_id,
                tipo_grafico, regla_western_electric, descripcion_regla,
                valor_detectado, estado)
                VALUES (?,?,?,?,'xbar',1,?,?,  'nueva')",
                [
                    $sesion['lote_id'],
                    $sesionId,
                    $id,
                    $productoId,
                    $registro['regla_violada'] ?? 'Señal detectada',
                    $registro['promedio_xbar'],
                ]
            );
        }

        $this->jsonSuccess([
            'id'              => $id,
            'promedio_xbar'   => $registro['promedio_xbar'],
            'rango_r'         => $registro['rango_r'],
            'fuera_de_control'=> (bool)$registro['fuera_de_control'],
            'regla_violada'   => $registro['regla_violada'],
            'ucl_xbar'        => $limites['ucl_xbar'],
            'lcl_xbar'        => $limites['lcl_xbar'],
            'cl_xbar'         => $limites['cl_xbar'],
        ], $registro['fuera_de_control']
            ? '⚠️ SEÑAL DETECTADA: '.$registro['regla_violada']
            : 'Subgrupo registrado correctamente.');
    }

    // GET /m2/sesion/:id/pesos/datos — JSON para gráfico en tiempo real
    public function datos(array $params): void
    {
        $sesionId = (int)$params['id'];
        $sesion   = $this->modelSesion->find($sesionId);
        if (!$sesion) $this->jsonError('Sesión no encontrada.', 404);

        $productoId = $this->db->fetchScalar(
            "SELECT producto_id FROM lotes_produccion WHERE id = ?",
            [$sesion['lote_id']]
        );

        $limites = $this->db->fetchOne(
            "SELECT ucl_xbar, lcl_xbar, cl_xbar, ucl_r, cl_r, lcl_r
            FROM spc_limites_control
            WHERE producto_id = ? AND vigente = 1
            ORDER BY calculado_en DESC LIMIT 1",
            [$productoId]
        );

        if (!$limites) {
            $producto = $this->db->fetchOne(
                "SELECT peso_nominal_g, lse_g, lie_g FROM productos WHERE id = ?",
                [$productoId]
            );
            $limites = [
                'ucl_xbar' => (float)($producto['lse_g'] ?? 0),
                'lcl_xbar' => (float)($producto['lie_g'] ?? 0),
                'cl_xbar'  => (float)($producto['peso_nominal_g'] ?? 0),
                'ucl_r'    => null, 'cl_r' => null, 'lcl_r' => null,
            ];
        }

        $datos = $this->model->datosGrafico(
            $sesionId,
            (float)$limites['ucl_xbar'],
            (float)$limites['lcl_xbar'],
            (float)$limites['cl_xbar']
        );

        $this->jsonSuccess(array_merge($datos, ['limites' => $limites]));
    }
}