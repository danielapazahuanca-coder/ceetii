<?php ob_start();
include("dbconnect.php");
 
$fecha=$_GET['fechaB'];
$fecha2=$_GET['fechaB2'];
 $sucursal=$_SESSION['rainbow_sucursal'];
 $usuario=$_SESSION['rainbow_username'];
    $sql = "SELECT sum(paid) as totalingreso FROM fees_transaction WHERE estado = 1 and date(submitdate)>='$fecha' and date(submitdate)<='$fecha2' and sucursal_varchar='$sucursal' and usuario='$usuario'";

    $ingreso = $conn->query($sql);
    $TotalIngreso= $ingreso->fetch_assoc();
    $tIn=number_format($TotalIngreso['totalingreso'],2,',',' ');
    $tInAux=$TotalIngreso['totalingreso'];
    $sql2="SELECT sum(monto) as totalegreso FROM egresos where estado=1 and date(fecha)>='$fecha' and date(fecha)<='$fecha2' and sucursal_varchar='$sucursal' and usuario='$usuario' ";
    $egreso=$conn->query($sql2);
    $TotalEgreso=$egreso->fetch_assoc();
    $tEgr=number_format($TotalEgreso['totalegreso'],2,',',' ');
	$tEgrAux=$TotalEgreso['totalegreso'];

    $auxSaldo=$tInAux-$tEgrAux;
    $SaldoActual= number_format($auxSaldo,2,',',' ');


$q = $conn->query($sql);
?>
<link rel="stylesheet" href="../css/AdminLTE.min.css">

<link href="css/datatable/datatable.css" rel="stylesheet" /> 
<html>
<body>
	<h2 style="text-align:center">Reporte de Caja </h2>
		<!--<div class="col-md-6" style="">
			
			<div class="col-sm-4" style="float:right;">
				<label class="col-sm-2 control-label" for="Old" style="">Fecha Transaccion </label>
				<input type="text" class="form-control" id="bus" name="bus"  style="background-color: #fff;" readonly value="<?php echo $fecha ?>"  />
			</div>
			
			<div class="col-sm-4" style="float:left;">
				<label class="col-sm-2 control-label" for="Old">Total Ingresos </label>
				<input type="text" class="form-control" id="bus" name="bus"  style="background-color: #fff;" readonly value="<?php echo $tIn ?>"  />
			</div>
			
			<div class="col-sm-4" style="float:left;">
				<label class="col-sm-2 control-label" for="Old">Total Egresos </label>
				<input type="text" class="form-control" id="bus" name="bus"  style="background-color: #fff;" readonly value="<?php echo $tEgr ?>"  />
			</div>
			
			<div class="col-sm-4" style="float:left;">
				<label class="col-sm-2 control-label" for="Old">SALDO TOTAL </label>
				<input type="text" class="form-control" id="bus" name="bus"  style="background-color: #fff;" readonly value="<?php echo $SaldoActual ?>"  />
			</div>
		</div>
		 <label class="col-sm-2 control-label" for="Old" style="">Usuario: <?php $_SESSION['rainbow_name']; ?>   </label>-->
		 
		 
		 		 <form action="ejemplo.php" method="get">
  <p>Fecha Transaccion: <input type="text" class="form-control" id="bus" name="bus"  style="background-color: #fff;" readonly value="<?php echo $fecha ?>"  /></p>
  <p>Total Ingresos: <input type="text" name="nombre"   size="10" readonly value="<?php echo $tIn ?>"></p>
  <p>Total Egresos: <input type="text" name="nombre"  readonly value="<?php echo $tEgr ?>"></p>
  <p>SALDO TOTAL: <input type="text" name="nombre"  minlength="4" maxlength="8" size="10"  readonly value="<?php echo $SaldoActual ?>"></p>
     <label class="col-sm-2 control-label" for="Old" style="">Usuario: <?php echo $_SESSION['rainbow_name']; ?>   </label>
		 
		 

	</body>
</html>
<?php
require_once('dompdf/autoload.inc.php');
use Dompdf\Dompdf;

$dompdf=new DOMPDF();
$dompdf->load_Html(ob_get_clean());

$dompdf->render();
$pdf=$dompdf->output();
$filename='ResumenDiario.pdf';
$dompdf->stream($filename,array("Attachment"=>0));


?>