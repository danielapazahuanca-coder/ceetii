<?php ob_start();
include("dbconnect.php");
 
$carreB=$_GET['Bcategoria'];
	
	//para mostrar todos activos
	if (empty($carreB))
	{
		$sql = "select ac.id, ac.nombre_categoria, a.nombre,a.cantidad,ae.nombre_estado,a.precio_compra,a.fecha_ingreso,a.depresiacion
from activos as a inner join activos_categoria as ac on a.categoria_id=ac.id INNER JOIN activos_estado as ae on a.estado_activo_id=ae.id where a.estado=1";
	}
	//mostrar por categorias
	if ($carreB>0 )
	{
		$sql = "select ac.id, ac.nombre_categoria, a.nombre,a.cantidad,ae.nombre_estado,a.precio_compra,a.fecha_ingreso,a.depresiacion
from activos as a inner join activos_categoria as ac on a.categoria_id=ac.id INNER JOIN activos_estado as ae on a.estado_activo_id=ae.id where a.estado=1  and ac.id='$carreB'";
	}


$q = $conn->query($sql);
?>
<link rel="stylesheet" href="../css/AdminLTE.min.css">

<link href="css/datatable/datatable.css" rel="stylesheet" /> 
<html>
<body>
	<h2 style="text-align:center">Reporte de Activos Fijos</h2>
		<table class="table table-striped table-bordered table-hover" id="tSortable22">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Categoria</th>
                                            <th>Cantidad</th>
                                            <th>Activo</th>
                                            <th>Estado</th>
                                            <th>Precio Compra</th>
											<th>Vida Util</th>
                                        </tr>
                                    </thead>
                                    <tbody>
									<?php
									$q = $conn->query($sql);
									$i=1;
									while($r = $q->fetch_assoc())
									{
									
									echo '<tr '.(($r['nombre_categoria']>0)?'class="danger"':'').'>
                                            <td>'.$i.'</td>
                                            <td>'.$r['nombre_categoria'].'</td>
                                            <td>'.$r['cantidad'].'</td>
                                            <td>'.$r['nombre'].'</td>
                                            <td>'.$r['nombre_estado'].'</td>
                                            <td>'.$r['precio_compra'].'</td>
                                            <td>'.$r['depresiacion'].'</td>
											
                                        </tr>';
										$i++;
									}
									?>
									
                                        
                                        
                                    </tbody>
                                </table>
	</body>
</html>
<?php
//requiere_once("dompdf/dompdf_config.inc.php");
require_once('dompdf/dompdf_config.inc.php');
$dompdf=new DOMPDF();
$dompdf->load_Html(ob_get_clean());

$dompdf->render();
$pdf=$dompdf->output();
$filename='Activos.pdf';
$dompdf->stream($filename,array("Attachment"=>0));


?>