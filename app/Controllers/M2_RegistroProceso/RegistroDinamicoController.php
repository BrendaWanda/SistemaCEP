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
        $recibidos = []; // parametro_id => true, para detectar obligatorios que ni siquiera llegaron en el POST

        foreach ($valores as $parametroId => $valor) {
            $parametroId = (int)$parametroId;
            $recibidos[$parametroId] = true;

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

            // Validación numérica estricta: solo dígitos, un decimal, y un
            // tamaño razonable (máx. 6 dígitos enteros, 4 decimales). Evita
            // que se guarde texto en un campo numérico o un número
            // desproporcionado por typeo (ej. "4000" en vez de "40.0").
            if ($parametro['tipo_dato'] === 'numerico') {
                $num = $this->validarNumero($valor);
                if ($num === null) {
                    $faltantes[] = $parametro['nombre'] . ' (valor no numérico o fuera de rango)';
                    continue;
                }
                $valor = (string)$num;
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

        // Hallazgo G de la auditoría: un parámetro obligatorio que ni
        // siquiera llegó en el POST (campo eliminado del HTML por error,
        // formulario roto, etc.) antes pasaba desapercibido porque el
        // foreach solo evaluaba lo que SÍ llegó. Aquí se compara contra
        // la lista completa de obligatorios configurados en M0.
        $obligatoriosProducto = $this->db->fetchAll(
            "SELECT id, nombre FROM parametros_proceso
            WHERE producto_id = ? AND es_variable_spc = 0
                AND activo = 1 AND obligatorio = 1",
            [$productoId]
        );
        foreach ($obligatoriosProducto as $op) {
            if (!isset($recibidos[(int)$op['id']])) {
                $faltantes[] = $op['nombre'] . ' (no incluido en el formulario)';
            }
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
    //
    // Responde JSON si la petición es AJAX (X-Requested-With), y si no
    // hace flash + redirect a la sesión (evita mostrar el JSON crudo).
    // -------------------------------------------------------------------
    public function guardarSubgrupo(array $params): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $sesionId = (int)$params['id'];
        $sesion   = $this->modelSesion->find($sesionId);

        if (!$sesion || $sesion['estado'] !== 'en_proceso') {
            if ($this->isAjax()) {
                $this->jsonError('Sesión no disponible.', 400);
                return;
            }
            $this->flash('error', 'Sesión no disponible.');
            $this->redirect('/m2');
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
            if ($this->isAjax()) {
                $this->jsonError('Parámetro SPC no válido para este producto.', 400);
                return;
            }
            $this->flash('error', 'Parámetro SPC no válido para este producto.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        if (!is_array($lecturas)) $lecturas = [];
        $lecturasCrudas = array_values(array_filter(
            $lecturas, fn($v) => $v !== '' && $v !== null
        ));

        if (empty($lecturasCrudas)) {
            if ($this->isAjax()) {
                $this->jsonError('Ingrese al menos una lectura.', 400);
                return;
            }
            $this->flash('error', 'Ingrese al menos una lectura.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        // Validación numérica estricta: cada lectura debe ser un número
        // real (nunca letras), con un tamaño razonable (máx. 6 dígitos
        // enteros, 4 decimales) — evita valores absurdos por typeo.
        $lecturas = [];
        foreach ($lecturasCrudas as $v) {
            $num = $this->validarNumero($v);
            if ($num === null) {
                $msg = 'La lectura "' . htmlspecialchars((string)$v) . '" no es un número válido.';
                if ($this->isAjax()) { $this->jsonError($msg, 422); return; }
                $this->flash('error', $msg);
                $this->redirect("/m2/sesion/{$sesionId}");
                return;
            }
            $lecturas[] = $num;
        }

        // El tamaño del subgrupo debe coincidir con lo configurado en M0
        // (parametro.tamanio_subgrupo) — una carta X̄-R exige n constante.
        $tamanioEsperado = (int)$parametro['tamanio_subgrupo'];
        if ($tamanioEsperado > 0 && count($lecturas) !== $tamanioEsperado) {
            $msg = "Se esperaban {$tamanioEsperado} lectura(s) para este parámetro, se recibieron " . count($lecturas) . '.';
            if ($this->isAjax()) { $this->jsonError($msg, 422); return; }
            $this->flash('error', $msg);
            $this->redirect("/m2/sesion/{$sesionId}");
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

        // Hallazgo F de la auditoría: antes el subgrupo y su señal SPC se
        // insertaban en dos pasos sueltos — si el segundo fallaba, quedaba
        // un subgrupo fuera de control sin señal asociada. Ahora ambos
        // pasos van en una sola transacción: o se guardan los dos, o no
        // se guarda ninguno.
        $id = $this->db->transaction(function($db) use (
            $sesionId, $parametroId, $hora, $lecturas, $limites, $sesion, $productoId
        ) {
            $id = $this->modelSubgrupo->registrarSubgrupo(
                $sesionId, $parametroId, $hora, $lecturas, $limites, Auth::id()
            );

            $registro = $this->modelSubgrupo->find($id);

            if ($registro['fuera_de_control']) {
                $db->execute(
                    "INSERT INTO spc_senales_detectadas
                        (lote_id, sesion_id, registro_peso_id, producto_id,
                        tipo_grafico, regla_western_electric, descripcion_regla,
                        valor_detectado, estado)
                    VALUES (?,?,?,?,'xbar',?,?,?, 'nueva')",
                    [
                        $sesion['lote_id'],
                        $sesionId,
                        $id,
                        $productoId,
                        $this->extraerNumeroRegla($registro['regla_violada']),
                        $registro['regla_violada'] ?? 'Señal detectada',
                        $registro['promedio_xbar'],
                    ]
                );
            }

            return $id;
        });

        $registro = $this->modelSubgrupo->find($id);
        $registro['valores'] = json_decode($registro['valores'], true);

        $mensaje = $registro['fuera_de_control']
            ? 'ATENCIÓN — SEÑAL DETECTADA: ' . $registro['regla_violada']
            : 'Subgrupo registrado correctamente.';

        if ($this->isAjax()) {
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
            ], $mensaje);
            return;
        }

        $this->flash('success', $mensaje);
        $this->redirect("/m2/sesion/{$sesionId}");
    }

    // -------------------------------------------------------------------
    // Validación numérica estricta compartida por los 4 métodos nuevos.
    // Exige: solo dígitos y un único punto decimal (nunca letras ni texto
    // mezclado), máximo 6 dígitos enteros y 4 decimales (evita valores
    // absurdamente grandes por typeo, ej. "40000" en vez de "40.00").
    // Devuelve el float ya validado, o null si no pasa la validación.
    // -------------------------------------------------------------------
    private function validarNumero(mixed $valor): ?float
    {
        if (!is_string($valor) && !is_numeric($valor)) return null;
        $valor = trim((string)$valor);
        if ($valor === '') return null;

        // Acepta coma o punto decimal (el operador puede escribir cualquiera)
        $valor = str_replace(',', '.', $valor);

        if (!preg_match('/^-?\d{1,6}(\.\d{1,4})?$/', $valor)) {
            return null;
        }
        return (float)$valor;
    }

    // -------------------------------------------------------------------
    // Extrae el número de la primera regla de Nelson detectada desde el
    // texto descriptivo (ej. "Regla 1 (Punto fuera de UCL...), 6 (...)"
    // → 1). spc_senales_detectadas.regla_western_electric es un tinyint
    // único, así que se guarda la más relevante (la de menor número,
    // por convención la primera evaluada); el texto completo con todas
    // las reglas que se dispararon queda en descripcion_regla.
    // -------------------------------------------------------------------
    private function extraerNumeroRegla(?string $texto): int
    {
        if ($texto && preg_match('/Regla (\d+)/', $texto, $m)) {
            return (int)$m[1];
        }
        return 1;
    }

    // -------------------------------------------------------------------
    // POST /m2/sesion/:id/subgrupo/:subgrupo_id/editar
    // Corrige las lecturas de un subgrupo ya guardado (typeo humano).
    // Recalcula x̄/R/S y sincroniza la señal SPC asociada:
    //   - si ya no está fuera de control  → borra la señal 'nueva' asociada
    //   - si sigue fuera de control       → actualiza el valor/descripcion
    //   - si ahora SÍ queda fuera control → crea la señal si no existía
    // -------------------------------------------------------------------
    public function editarSubgrupo(array $params): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $sesionId    = (int)$params['id'];
        $subgrupoId  = (int)$params['subgrupo_id'];
        $sesion      = $this->modelSesion->find($sesionId);

        if (!$sesion || $sesion['estado'] !== 'en_proceso') {
            $msg = 'Esta sesión ya está cerrada; no se pueden editar sus registros.';
            if ($this->isAjax()) { $this->jsonError($msg, 403); return; }
            $this->flash('error', $msg);
            $this->redirect('/m2');
            return;
        }

        $subgrupo = $this->modelSubgrupo->find($subgrupoId);
        if (!$subgrupo || (int)$subgrupo['sesion_id'] !== $sesionId) {
            if ($this->isAjax()) { $this->jsonError('Registro no encontrado.', 404); return; }
            $this->flash('error', 'Registro no encontrado.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        $lecturasCrudas = $_POST['lecturas'] ?? [];
        if (!is_array($lecturasCrudas)) $lecturasCrudas = [];

        $lecturas = [];
        foreach ($lecturasCrudas as $v) {
            $num = $this->validarNumero($v);
            if ($num === null) {
                $msg = 'Todas las lecturas deben ser números válidos (máx. 6 dígitos enteros, 4 decimales). Revise el valor "' . htmlspecialchars((string)$v) . '".';
                if ($this->isAjax()) { $this->jsonError($msg, 422); return; }
                $this->flash('error', $msg);
                $this->redirect("/m2/sesion/{$sesionId}");
                return;
            }
            $lecturas[] = $num;
        }

        if (empty($lecturas)) {
            $msg = 'Ingrese al menos una lectura numérica.';
            if ($this->isAjax()) { $this->jsonError($msg, 422); return; }
            $this->flash('error', $msg);
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        $hora = $this->input('hora') ?: $subgrupo['hora'];

        $limites = $this->db->fetchOne(
            "SELECT ucl_xbar, lcl_xbar, cl_xbar FROM spc_limites_control
            WHERE parametro_id = ? AND vigente = 1
            ORDER BY calculado_en DESC LIMIT 1",
            [$subgrupo['parametro_id']]
        );
        if (!$limites) {
            $parametro = $this->db->fetchOne(
                "SELECT valor_min, valor_max, valor_nominal FROM parametros_proceso WHERE id = ?",
                [$subgrupo['parametro_id']]
            );
            $limites = [
                'ucl_xbar' => $parametro['valor_max']     ?? null,
                'lcl_xbar' => $parametro['valor_min']     ?? null,
                'cl_xbar'  => $parametro['valor_nominal'] ?? null,
            ];
        }

        $stats = $this->modelSubgrupo->actualizarSubgrupo($subgrupoId, $hora, $lecturas, $limites);

        // Sincronizar señal SPC asociada a este subgrupo
        $senalExistente = $this->db->fetchOne(
            "SELECT id FROM spc_senales_detectadas
            WHERE registro_peso_id = ? AND estado = 'nueva'",
            [$subgrupoId]
        );

        if ($stats['fuera_control']) {
            if ($senalExistente) {
                $this->db->execute(
                    "UPDATE spc_senales_detectadas
                    SET valor_detectado = ?, descripcion_regla = ?, regla_western_electric = ?
                    WHERE id = ?",
                    [$stats['xbar'], $stats['regla'] ?? 'Señal detectada',
                     $this->extraerNumeroRegla($stats['regla']), $senalExistente['id']]
                );
            } else {
                // CORREGIDO: aquí se insertaba $subgrupo['parametro_id']
                // en la columna producto_id — reg_subgrupos_spc no tiene
                // ninguna columna producto_id, así que esto guardaba el
                // ID de un PARÁMETRO en la columna del PRODUCTO. Se
                // deriva el producto_id real desde el lote, igual que ya
                // se hace correctamente en el flujo de creación
                // (guardarSubgrupo()).
                $productoIdReal = (int)$this->db->fetchScalar(
                    "SELECT producto_id FROM lotes_produccion WHERE id = ?",
                    [$sesion['lote_id']]
                );
                $this->db->execute(
                    "INSERT INTO spc_senales_detectadas
                        (lote_id, sesion_id, registro_peso_id, producto_id,
                        tipo_grafico, regla_western_electric, descripcion_regla,
                        valor_detectado, estado)
                    VALUES (?,?,?,?, 'xbar',?,?,?, 'nueva')",
                    [
                        $sesion['lote_id'], $sesionId, $subgrupoId,
                        $productoIdReal,
                        $this->extraerNumeroRegla($stats['regla']),
                        $stats['regla'] ?? 'Señal detectada',
                        $stats['xbar'],
                    ]
                );
            }
        } elseif ($senalExistente) {
            // Ya no está fuera de control (el typeo era la causa) → se
            // marca como falsa alarma en vez de borrarla, para conservar
            // el historial de que hubo una alarma y por qué se descartó.
            $this->db->execute(
                "UPDATE spc_senales_detectadas
                SET estado = 'falsa_alarma',
                    accion_tomada = CONCAT(COALESCE(accion_tomada,''),
                        ' [Corregido: lectura editada, ya no excede límites]')
                WHERE id = ?",
                [$senalExistente['id']]
            );
        }

        $mensaje = $stats['fuera_control']
            ? 'ATENCIÓN — Registro actualizado. SEÑAL: ' . $stats['regla']
            : 'Registro actualizado correctamente.';

        if ($this->isAjax()) {
            $this->jsonSuccess([
                'id'               => $subgrupoId,
                'promedio_xbar'    => round($stats['xbar'], 4),
                'rango_r'          => round($stats['r'], 4),
                'fuera_de_control' => $stats['fuera_control'],
                'regla_violada'    => $stats['regla'],
                'valores'          => $stats['valores'],
            ], $mensaje);
            return;
        }
        $this->flash('success', $mensaje);
        $this->redirect("/m2/sesion/{$sesionId}");
    }

    // -------------------------------------------------------------------
    // POST /m2/sesion/:id/subgrupo/:subgrupo_id/eliminar
    // Borra un subgrupo y cualquier señal SPC derivada de él (la señal no
    // tiene sentido sin el subgrupo que la generó).
    // -------------------------------------------------------------------
    public function eliminarSubgrupo(array $params): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $sesionId   = (int)$params['id'];
        $subgrupoId = (int)$params['subgrupo_id'];
        $sesion     = $this->modelSesion->find($sesionId);

        if (!$sesion || $sesion['estado'] !== 'en_proceso') {
            $msg = 'Esta sesión ya está cerrada; no se pueden eliminar sus registros.';
            if ($this->isAjax()) { $this->jsonError($msg, 403); return; }
            $this->flash('error', $msg);
            $this->redirect('/m2');
            return;
        }

        $subgrupo = $this->modelSubgrupo->find($subgrupoId);
        if (!$subgrupo || (int)$subgrupo['sesion_id'] !== $sesionId) {
            if ($this->isAjax()) { $this->jsonError('Registro no encontrado.', 404); return; }
            $this->flash('error', 'Registro no encontrado.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        $this->db->transaction(function($db) use ($subgrupoId) {
            $db->execute("DELETE FROM spc_senales_detectadas WHERE registro_peso_id = ?", [$subgrupoId]);
            $db->execute("DELETE FROM reg_subgrupos_spc WHERE id = ?", [$subgrupoId]);
        });

        if ($this->isAjax()) {
            $this->jsonSuccess(['id' => $subgrupoId], 'Registro eliminado correctamente.');
            return;
        }
        $this->redirectWithSuccess("/m2/sesion/{$sesionId}", 'Registro eliminado correctamente.');
    }

    // -------------------------------------------------------------------
    // POST /m2/sesion/:id/valor/:valor_id/editar
    // Corrige un valor simple ya guardado. Si el parámetro es numérico,
    // valida estrictamente; si es texto/selección/si_no, solo sanitiza.
    // -------------------------------------------------------------------
    public function editarValor(array $params): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $sesionId = (int)$params['id'];
        $valorId  = (int)$params['valor_id'];
        $sesion   = $this->modelSesion->find($sesionId);

        if (!$sesion || $sesion['estado'] !== 'en_proceso') {
            $msg = 'Esta sesión ya está cerrada; no se pueden editar sus registros.';
            if ($this->isAjax()) { $this->jsonError($msg, 403); return; }
            $this->flash('error', $msg);
            $this->redirect('/m2');
            return;
        }

        $registro = $this->modelValor->find($valorId);
        if (!$registro || (int)$registro['sesion_id'] !== $sesionId) {
            if ($this->isAjax()) { $this->jsonError('Registro no encontrado.', 404); return; }
            $this->flash('error', 'Registro no encontrado.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        $parametro = $this->db->fetchOne(
            "SELECT tipo_dato FROM parametros_proceso WHERE id = ?",
            [$registro['parametro_id']]
        );

        $valorCrudo = $_POST['valor'] ?? '';

        if (($parametro['tipo_dato'] ?? '') === 'numerico') {
            $num = $this->validarNumero($valorCrudo);
            if ($num === null) {
                $msg = 'El valor debe ser un número válido (máx. 6 dígitos enteros, 4 decimales).';
                if ($this->isAjax()) { $this->jsonError($msg, 422); return; }
                $this->flash('error', $msg);
                $this->redirect("/m2/sesion/{$sesionId}");
                return;
            }
            $valorFinal = (string)$num;
        } elseif (($parametro['tipo_dato'] ?? '') === 'si_no') {
            $valorFinal = in_array($valorCrudo, ['1','si','on','true'], true) ? '1' : '0';
        } else {
            // texto / selección: solo longitud razonable, sin HTML
            $valorFinal = substr(strip_tags(trim((string)$valorCrudo)), 0, 255);
            if ($valorFinal === '') {
                $msg = 'El valor no puede quedar vacío.';
                if ($this->isAjax()) { $this->jsonError($msg, 422); return; }
                $this->flash('error', $msg);
                $this->redirect("/m2/sesion/{$sesionId}");
                return;
            }
        }

        $this->modelValor->actualizarValor($valorId, $valorFinal);

        if ($this->isAjax()) {
            $this->jsonSuccess(['id' => $valorId, 'valor' => $valorFinal], 'Valor actualizado correctamente.');
            return;
        }
        $this->redirectWithSuccess("/m2/sesion/{$sesionId}", 'Valor actualizado correctamente.');
    }

    // -------------------------------------------------------------------
    // POST /m2/sesion/:id/valor/:valor_id/eliminar
    // -------------------------------------------------------------------
    public function eliminarValor(array $params): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $sesionId = (int)$params['id'];
        $valorId  = (int)$params['valor_id'];
        $sesion   = $this->modelSesion->find($sesionId);

        if (!$sesion || $sesion['estado'] !== 'en_proceso') {
            $msg = 'Esta sesión ya está cerrada; no se pueden eliminar sus registros.';
            if ($this->isAjax()) { $this->jsonError($msg, 403); return; }
            $this->flash('error', $msg);
            $this->redirect('/m2');
            return;
        }

        $registro = $this->modelValor->find($valorId);
        if (!$registro || (int)$registro['sesion_id'] !== $sesionId) {
            if ($this->isAjax()) { $this->jsonError('Registro no encontrado.', 404); return; }
            $this->flash('error', 'Registro no encontrado.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        $this->modelValor->delete($valorId);

        if ($this->isAjax()) {
            $this->jsonSuccess(['id' => $valorId], 'Valor eliminado correctamente.');
            return;
        }
        $this->redirectWithSuccess("/m2/sesion/{$sesionId}", 'Valor eliminado correctamente.');
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