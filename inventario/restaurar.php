<?php
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: papelera.php");
    exit;
}

$url = "http://localhost/api_ceti/public/index.php/activos/$id";

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