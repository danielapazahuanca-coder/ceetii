<?php
session_start();
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 2], true)) {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/config_api.php';
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: prestamos.php");
    exit();
}

$url = API_BASE_URL . "/prestamos/" . $id;
$data = ['fecha_devolucion' => date('Y-m-d H:i:s')];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'PUT',
        'content' => json_encode($data),
        'ignore_errors' => true
    ],
];

$context = stream_context_create($options);
@file_get_contents($url, false, $context);

header("Location: prestamos.php");
exit();