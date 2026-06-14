<?php
// =============================================================================
//  SIACEP — Controller: Registro Dinámico de Proceso (M2)
//  Archivo: app/Controllers/M2_RegistroProceso/RegistroDinamicoController.php
//
//  Reemplaza a AmasadoController, HorneadoController, EnvasadoController y
//  PesosController. En lugar de columnas fijas por etapa, lee los parámetros
//  configurados en M0 (parametros_proceso) y guarda los valores de forma
//  genérica:
//
//   - guardarValores()  → parámetros con es_variable_spc = 0 (cualquier
//                          tipo_dato). Se guarda 1 registro por parámetro
//                          en reg_valores_simples, todos con la misma hora.
//
//   - guardarSubgrupo() → parámetros numéricos con es_variable_spc = 1.
//                          Se guarda 1 subgrupo de n lecturas en
//                          reg_subgrupos_spc, con cálculo automático de
//                          X̄/R/S y verificación de reglas SPC.
//
//   - datosGrafico()    → datos JSON para el gráfico X̄ en tiempo real de
//                          un parámetro SPC dentro de una sesión.
//
//  Los parámetros de atributo (es_variable_spc=1, tipo_dato seleccion/si_no)
//  siguen usando InspeccionAtributosController (ya era genérico).
// =============================================================================

namespace App\Controllers\M2_RegistroProceso;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\SesionRegistro;
use App\Models\RegistroValorSimple;
use App\Models\RegistroSubgrupoSpc;

class RegistroDinamicoController extends Controller
{
    private SesionRegistro      $modelSesion;
    private RegistroValorSimple $modelValor;
    private RegistroSubgrupoSpc $modelSubgrupo;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m2_registro_proceso');
        $this->modelSesion   = new SesionRegistro();
        $this->modelValor    = new RegistroValorSimple();
        $this->modelSubgrupo = new RegistroSubgrupoSpc();
    }

    // -------------------------------------------------------------------
    // POST /m2/sesion/:id/valores
    //
    // Espera:
    //   hora      = "HH:MM" (opcional, default = hora actual)
    //   valores[] = array asociativo [parametro_id => valor], ej:
    //               <input name="valores[12]" value="26.5">
    //               <input name="valores[13]" value="5.80">
    //
    // Para tipo_dato='si_no' se recomienda el patrón:
    //   <input type="hidden"   name="valores[14]" value="0">
    //   <input type="checkbox" name="valores[14]" value="1">
    // (si está marcado, el navegador envía '1' que sobrescribe al '0')
    // -------------------------------------------------------------------
    public function guardarValores(array $params): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $sesionId = (int)$params['id'];
        $sesion   = $this->modelSesion->find($sesionId);

        if (!$sesion || $sesion['estado'] !== 'en_proceso') {
            $this->flash('error', 'Sesión no disponible.');
            $this->redirect('/m2');
            return;
        }

        $productoId = (int)$this->db->fetchScalar(
            "SELECT producto_id FROM lotes_produccion WHERE id = ?",
            [$sesion['lote_id']]
        );

        $hora    = $this->input('hora') ?: date('H:i:s');
        $valores = $_POST['valores'] ?? [];

        if (!is_array($valores) || empty($valores)) {
            $this->flash('error', 'No se recibieron valores para guardar.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        $guardados = 0;
        $faltantes = [];

        foreach ($valores as $parametroId => $valor) {
            $parametroId = (int)$parametroId;

            // El parámetro debe pertenecer a este producto, ser NO-SPC
            // (es_variable_spc = 0) y estar activo.
            $parametro = $this->db->fetchOne(
                "SELECT id, nombre, tipo_dato, obligatorio
                 FROM parametros_proceso
                 WHERE id = ? AND producto_id = ?
                   AND es_variable_spc = 0 AND activo = 1",
                [$parametroId, $productoId]
            );
            if (!$parametro) continue;

            $valor = is_string($valor) ? trim($valor) : $valor;

            // Normalizar checkboxes 'si_no' a '1'/'0'
            if ($parametro['tipo_dato'] === 'si_no') {
                $valor = in_array($valor, ['1','si','on','true'], true) ? '1' : '0';
            }

            if ($valor === '' || $valor === null) {
                if ((int)$parametro['obligatorio'] === 1) {
                    $faltantes[] = $parametro['nombre'];
                }
                continue;
            }

            $this->modelValor->create([
                'sesion_id'         => $sesionId,
                'parametro_id'      => $parametroId,
                'hora'              => $hora,
                'valor'             => (string)$valor,
                'registrado_por_id' => Auth::id(),
            ]);
            $guardados++;
        }

        if (!empty($faltantes)) {
            $this->flash('error',
                'Faltan campos obligatorios: ' . implode(', ', $faltantes));
        }

        if ($this->isAjax()) {
            $this->jsonSuccess(
                ['guardados' => $guardados, 'faltantes' => $faltantes],
                $guardados > 0
                    ? 'Datos guardados correctamente.'
                    : 'No se guardó ningún valor.'
            );
            return;
        }

        if ($guardados > 0) {
            $this->flash('success', 'Datos guardados correctamente.');
        }
        $this->redirect("/m2/sesion/{$sesionId}");
    }

    // -------------------------------------------------------------------
    // POST /m2/sesion/:id/subgrupo
    //
    // Espera:
    //   parametro_id = id del parámetro numérico SPC (es_variable_spc=1)
    //   hora         = "HH:MM" (opcional)
    //   lecturas[]   = array con las n lecturas individuales, ej:
    //                  <input name="lecturas[]" value="40.2">
    //                  <input name="lecturas[]" value="39.8">
    //                  ... (n = parametro.tamanio_subgrupo)
    // -------------------------------------------------------------------
    public function guardarSubgrupo(array $params): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $sesionId = (int)$params['id'];
        $sesion   = $this->modelSesion->find($sesionId);

        if (!$sesion || $sesion['estado'] !== 'en_proceso') {
            $this->jsonError('Sesión no disponible.', 400);
            return;
        }

        $productoId = (int)$this->db->fetchScalar(
            "SELECT producto_id FROM lotes_produccion WHERE id = ?",
            [$sesion['lote_id']]
        );

        $parametroId = (int)($_POST['parametro_id'] ?? 0);
        $hora        = $this->input('hora') ?: date('H:i:s');
        $lecturas    = $_POST['lecturas'] ?? [];

        // El parámetro debe pertenecer a este producto, ser numérico SPC
        // (es_variable_spc=1) y estar activo.
        $parametro = $this->db->fetchOne(
            "SELECT * FROM parametros_proceso
             WHERE id = ? AND producto_id = ?
               AND es_variable_spc = 1 AND tipo_dato = 'numerico'
               AND activo = 1",
            [$parametroId, $productoId]
        );
        if (!$parametro) {
            $this->jsonError('Parámetro SPC no válido para este producto.', 400);
            return;
        }

        if (!is_array($lecturas)) $lecturas = [];
        $lecturas = array_values(array_filter(
            $lecturas, fn($v) => $v !== '' && $v !== null
        ));

        if (empty($lecturas)) {
            $this->jsonError('Ingrese al menos una lectura.', 400);
            return;
        }

        // Límites: primero spc_limites_control específicos del parámetro;
        // si no existen, usar valor_min/valor_max/valor_nominal definidos
        // en M0 como límites provisionales.
        $limites = $this->db->fetchOne(
            "SELECT ucl_xbar, lcl_xbar, cl_xbar FROM spc_limites_control
             WHERE parametro_id = ? AND vigente = 1
             ORDER BY calculado_en DESC LIMIT 1",
            [$parametroId]
        );
        if (!$limites) {
            $limites = [
                'ucl_xbar' => $parametro['valor_max'],
                'lcl_xbar' => $parametro['valor_min'],
                'cl_xbar'  => $parametro['valor_nominal'],
            ];
        }

        $id = $this->modelSubgrupo->registrarSubgrupo(
            $sesionId, $parametroId, $hora, $lecturas, $limites, Auth::id()
        );

        $registro = $this->modelSubgrupo->find($id);
        $registro['valores'] = json_decode($registro['valores'], true);

        // Señal SPC → registrar en spc_senales_detectadas
        if ($registro['fuera_de_control']) {
            $this->db->execute(
                "INSERT INTO spc_senales_detectadas
                    (lote_id, sesion_id, registro_peso_id, producto_id,
                     tipo_grafico, regla_western_electric, descripcion_regla,
                     valor_detectado, estado)
                 VALUES (?,?,?,?,'xbar',1,?,?, 'nueva')",
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
            'id'               => $id,
            'parametro_id'     => $parametroId,
            'parametro_nombre' => $parametro['nombre'],
            'promedio_xbar'    => $registro['promedio_xbar'],
            'rango_r'          => $registro['rango_r'],
            'fuera_de_control' => (bool)$registro['fuera_de_control'],
            'regla_violada'    => $registro['regla_violada'],
            'ucl_xbar'         => (float)$limites['ucl_xbar'],
            'lcl_xbar'         => (float)$limites['lcl_xbar'],
            'cl_xbar'          => (float)$limites['cl_xbar'],
        ], $registro['fuera_de_control']
            ? '⚠️ SEÑAL DETECTADA: ' . $registro['regla_violada']
            : 'Subgrupo registrado correctamente.');
    }

    // -------------------------------------------------------------------
    // GET /m2/sesion/:id/subgrupo/:parametro_id/datos
    // JSON para el gráfico X̄ en tiempo real de un parámetro SPC.
    // -------------------------------------------------------------------
    public function datosGrafico(array $params): void
    {
        $sesionId    = (int)$params['id'];
        $parametroId = (int)$params['parametro_id'];

        $sesion = $this->modelSesion->find($sesionId);
        if (!$sesion) {
            $this->jsonError('Sesión no encontrada.', 404);
            return;
        }

        $parametro = $this->db->fetchOne(
            "SELECT valor_min, valor_max, valor_nominal
             FROM parametros_proceso WHERE id = ?",
            [$parametroId]
        );

        $limites = $this->db->fetchOne(
            "SELECT ucl_xbar, lcl_xbar, cl_xbar FROM spc_limites_control
             WHERE parametro_id = ? AND vigente = 1
             ORDER BY calculado_en DESC LIMIT 1",
            [$parametroId]
        );
        if (!$limites) {
            $limites = [
                'ucl_xbar' => (float)($parametro['valor_max']     ?? 0),
                'lcl_xbar' => (float)($parametro['valor_min']     ?? 0),
                'cl_xbar'  => (float)($parametro['valor_nominal'] ?? 0),
            ];
        }

        $datos = $this->modelSubgrupo->datosGrafico(
            $sesionId, $parametroId,
            (float)$limites['ucl_xbar'],
            (float)$limites['lcl_xbar'],
            (float)$limites['cl_xbar']
        );

        $this->jsonSuccess(array_merge($datos, ['limites' => $limites]));
    }
}