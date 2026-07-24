<?php
namespace App\Models;

use App\Core\Model;

class RecepcionMP extends Model
{
    protected string $table    = 'recepciones_mp';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'insumo_id','proveedor_id','codigo_recepcion','lote_proveedor',
        'fecha_fabricacion','fecha_vencimiento','fecha_muestreo','fecha_analisis',
        'cantidad_recibida','unidad_medida','tamanio_muestra',
        'nro_registro_sanitario','presentacion','envase_primario','envase_secundario',
        'obs_empaque',
        'sabor_olor','conf_sabor_olor','color','conf_color',
        'descripcion_fisica','conf_descripcion_fisica','obs_organoleptico',
        'esp_humedad','res_humedad','conf_humedad',
        'esp_densidad','res_densidad','conf_densidad',
        'esp_ph','res_ph','conf_ph',
        'esp_brix','res_brix','conf_brix',
        'esp_gluten','res_gluten','conf_gluten',
        'esp_actividad_agua','res_actividad_agua','conf_actividad_agua',
        'obs_fisicoquimico',
        'esp_impurezas','res_impurezas','conf_impurezas','obs_impurezas',
        'conclusion','decision','fecha_decision',
        'responsable_muestreo_id','responsable_analisis_id','visto_bueno_id',
        'obs_generales','stock_disponible_kg'
    ];

    // Estados de decisión con colores
    public const DECISIONES = [
        'aprobado'   => ['label' => 'Aprobado',   'badge' => 'badge-success'],
        'rechazado'  => ['label' => 'Rechazado',  'badge' => 'badge-danger'],
        'observado'  => ['label' => 'Observado',  'badge' => 'badge-warning'],
        'cuarentena' => ['label' => 'Cuarentena', 'badge' => 'badge-purple'],
    ];

    public const CONF = [
        'conforme'     => ['label' => 'Conforme',     'badge' => 'badge-success'],
        'no_conforme'  => ['label' => 'No conforme',  'badge' => 'badge-danger'],
        'na'           => ['label' => 'N/A',           'badge' => 'badge-muted'],
    ];

    // Historial completo con joins
    public function historial(array $filtros = [], int $pagina = 1, int $porPagina = 20): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['insumo_id'])) {
            $where[]  = 'r.insumo_id = ?';
            $params[] = $filtros['insumo_id'];
        }
        if (!empty($filtros['proveedor_id'])) {
            $where[]  = 'r.proveedor_id = ?';
            $params[] = $filtros['proveedor_id'];
        }
        if (!empty($filtros['decision'])) {
            $where[]  = 'r.decision = ?';
            $params[] = $filtros['decision'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[]  = 'r.fecha_analisis >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[]  = 'r.fecha_analisis <= ?';
            $params[] = $filtros['fecha_hasta'];
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($pagina - 1) * $porPagina;

        $total = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM recepciones_mp r WHERE {$whereStr}",
            $params
        );

        $data = $this->db->fetchAll(
            "SELECT r.*,
                    i.descripcion AS insumo_nombre, i.codigo AS insumo_codigo,
                    i.unidad_medida AS insumo_unidad,
                    p.nombre AS proveedor_nombre,
                    u1.nombre AS muestreo_nombre,
                    u2.nombre AS analisis_nombre
            FROM recepciones_mp r
            JOIN insumos i ON i.id = r.insumo_id
            JOIN proveedores p ON p.id = r.proveedor_id
            JOIN usuarios u1 ON u1.id = r.responsable_muestreo_id
            JOIN usuarios u2 ON u2.id = r.responsable_analisis_id
            WHERE {$whereStr}
            ORDER BY r.creado_en DESC
            LIMIT {$porPagina} OFFSET {$offset}",
            $params
        );

        $totalPaginas = (int)ceil($total / $porPagina);

        return [
            'data'            => $data,
            'total'           => $total,
            'pagina'          => $pagina,
            'por_pagina'      => $porPagina,
            'total_paginas'   => $totalPaginas,
            // Hallazgo de auditoría: historial.php revisa estas 2 claves
            // para mostrar los enlaces "Anterior"/"Siguiente", pero nunca
            // se devolvían — la paginación existía visualmente pero era
            // imposible de usar, incluso con más de 20 registros.
            'tiene_anterior'  => $pagina > 1,
            'tiene_siguiente' => $pagina < $totalPaginas,
        ];
    }

    // Recepción completa con todos los datos para vista detalle e impresión
    public function conDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT r.*,
                    i.descripcion AS insumo_nombre, i.codigo AS insumo_codigo,
                    i.tipo AS insumo_tipo, i.unidad_medida AS insumo_unidad,
                    p.nombre AS proveedor_nombre, p.procedencia AS proveedor_procedencia,
                    u1.nombre AS muestreo_nombre, u1.apellidos AS muestreo_apellidos,
                    u2.nombre AS analisis_nombre, u2.apellidos AS analisis_apellidos,
                    u3.nombre AS visto_bueno_nombre, u3.apellidos AS visto_bueno_apellidos
            FROM recepciones_mp r
            JOIN insumos i ON i.id = r.insumo_id
            JOIN proveedores p ON p.id = r.proveedor_id
            JOIN usuarios u1 ON u1.id = r.responsable_muestreo_id
            JOIN usuarios u2 ON u2.id = r.responsable_analisis_id
            LEFT JOIN usuarios u3 ON u3.id = r.visto_bueno_id
            WHERE r.id = ?",
            [$id]
        );
    }

    // Generar código único de recepción: REC-YYYYMMDD-NNN
    //
    // Hallazgo CRÍTICO de auditoría: el código se generaba UNA SOLA VEZ
    // al abrir el formulario "Nueva recepción" (GET /m1/nueva) y se
    // enviaba de vuelta en un campo oculto — si el formulario quedaba
    // abierto varios minutos mientras se completaba (es un formulario
    // largo, con análisis organoléptico y fisicoquímico), y alguien más
    // abría el formulario y guardaba primero, ambos envíos cargaban el
    // MISMO código — codigo_recepcion tiene UNIQUE KEY, así que el
    // segundo INSERT fallaba con una excepción cruda de PDO. La ventana
    // de colisión aquí era de minutos, no milisegundos como en
    // lotes_produccion — más grave todavía.
    //
    // $intento permite pedir el "siguiente candidato" cuando el
    // controller reintenta tras una colisión (ver
    // RecepcionController::guardar()), sin repetir esta consulta.
    public function generarCodigo(int $intento = 0): string
    {
        $fecha  = date('Ymd');
        $prefix = "REC-{$fecha}-";
        $ultimo = $this->db->fetchScalar(
            "SELECT codigo_recepcion FROM recepciones_mp
            WHERE codigo_recepcion LIKE ?
            ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );
        $num = ($ultimo ? (int)substr($ultimo, -3) + 1 : 1) + $intento;
        return $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    // MP próximas a vencer o ya vencidas en stock
    public function alertasVencimiento(int $dias = 30): array
    {
        return $this->db->fetchAll(
            "SELECT s.*, i.descripcion AS insumo_nombre, i.codigo AS insumo_codigo,
                    i.unidad_medida,
                    DATEDIFF(s.fecha_vencimiento, CURDATE()) AS dias_restantes
            FROM stock_mp s
            JOIN insumos i ON i.id = s.insumo_id
            WHERE s.estado = 'disponible'
                AND s.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY s.fecha_vencimiento ASC",
            [$dias]
        );
    }

    // Stock disponible por insumo
    public function stockDisponible(): array
    {
        return $this->db->fetchAll(
            "SELECT i.id, i.codigo, i.descripcion, i.unidad_medida, i.tipo,
                    COALESCE(SUM(s.cantidad_disponible), 0) AS stock_total,
                    COUNT(s.id) AS lotes_disponibles,
                    MIN(s.fecha_vencimiento) AS proximo_vencimiento
            FROM insumos i
            LEFT JOIN stock_mp s ON s.insumo_id = i.id AND s.estado = 'disponible'
            WHERE i.activo = 1
            GROUP BY i.id
            ORDER BY i.tipo, i.descripcion"
        );
    }

    // ═══════════════════════════════════════════════════════════
    // Resolver una recepción que quedó en 'cuarentena' u 'observado'.
    //
    // Hallazgo de auditoría: antes NO existía ninguna forma de cambiar
    // la decisión de una recepción ya guardada — "cuarentena" se
    // trataba exactamente igual que "rechazado" (cero stock), sin
    // ninguna manera de aprobarlo después si el resultado pendiente
    // finalmente salía bien. El enum de stock_mp.estado ya tenía
    // 'reservado' pensado exactamente para esto, pero nunca se usaba.
    //
    // Este método cambia la decisión final, actualiza el estado del
    // stock reservado a 'disponible' (aprobado) o 'rechazado', y deja
    // la nota de resolución en obs_generales.
    // ═══════════════════════════════════════════════════════════
    public function resolverDecision(
        int $id, string $nuevaDecision, string $notaResolucion, int $usuarioId
    ): bool {
        $recepcion = $this->find($id);
        if (!$recepcion) return false;
        if (!in_array($recepcion['decision'], ['cuarentena', 'observado'], true)) {
            return false; // Solo se resuelven recepciones pendientes
        }
        if (!in_array($nuevaDecision, ['aprobado', 'rechazado'], true)) {
            return false; // La resolución final solo puede ser aprobado/rechazado
        }

        return (bool)$this->db->transaction(function($db) use (
            $id, $recepcion, $nuevaDecision, $notaResolucion
        ) {
            $notaCompleta = trim(
                ($recepcion['obs_generales'] ? $recepcion['obs_generales'] . "\n\n" : '')
                . '[Resolución ' . date('d/m/Y H:i') . '] '
                . strtoupper($nuevaDecision) . ': ' . $notaResolucion
            );

            $db->execute(
                "UPDATE recepciones_mp
                SET decision = ?, obs_generales = ?
                WHERE id = ?",
                [$nuevaDecision, $notaCompleta, $id]
            );

            // El stock reservado al guardar la recepción (ver guardar())
            // pasa a disponible si se aprueba, o a rechazado si no.
            $nuevoEstadoStock = $nuevaDecision === 'aprobado' ? 'disponible' : 'rechazado';
            $db->execute(
                "UPDATE stock_mp SET estado = ? WHERE recepcion_id = ? AND estado = 'reservado'",
                [$nuevoEstadoStock, $id]
            );

            return true;
        });
    }
}