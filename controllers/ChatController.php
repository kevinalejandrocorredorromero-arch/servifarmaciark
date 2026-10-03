<?php

class ChatController extends Controlador {
    public function __construct() {
        parent::__construct();
    }

    public function send(): void {
        $input = $this->getInput();
        $userMessage = trim((string) ($input['message'] ?? ''));
        $conversationHistory = $input['history'] ?? [];

        if ($userMessage === '' || mb_strlen($userMessage, 'UTF-8') > 1000) {
            $this->error('Mensaje requerido o demasiado largo');
            return;
        }
        if (!is_array($conversationHistory) || count($conversationHistory) > 20) {
            $this->error('Historial inválido o demasiado largo');
            return;
        }
        foreach ($conversationHistory as $msg) {
            if (!is_array($msg) || !isset($msg['content']) || mb_strlen((string) $msg['content'], 'UTF-8') > 2000) {
                $this->error('Historial inválido');
                return;
            }
        }

        $rateKey = 'chat_' . sha1((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . '|' . session_id());
        $rateFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $rateKey . '.json';
        $rate = is_file($rateFile) ? json_decode((string) @file_get_contents($rateFile), true) : null;
        $now = time();
        if (!is_array($rate) || ($now - (int) ($rate['inicio'] ?? 0)) >= 60) {
            $rate = ['inicio' => $now, 'cantidad' => 0];
        }
        $rate['cantidad']++;
        @file_put_contents($rateFile, json_encode($rate), LOCK_EX);
        if ($rate['cantidad'] > 20) {
            $this->error('Demasiadas solicitudes. Intenta nuevamente en un minuto.', 429);
            return;
        }

        $config = require __DIR__ . '/../config/config.php';
        $apiKey = $config['ai']['api_key'];
        $model = $config['ai']['model'];
        $apiUrl = $config['ai']['api_url'];

        $productModel = new ProductoModelo();
        $productos = $productModel->obtenerTodos();
        $relevantes = $this->filtrarProductos($productos, $userMessage);
        // Si hay coincidencias, enviamos solo esas (contexto corto y preciso).
        // Si no hay coincidencias, enviamos un subconjunto acotado para no reventar la ventana del modelo.
        $productContext = $this->buildProductContext(!empty($relevantes) ? $relevantes : array_slice($productos, 0, 60));
        $systemPrompt = $this->buildSystemPrompt($productContext);

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($conversationHistory as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            // Gemini gasta parte de este presupuesto en razonamiento interno:
            // con muy pocos tokens puede responder vacío.
            'max_tokens' => 2048,
            'temperature' => 0.7,
        ];

        // Google a veces devuelve 429/5xx por picos de demanda: reintentamos una
        // vez tras una breve espera antes de fallarle al usuario.
        $httpCode = 0;
        $response = false;
        $curlError = '';
        for ($intento = 1; $intento <= 2; $intento++) {
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                CURLOPT_TIMEOUT => 25,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError === '' && !in_array($httpCode, [429, 500, 502, 503, 504], true)) {
                break;
            }
            if ($intento === 1) {
                sleep(2);
            }
        }

        if ($curlError) {
            error_log('[chat] Error cURL del proveedor: ' . $curlError);
            $this->error('No se pudo conectar con el asistente.', 502);
            return;
        }

        if ($httpCode !== 200) {
            error_log('[chat] Proveedor respondió HTTP ' . $httpCode . ': ' . substr((string) $response, 0, 500));
            $this->error('El asistente no está disponible temporalmente.', 502);
            return;
        }

        $result = json_decode($response, true);
        if (!$result || empty($result['choices'][0]['message']['content'])) {
            error_log('[chat] Respuesta sin contenido del proveedor: ' . substr((string) $response, 0, 500));
            $this->error('No se pudo generar una respuesta', 500);
            return;
        }

        $this->success([
            'reply' => $result['choices'][0]['message']['content'],
            'model' => $model,
        ]);
    }

    private function filtrarProductos(array $productos, string $consulta): array {
        $stopwords = ['que','hay','de','la','el','lo','los','las','una','uno','un','y','o','para','por','con','del','al','se','su','si','no','en','es','son','fue','fui','como','cuanto','cuanta','cuantos','tienen','tiene','disponible','disponibles','stock','precio','precios','costo','cuesta','hay','algo','cual','cuales','este','esta','estos','estas','mi','tu','su'];
        $q = mb_strtolower(trim($consulta), 'UTF-8');
        $q = preg_replace('/[^a-z0-9\sáéíóúñü]/u', ' ', $q);
        $terminos = array_filter(
            array_map('trim', preg_split('/\s+/', $q)),
            fn($t) => mb_strlen($t) > 2 && !in_array($t, $stopwords, true)
        );
        if (empty($terminos)) {
            return [];
        }

        $puntuados = [];
        foreach ($productos as $p) {
            $nombre = mb_strtolower($p['name'] ?? '', 'UTF-8');
            $texto = $nombre . ' ' . mb_strtolower(($p['category'] ?? '') . ' ' . ($p['description'] ?? ''), 'UTF-8');
            $puntaje = 0;
            foreach ($terminos as $t) {
                // Coincidencia como palabra completa en el nombre: peso mayor
                if (preg_match('/\b' . preg_quote($t, '/') . '\b/u', $nombre)) {
                    $puntaje += 3;
                } elseif (strpos($texto, $t) !== false) {
                    $puntaje += 1;
                }
            }
            if ($puntaje > 0) {
                $puntuados[] = ['p' => $p, 'score' => $puntaje];
            }
        }

        // Ordenar por puntaje descendente y quedarnos con los más relevantes
        usort($puntuados, fn($a, $b) => $b['score'] <=> $a['score']);
        $top = array_slice($puntuados, 0, 15);
        return array_map(fn($x) => $x['p'], $top);
    }

    private function buildProductContext(array $products): string {
        if (empty($products)) return "No hay productos disponibles actualmente.";

        $grouped = [];
        foreach ($products as $p) {
            $cat = $p['category'] ?? 'Sin categoría';
            $grouped[$cat][] = $p;
        }

        $context = "INVENTARIO DISPONIBLE (todos estos productos TIENEN stock y están disponibles en la droguería):\n\n";
        foreach ($grouped as $category => $items) {
            $context .= "Categoría: " . strtoupper($category) . "\n";
            foreach ($items as $item) {
                $desc = !empty($item['description']) ? " - " . $item['description'] : "";
                $context .= "- {$item['name']} | precio: $" . number_format($item['price'], 0, ',', '.') . "{$desc}\n";
            }
            $context .= "\n";
        }
        return $context;
    }

    private function buildSystemPrompt(string $productContext): string {
        $prompt = "Eres el asistente virtual de SERVIFARMACIA RK, una farmacia colombiana. Tu única función es proporcionar información sobre productos del inventario y datos de la droguería. No debes dar consejos médicos, diagnósticos ni recomendaciones de dosis.\n\n";
        $prompt .= "INFORMACIÓN DE LA DROGUERÍA:\n";
        $prompt .= "- Nombre: SERVIFARMACIA RK\n";
        $prompt .= "- Dirección: Cra. 67 # 61- 24, Bogotá, Colombia\n";
        $prompt .= "- Teléfono: 3115631854\n";
        $prompt .= "- Correo: info@servifarmaciark.com\n";
        $prompt .= "- Horario: Lunes a Domingo, 8:00 AM - 8:00 PM\n";
        $prompt .= "- Misión: Brindar servicios farmacéuticos de calidad, garantizando el acceso a medicamentos seguros y efectivos, con un servicio personalizado que contribuya al bienestar y la salud de nuestra comunidad.\n";
        $prompt .= "- Visión: Ser la farmacia líder en la región, reconocida por nuestra excelencia en el servicio, innovación tecnológica y compromiso con la salud integral de nuestros clientes.\n";
        $prompt .= "- Términos y Condiciones: Al usar nuestro sitio web y servicios, aceptas nuestros términos. Todos los productos están sujetos a disponibilidad de stock. Los precios pueden variar sin previo aviso. No nos hacemos responsables por el uso indebido de los medicamentos. Para devoluciones, el producto debe estar sellado y dentro de los 30 días posteriores a la compra.\n";
        $prompt .= "- Políticas de Privacidad: Tus datos personales son tratados con confidencialidad. Solo utilizamos tu información para procesar pedidos y mejorar nuestro servicio. No compartimos datos con terceros sin tu consentimiento. Puedes solicitar la eliminación de tus datos en cualquier momento contactándonos.\n\n";
        $prompt .= "REGLAS ESTRICTAS:\n";
        $prompt .= "1. SOLO responde preguntas sobre productos del inventario o información de la droguería.\n";
        $prompt .= "2. NUNCA inventes productos que no estén en el inventario.\n";
        $prompt .= "3. Si preguntan por productos para un síntoma (dolor, fiebre, alergia, etc.), lista los productos del inventario que sirvan para eso con su precio y función, pero NUNCA digas \"te recomiendo\" ni des diagnósticos.\n";
        $prompt .= "4. NUNCA recomiendes dosis, ni reemplaces la opinión de un profesional de salud.\n";
        $prompt .= "5. Cuando menciones un producto, incluye SIEMPRE el precio y una breve descripción de su función/uso. Si el usuario pregunta explícitamente por el stock o disponibilidad, PUEDES indicar que el producto SÍ está disponible (todos los que aparecen en el INVENTARIO DISPONIBLE lo están).\n";
        $prompt .= "6. Si preguntan por un producto que NO aparece en el INVENTARIO DISPONIBLE de arriba, responde solo: \"Ese producto no está disponible actualmente.\" Sin sugerir otros productos. IMPORTANTE: si el producto SÍ aparece en el inventario, confirmalo como disponible y nunca digas que no tienes información sobre él.\n";
        $prompt .= "7. Si preguntan por dirección, teléfono, horario, correo, misión, visión, términos o políticas, responde únicamente con la información de la droguería de arriba.\n";
        $prompt .= "8. Responde SOLO lo que te preguntan, de forma breve y directa. No añadas información extra ni productos no solicitados.\n";
        $prompt .= "9. Responde SIEMPRE en español, claro, amable y profesional. NO uses formato markdown (nada de asteriscos, almohadillas ni guiones de lista): escribe texto plano, y si listas productos usa una línea por producto empezando con un guion simple.\n";
        $prompt .= "10. Si la pregunta no tiene relación con productos, la droguería o sus servicios, di: \"Solo puedo brindarte información sobre nuestros productos y la droguería.\"\n\n";
        $prompt .= "INVENTARIO DISPONIBLE:\n" . $productContext;
        return $prompt;
    }
}
