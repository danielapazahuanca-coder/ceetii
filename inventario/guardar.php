<?php 

date_default_timezone_set('America/La_Paz');

$nombre = $_POST['nombre'] ?? '';
$ubicacion = $_POST['ubicacion'] ?? '';

if (empty($nombre) || empty($ubicacion)) {
    die("Error: Nombre y Ubicación son campos obligatorios. <a href='crear.php'>Volver</a>");
}

$nombreLimpio = trim(strtoupper($nombre));
$palabras = explode(' ', $nombreLimpio);
$importantes = [];
foreach($palabras as $p) {
    if(strlen(trim($p)) > 2) { $importantes[] = $p; }
}

if(count($importantes) >= 2) {
    $nomCod = substr($importantes[0], 0, 2) . substr($importantes[1], 0, 1);
} else {
    if(strlen($nombreLimpio) >= 3) {
        $nomCod = substr($nombreLimpio, 0, 2) . substr($nombreLimpio, -1);
    } else {
        $nomCod = str_pad($nombreLimpio, 3, 'X');
    }
}

$ubiFull = strtoupper(trim($ubicacion));
$ubiNum = preg_replace('/[^0-9]/', '', $ubiFull); // Extraer números

if ($ubiNum !== "") {

    $ubiCod = substr($ubiFull, 0, 2) . $ubiNum;
} else {
    $ubiCod = substr($ubiFull, 0, 3);
}

$base_codigo = $ubiCod . "-" . $nomCod;

$url_check = "http://localhost/api_ceti/public/index.php/activos?buscar=" . urlencode($base_codigo);
$res_check = @file_get_contents($url_check);
$data_check = json_decode($res_check, true);
$lista = $data_check['data'] ?? [];
$correlativo = count($lista) + 1;
$codigo_final = $base_codigo . "-" . str_pad($correlativo, 2, "0", STR_PAD_LEFT);

$fecha_input = $_POST['fecha_compra'] ?? '';
$fecha_para_api = (!empty($fecha_input)) ? $fecha_input : null;

$data = [
    'nombre'          => $nombre,
    'codigo_activo'   => $codigo_final,
    'estado_id'       => (int)($_POST['estado_id'] ?? 1),
    'ubicacion'       => $ubicacion,
    'precio_compra'   => (float)($_POST['precio_compra'] ?? 0),
    'fecha_compra'    => $fecha_para_api, 
    'responsable'     => $_POST['responsable'] ?? '',
    'observaciones'   => $_POST['observaciones'] ?? ''
];

$url = "http://localhost/api_ceti/public/index.php/activos";
$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data), 
        'ignore_errors' => true 
    ],
];

$context = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result !== FALSE) {
    $res = json_decode($result, true);
    $status_code = (isset($res['status']) && ($res['status'] === 'success' || $res['status'] === 201 || $res['status'] === 200));
    
    if ($status_code) {
        header("Location: activos.php");
        exit();
    } else {
        echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #ff0000; background: #fff5f5;'>";
        echo "<h3 style='color: #d32f2f;'>Error al guardar en la base de datos</h3>";
        echo "<p><strong>Mensaje:</strong> " . ($res['message'] ?? 'La API no devolvió un mensaje de error.') . "</p>";
        echo "<a href='crear.php' style='display:inline-block; padding:10px 20px; background:#333; color:#fff; text-decoration:none; border-radius:5px;'>Volver</a>";
        echo "</div>";
    }
} else {
    echo "<h2 style='color:red;'>Error crítico: No se pudo conectar con la API</h2>";
    echo "<a href='crear.php'>Reintentar</a>";
}
?>