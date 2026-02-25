<?php 
ob_start();
include("dbconnect.php");

$fecha = $_GET['fechaB'];
$fecha2 = $_GET['fechaB2'];
$sucursal = $_SESSION['rainbow_sucursal'];

$sql = "SELECT de.id, de.codEstu, db.nombre_banco, s.sname, de.fecha_deposito, de.monto, de.detalle, de.fecha_registro 
        FROM depositos_estudiantes AS de 
        INNER JOIN student AS s ON de.codEstu = s.Cod_Estu 
        INNER JOIN depositos_banco AS db ON de.banco_id = db.id 
        WHERE de.estado = 1 
        AND DATE(de.fecha_registro) >= '$fecha' 
        AND DATE(de.fecha_registro) <= '$fecha2' 
        AND de.sucursal_varchar = '$sucursal' 
        AND db.sucursal_varchar = '$sucursal'";

$sqlSum = "SELECT SUM(monto) AS total 
           FROM depositos_estudiantes 
           WHERE estado = 1 
           AND DATE(fecha_registro) >= '$fecha' 
           AND DATE(fecha_registro) <= '$fecha2' 
           AND sucursal_varchar = '$sucursal'";

$q = $conn->query($sql);

if (!$q) {
    die("Error en la consulta SQL: " . $conn->error);
}

$qs = $conn->query($sqlSum);

if (!$qs) {
    die("Error en la consulta SQL: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Depósitos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        h2 {
            text-align: center;
        }
        .total-container {
            margin: 20px 0;
            text-align: left;
        }
        .total-label {
            font-weight: bold;
        }
        .total-value {
            font-size: 1.2em;
            background-color: #f0f0f0;
            padding: 5px;
            border: 1px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <h2>Reporte de Depósitos</h2>
    <div class="total-container">
        <?php 
            while($rs = $qs->fetch_assoc())
            {
                echo '<span class="total-label">TOTAL: </span>';
                echo '<span class="total-value">'.number_format($rs['total'], 2, ',', ' ').'</span>';
            }
        ?>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Banco</th>
                <th>Estudiante</th>
                <th>Fecha Depósito</th>
                <th>Monto</th>
                <th>Detalle</th>
                <th>Fecha de Registro</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        while($r = $q->fetch_assoc())
        {
            echo '<tr>
                    <td>'.$i.'</td>
                    <td>'.$r['nombre_banco'].'</td>
                    <td>'.$r['sname'].'</td>
                    <td>'.date("d M y", strtotime($r['fecha_deposito'])).'</td>
                    <td>'.number_format($r['monto'], 2, ',', ' ').'</td>
                    <td>'.$r['detalle'].'</td>
                    <td>'.$r['fecha_registro'].'</td>
                </tr>';
            $i++;
        }
        ?>
        </tbody>
    </table>
</body>
</html>
<?php
require_once('dompdf/autoload.inc.php');
use Dompdf\Dompdf;
$dompdf = new Dompdf();
$dompdf->loadHtml(ob_get_clean());

$dompdf->render();
$pdf = $dompdf->output();
$filename = 'Depositos.pdf';
$dompdf->stream($filename, array("Attachment" => 0));
?>
