<?php

class Controlador {
    protected Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    protected function jsonResponse(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function success(array $data = []): void {
        $this->jsonResponse(array_merge(['success' => true], $data));
    }

    protected function error(string $message, int $code = 400): void {
        $this->jsonResponse(['success' => false, 'error' => $message], $code);
    }

    protected function getInput(): array {
        // api.php puede haber leído y conservado el JSON al validar CSRF.
        if (isset($GLOBALS['api_json_input']) && is_array($GLOBALS['api_json_input'])) {
            return $GLOBALS['api_json_input'];
        }
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function normalizeUserRole(array $row): string {
        $role = strtolower(trim((string) ($row['role'] ?? '')));
        if (in_array($role, ['admin', 'seller', 'customer'])) {
            return $role;
        }
        return !empty($row['is_admin']) ? 'admin' : 'customer';
    }
}
