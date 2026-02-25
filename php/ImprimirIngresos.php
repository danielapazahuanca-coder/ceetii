<?php ob_start();
include("dbconnect.php");

$sucursal=$_SESSION['rainbow_sucursal'];
$usuario=$_SESSION['rainbow_username'];
$fecha=$_GET['fechaB'];
$fecha2=$_GET['fechaB2'];
//echo $fecha;
$sql = "select * from fees_transaction where estado=1 and DATE(submitdate)>='$fecha' and date(submitdate)<='$fecha2' and sucursal_varchar='$sucursal' and usuario='$usuario'";

$sqlSum = "select sum(paid) as total from fees_transaction where estado=1 and DATE(submitdate)>='$fecha' and date(submitdate)<='$fecha2' and sucursal_varchar='$sucursal' and usuario='$usuario'";
$qs=$conn->query($sqlSum);

$q = $conn->query($sql);
?>
<link rel="stylesheet" href="../css/AdminLTE.min.css">

<link href="css/datatable/datatable.css" rel="stylesheet" /> 
<html>
<body>
	<h2 style="text-align:center">Reporte de Ingresos</h2>
		<!-- <label class="col-sm-2 control-label" for="Old" style="float ;">TOTAL: </label> -->
		<div class="col-sm-2" style="float: left;">
		<?php 
			$qs = $conn->query($sqlSum);
									
			while($rs = $qs->fetch_assoc())
			{
				echo '<label class="col-sm-2 control-label" for="Old" style="float ;">TOTAL: </label>';
				echo '<input type="text" class="form-control" id="total" name="total"  style="background-color: #fff;" readonly value="'. number_format($rs['total'],2,',',' ').'"  />';
			}
		?>
		</div>
		<br>
		<br>
		<table class="table table-striped table-bordered table-hover" id="tSortable22" style="font-size: 12px;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>IdIngreso</th>
                                            <th>IdEstu</th>
                                            <th>Estudiante</th>
                                            <th>Monto</th>
											<th>Detalle</th>
											<th>Fecha de Registro </th>
                                        </tr>
                                    </thead>
                                    <tbody>
									<?php
									/*$actual=date("Y-m-d");
									
									$sql = "select * from fees_transaction where estado=1 and DATE(submitdate)='$actual'";*/
									$q = $conn->query($sql);
									$i=1;
									while($r = $q->fetch_assoc())
									{
									
									echo '<tr '.(($r['transcation_remark']>0)?'class="danger"':'').'>
                                            <td>'.$i.'</td>
                                            <td>'.$r['id'].'</td>
                                            <td>'.$r['stdid'].'</td>
                                            <td>'.$r['nombre_estudiante'].'</td>
                                            <td>'.$r['paid'].'</td>
                                            <td>'.$r['transcation_remark'].'</td>
                                            <td>'.$r['submitdate'].'</td>					
											
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
$filename='Ingresos.pdf';
$dompdf->stream($filename,array("Attachment"=>0));


?>