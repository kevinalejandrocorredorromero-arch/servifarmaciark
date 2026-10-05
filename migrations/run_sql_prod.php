<?php
// Ejecuta un archivo .sql contra una base MySQL usando mysqli con SSL (Aiven/MySQL 8),
// porque el cliente mysql.exe de XAMPP (MariaDB) no soporta caching_sha2_password.
// Uso: php migrations/run_sql_prod.php <archivo.sql> <host> <puerto> <bd> <usuario>
// La contraseña se pide por teclado y no queda en el historial de la consola.
if ($argc < 6) {
    fwrite(STDERR, 'Uso: php migrations/run_sql_prod.php <archivo.sql> <host> <puerto> <bd> <usuario>' . PHP_EOL);
    exit(1);
}
[, $archivo, $host, $puerto, $bd, $usuario] = $argv;
$sql = file_get_contents($archivo);
if ($sql === false) {
    fwrite(STDERR, "No se pudo leer el archivo: {$archivo}" . PHP_EOL);
    exit(1);
}

echo "Contraseña de {$usuario} en {$host}: ";
$pass = rtrim(fgets(STDIN), "\r\n");

mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = mysqli_init();
$mysqli->ssl_set(null, null, __DIR__ . '/../ca.pem', null, null);
$ok = @mysqli_real_connect($mysqli, $host, $usuario, $pass, $bd, (int) $puerto, null, MYSQLI_CLIENT_SSL);
if (!$ok) {
    $errorSsl = $mysqli->connect_error;
    $mysqli->close();
    // Reintento sin SSL: solo aplica a servidores que no lo soportan (XAMPP local);
    // Aiven exige SSL del lado del servidor, así que allá un intento sin SSL fallaría igual.
    $mysqli = mysqli_init();
    $ok = mysqli_real_connect($mysqli, $host, $usuario, $pass, $bd, (int) $puerto);
    if (!$ok) {
        fwrite(STDERR, 'Error de conexión con SSL: ' . $errorSsl . PHP_EOL
            . 'Error de conexión sin SSL: ' . $mysqli->connect_error . PHP_EOL);
        exit(1);
    }
    fwrite(STDERR, 'Aviso: el servidor no aceptó SSL; se continúa sin cifrar.' . PHP_EOL);
}
mysqli_set_charset($mysqli, 'utf8mb4');

if (!mysqli_multi_query($mysqli, $sql)) {
    fwrite(STDERR, 'Error SQL: ' . mysqli_error($mysqli) . PHP_EOL);
    exit(1);
}
$n = 0;
do {
    if ($res = mysqli_store_result($mysqli)) {
        $cols = $res->fetch_fields();
        echo implode("\t", array_map(fn($c) => $c->name, $cols)) . PHP_EOL;
        while ($fila = $res->fetch_row()) {
            echo implode("\t", $fila) . PHP_EOL;
        }
        $res->free();
    } else {
        $n++;
        echo "sentencia {$n}: OK, filas afectadas = " . mysqli_affected_rows($mysqli) . PHP_EOL;
    }
    if (!mysqli_more_results($mysqli)) break;
} while (mysqli_next_result($mysqli));
if (mysqli_errno($mysqli)) {
    fwrite(STDERR, 'Error SQL: ' . mysqli_error($mysqli) . PHP_EOL);
    exit(1);
}
echo 'Migración completada sin errores.' . PHP_EOL;
$mysqli->close();
