<?php

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: activos.php");
    exit;
}

$url = "http://localhost/api_ceti/public/index.php/activos/$id";

$options = [
    "http" => [
        "method" => "DELETE",
        "header" => "Content-Type: application/json"
    ]
];

$context = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

header("Location: activos.php");
exit;

?>