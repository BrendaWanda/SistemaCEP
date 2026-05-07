<?php
// =============================================================================
//  SIACEP — Modelo base
//  Archivo: app/Core/Model.php
//  Todos los modelos del sistema extienden esta clase.
//  Provee: operaciones CRUD genéricas, paginación, y helpers de consulta.
// =============================================================================

namespace App\Core;

abstract class Model
{
    protected Database $db;

    // Cada modelo hijo define su tabla y clave primaria
    protected string $table  = '';
    protected string $pk     = 'id';

    // Columnas permitidas para INSERT/UPDATE (whitelist)
    // El modelo hijo las define para proteger contra mass assignment
    protected array $fillable = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── CRUD genérico ─────────────────────────────────────────────────────────

    /**
     * Obtiene todos los registros de la tabla.
     */
    public function all(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";
        return $this->db->fetchAll($sql);
    }

    /**
     * Busca un registro por su clave primaria.
     */
    public function find(int|string $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$this->pk} = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * Busca un registro por un campo específico.
     */
    public function findBy(string $field, mixed $value): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$field} = ? LIMIT 1",
            [$value]
        );
    }

    /**
     * Obtiene todos los registros que coincidan con una condición.
     */
    public function where(string $field, mixed $value, string $orderBy = ''): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";
        return $this->db->fetchAll($sql, [$value]);
    }

    /**
     * Inserta un nuevo registro. Retorna el ID generado.
     * Solo usa los campos definidos en $fillable.
     */
    public function create(array $data): int
    {
        $data    = $this->filterFillable($data);
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->db->execute(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza un registro por su ID. Retorna filas afectadas.
     */
    public function update(int|string $id, array $data): int
    {
        $data = $this->filterFillable($data);
        $set  = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));

        return $this->db->execute(
            "UPDATE {$this->table} SET {$set} WHERE {$this->pk} = ?",
            [...array_values($data), $id]
        );
    }

    /**
     * Elimina un registro por su ID. Retorna filas afectadas.
     */
    public function delete(int|string $id): int
    {
        return $this->db->execute(
            "DELETE FROM {$this->table} WHERE {$this->pk} = ?",
            [$id]
        );
    }

    /**
     * Soft delete: marca como inactivo en lugar de eliminar.
     * Requiere que la tabla tenga campo `activo`.
     */
    public function softDelete(int $id): int
    {
        return $this->db->execute(
            "UPDATE {$this->table} SET activo = 0 WHERE {$this->pk} = ?",
            [$id]
        );
    }

    /**
     * Cuenta registros, con condición opcional.
     */
    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if ($where) $sql .= " WHERE {$where}";
        return (int) $this->db->fetchScalar($sql, $params);
    }

    /**
     * Verifica si existe un registro.
     */
    public function exists(string $where, array $params): bool
    {
        return (bool) $this->db->fetchScalar(
            "SELECT 1 FROM {$this->table} WHERE {$where} LIMIT 1",
            $params
        );
    }

    // ── Paginación ────────────────────────────────────────────────────────────
    /**
     * Retorna un slice de registros para paginación.
     *
     * Retorna: ['data' => [...], 'total' => N, 'pagina' => N, 'por_pagina' => N,
     *           'total_paginas' => N, 'tiene_anterior' => bool, 'tiene_siguiente' => bool]
     */
    public function paginate(
        int    $pagina    = 1,
        int    $porPagina = 20,
        string $where     = '',
        array  $params    = [],
        string $orderBy   = ''
    ): array {
        $pagina    = max(1, $pagina);
        $porPagina = max(1, min(100, $porPagina));
        $offset    = ($pagina - 1) * $porPagina;

        $whereClause = $where ? "WHERE {$where}" : '';
        $orderClause = $orderBy ? "ORDER BY {$orderBy}" : '';

        $total = (int) $this->db->fetchScalar(
            "SELECT COUNT(*) FROM {$this->table} {$whereClause}",
            $params
        );

        $data = $this->db->fetchAll(
            "SELECT * FROM {$this->table} {$whereClause} {$orderClause}
             LIMIT {$porPagina} OFFSET {$offset}",
            $params
        );

        $totalPaginas = (int) ceil($total / $porPagina);

        return [
            'data'             => $data,
            'total'            => $total,
            'pagina'           => $pagina,
            'por_pagina'       => $porPagina,
            'total_paginas'    => $totalPaginas,
            'tiene_anterior'   => $pagina > 1,
            'tiene_siguiente'  => $pagina < $totalPaginas,
        ];
    }

    // ── Acceso directo a la DB ────────────────────────────────────────────────
    /**
     * Ejecuta una consulta personalizada. Para casos que no cubre el CRUD genérico.
     */
    protected function query(string $sql, array $params = []): array
    {
        return $this->db->fetchAll($sql, $params);
    }

    protected function queryOne(string $sql, array $params = []): array|false
    {
        return $this->db->fetchOne($sql, $params);
    }

    protected function execute(string $sql, array $params = []): int
    {
        return $this->db->execute($sql, $params);
    }

    // ── Transacciones ─────────────────────────────────────────────────────────
    protected function transaction(callable $callback): mixed
    {
        return $this->db->transaction($callback);
    }

    // ── Filtro de campos permitidos ───────────────────────────────────────────
    private function filterFillable(array $data): array
    {
        if (empty($this->fillable)) return $data;
        return array_intersect_key($data, array_flip($this->fillable));
    }

    // ── Helper: lista para selects HTML ───────────────────────────────────────
    /**
     * Retorna un array listo para <select>: [id => nombre].
     */
    public function toSelectList(string $valueField = 'id', string $labelField = 'nombre', string $where = '', array $params = []): array
    {
        $whereClause = $where ? "WHERE {$where}" : '';
        $rows = $this->db->fetchAll(
            "SELECT {$valueField}, {$labelField} FROM {$this->table} {$whereClause} ORDER BY {$labelField}",
            $params
        );
        return array_column($rows, $labelField, $valueField);
    }
}