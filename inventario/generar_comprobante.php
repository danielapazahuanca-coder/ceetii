<?php
session_start();
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 2], true)) {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/config_api.php';
date_default_timezone_set('America/La_Paz');

require_once 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de préstamo no proporcionado.");
}

$url = API_BASE_URL . "/prestamos/" . $id;
$response = @file_get_contents($url);
$resultado = json_decode($response, true);
$p = $resultado['data'] ?? null;

if (!$p) {
    die("No se encontró la información del préstamo.");
}

$path = 'img/logo.png';
$base64 = '';
if (file_exists($path)) {
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

$es_devolucion = !empty($p['fecha_devolucion']);
$titulo_documento = $es_devolucion ? "ACTA DE DEVOLUCIÓN" : "ACTA DE PRÉSTAMO";

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; line-height: 1.5; color: #000; }
        
        .header { width: 100%; border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px; }
        .logo { width: 70px; }
        .titulo-container { text-align: right; }
        .titulo-doc { font-size: 17px; font-weight: bold; margin: 0; }
        .subtitulo { font-size: 10px; margin: 0; }

        .seccion-titulo { background-color: #f2f2f2; padding: 5px 10px; font-weight: bold; border-left: 4px solid #000; margin: 15px 0 10px 0; }
        
        .tabla-datos { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .tabla-datos th, .tabla-datos td { padding: 8px; border: 1px solid #ccc; text-align: left; }
        .tabla-datos th { background-color: #f9f9f9; width: 30%; }

        .tabla-activos { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla-activos th { background-color: #000; color: white; padding: 7px; border: 1px solid #000; }
        .tabla-activos td { padding: 7px; border: 1px solid #ccc; text-align: center; }

        .tabla-firmas { width: 100%; margin-top: 80px; }
        .firma-box { width: 45%; text-align: center; border-top: 1px solid #000; padding-top: 10px; }
        
        .footer { position: fixed; bottom: -10px; width: 100%; text-align: center; font-size: 8px; color: #777; }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td style="width: 20%;">
                <?php if($base64): ?>
                    <img src="<?= $base64 ?>" class="logo">
                <?php else: ?>
                    <strong style="font-size: 20px;">CEETII</strong>
                <?php endif; ?>
            </td>
            <td class="titulo-container">
                <p class="titulo-doc"><?= $titulo_documento ?></p>
                <p class="subtitulo">Instituto Tecnológico CEETII - Gestión 2026</p>
            </td>
        </tr>
    </table>

    <div class="bloque-info">
        <p>Se registra la siguiente información detallada del movimiento de activos:</p>
        
        <div class="seccion-titulo">DATOS GENERALES</div>
        <table class="tabla-datos">
            <tr>
                <th>Solicitante:</th>
                <td><?= htmlspecialchars($p['solicitante']) ?></td>
            </tr>
            <tr>
                <th>C.I. / Documento:</th>
                <td><?= htmlspecialchars($p['documento_identidad']) ?></td>
            </tr>
            <tr>
                <th>Estado del Activo:</th>
                <td>
                    <strong><?= $es_devolucion ? 'DEVUELTO' : 'ENTREGADO EN CALIDAD DE PRÉSTAMO' ?></strong>
                </td>
            </tr>
        </table>

        <div class="seccion-titulo">LISTA DE ACTIVOS</div>
        <table class="tabla-activos">
            <thead>
                <tr>
                    <th width="15%">Cantidad</th>
                    <th>Nombre del Activo</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $activos_lista = explode(', ', $p['nombre_activo'] ?? ''); 
                foreach($activos_lista as $item): 
                    if(empty($item)) continue;
                    $partes = explode('x ', $item);
                    $cant = count($partes) > 1 ? $partes[0] : "1";
                    $nom = count($partes) > 1 ? $partes[1] : $item;
                ?>
                <tr>
                    <td><?= htmlspecialchars($cant) ?></td>
                    <td style="text-align: left;"><?= htmlspecialchars($nom) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="seccion-titulo">REGISTRO DE FECHAS</div>
        <table class="tabla-datos">
            <tr>
                <th>Fecha de Entrega:</th>
                <td><?= date('d/m/Y', strtotime($p['fecha_prestamo'])) ?></td>
            </tr>
            <?php if($es_devolucion): ?>
            <tr>
                <th>Fecha de Devolución:</th>
                <td><?= date('d/m/Y', strtotime($p['fecha_devolucion'])) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <p style="margin-top: 25px; text-align: justify;">
            <strong>Nota:</strong> El solicitante se compromete a devolver los activos en las mismas condiciones que le fueron entregados. Cualquier daño o pérdida es responsabilidad directa del mismo.
        </p>
    </div>

    <table class="tabla-firmas">
        <tr>
            <td class="firma-box">
                Entregado por<br>
                <strong>INSTITUTO TECNOLÓGICO CEETII</strong><br>
                <small>Firma y Sello</small>
            </td>
            <td style="width: 10%;"></td>
            <td class="firma-box">
                Recibí Conforme (Solicitante)<br>
                <strong><?= htmlspecialchars($p['solicitante']) ?></strong><br>
                C.I.: <?= htmlspecialchars($p['documento_identidad']) ?>
            </td>
        </tr>
    </table>

    <div class="footer">
        Documento oficial - Generado el <?= date('d/m/Y') ?>
    </div>

</body>
</html>
<?php
$html = ob_get_clean();
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$dompdf->stream("Comprobante_".$p['id'].".pdf", ["Attachment" => false]);
?>