<?php
date_default_timezone_set('America/La_Paz');

$activo_id = isset($_POST['activo_id']) ? (int)$_POST['activo_id'] : 0;
$cantidad  = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
$solicitante = $_POST['solicitante'] ?? '';
$documento = $_POST['documento_identidad'] ?? '';

if ($activo_id === 0 || empty($solicitante)) {
    die("Error: Datos incompletos.");
}

$data = [
    'activo_id'           => $activo_id,
    'cantidad'            => $cantidad,
    'solicitante'         => $solicitante,
    'documento_identidad' => $documento,
    'fecha_prestamo'      => date('Y-m-d H:i:s'),
    'estado'              => 'Prestado'
];

$url = "http://localhost/api_ceti/public/index.php/prestamos";
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
$response = json_decode($result, true);

if (isset($response['status']) && ($response['status'] == 'success' || $response['status'] == 201)) {
    header("Location: prestamos.php");
    exit();
} else {
    echo "<h3>Error al guardar en la API:</h3>";
    echo "<pre>"; print_r($response); echo "</pre>";
    echo "<a href='prestamos.php'>Regresar</a>";
}