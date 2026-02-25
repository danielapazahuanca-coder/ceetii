<?php ob_start();
include("dbconnect.php");
 
$egresoID=$_GET['IdEgreso'];
$sucursal=$_SESSION['rainbow_sucursal'];

    /*$sql2="SELECT sum(monto) as totalegreso FROM egresos where estado=1 and date(fecha)='$fecha'";*/
    $sql2="SELECT * FROM egresos where sucursal_varchar='$sucursal' and idegreso=$egresoID and estado=1 order by fecha desc LIMIT 1";
    $datos=$conn->query($sql2);
    $DatosC=$datos->fetch_assoc();
$nombreImagen = "../img/LogoLoginCeti.jpeg";
$imagenBase64 = "data:image/png;base64," . base64_encode(file_get_contents($nombreImagen));
?>
<link rel="stylesheet" href="../css/AdminLTE.min.css">

<link href="css/datatable/datatable.css" rel="stylesheet" />
<!-- BOOTSTRAP STYLES-->

<html>
<body>
	<h2 style="text-align:center">Comprobante de Egreso </h2>
	<img src="<?php echo $imagenBase64 ?>" style="width: 100px; height: 100px; margin-top: -80px;">
	<div style="line-height:4px">	
		<h4>Nro. Comprobante:<label style="font-weight: 30; font-size:18px"> <?php echo $DatosC['idegreso'];?></label> </h4>
		<h4>Lugar y Fecha: <label style="font-weight:30"> La Paz, <?php echo $DatosC['fecha'] ?> </label></h4>
		<hr class="solid">
		<h4>Numero Recibo:<label style="font-weight:30"> <?php echo $DatosC['nro_recibo']; ?></label></h4>
		<h4>Fecha de Recibo: <label style="font-weight:30"><?php echo $DatosC['fecha_recibo']; ?></label></h4
		>
		<h4>Proveedor: <label style="font-weight:30"><?php echo $DatosC['proveedor']; ?></label></h4
		>
		<h4>Detalle: 
			<!-- <textarea class="form-control" style="line-height:100%"> <?php echo $DatosC['detalle']; ?></textarea> -->
			<p style="font-weight:30; height: 50px;line-height : 20px;"><?php echo $DatosC['detalle']; ?></p>
		</h4> 
		<h3>Monto Bs.-: <label style="font-weight:30"><?php echo $DatosC['monto']; ?></label></h3
		>
	</div>



    <!-- <h5 >Son:<label style="font-weight: 30; font-size:14px"> <?php

		$num=$DatosC['paid'];
     echo convertir($num);?> /Bolivianos.-</label> </h5> -->
     <br>
     <br>
     <div  style="text-align:center">
     	
     
     	<label style="padding-right:80px" >Recibi Conforme               </label>
     	<label style="padding-left:80px" >           Entregue Conforme</label>

     </div>
     <br>
     <label class="col-sm-2 control-label" for="Old" style="">Usuario: <?php echo $_SESSION['rainbow_name']; ?> - Fecha Impresion: <?php echo date("Y-m-d h:i:sa")?>  </label>
     
	</body>

</html>
<?php
require_once('dompdf/autoload.inc.php');
use Dompdf\Dompdf;

$dompdf=new DOMPDF();
$dompdf->load_Html(ob_get_clean());

$dompdf->render();
$pdf=$dompdf->output();
$filename='ComprobanteEgresosReg.pdf';
$dompdf->stream($filename,array("Attachment"=>0));

//function convert

function basico($numero) {
$valor = array ('uno','dos','tres','cuatro','cinco','seis','siete','ocho',
'nueve','diez', 'veinticuatro','veinticinco',
'veintiséis','veintisiete','veintiocho','veintinueve');
return $valor[$numero - 1];
}

function decenas($n) {
$decenas = array (30=>'treinta',40=>'cuarenta',50=>'cincuenta',60=>'sesenta',
70=>'setenta',80=>'ochenta',90=>'noventa');
if( $n <= 29) return basico($n);
$x = $n % 10;
if ( $x == 0 ) {
return $decenas[$n];
} else return $decenas[$n - $x].' y '. basico($x);
}

function centenas($n) {
$cientos = array (100 =>'cien',200 =>'doscientos',300=>'trecientos',
400=>'cuatrocientos', 500=>'quinientos',600=>'seiscientos',
700=>'setecientos',800=>'ochocientos', 900 =>'novecientos');
if( $n >= 100) {
if ( $n % 100 == 0 ) {
return $cientos[$n];
} else {
$u = (int) substr($n,0,1);
$d = (int) substr($n,1,2);
return (($u == 1)?'ciento':$cientos[$u*100]).' '.decenas($d);
}
} else return decenas($n);
}

function miles($n) {
if($n > 999) {
if( $n == 1000) {return 'mil';}
else {
$l = strlen($n);
$c = (int)substr($n,0,$l-3);
$x = (int)substr($n,-3);
if($c == 1) {$cadena = 'mil '.centenas($x);}
else if($x != 0) {$cadena = centenas($c).' mil '.centenas($x);}
else $cadena = centenas($c). ' mil';
return $cadena;
}
} else return centenas($n);
}

function millones($n) {
if($n == 1000000) {return 'un millón';}
else {
$l = strlen($n);
$c = (int)substr($n,0,$l-6);
$x = (int)substr($n,-6);
if($c == 1) {
$cadena = ' millón ';
} else {
$cadena = ' millones ';
}
return miles($c).$cadena.(($x > 0)?miles($x):'');
}
}
function convertir($n) {
switch (true) {
case ( $n >= 1 && $n <= 29) : return basico($n); break;
case ( $n >= 30 && $n < 100) : return decenas($n); break;
case ( $n >= 100 && $n < 1000) : return centenas($n); break;
case ($n >= 1000 && $n <= 999999): return miles($n); break;
case ($n >= 1000000): return millones($n);
}
}

?>