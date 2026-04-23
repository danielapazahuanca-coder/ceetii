<?php
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: prestamos.php");
    exit();
}

$url = "http://localhost/api_ceti/public/index.php/prestamos/" . $id;
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