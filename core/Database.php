<?php

class Database {
    private static ?Database $instance = null;
    private $conexion;

    private function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $db = $config['db'];

        $this->conexion = mysqli_connect($db['host'], $db['user'], $db['pass'], $db['name']);
        if (!$this->conexion) {
            die("Conexión fallida: " . mysqli_connect_error());
        }
        mysqli_set_charset($this->conexion, $db['charset']);

        mysqli_query($this->conexion, "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS rol VARCHAR(20) NOT NULL DEFAULT 'customer'");
        mysqli_query($this->conexion, "ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS vendedor_id INT NULL");
        mysqli_query($this->conexion, "ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS usuario_id INT NULL");
        mysqli_query($this->conexion, "ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS estado VARCHAR(30) NOT NULL DEFAULT 'pendiente'");
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
