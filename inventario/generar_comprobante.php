<?php
// zona horaria
date_default_timezone_set('America/La_Paz');

require_once 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Obtener ID del préstamo
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de préstamo no proporcionado.");
}

// 2. Consultar datos del préstamo a la API
$url = "http://localhost/api_ceti/public/index.php/prestamos/" . $id;
$response = @file_get_contents($url);
$resultado = json_decode($response, true);
$p = $resultado['data'] ?? null;

if (!$p) {
    die("No se encontró la información del préstamo.");
}

// 3. Preparar Logo
$path = 'img/logo.png';
$base64 = '';
if (file_exists($path)) {
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// 4. Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; line-height: 1.6; color: #000; padding: 20px; }
        .header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px; }
        .logo { width: 80px; }
        .titulo { text-align: right; }
        .titulo h1 { margin: 0; font-size: 18px; }
        .titulo p { margin: 0; font-size: 10px; color: #444; }
        
        .contenido { margin-top: 30px; text-align: justify; }
        .datos-tabla { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .datos-tabla td { padding: 8px; border: 1px solid #ddd; }
        .label { font-weight: bold; background-color: #f9f9f9; width: 30%; }
        
        .firmas { margin-top: 80px; width: 100%; }
        .firma-box { text-align: center; width: 45%; border-top: 1px solid #000; padding-top: 10px; }
        .espacio { width: 10%; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #777; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="border:none;">
                <?php if($base64): ?>
                    <img src="<?= $base64 ?>" class="logo">
                <?php endif; ?>
            </td>
            <td style="border:none;" class="titulo">
                <h1>ACTA DE PRÉSTAMO DE ACTIVOS</h1>
                <p>Centro de Estudiantes CEETII - Gestión 2026</p>
                <p>Nº Control: PR-<?= str_pad($p['id'], 5, "0", STR_PAD_LEFT) ?></p>
            </td>
        </tr>
    </table>

    <div class="contenido">
        <p>En la ciudad de El Alto, a los <?= date('d') ?> días del mes de <?= date('m') ?> del año <?= date('Y') ?>, se procede a realizar la entrega en calidad de préstamo del activo que se detalla a continuación:</p>
        
        <table class="datos-tabla">
            <tr>
                <td class="label">Activo:</td>
                <td><?= htmlspecialchars($p['nombre_activo']) ?></td>
            </tr>
            <tr>
                <td class="label">Cantidad:</td>
                <td><strong><?= htmlspecialchars($p['cantidad'] ?? '1') ?> Unidad(es)</strong></td>
            </tr>
            <tr>
                <td class="label">Código de Inventario:</td>
                <td><?= htmlspecialchars($p['activo_id']) ?> (Ref: <?= $p['id'] ?>)</td>
            </tr>
            <tr>
                <td class="label">Solicitante:</td>
                <td><?= htmlspecialchars($p['solicitante']) ?></td>
            </tr>
            <tr>
                <td class="label">C.I. / Documento:</td>
                <td><?= htmlspecialchars($p['documento_identidad']) ?></td>
            </tr>
            <tr>
                <td class="label">Fecha y Hora Salida:</td>
                <td><?= date('d/m/Y H:i', strtotime($p['fecha_prestamo'])) ?></td>
            </tr>
            <tr>
                <td class="label">Estado actual:</td>
                <td><?= htmlspecialchars($p['estado']) ?></td>
            </tr>
        </table>

        <p style="margin-top: 20px;"><strong>Cláusula de Responsabilidad:</strong> El solicitante se compromete a cuidar el/los activo(s) mencionado(s) y devolverlos en las mismas condiciones en las que fueron entregados. En caso de pérdida, daño parcial o total, el solicitante asumirá la responsabilidad de la reparación o reposición del bien.</p>
    </div>

    <table class="firmas">
        <tr>
            <td class="firma-box">
                Entregado por (CEETII)<br>
                <small>Sello y Firma</small>
            </td>
            <td class="espacio"></td>
            <td class="firma-box">
                Recibí Conforme (Solicitante)<br>
                Nombre: <?= htmlspecialchars($p['solicitante']) ?><br>
                C.I.: <?= htmlspecialchars($p['documento_identidad']) ?>
            </td>
        </tr>
    </table>

    <div class="footer">
        Este documento es un registro oficial del sistema de inventarios CEETII. Generado el <?= date('d/m/Y H:i:s') ?>.
    </div>
</body>
</html>
<?php
$html = ob_get_clean();
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$dompdf->stream("Comprobante_Prestamo_".$p['id'].".pdf", ["Attachment" => false]);