<?php
// recoger datos
$data = [
    'nombre'         => $_POST['nombre'] ?? '',
    'codigo_activo'  => $_POST['codigo_activo'] ?? '',
    'estado_id'      => (int)($_POST['estado_id'] ?? 0),
    'ubicacion'      => $_POST['ubicacion'] ?? '',
    'precio_compra'  => (float)($_POST['precio_compra'] ?? 0),
    'responsable'    => $_POST['responsable'] ?? '',
    'observaciones'  => $_POST['observaciones'] ?? '',
    'fecha_registro' => date('Y-m-d'),
    'foto_path'      => null
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

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

// resp
if ($result !== FALSE) {
    $res = json_decode($result, true);
    
    if (isset($res['status']) && $res['status'] === 'success') {
        header("Location: activos.php");
        exit();
    } else {
        echo "<h3>Error de la API:</h3>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        echo "<a href='crear.php'>Volver</a>";
    }
} else {
    echo "No se pudo conectar con la API.";
}