<?php
session_start();
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 2], true)) {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/config_api.php';
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: papelera.php");
    exit;
}

$url = API_BASE_URL . "/activos/$id";

$data = ["activo_sistema" => 1];

$options = [
    "http" => [
        "method" => "PUT",
        "header" => "Content-Type: application/json",
        "content" => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

header("Location: activos.php");
exit;