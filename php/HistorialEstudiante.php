<?php ob_start();
include("dbconnect.php");
 

$sid=$_GET['student'];
$sucursal=$_SESSION['rainbow_sucursal'];

$sql = "select id,stdid,paid,submitdate,transcation_remark from fees_transaction  where sucursal_varchar='".$sucursal."' and stdid='".$sid."' and otros_ingresos=0";
$fq = $conn->query($sql);
/*if($fq->num_rows>0)
{*/


  $sqlD = "select id,stdid,paid_deposito,submitdate,transcation_remark from fees_transaction  where sucursal_varchar='".$sucursal."' and stdid='".$sid."' and otros_ingresos=1";
$fqD = $conn->query($sqlD);


 $sql = "select s.idRegistro,s.Cod_Estu,s.sname,s.balance,s.fees,s.contact,b.branch,s.joindate from student as s,branch as b where b.id=s.branch and  s.sucursal_varchar='".$sucursal."' and s.Cod_Estu='".$sid."'";

$sq = $conn->query($sql);
$sr = $sq->fetch_assoc();
?>
<link rel="stylesheet" href="../css/AdminLTE.min.css">

<link href="css/datatable/datatable.css" rel="stylesheet" /> 
<html>
<body>
	<h2 style="text-align: center">Historial de Pagos </h2>
	<?php
	$nombres=$sr['Cod_Estu'].$sr['sname'];
	echo'
	<h4>Información del Estudiante</h4>
	<div class="table-responsive">
		<table class="table table-bordered" style="border: 1px solid black">
			<tr>
			<th>Nombre</th>
			<td>'.$sr['sname'].'</td>
			<th>Banco</th>
			<td>'.$sr['branch'].'</td>
			</tr>
			<tr>
			<th>Contacto</th>
			<td>'.$sr['contact'].'</td>
			<th>Fecha de Ingreso</th>
			<td>'.date("d-m-Y", strtotime($sr['joindate'])).'</td>
			</tr> 


		</table>
	</div>
	';
	?>
</body>
</html>
<?php

echo '
<h4>Información de Pagos - Efectivo</h4>
<div class="table-responsive">
<table class="table table-bordered" style="border: 1px solid black">
    <thead>
      <tr>
        <th>Fecha de Pago</th>
        <th>Monto</th>
        <th>Detalle</th>
      </tr>
    </thead>
    <tbody>';
	$totapaid = 0;
while($res = $fq->fetch_assoc())
{
	$totapaid+=$res['paid'];
	        echo '<tr>
	        <td>'.date("d-m-Y", strtotime($res['submitdate'])).'</td>
	        <td>'.$res['paid'].'</td>
	        <td>'.$res['transcation_remark'].'</td>
	      </tr>' ;
}
      
echo '	  
    </tbody>
  </table>

  <h4>Información de Pagos - Depositos</h4>
<div class="table-responsive">
<table class="table table-bordered">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Pago</th>
        <th>Observaciones</th>
      </tr>
    </thead>
    <tbody>';
  $totapaidD = 0;
while($resD = $fqD->fetch_assoc())
{
$totapaidD+=$resD['paid_deposito'];
        echo '<tr>
        <td>'.date("d-m-Y", strtotime($resD['submitdate'])).'</td>
        <td>'.$resD['paid_deposito'].'</td>
        <td>'.$resD['transcation_remark'].'</td>
      </tr>' ;
}
      
echo '    
    </tbody>
  </table>

 </div> 
 <br>
<table style="width:150px;border: 1px solid black" class="table table-bordered">
<tr>
	<th>Total Adeudado </th>
	<th>Total Pagado </th>
	<th>Balance </th>

</tr>
<tbody>
	<tr>
	<td>'.$sr['fees'].'</td>
	<td>'.$totapaid+$totapaidD. '</td>
	<td>'.$sr['balance'].'</td>
	</tr>
</tbody>

</table>
<br>
 <div  style="text-align:right">
     	
     
     	<label style="padding-right:50px" >Recibi Conforme      </label>
     	<label style="padding-left:80px" >           Entregue Conforme</label>

     </div>

     <h4 style="font-size:09px" >Lugar y Fecha Impresion: <label style="font-weight:30"> El Alto, '.date("Y-m-d h:i:sa").' </label></h4>
 ';


/* }
else
{
echo 'Sin pagos ingresados.';
}*/
 
//}

require_once('dompdf/autoload.inc.php');
use Dompdf\Dompdf;

$dompdf=new DOMPDF();
$dompdf->load_Html(ob_get_clean());

$dompdf->render();
$pdf=$dompdf->output();
$filename='HistorialPagos.pdf';
$dompdf->stream($filename,array("Attachment"=>0));


?>