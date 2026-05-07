<?php
// =============================================================================
//  SIACEP — Conexión a la base de datos
//  Archivo: app/Core/Database.php
//  Patrón: Singleton — una sola instancia PDO en toda la aplicación.
//  Uso:    $db = Database::getInstance();
//          $stmt = $db->prepare("SELECT ...");
// =============================================================================

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    // ── Constructor privado (Singleton) ───────────────────────────────────────
    private function __construct()
    {
        $host    = env('DB_HOST', 'localhost');
        $port    = env('DB_PORT', '3306');
        $dbname  = env('DB_NAME', 'siacep');
        $user    = env('DB_USER', 'root');
        $pass    = env('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_spanish_ci,
                                            time_zone = '-04:00'",
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // En producción no exponemos detalles del error
            if (APP_DEBUG) {
                die('<pre>❌ Error de conexión BD: ' . $e->getMessage() . '</pre>');
            }
            die('Error interno del servidor. Contacte al administrador.');
        }
    }

    // ── Obtener instancia única ───────────────────────────────────────────────
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ── Acceso directo al objeto PDO ─────────────────────────────────────────
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // ── Atajos para las operaciones más comunes ───────────────────────────────

    /**
     * Prepara y ejecuta una consulta con parámetros.
     * Retorna el PDOStatement para obtener resultados.
     *
     * Uso: $stmt = $db->query("SELECT * FROM usuarios WHERE id = ?", [$id]);
     *      $user = $stmt->fetch();
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Retorna todos los registros de una consulta.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Retorna un único registro.
     */
    public function fetchOne(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Retorna un único valor escalar (ej: COUNT, SUM).
     */
    public function fetchScalar(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Ejecuta INSERT/UPDATE/DELETE. Retorna número de filas afectadas.
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Retorna el ID del último INSERT.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    // ── Transacciones ─────────────────────────────────────────────────────────

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Ejecuta un bloque de código dentro de una transacción.
     * Si lanza excepción, hace rollback automático.
     *
     * Uso: $db->transaction(function($db) {
     *          $db->execute("INSERT ...", [...]);
     *          $db->execute("UPDATE ...", [...]);
     *      });
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // ── Prevenir clonación (Singleton) ────────────────────────────────────────
    private function __clone() {}
    public function __wakeup() {
        throw new \RuntimeException('No se puede deserializar un Singleton.');
    }
}
