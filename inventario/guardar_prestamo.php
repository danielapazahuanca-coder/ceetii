<?php
session_start();
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 2], true)) {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/config_api.php';
date_default_timezone_set('America/La_Paz');

$items = isset($_POST['items']) ? $_POST['items'] : [];
$solicitante = $_POST['solicitante'] ?? '';
$documento = $_POST['documento_identidad'] ?? '';

if (empty($items) || empty($solicitante)) {
    die("Error: Debe seleccionar al menos un activo y llenar el nombre del solicitante. <a href='prestamos.php'>Regresar</a>");
}

$data = [
    'items'               => $items, 
    'solicitante'         => $solicitante,
    'documento_identidad' => $documento
];

$url = API_BASE_URL . "/prestamos";
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
    echo "<h3>Error al registrar el préstamo múltiple:</h3>";
    echo "<p>Mensaje: " . ($response['message'] ?? 'Error desconocido') . "</p>";
    echo "<pre>"; print_r($response); echo "</pre>";
    echo "<a href='prestamos.php'>Regresar al sistema</a>";
}