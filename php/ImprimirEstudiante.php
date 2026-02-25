<?php ob_start();
include("dbconnect.php");

$sucursal=$_SESSION['rainbow_sucursal'];
$carreB=$_GET['Bcarrera'];
	$turnoB=$_GET['Bturno'];
	$modaB=$_GET['Bmodalidad'];
	//para mostrar todos activos
	if (empty($carreB) && empty($turnoB) && empty($modaB))
	{
		$sql = "select e.idRegistro,e.sname,e.emailid,e.contact,e.fees,e.balance,c.carrera,t.turno,m.modalidad 
from student as e inner join carrera as c on e.carrera=c.id INNER JOIN turnos as t on e.turno=t.id INNER join modalidad as m on e.modalidad=m.id where delete_status='0' and e.sucursal_varchar='$sucursal' and c.sucursal_varchar='$sucursal' and t.sucursal_varchar='$sucursal' and m.sucursal_varchar='$sucursal' ";
	}
	//mostrar por carreras
	if ($carreB>0 && empty($turnoB) && empty($modaB))
	{
		$sql = "select e.idRegistro,e.sname,e.emailid,e.contact,e.fees,e.balance,c.carrera,t.turno,m.modalidad 
from student as e inner join carrera as c on e.carrera=c.id INNER JOIN turnos as t on e.turno=t.id INNER join modalidad as m on e.modalidad=m.id where e.delete_status='0' and e.carrera='$carreB' and e.sucursal_varchar='$sucursal' and c.sucursal_varchar='$sucursal' and t.sucursal_varchar='$sucursal' and m.sucursal_varchar='$sucursal' ";
	}
	//mostrar por carreras y turnos
	if ($carreB>0 && $turnoB>0 && empty($modaB))
	{
		$sql = "select e.idRegistro,e.sname,e.emailid,e.contact,e.fees,e.balance,c.carrera,t.turno,m.modalidad 
from student as e inner join carrera as c on e.carrera=c.id INNER JOIN turnos as t on e.turno=t.id INNER join modalidad as m on e.modalidad=m.id where e.delete_status='0' and e.carrera='$carreB' and e.turno='$turnoB' and  e.sucursal_varchar='$sucursal' and c.sucursal_varchar='$sucursal' and t.sucursal_varchar='$sucursal' and m.sucursal_varchar='$sucursal'  ";
	}
	//para mostrar por carrera, tunro y modalidad
	if ($carreB>0 && $turnoB>0 && $modaB>0)
	{
		$sql = "select e.idRegistro,e.sname,e.emailid,e.contact,e.fees,e.balance,c.carrera,t.turno,m.modalidad 
from student as e inner join carrera as c on e.carrera=c.id INNER JOIN turnos as t on e.turno=t.id INNER join modalidad as m on e.modalidad=m.id where e.delete_status='0' and e.carrera='$carreB' and e.turno='$turnoB' and e.modalidad='$modaB' and  e.sucursal_varchar='$sucursal' and c.sucursal_varchar='$sucursal' and t.sucursal_varchar='$sucursal' and m.sucursal_varchar='$sucursal' ";
	}


$q = $conn->query($sql);
?>
<link rel="stylesheet" href="../css/AdminLTE.min.css">

<link href="css/datatable/datatable.css" rel="stylesheet" /> 
<html>
<body>
	<h2 style="text-align:center">Reporte de Estudiantes</h2>
		<table class="table table-striped table-bordered table-hover" id="tSortable22" style="font-size: 12px;">
         	<thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Estudiante</th>
                                            <th>Carrera</th>
                                            <th>Turno</th>
                                            <th>Modalidad</th>
                                            <th>Cuota Total</th>
											<th>Por Pagar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
									<?php
									$q = $conn->query($sql);
									$i=1;
									while($r = $q->fetch_assoc())
									{
									
									echo '<tr '.(($r['balance']>0)?'class="danger"':'').'>
                                            <td>'.$i.'</td>
                                            <td>'.$r['sname'].'<br/>'.$r['contact'].'</td>
                                            <td>'.$r['carrera'].'</td>
                                            <td>'.$r['turno'].'</td>
                                            <td>'.$r['modalidad'].'</td>
                                            <td>'.$r['fees'].'</td>
											<td>'.$r['balance'].'</td>
											
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
$filename='Estudiantes.pdf';
$dompdf->stream($filename,array("Attachment"=>0));


?>