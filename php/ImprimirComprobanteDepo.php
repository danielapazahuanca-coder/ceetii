<?php ob_start();
include("dbconnect.php");
 
$estuId=$_GET['idEstu'];
$sucursal=$_SESSION['rainbow_sucursal'];

    /*$sql2="SELECT sum(monto) as totalegreso FROM egresos where estado=1 and date(fecha)='$fecha'";*/
    $sql2="SELECT * FROM fees_transaction where sucursal_varchar='$sucursal' and stdid=$estuId and estado=1 order by submitdate desc LIMIT 1";
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
	<h2 style="text-align:center">Comprobante de Ingresos (Depositos) </h2>
	<img src="<?php echo $imagenBase64 ?>" style="width: 100px; height: 100px; margin-top: -80px;">
	<div style="line-height:4px">	
		<h4>Nro. Comprobante:<label style="font-weight: 30; font-size:18px"> <?php echo $DatosC['id'];?></label> </h4>
		<h4>Lugar y Fecha: <label style="font-weight:30"> La Paz, <?php echo date("Y-m-d h:i:sa") ?> </label></h4>
		<h4>Codigo Estudiante:<label style="font-weight:30"> <?php echo $DatosC['stdid']; ?></label></h4>
		<h4>Nombre Estudiante: <label style="font-weight:30"><?php echo $DatosC['nombre_estudiante']; ?></label></h4>
	</div>

	<table class="table table-striped table-bordered table-hover" id="tSortable22" style="font-size: 16px;width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Cantidad</th>
                                            <th>Concepto</th>
                                            <th>Subtotal</th>
											
                                        </tr>
                                    </thead>
                                    <tbody>
									<?php
									//$sql = "select * from egresos where estado=1";
									
									$i=1;

									
									echo '<tr>
                                            <td style="text-align:center">'.$i.'</td>
                                            <td>'.$DatosC['transcation_remark'].'</td>
                                            <td style="text-align:right">'.$DatosC['paid_deposito'].'</td>
											
                                        </tr>';
									
									
									?> 
                                    </tbody>
    </table>
    <h3 style="text-align:right">Total Bolivianos:<label style="font-weight: 30; font-size:24px"> <?php echo $DatosC['paid_deposito'];?>.-</label> </h3>
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
$filename='ComprobanteIngreso.pdf';
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