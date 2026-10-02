<?php

class Database {
    private static ?Database $instance = null;
    private $conexion;

    private function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $db = $config['db'];

        mysqli_report(MYSQLI_REPORT_OFF);
        $port = isset($db['port']) ? (int) $db['port'] : 3306;
        $flags = !empty($db['ssl']) ? MYSQLI_CLIENT_SSL : 0;
        $this->conexion = mysqli_init();
        if (!$this->conexion
            || !mysqli_real_connect($this->conexion, $db['host'], $db['user'], $db['pass'], $db['name'], $port, null, $flags)) {
            error_log('[Database] Conexión MySQL fallida: ' . mysqli_connect_error());
            throw new RuntimeException('No se pudo conectar a la base de datos.');
        }
        if (!mysqli_set_charset($this->conexion, $db['charset'])) {
            error_log('[Database] No se pudo establecer charset MySQL: ' . mysqli_error($this->conexion));
            throw new RuntimeException('No se pudo preparar la conexión a la base de datos.');
        }
        // Los cambios de esquema se ejecutan manualmente mediante migrations/*.sql;
        // no se modifica la estructura en cada request de la aplicación.
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conexion;
    }

    public function escape(string $value): string {
        return mysqli_real_escape_string($this->conexion, $value);
    }

    public function query(string $sql) {
        return mysqli_query($this->conexion, $sql);
    }

    public function prepare(string $sql) {
        return mysqli_prepare($this->conexion, $sql);
    }

    public function insertId(): int {
        return (int) mysqli_insert_id($this->conexion);
    }

    public function error(): string {
        return mysqli_error($this->conexion);
    }

    public function beginTransaction(): bool {
        return mysqli_begin_transaction($this->conexion);
    }

    public function commit(): bool {
        return mysqli_commit($this->conexion);
    }

    public function rollback(): bool {
        return mysqli_rollback($this->conexion);
    }
}
