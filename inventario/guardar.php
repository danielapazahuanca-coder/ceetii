<?php
$nombre = $_POST['nombre'] ?? '';
$ubicacion = $_POST['ubicacion'] ?? '';

// Lógica de generación de código (la mantenemos igual)
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

$ubiLetra = strtoupper(substr($ubicacion, 0, 1));
$ubiNum = preg_replace('/[^0-9]/', '', $ubicacion);
$ubiCod = $ubiLetra . $ubiNum;
$base_codigo = $ubiCod . "-" . $nomCod;

// Verificación de correlativo
$url_check = "http://localhost/api_ceti/public/index.php/activos?buscar=" . urlencode($base_codigo);
$res_check = @file_get_contents($url_check);
$data_check = json_decode($res_check, true);
$lista = $data_check['data'] ?? [];
$correlativo = count($lista) + 1;
$codigo_final = $base_codigo . "-" . str_pad($correlativo, 2, "0", STR_PAD_LEFT);

// Preparamos los datos para la API
$data = [
    'nombre'         => $nombre,
    'codigo_activo'  => $codigo_final,
    'estado_id'      => (int)($_POST['estado_id'] ?? 1),
    'ubicacion'      => $ubicacion,
    'precio_compra'  => (float)($_POST['precio_compra'] ?? 0),
    'responsable'    => $_POST['responsable'] ?? '',
    'observaciones'  => $_POST['observaciones'] ?? ''
];

$url = "http://localhost/api_ceti/public/index.php/activos";
$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data), 
        'ignore_errors' => true // Permite leer la respuesta aunque sea un error 400 o 500
    ],
];

$context = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result !== FALSE) {
    $res = json_decode($result, true);
    // Si el status es success o la API devuelve un código de creado (201)
    if (isset($res['status']) && ($res['status'] === 'success' || $res['status'] === 201)) {
        header("Location: activos.php");
        exit();
    } else {
        // Muestra el error real que viene de la API para que sepas qué pasó
        echo "<h3>Error al guardar en la base de datos:</h3>";
        echo "<p>Mensaje: " . ($res['message'] ?? 'Error no especificado por la API') . "</p>";
        echo "<pre>Detalles técnicos: "; print_r($res); echo "</pre>";
        echo "<a href='crear.php'>Volver al formulario</a>";
    }
} else {
    echo "Error crítico: No hay conexión con la API en $url";
}
?>