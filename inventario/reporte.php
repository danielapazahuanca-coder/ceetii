<?php
// zona horaria
date_default_timezone_set('America/La_Paz');

require_once 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$busqueda = $_GET['buscar'] ?? '';
$ubicacion = $_GET['ubicacion'] ?? '';

$ver_resp = isset($_GET['col_resp']) || !isset($_GET['filtrar']);
$ver_precio = isset($_GET['col_precio']) || !isset($_GET['filtrar']);
$ver_fecha = isset($_GET['col_fecha']) || !isset($_GET['filtrar']);
$ver_obs = isset($_GET['col_obs']) || !isset($_GET['filtrar']);

$url = "http://localhost/api_ceti/public/index.php/activos?buscar=" . urlencode($busqueda) . "&ubicacion=" . urlencode($ubicacion);
$response = @file_get_contents($url);
$resultado = json_decode($response, true);
$activos = $resultado['data'] ?? [];

$path = 'img/logo.png';
$base64 = '';
if (file_exists($path)) {
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

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
        body { font-family: sans-serif; font-size: 10px; color: #000; }
        .header-table { width: 100%; border-bottom: 1px solid #000; margin-bottom: 15px; padding-bottom: 10px; }
        .logo { width: 60px; }
        .titulo-reporte { text-align: right; }
        .titulo-reporte h1 { margin: 0; font-size: 15px; text-transform: uppercase; }
        .titulo-reporte p { margin: 0; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { border: 1px solid #000; padding: 6px; text-align: left; background-color: #f2f2f2; font-size: 9px; }
        td { border: 1px solid #000; padding: 5px; vertical-align: top; }
        .footer { position: fixed; bottom: -20px; width: 100%; text-align: center; font-size: 8px; border-top: 0.5px solid #ccc; padding-top: 5px; }
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
            <td style="border:none;" class="titulo-reporte">
                <h1>REPORTE DE ACTIVOS - CEETII</h1>
                <p>Ubicación: <?= $ubicacion ?: 'Todas' ?> | Emisión: <?= date('d/m/Y H:i') ?></p>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="25%">Nombre</th>
                <th width="12%">Código</th>
                <th width="10%">Estado</th>
                <?php if($ver_resp): ?> <th>Responsable</th> <?php endif; ?>
                <?php if($ver_precio): ?> <th>Precio</th> <?php endif; ?>
                <?php if($ver_fecha): ?> <th>Fecha Registro</th> <?php endif; ?>
                <?php if($ver_obs): ?> <th>Obs.</th> <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($activos as $a): 
                $txt_estado = [1=>"Bueno", 2=>"Regular", 3=>"Malo", 4=>"Desecho"];
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($a['nombre']) ?></strong></td>
                <td><?= htmlspecialchars($a['codigo_activo']) ?></td>
                <td style="text-align: center;"><?= $txt_estado[$a['estado_id']] ?? 'N/A' ?></td>
                <?php if($ver_resp): ?> <td><?= htmlspecialchars($a['responsable']) ?></td> <?php endif; ?>
                <?php if($ver_precio): ?> <td>Bs. <?= number_format($a['precio_compra'], 2) ?></td> <?php endif; ?>
                <?php if($ver_fecha): ?> <td style="font-size: 8px;"><?= $a['fecha_registro'] ?></td> <?php endif; ?>
                <?php if($ver_obs): ?> <td style="font-size: 8px; font-style: italic;"><?= htmlspecialchars($a['observaciones'] ?? '') ?></td> <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Sistema de Inventario CEETII - El Alto (La Paz - Bolivia)
    </div>
</body>
</html>
<?php
$html = ob_get_clean();
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$dompdf->stream("Reporte_Activos_CEETII.pdf", ["Attachment" => false]);