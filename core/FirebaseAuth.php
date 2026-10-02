<?php

/**
 * FirebaseAuth.php — Verifica los ID tokens (JWT RS256) que emite el SDK de Firebase.
 * No usa Composer ni el Admin SDK: valida la firma contra las claves públicas de Google,
 * que se descargan una vez y se guardan en caché local.
 *
 * La caché vive en logs/ a propósito: el directorio temporal compartido del sistema es
 * escribible por otros procesos y permitiría colar certificados falsos.
 */
class FirebaseAuth {

    private const CERT_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
    private const DESFASE_TOLERADO = 10;
    private const INTENTOS_DESCARGA = 2;
    /** Más largo que el max-age de Google: si rotan las claves, el kid ausente fuerza la recarga en verificar(). */
    private const VIDA_CACHE = 86400;
    /** Margen para usar una caché ya vencida cuando Google no responde. */
    private const MARGEN_CADUCADA = 86400;
    /** Frena las recargas forzadas: un token con kid inventado no puede saturar la red. */
    private const ESPERA_REFRESCO = 60;

    private string $projectId;
    private string $rutaCache;

    public function __construct(string $projectId) {
        $this->projectId = trim($projectId);
        $this->rutaCache = __DIR__ . '/../logs/firebase_certs.json';
        if ($this->projectId === '') {
            throw new RuntimeException('Falta el projectId de Firebase en config/config.php');
        }
    }

    /**
     * Verifica la firma y los claims del token. Devuelve el payload completo.
     * Lanza RuntimeException con un mensaje legible si el token no sirve.
     */
    public function verificar(string $idToken): array {
        $idToken = trim($idToken);
        $partes = explode('.', $idToken);
        if (count($partes) !== 3) {
            throw new RuntimeException('El token no tiene el formato de un JWT');
        }
        [$cabeceraB64, $payloadB64, $firmaB64] = $partes;

        $cabecera = $this->decodificarJson($cabeceraB64, 'cabecera del token');
        if (($cabecera['alg'] ?? '') !== 'RS256') {
            throw new RuntimeException('Algoritmo de firma no admitido');
        }
        $kid = (string) ($cabecera['kid'] ?? '');
        if ($kid === '') {
            throw new RuntimeException('El token no indica qué clave lo firmó');
        }

        $certificados = $this->obtenerCertificados();
        if (!isset($certificados[$kid])) {
            // Google rota las claves cada pocas horas; el kid puede ser más nuevo que la caché.
            $certificados = $this->obtenerCertificados(true);
        }
        if (!isset($certificados[$kid])) {
            throw new RuntimeException('No se encontró la clave pública del token');
        }

        $clave = openssl_pkey_get_public($certificados[$kid]);
        if ($clave === false) {
            throw new RuntimeException('El certificado público no se pudo leer');
        }
        $firma = $this->decodificarBase64Url($firmaB64);
        $valida = openssl_verify($cabeceraB64 . '.' . $payloadB64, $firma, $clave, OPENSSL_ALGO_SHA256);
        if ($valida !== 1) {
            throw new RuntimeException('La firma del token no es válida');
        }

        $payload = $this->decodificarJson($payloadB64, 'contenido del token');
        $this->validarClaims($payload);
        return $payload;
    }

    /**
     * Convierte un payload verificado en los datos que usa la aplicación.
     * Devuelve null si el token no trae correo (la tabla usuarios lo exige).
     */
    public static function resumir(array $payload): ?array {
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $nombre = trim((string) ($payload['name'] ?? ''));
        if ($nombre === '') {
            $nombre = explode('@', $email)[0];
        }

        $foto = (string) ($payload['picture'] ?? '');
        if (!filter_var($foto, FILTER_VALIDATE_URL)) {
            $foto = '';
        }

        return [
            'uid'              => (string) ($payload['sub'] ?? ''),
            'email'            => $email,
            'nombre'           => $nombre,
            'foto_url'         => $foto !== '' ? $foto : null,
            'proveedor'        => self::proveedor($payload),
            'email_verificado' => !empty($payload['email_verified']),
        ];
    }

    /** Normaliza el proveedor del inicio de sesión ('google.com' -> 'google'). */
    public static function proveedor(array $payload): string {
        $crudo = strtolower(trim((string) ($payload['firebase']['sign_in_provider'] ?? '')));
        $crudo = preg_replace('/\.com$/', '', $crudo) ?? '';
        $conocidos = ['google', 'github', 'facebook', 'twitter', 'apple', 'microsoft', 'yahoo', 'password', 'phone', 'anonymous'];
        return in_array($crudo, $conocidos, true) ? $crudo : 'desconocido';
    }

    private function validarClaims(array $payload): void {
        $emisoresValidos = [
            'https://securetoken.google.com/' . $this->projectId,
            'http://securetoken.google.com/' . $this->projectId,
        ];
        if (!in_array((string) ($payload['iss'] ?? ''), $emisoresValidos, true)) {
            throw new RuntimeException('El token no fue emitido para este proyecto');
        }
        if ((string) ($payload['aud'] ?? '') !== $this->projectId) {
            throw new RuntimeException('El destinatario del token no coincide');
        }
        if ((string) ($payload['sub'] ?? '') === '') {
            throw new RuntimeException('El token no identifica a ningún usuario');
        }

        $ahora = time();
        $exp = (int) ($payload['exp'] ?? 0);
        if ($exp <= $ahora + self::DESFASE_TOLERADO) {
            throw new RuntimeException('El token ya expiró');
        }
        $iat = (int) ($payload['iat'] ?? 0);
        if ($iat > $ahora + self::DESFASE_TOLERADO) {
            throw new RuntimeException('El token fue emitido en el futuro');
        }
    }

    private function obtenerCertificados(bool $forzar = false): array {
        $cache = $this->leerArchivoCache();
        $guardados = is_array($cache['certs'] ?? null) ? $cache['certs'] : [];

        if (!$forzar) {
            if ($guardados !== [] && (int) ($cache['expira'] ?? 0) > time()) {
                return $guardados;
            }
        } elseif ($guardados !== [] && time() - (int) ($cache['refresco'] ?? 0) < self::ESPERA_REFRESCO) {
            throw new RuntimeException('No se encontró la clave pública del token');
        }

        $ultimoError = 'No se pudieron obtener los certificados de Google';
        for ($intento = 1; $intento <= self::INTENTOS_DESCARGA; $intento++) {
            try {
                $certificados = $this->descargarCertificados();
                $this->guardarCache($certificados);
                return $certificados;
            } catch (RuntimeException $e) {
                $ultimoError = $e->getMessage();
                if ($intento < self::INTENTOS_DESCARGA) {
                    usleep(250000);
                }
            }
        }

        // Una caché caducada aún tiene certificados genuinos: solo puede causar un rechazo falso, nunca una aceptación falsa.
        if ($guardados !== [] && (int) ($cache['expira'] ?? 0) + self::MARGEN_CADUCADA > time()) {
            return $guardados;
        }
        throw new RuntimeException($ultimoError);
    }

    private function leerArchivoCache(): array {
        if (!is_file($this->rutaCache)) {
            return [];
        }
        $crudo = @file_get_contents($this->rutaCache);
        if ($crudo === false) {
            return [];
        }
        $cache = json_decode($crudo, true);
        return is_array($cache) ? $cache : [];
    }

    private function guardarCache(array $certificados): void {
        $directorio = dirname($this->rutaCache);
        if (!is_dir($directorio)) {
            @mkdir($directorio, 0775, true);
        }
        $ahora = time();
        $datos = json_encode(['expira' => $ahora + self::VIDA_CACHE, 'refresco' => $ahora, 'certs' => $certificados]);
        // Si el disco falla seguimos igual: solo perdemos la caché, no la autenticación.
        @file_put_contents($this->rutaCache, $datos, LOCK_EX);
    }

    /** @return array<string,string> */
    private function descargarCertificados(): array {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extensión curl de PHP no está habilitada');
        }

        $ch = curl_init(self::CERT_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $cuerpo = curl_exec($ch);
        $fallo = curl_error($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if (!is_string($cuerpo) || $estado !== 200) {
            throw new RuntimeException('No se pudieron descargar los certificados de Google' . ($fallo !== '' ? ': ' . $fallo : ' (HTTP ' . $estado . ')'));
        }

        $certificados = json_decode($cuerpo, true);
        if (!is_array($certificados) || $certificados === []) {
            throw new RuntimeException('Los certificados de Google llegaron en un formato inesperado');
        }
        foreach ($certificados as $kid => $pem) {
            if (!is_string($kid) || !is_string($pem)) {
                throw new RuntimeException('Los certificados de Google llegaron en un formato inesperado');
            }
        }
        return $certificados;
    }

    private function decodificarJson(string $base64Url, string $queEs): array {
        $crudo = $this->decodificarBase64Url($base64Url);
        $datos = json_decode($crudo, true);
        if (!is_array($datos)) {
            throw new RuntimeException('La ' . $queEs . ' no es JSON válido');
        }
        return $datos;
    }

    private function decodificarBase64Url(string $valor): string {
        $normal = strtr($valor, '-_', '+/');
        $relleno = strlen($normal) % 4;
        if ($relleno > 0) {
            $normal .= str_repeat('=', 4 - $relleno);
        }
        $decodificado = base64_decode($normal, true);
        if ($decodificado === false) {
            throw new RuntimeException('El token contiene base64 inválido');
        }
        return $decodificado;
    }
}
