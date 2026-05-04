<?php 
/**
 * GUARDAR ACTIVO - Sistema de Inventarios CEETII
 * Autor: Brandon Meza (Brand) / David Ramirez
 */

// 1. Configuración de Zona Horaria
date_default_timezone_set('America/La_Paz');

// 2. Recepción de datos básicos
$nombre = $_POST['nombre'] ?? '';
$ubicacion = $_POST['ubicacion'] ?? '';

if (empty($nombre) || empty($ubicacion)) {
    die("Error: Nombre y Ubicación son campos obligatorios. <a href='crear.php'>Volver</a>");
}

// 3. Lógica de generación automática de código
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

// 4. Verificación de correlativo consultando la API
$url_check = "http://localhost/api_ceti/public/index.php/activos?buscar=" . urlencode($base_codigo);
$res_check = @file_get_contents($url_check);
$data_check = json_decode($res_check, true);
$lista = $data_check['data'] ?? [];
$correlativo = count($lista) + 1;
$codigo_final = $base_codigo . "-" . str_pad($correlativo, 2, "0", STR_PAD_LEFT);

// 5. Validación y limpieza de la FECHA DE COMPRA
$fecha_input = $_POST['fecha_compra'] ?? '';
// Si el string está vacío, mandamos NULL, de lo contrario mandamos la fecha
$fecha_para_api = (!empty($fecha_input)) ? $fecha_input : null;

// 6. Preparación del JSON para la API
$data = [
    'nombre'          => $nombre,
    'codigo_activo'   => $codigo_final,
    'estado_id'       => (int)($_POST['estado_id'] ?? 1),
    'ubicacion'       => $ubicacion,
    'precio_compra'   => (float)($_POST['precio_compra'] ?? 0),
    'fecha_compra'    => $fecha_para_api, 
    'responsable'     => $_POST['responsable'] ?? '',
    'observaciones'   => $_POST['observaciones'] ?? ''
];

// 7. Envío de datos a la API mediante POST
$url = "http://localhost/api_ceti/public/index.php/activos";
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

// 8. Manejo de la respuesta
if ($result !== FALSE) {
    $res = json_decode($result, true);
    
    // Verificamos si la API devolvió éxito (200, 201 o status 'success')
    $status_code = (isset($res['status']) && ($res['status'] === 'success' || $res['status'] === 201 || $res['status'] === 200));
    
    if ($status_code) {
        // Redirección exitosa a la lista de activos
        header("Location: activos.php");
        exit();
    } else {
        // Error reportado por la API
        echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #ff0000; background: #fff5f5;'>";
        echo "<h3 style='color: #d32f2f;'>Error al guardar en la base de datos</h3>";
        echo "<p><strong>Mensaje:</strong> " . ($res['message'] ?? 'La API no devolvió un mensaje de error.') . "</p>";
        echo "<p><strong>Datos enviados:</strong></p><pre style='background:#eee; padding:10px;'>";
        print_r($data);
        echo "</pre>";
        echo "<a href='crear.php' style='display:inline-block; padding:10px 20px; background:#333; color:#fff; text-decoration:none; border-radius:5px;'>Volver al formulario</a>";
        echo "</div>";
    }
} else {
    // Error de conexión física con el servidor
    echo "<h2 style='color:red;'>Error crítico: No se pudo conectar con la API</h2>";
    echo "<p>Verifica que el servidor de la API en <b>$url</b> esté encendido.</p>";
    echo "<a href='crear.php'>Reintentar</a>";
}
?>