<?php
// Reparación puntual de textos dañados por una importación con charset incorrecto.
// No inserta ni elimina datos: solo actualiza nombre/descripcion cuando contienen '?'.
require __DIR__ . '/config/config.php';
$cfg = require __DIR__ . '/config/config.php';
$db = $cfg['db'];
$mysqli = mysqli_connect($db['host'], $db['user'], $db['pass'], $db['name']);
if (!$mysqli) { fwrite(STDERR, "No se pudo conectar a MySQL\n"); exit(1); }
mysqli_set_charset($mysqli, 'utf8mb4');

$map = [
    'Champ?' => 'Champú', 'Beb?' => 'Bebé', 'Pa?al' => 'Pañal', 'Pa?ales' => 'Pañales',
    'Protecci?n' => 'Protección', 'protecci?n' => 'protección', 'Cicatrizaci?n' => 'Cicatrización',
    'l?grimas' => 'lágrimas', 'Jab?n' => 'Jabón', 'L?quido' => 'Líquido', 'L?quida' => 'Líquida',
    'reci?n' => 'recién', 'F?rmula' => 'Fórmula', 'S?per' => 'Súper', 'H?medas' => 'Húmedas',
    'Hipoalerg?nicas' => 'Hipoalergénicas', 'Delineador L?quido' => 'Delineador Líquido',
    'Precisi?n' => 'Precisión', 'L?piz' => 'Lápiz', 'Pesta?as' => 'Pestañas',
    'Antif?ngico' => 'Antifúngico', 'T?pico' => 'Tópico', 'Acn?' => 'Acné', 'Loci?n' => 'Loción',
    'At?pica' => 'Atópica', 'col?geno' => 'colágeno', 'Col?geno' => 'Colágeno',
    'Hidrataci?n' => 'Hidratación', 'T?nico' => 'Tónico', 'Botiqu?n' => 'Botiquín',
    'Gluc?metro' => 'Glucómetro', 'Ultras?nico' => 'Ultrasónico', 'v?as' => 'vías',
    'Vibraci?n' => 'Vibración', 'Ox?metro' => 'Oxímetro', 'Saturaci?n' => 'Saturación',
    'card?aca' => 'cardíaca', 'Tensi?metro' => 'Tensiómetro', 'Autom?tico' => 'Automático',
    'Monitoreo presi?n' => 'Monitoreo presión', 'Term?metro' => 'Termómetro', 'r?pida' => 'rápida',
    'Antis?ptico' => 'Antiséptico', 'antiperspirante' => 'antiperspirante', 'Ba?o' => 'Baño',
    'Pa?uelos' => 'Pañuelos', 'Higi?nico' => 'Higiénico', 'Antibi?tico' => 'Antibiótico',
    'c?psulas' => 'cápsulas', 'Analg?sico' => 'Analgésico', 'antipir?tico' => 'antipirético',
    'Antihistam?nico' => 'Antihistamínico', 'inflamaci?n' => 'inflamación', 'acci?n' => 'acción',
    'Losart?n' => 'Losartán', 'presi?n' => 'presión', '?lceras' => 'úlceras',
    'vitam?nico' => 'vitamínico', 'u?as' => 'uñas', '?sea' => 'ósea', '?cido' => 'ácido',
    'gestaci?n' => 'gestación', 'Probi?ticos' => 'Probióticos', 'Multivitam?nico' => 'Multivitamínico',
    'Energ?a' => 'Energía', 'inmunol?gico' => 'inmunológico', 'B?sico' => 'Básico',
    'art?culos' => 'artículos', 'Champ? + Acondicionador' => 'Champú + Acondicionador',
    'F?lico' => 'Fólico', 'cicatrizaci?n' => 'cicatrización', 't?pico' => 'tópico',
    'ox?geno' => 'oxígeno', 'Prevenci?n' => 'Prevención', 'Antis?ptico' => 'Antiséptico',
];

$q = mysqli_query($mysqli, "SELECT id, nombre, descripcion FROM productos WHERE nombre LIKE '%?%' OR descripcion LIKE '%?%'");
$update = mysqli_prepare($mysqli, 'UPDATE productos SET nombre = ?, descripcion = ? WHERE id = ?');
$count = 0;
while ($row = mysqli_fetch_assoc($q)) {
    $name = $row['nombre']; $desc = $row['descripcion'];
    foreach ($map as $bad => $good) { $name = str_replace($bad, $good, $name); $desc = str_replace($bad, $good, $desc); }
    if ($name !== $row['nombre'] || $desc !== $row['descripcion']) {
        $id = (int)$row['id'];
        mysqli_stmt_bind_param($update, 'ssi', $name, $desc, $id);
        mysqli_stmt_execute($update);
        $count++;
    }
}
mysqli_stmt_close($update);
mysqli_close($mysqli);
echo "Productos actualizados: {$count}\n";
