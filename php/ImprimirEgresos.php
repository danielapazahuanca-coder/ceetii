<?php ob_start();
include("dbconnect.php");
 
$fecha=$_GET['fechaB'];
$fecha2=$_GET['fechaB2'];
$sucursal=$_SESSION['rainbow_sucursal'];
$usuario= $_SESSION['rainbow_username'];
//echo $fecha;
$sql = "select * from egresos where estado=1 and DATE(fecha)>='$fecha' and date(fecha)<='$fecha2' and sucursal_varchar='$sucursal' and usuario='$usuario'";
$sqlSum = "select sum(monto) as total from egresos where estado=1 and DATE(fecha)>='$fecha' and date(fecha)<='$fecha2' and sucursal_varchar='$sucursal' and usuario='$usuario'";

$q = $conn->query($sql);

$qs=$conn->query($sqlSum);
?>
<link rel="stylesheet" href="../css/AdminLTE.min.css">

<link href="css/datatable/datatable.css" rel="stylesheet" /> 
<html>
<body>
	<h2 style="text-align:center">Reporte de Egresos</h2>
	<div class="col-sm-2" style="float: left;">
		<?php 
			$qs = $conn->query($sqlSum);
									
			while($rs = $qs->fetch_assoc())
			{
				echo '<label class="col-sm-2 control-label" for="Old" style="float ;">TOTAL: </label>';
				echo '<input type="text" class="form-control" id="total" name="total"  style="background-color: #fff;" readonly value="'.number_format($rs['total'],2,',',' ').'"  />';
			}
		?>
		</div>
		<br>
		<br>
		<table class="table table-striped table-bordered table-hover" id="tSortable22" style="font-size: 12px;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Proveedor</th>
                                            <th>Fecha de Recibo</th>
                                            <th>Nro. Recibo</th>
                                            <th>Monto</th>
											<th>Detalle</th>
											<th>Fecha de Registro </th>
											
                                        </tr>
                                    </thead>
                                    <tbody>
									<?php
									//$sql = "select * from egresos where estado=1";
									$q = $conn->query($sql);
									$i=1;
									while($r = $q->fetch_assoc())
									{
									
									echo '<tr '.(($r['detalle']>0)?'class="danger"':'').'>
                                            <td>'.$i.'</td>
                                            <td>'.$r['proveedor'].'</td>
                                            <td>'.date("d M y", strtotime($r['fecha_recibo'])).'</td>
                                            <td>'.$r['nro_recibo'].'</td>
                                            <td>'.$r['monto'].'</td>
											<td>'.$r['detalle'].'</td>
											<td>'.$r['fecha'].'</td>
											
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
$dompdf=new DOMPDF();
$dompdf->load_Html(ob_get_clean());

$dompdf->render();
$pdf=$dompdf->output();
$filename='Egresos.pdf';
$dompdf->stream($filename,array("Attachment"=>0));


?>