<?php
/**
 * PaymentConfigController
 * Gestiona la configuración de los métodos de pago (URLs de QR de Nequi y Daviplata).
 * La configuración se guarda en config/payment_config.json para no depender de la BD.
 */

require_once __DIR__ . "/../core/Controlador.php";;

class PagoConfiguracionControlador extends Controlador
{
    private $configPath;

    public function __construct()
    {
        parent::__construct();
        $this->configPath = __DIR__ . '/../config/payment_config.json';
    }

    /**
     * Lee la configuración actual de pagos.
     */
    public function obtener()
    {
        $config = $this->readConfig();
        $this->success(['config' => $config]);
    }

    /**
     * Sube un archivo QR al directorio público de imágenes.
     */
    public function subirQr()
    {
        $tipo = $_POST['tipo'] ?? '';
        if (!in_array($tipo, ['nequi', 'daviplata'], true)) {
            $this->error('Tipo de QR inválido.', 400);
            return;
        }
        $campo = $tipo . '_file';
        if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
            $this->error('No se recibió ningún archivo válido.', 400);
            return;
        }
        $archivo = $_FILES[$campo];
        if ((int)$archivo['size'] > 3 * 1024 * 1024) {
            $this->error('El QR no puede superar 3 MB.', 400);
            return;
        }
        $info = @getimagesize($archivo['tmp_name']);
        $permitidos = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
        if (!$info || !isset($permitidos[$info[2]])) {
            $this->error('El archivo debe ser PNG, JPG, WEBP o GIF.', 400);
            return;
        }
        $dir = __DIR__ . '/../images/payment';
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $this->error('No se pudo crear el directorio de imágenes.', 500);
            return;
        }
        $nombre = $tipo . '_qr_' . bin2hex(random_bytes(8)) . '.' . $permitidos[$info[2]];
        if (!move_uploaded_file($archivo['tmp_name'], $dir . '/' . $nombre)) {
            $this->error('No se pudo guardar la imagen.', 500);
            return;
        }
        $this->success(['url' => 'images/payment/' . $nombre]);
    }

    /**
     * Guarda la configuración de pagos enviada por el admin.
     * Espera JSON: { nequi_qr: "...", daviplata_qr: "..." }
     */
    public function guardar()
    {
        $raw = $this->getInput();

        $nequi    = isset($raw['nequi_qr'])    ? trim($raw['nequi_qr'])    : '';
        $daviplata = isset($raw['daviplata_qr']) ? trim($raw['daviplata_qr']) : '';

        // Validación ligera: debe ser una URL http(s) o una ruta relativa local.
        foreach (['nequi' => $nequi, 'daviplata' => $daviplata] as $key => $val) {
            if ($val !== '' && !preg_match('#^(https?://|/|images/|uploads/)#i', $val)) {
                $this->error("La URL del QR de $key no es válida.", 400);
                return;
            }
        }

        $config = [
            'nequi_qr'     => $nequi,
            'daviplata_qr' => $daviplata,
            'updated_at'   => date('c'),
        ];

        $ok = $this->writeConfig($config);

        if ($ok) {
            $this->success(['config' => $config]);
        } else {
            $this->error('No se pudo guardar la configuración.', 500);
        }
    }

    private function readConfig()
    {
        if (!file_exists($this->configPath)) {
            return ['nequi_qr' => '', 'daviplata_qr' => ''];
        }
        $content = file_get_contents($this->configPath);
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return ['nequi_qr' => '', 'daviplata_qr' => ''];
        }
        return [
            'nequi_qr'     => $data['nequi_qr'] ?? '',
            'daviplata_qr' => $data['daviplata_qr'] ?? '',
        ];
    }

    private function writeConfig(array $config)
    {
        $dir = dirname($this->configPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return file_put_contents($this->configPath, $json) !== false;
    }
}
