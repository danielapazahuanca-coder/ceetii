<?php
session_start();
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 2], true)) {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/config_api.php';
$id = $_POST['id'] ?? null;
if (!$id) {
    header("Location: activos.php");
    exit;
}

$url = API_BASE_URL . "/activos/" . $id;

// preparamos los datos
$data = [
    "nombre"         => $_POST['nombre'],
    "codigo_activo"  => $_POST['codigo_activo'],
    "estado_id"      => (int)$_POST['estado_id'],
    "ubicacion"      => $_POST['ubicacion'],
    "precio_compra"  => (float)$_POST['precio_compra'],
    "responsable"    => $_POST['responsable'],
    "observaciones"  => $_POST['observaciones']
];

$json_data = json_encode($data);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); 
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($json_data)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);


header("Location: activos.php");
exit;
?>