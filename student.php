<?php
include("php/dbconnect.php");
//include("php/dbconnectAdventas.php");
include("php/checklogin.php");
$errormsg = '';
$errormsgESTU = '';
$action = "add";

$id="";
$emailid='';
$sname='';
$ci=''; 
$joindate = '';
$remark='';
$contact='';
$balance = 0;
$fees='';
$about = '';
$branch='';

$sucursal=$_SESSION['rainbow_sucursal'];

if(isset($_POST['save']))
{

$sname = mysqli_real_escape_string($conn,$_POST['sname']);
$joindate = mysqli_real_escape_string($conn,$_POST['joindate']);
$ci=mysqli_real_escape_string($conn,$_POST['ci']);
$exp=mysqli_real_escape_string($conn,$_POST['exp']);
$contact = mysqli_real_escape_string($conn,$_POST['contact']);
$about = mysqli_real_escape_string($conn,$_POST['about']);
$emailid = mysqli_real_escape_string($conn,$_POST['emailid']);
$branch = mysqli_real_escape_string($conn,$_POST['branch']);
$carrera=mysqli_real_escape_string($conn,$_POST['carrera']);
$turno=mysqli_real_escape_string($conn,$_POST['turno']);
$modalidad=mysqli_real_escape_string($conn,$_POST['modalidad']);


 if($_POST['action']=="add")
 {
	 $remark = mysqli_real_escape_string($conn,$_POST['remark']);
	 $fees = mysqli_real_escape_string($conn,$_POST['fees']);
	 $advancefees = mysqli_real_escape_string($conn,$_POST['advancefees']);
	 $balance = $fees-$advancefees;
	 
	$sqlestuExiste = "select ci  from student where ci= '$ci' and sucursal_varchar='$sucursal'";
	$sq0 = $conn->query($sqlestuExiste);
	$sr0 = $sq0->fetch_assoc();
	$ciExiste = $sr0['ci'];
	if ($ciExiste==$ci)
	{
		header("location: student.php?action=add&tipo=1&act=4");
	}
	else {
		$sqlNum="SELECT Cod_Estu FROM student WHERE sucursal_varchar='$sucursal' ORDER BY Cod_Estu DESC LIMIT 1;";
		$tpq = $conn->query($sqlNum);
		$tpr = $tpq->fetch_assoc();
		$NuevoCodEstu=$tpr['Cod_Estu']+1;
	  	$q1 = $conn->query("INSERT INTO student (Cod_Estu,sname,contrasena,ci,exp_ci,joindate,contact,about,emailid,branch,balance,fees,carrera,turno,modalidad,sucursal_varchar) VALUES ('$NuevoCodEstu','$sname','0e042e777d0e8055924f0c13e405da94','$ci','$exp','$joindate','$contact','$about','$emailid','$branch','$balance','$fees','$carrera','$turno','$modalidad','$sucursal')") ;
	  
	  	/*$sid = $conn->insert_id;*/
	  
	    
	   echo '<script type="text/javascript">window.location="student.php?action=buscar&tipo=1&act=1";</script>';
 	}
 }
 else
  if($_POST['action']=="update")
 {
     
 $id = mysqli_real_escape_string($conn,$_POST['id']);	
 $fees = mysqli_real_escape_string($conn,$_POST['fees']);
 //$advancefees = mysqli_real_escape_string($conn,$_POST['advancefees']);
 //$balance = $fees-$advancefees;
 echo $id;
 echo $fees;
 
   $sql = $conn->query("UPDATE  student  SET fees='$fees',balance='$fees', sname='$sname',ci='$ci',exp_ci='$exp',contact='$contact', branch ='$branch',joindate='$joindate', carrera  = '$carrera',turno='$turno',modalidad='$modalidad',about='$about', emailid='$emailid'  WHERE  idRegistro  = '$id' and sucursal_varchar='$sucursal'");
   echo '<script type="text/javascript">window.location="student.php?action=buscar&tipo=1&act=2";</script>';
 }



}




if(isset($_GET['action']) && $_GET['action']=="delete"){

$conn->query("UPDATE  student set delete_status = '1'  WHERE sucursal_varchar='$sucursal' and idRegistro='".$_GET['id']."' ");	
header("location: student.php?action=buscar&tipo=1&act=3");

}


$action = "add";
if(isset($_GET['action']) && $_GET['action']=="edit" ){
$id = isset($_GET['id'])?mysqli_real_escape_string($conn,$_GET['id']):'';

$sqlEdit = $conn->query("SELECT * FROM student WHERE sucursal_varchar='$sucursal' and idRegistro='".$id."'");
if($sqlEdit->num_rows)
{
$rowsEdit = $sqlEdit->fetch_assoc();
extract($rowsEdit);
$action = "update";
}else
{
$_GET['action']="";
}

}


if(isset($_REQUEST['act']) && @$_REQUEST['act']=="1")
{
$errormsg = "<div class='alert alert-success'> <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong>Excelente!</strong> Estudiante Agregado Exitósamente</div>";
}else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="2")
{
$errormsg = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a> <strong>Excelente!</strong> Estudiante Editado Exitósamente</div>";
}
else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="3")
{
$errormsg = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong>Excelente!</strong> Estudiante Eliminado Exitósamente</div>";
}
else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="4")
{
$errormsgESTU = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong>Excelente!</strong> Estudiante ya Existe</div>";
}

//*****BUSCAR***
if(isset($_GET['action']) && $_GET['action']=="buscar"){
$tip=$_GET['tipo'];
if ($tip=1){
	/*echo "<script>console.log('111');</script>";*/
	$actual=date("Y-m-d");							
	$sql = "select e.idRegistro,e.sname,e.ci,e.exp_ci,e.emailid,e.contact,e.fees,e.balance,c.carrera,t.turno,m.modalidad 
from student as e inner join carrera as c on e.carrera=c.id INNER JOIN turnos as t on e.turno=t.id INNER join modalidad as m on e.modalidad=m.id where delete_status='0' and DATE(fecha_reg)='$actual' and e.sucursal_varchar='$sucursal' and m.sucursal_varchar='$sucursal' and t.sucursal_varchar='$sucursal'";
}
}
//buscar con parametros
if (isset($_GET['Bcarrera']))
{
	$carreB=$_GET['Bcarrera'];
	$turnoB=$_GET['Bturno'];
	$modaB=$_GET['Bmodalidad'];
	//para mostrar todos activos
	if (empty($carreB) && empty($turnoB) && empty($modaB))
	{
		$sql = "select e.idRegistro,e.sname,e.ci,e.exp_ci,e.emailid,e.contact,e.fees,e.balance,c.carrera,t.turno,m.modalidad 
from student as e inner join carrera as c on e.carrera=c.id INNER JOIN turnos as t on e.turno=t.id INNER join modalidad as m on e.modalidad=m.id where delete_status='0' and e.sucursal_varchar='$sucursal' and c.sucursal_varchar='$sucursal' and t.sucursal_varchar='$sucursal' and m.sucursal_varchar='$sucursal'";
	}
	//mostrar por carreras
	if ($carreB>0 && empty($turnoB) && empty($modaB))
	{
		$sql = "select e.idRegistro,e.sname,e.ci,e.exp_ci,e.emailid,e.contact,e.fees,e.balance,c.carrera,t.turno,m.modalidad 
from student as e inner join carrera as c on e.carrera=c.id INNER JOIN turnos as t on e.turno=t.id INNER join modalidad as m on e.modalidad=m.id where e.delete_status='0' and e.carrera='$carreB' and e.sucursal_varchar='$sucursal' and c.sucursal_varchar='$sucursal' and t.sucursal_varchar='$sucursal' and t.sucursal_varchar='$sucursal' and m.sucursal_varchar='$sucursal' ";
	}
	//mostrar por carreras y turnos
	if ($carreB>0 && $turnoB>0 && empty($modaB))
	{
		$sql = "select e.idRegistro,e.sname,e.ci,e.exp_ci,e.emailid,e.contact,e.fees,e.balance,c.carrera,t.turno,m.modalidad 
from student as e inner join carrera as c on e.carrera=c.id INNER JOIN turnos as t on e.turno=t.id INNER join modalidad as m on e.modalidad=m.id where e.delete_status='0' and e.carrera='$carreB' and e.turno='$turnoB' and e.sucursal_varchar='$sucursal' and c.sucursal_varchar='$sucursal' and t.sucursal_varchar='$sucursal' and m.sucursal_varchar='$sucursal' ";
	}
	//para mostrar por carrera, tunro y modalidad
	if ($carreB>0 && $turnoB>0 && $modaB>0)
	{
		$sql = "select e.idRegistro,e.sname,e.ci,e.exp_ci,e.emailid,e.contact,e.fees,e.balance,c.carrera,t.turno,m.modalidad 
from student as e inner join carrera as c on e.carrera=c.id INNER JOIN turnos as t on e.turno=t.id INNER join modalidad as m on e.modalidad=m.id where e.delete_status='0' and e.carrera='$carreB' and e.turno='$turnoB' and e.modalidad='$modaB' and e.sucursal_varchar='$sucursal' and c.sucursal_varchar='$sucursal' and t.sucursal_varchar='$sucursal' and m.sucursal_varchar='$sucursal'  ";
	}
	
	
}


?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistema de Pago CITE</title>

    <!-- BOOTSTRAP STYLES-->
    <link href="css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="css/font-awesome.css" rel="stylesheet" />
       <!--CUSTOM BASIC STYLES-->
    <link href="css/basic.css" rel="stylesheet" />
    <!--CUSTOM MAIN STYLES-->
    <link href="css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
	
	<link href="css/ui.css" rel="stylesheet" />
	<link href="css/datepicker.css" rel="stylesheet" />	
	
    <script src="js/jquery-1.10.2.js"></script>
	
    <script type='text/javascript' src='js/jquery/jquery-ui-1.10.1.custom.min.js'></script>
   <style type="text/css">
   	

   </style>
	
</head>
<?php
include("php/header.php");
?>
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="page-head-line" >Estudiantes  
						<?php
						echo (isset($_GET['action']) && @$_GET['action']=="add" || @$_GET['action']=="edit")?
						' <a href="student.php?action=buscar&tipo=1" class="btn btn-primary btn-sm pull-right">Volver <i class="glyphicon glyphicon-arrow-right"></i></a>':'<a href="student.php?action=add" class="btn btn-primary btn-sm pull-right"><i class="glyphicon glyphicon-plus"></i> Agregar Estudiante </a>';
						?>
						</h1>
                     
<?php

echo $errormsg;
?>
                    </div>
                </div>
				
				
				
        <?php 
		 if(isset($_GET['action']) && @$_GET['action']=="add" || @$_GET['action']=="edit")
		 {
		?>
		
			<script type="text/javascript" src="js/validation/jquery.validate.min.js"></script>
                <div class="row">
				
                    <div class="col-sm-10 col-sm-offset-1">
               <div class="panel panel-primary">
                        <div class="panel-heading">
                           <?php echo ($action=="add")? "Agregar Estudiante": "Editar Estudiante"; ?>
                        </div>
						<form action="student.php" method="post" id="signupForm1" class="form-horizontal">
                        <div class="panel-body">
                        	<?php

						echo $errormsgESTU;
						?>
						<fieldset class="scheduler-border" >
						 <legend  class="scheduler-border">Información Personal:</legend>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Nombre* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="sname" name="sname" value="<?php echo $sname;?>"  />
								</div>
						</div>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">C.I.* </label>
								<div class="col-sm-5">
									<input type="text" class="form-control" id="ci" name="ci" value="<?php echo $ci;?>"  />
								</div>
								<label class="col-sm-2 control-label" for="Old">Expedido * </label>
								<div class="col-sm-2">
									<select  class="form-control" id="exp" name="exp" >
									<option value="LP" >LP</option>
									<option value="CB" >CB</option>
									<option value="OR`" >OR</option>
									<option value="PT" >PT</option>
									<option value="SC" >SC</option>
									<option value="TJ" >TJ</option>
									<option value="BE" >BE</option>
									<option value="PD" >PD</option>
									<option value="CH" >CH</option>
									</select>
								</div>
						</div>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Contacto* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="contact" name="contact" value="<?php echo $contact;?>" maxlength="10" />
								</div>
							</div>
							
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Tipo Pago* </label>
								<div class="col-sm-10">
									<select  class="form-control" id="branch" name="branch" >
                                    <?php
									$sql = "select * from branch where sucursal_varchar='$sucursal' and delete_status='0' order by branch.branch asc";
									$q = $conn->query($sql);
									
									while($r = $q->fetch_assoc())
									{
									echo '<option value="'.$r['id'].'"  '.(($branch==$r['id'])?'selected="selected"':'').'>'.$r['branch'].'</option>';
									}
									?>									
									
									</select>
								</div>
						</div>
						
						
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Fecha Inscripcion* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="joindate" name="joindate" value="<?php echo  ($joindate!='')?date("Y-m-d", strtotime($joindate)):date("Y-m-d");?>" style="background-color: #fff;" readonly />
								</div>
							</div>
						 </fieldset>
						
						
							<fieldset class="scheduler-border" >
						 <legend  class="scheduler-border">Información de Tarifas:</legend>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Total Adeudado* </label>
								<div class="col-sm-10">
									
									<input type="text" class="form-control" id="fees" name="fees" value="<?php echo $fees;?>" <?php echo ($action=="update")?"":""; ?>  />
								</div>
						</div>
						
						<?php
						if($action=="add")
						{
						?>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Avance Tarifa* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="advancefees" name="advancefees" readonly   />
								</div>
						</div>
						<?php
						}
						?>
						
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Balance </label>
								<div class="col-sm-10">
									<input type="text" class="form-control"  id="balance" name="balance" value="<?php echo $balance;?>" disabled />
								</div>
						</div>
						
						
						
							
							<?php
						if($action=="add")
						{
						?>
							<div class="form-group">
								<label class="col-sm-2 control-label" for="Password">Comentario </label>
								<div class="col-sm-10">
	                        <textarea class="form-control" id="remark" name="remark"><?php echo $remark;?></textarea >
								</div>
								
							</div>
						<?php
						}
						?>
							
							</fieldset>
							
							 <fieldset class="scheduler-border" >
						 <legend  class="scheduler-border">Información Academica:</legend>
						 	<div class="form-group">
						 		<label class="col-sm-2 control-label" for="Old">Carrera* </label>
								<div class="col-sm-10">
									<select  class="form-control" id="carrera" name="carrera" >
                                    <?php
									$sql = "select * from carrera where sucursal_varchar='$sucursal' and estado=1 order by id asc";
									$q = $conn->query($sql);
									
									while($r = $q->fetch_assoc())
									{
									echo '<option value="'.$r['id'].'"  '.(($carrera==$r['id'])?'selected="selected"':'').'>'.$r['carrera'].'</option>';
									}
									?>									
									
									</select>
								</div>
						 	</div>
						 	<div class="form-group">
						 		<label class="col-sm-2 control-label" for="Old">Turno* </label>
								<div class="col-sm-10">
									<select  class="form-control" id="turno" name="turno" >
                                    <?php
									$sql = "select * from turnos where sucursal_varchar='$sucursal' and estado=1 order by id asc";
									$q = $conn->query($sql);
									
									while($r = $q->fetch_assoc())
									{
									echo '<option value="'.$r['id'].'"  '.(($turno==$r['id'])?'selected="selected"':'').'>'.$r['turno'].'</option>';
									}
									?>									
									
									</select>
								</div>
						 	</div>
						 	<div class="form-group">
						 		<label class="col-sm-2 control-label" for="Old">Modalidad* </label>
								<div class="col-sm-10">
									<select  class="form-control" id="modalidad" name="modalidad" >
                                    <?php
									$sql = "select * from modalidad where sucursal_varchar='$sucursal' and estado=1 order by id asc";
									$q = $conn->query($sql);
									
									while($r = $q->fetch_assoc())
									{
									echo '<option value="'.$r['id'].'"  '.(($modalidad==$r['id'])?'selected="selected"':'').'>'.$r['modalidad'].'</option>';
									}
									?>									
									
									</select>
								</div>
						 	</div>
							<div class="form-group">
								<label class="col-sm-2 control-label" for="Password">Acerca del Estudiante </label>
								<div class="col-sm-10">
	                        <textarea class="form-control" id="about" name="about"><?php echo $about;?></textarea >
								</div>
							</div>
							
							<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Correo Electrónico </label>
								<div class="col-sm-10">
									
									<input type="text" class="form-control" id="emailid" name="emailid" value="<?php echo $emailid;?>"  />
								</div>
						    </div>
							</fieldset>
						
						<div class="form-group">
								<div class="col-sm-8 col-sm-offset-2">
								<input type="hidden" name="id" value="<?php echo $id;?>">
								<input type="hidden" name="action" value="<?php echo $action;?>">
								
									<button type="submit" name="save" class="btn btn-primary">Guardar </button>
								 
								   
								   
								</div>
							</div>
                         
                           
                           
                         
                           
                         </div>
							</form>
							
                        </div>
                            </div>
            
			
                </div>
               

			   
			   
		<script type="text/javascript">
		

		$( document ).ready( function () {			
			
		$( "#joindate" ).datepicker({
dateFormat:"yy-mm-dd",
changeMonth: true,
changeYear: true,
yearRange: "1970:<?php echo date('Y');?>"
});	
		

		
		if($("#signupForm1").length > 0)
         {
		 
		 <?php if($action=='add')
		 {
		 ?>
		 
			$( "#signupForm1" ).validate( {
				rules: {
					sname: "required",
					ci: "required",
					contact: "email",
					joindate: "required",
					fees:"required",


					
					sname: {required: true,},
					ci: {required: true,},
					contact: {required: true,},
					joindate:{required:true,},
					fees:{required:true,}


					
				},
			<?php
			}else
			{
			?>
			
			$( "#signupForm1" ).validate( {
				rules: {
					sname: "required",
					joindate: "required",
					emailid: "email",
					branch: "required",
					
					
					contact: {
						required: true,
						digits: true
					}
					
				},
			
			
			
			<?php
			}
			?>
				
				errorElement: "em",
				errorPlacement: function ( error, element ) {
					// Add the `help-block` class to the error element
					error.addClass( "help-block" );

					// Add `has-feedback` class to the parent div.form-group
					// in order to add icons to inputs
					element.parents( ".col-sm-10" ).addClass( "has-feedback" );

					if ( element.prop( "type" ) === "checkbox" ) {
						error.insertAfter( element.parent( "label" ) );
					} else {
						error.insertAfter( element );
					}

					// Add the span element, if doesn't exists, and apply the icon classes to it.
					if ( !element.next( "span" )[ 0 ] ) {
						$( "<span class='glyphicon glyphicon-remove form-control-feedback'></span>" ).insertAfter( element );
					}
				},
				success: function ( label, element ) {
					// Add the span element, if doesn't exists, and apply the icon classes to it.
					if ( !$( element ).next( "span" )[ 0 ] ) {
						$( "<span class='glyphicon glyphicon-ok form-control-feedback'></span>" ).insertAfter( $( element ) );
					}
				},
				highlight: function ( element, errorClass, validClass ) {
					$( element ).parents( ".col-sm-10" ).addClass( "has-error" ).removeClass( "has-success" );
					$( element ).next( "span" ).addClass( "glyphicon-remove" ).removeClass( "glyphicon-ok" );
				},
				unhighlight: function ( element, errorClass, validClass ) {
					$( element ).parents( ".col-sm-10" ).addClass( "has-success" ).removeClass( "has-error" );
					$( element ).next( "span" ).addClass( "glyphicon-ok" ).removeClass( "glyphicon-remove" );
				}
			} );
			
			}
			
		} );
		
		
		
		$("#fees").keyup( function(){
		$("#advancefees").val("");
		$("#balance").val(0);
		var fee = $.trim($(this).val());
		if( fee!='' && !isNaN(fee))
		{
		$("#advancefees").removeAttr("readonly");
		$("#balance").val(fee);
		$('#advancefees').rules("add", {
            max: parseInt(fee)
        });
		
		}
		else{
		$("#advancefees").attr("readonly","readonly");
		}
		
		});
		
		
		
		
		$("#advancefees").keyup( function(){
		
		var advancefees = parseInt($.trim($(this).val()));
		var totalfee = parseInt($("#fees").val());
		if( advancefees!='' && !isNaN(advancefees) && advancefees<=totalfee)
		{
		var balance = totalfee-advancefees;
		$("#balance").val(balance);
		
		}
		else{
		$("#balance").val(totalfee);
		}
		
		});
		
		
	</script>


			   
		<?php
		}else{
		?>
		
		 <link href="css/datatable/datatable.css" rel="stylesheet" />
		 
		
		 
		 
		<div class="panel panel-default">
                        <div class="panel-heading" style="height: 50px;">
                        	<div class="col-md-11">
                        	<form action="student.php" method="get" id="signupForm1" class="form-horizontal"> 
                        	
                        	
                        		<label class="col-sm-1 control-label" for="Old">Carrera </label>
								<div class="col-sm-3">
									<select  class="form-control" id="Bcarrera" name="Bcarrera" >
									<option value="" >Todos</option>
                                    <?php
									$sql5 = "select * from carrera where sucursal_varchar='$sucursal' and estado=1 order by id asc";
									$q5 = $conn->query($sql5);
									
									while($r5 = $q5->fetch_assoc())
									{
									echo '<option value="'.$r5['id'].'"  '.(($branch==$r5['id'])?'selected="selected"':'').'>'.$r5['carrera'].'</option>';
									}
									?>									
									
									</select>
								</div>
								<label class="col-sm-1 control-label" for="Old">Turno </label>
								<div class="col-sm-2">
									<select  class="form-control" id="Bturno" name="Bturno" >
									<option value="" >Todos</option>
                                    <?php
									$sql6 = "select * from turnos where sucursal_varchar='$sucursal' and estado=1 order by id asc";
									$q6 = $conn->query($sql6);
									
									while($r6 = $q6->fetch_assoc())
									{
									echo '<option value="'.$r6['id'].'"  '.(($branch==$r6['id'])?'selected="selected"':'').'>'.$r6['turno'].'</option>';
									}
									?>									
									
									</select>
								</div>
								<label class="col-sm-1 control-label" for="Old">Modalidad </label>
								<div class="col-sm-2">
									<select  class="form-control" id="Bmodalidad" name="Bmodalidad" >
									<option value="" >Todos</option>
                                    <?php
									$sql7 = "select * from modalidad where sucursal_varchar='$sucursal' and estado=1 order by id asc";
									$q7 = $conn->query($sql7);
									
									while($r7 = $q7->fetch_assoc())
									{
									echo '<option value="'.$r7['id'].'"  '.(($branch==$r7['id'])?'selected="selected"':'').'>'.$r7['modalidad'].'</option>';
									}
									?>									
									
									</select>
								</div>
								<div class="col-sm-1">
									<!-- <button id="GenerarPDF" class="btn btn-default">Buscar</button> -->
									
									<!-- <a href="ingresos.php?action=buscar" class="btn btn-primary "><span class="glyphicon .glyphicon-search">Buscar</span></a> -->
									<button type="submit" name="buscar" id="buscar" class="btn btn-primary">Buscar </button>
									
								</div>
							</form>
                        	</div>
                        	
                        	<div class="col-md-1">
                        			<!-- <button id="GenerarPDF" class="btn btn-default">Crear PDF</button> -->
                        		<a href="php/ImprimirEstudiante.php?action=imprimir&Bcarrera=<?php echo $carreB; ?>&Bturno=<?php echo $turnoB; ?>&Bmodalidad=<?php echo $modaB; ?>" target="_blank" class="btn btn-success "><span class="glyphicon .glyphicon-print">PDF</span></a> 
                        	</div>
                            
                        </div>
                        <div class="panel-body">
                            <div class="table-sorting table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="tSortable22">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Estudiante</th>
                                            <th>Carrera</th>
                                            <th>Turno</th>
                                            <th>Modalidad</th>
                                            <th>Cuota Total</th>
											<th>Por Pagar</th>
											<th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
									<?php
									/*$sql = "select e.id,e.sname,e.emailid,e.contact,e.fees,e.balance,c.carrera,t.turno,m.modalidad 
from student as e inner join carrera as c on e.carrera=c.id INNER JOIN turnos as t on e.turno=t.id INNER join modalidad as m on e.modalidad=m.id where delete_status='0'";*/
									$q = $conn->query($sql);
									$i=1;
									while($r = $q->fetch_assoc())
									{
									
									echo '<tr '.(($r['balance']>0)?'class="danger"':'').'>
                                            <td>'.$i.'</td>
                                            <td>'.$r['sname'].'<br/>'.$r['ci'].'-'.$r['exp_ci'].'</td>
                                            <td>'.$r['carrera'].'</td>
                                            <td>'.$r['turno'].'</td>
                                            <td>'.$r['modalidad'].'</td>
                                            <td>'.$r['fees'].'</td>
											<td>'.$r['balance'].'</td>
											<td>
											
											

											<a href="student.php?action=edit&id='.$r['idRegistro'].'" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-edit"></span></a>
											
											<a onclick="return confirm(\'Deseas realmente eliminar este registro, este proceso es irreversible\');" href="student.php?action=delete&id='.$r['idRegistro'].'" class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove"></span></a> </td>
											
                                        </tr>';
										$i++;
									}
									?>
									
                                        
                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                     
	<script src="js/dataTable/jquery.dataTables.min.js"></script>
    
     <script>
         $(document).ready(function () {
             $('#tSortable22').dataTable({
    "bPaginate": true,
    "bLengthChange": true,
    "bFilter": true,
    "bInfo": false,
    "bAutoWidth": true });
	
         });
		 
	
    </script>
		
		<?php
		}
		?>
				
				
            
            </div>
            <!-- /. PAGE INNER  -->
        </div>
        <!-- /. PAGE WRAPPER  -->
    </div>
    <!-- /. WRAPPER  -->

    <div id="footer-sec">
    Para más desarrollos, llama el siguiente numero <a href="#" target="_blank">73247852 - Fernando Apaza</a>
    </div>
   
  
    <!-- BOOTSTRAP SCRIPTS -->
    <script src="js/bootstrap.js"></script>
    <!-- METISMENU SCRIPTS -->
    <script src="js/jquery.metisMenu.js"></script>
       <!-- CUSTOM SCRIPTS -->
    <script src="js/custom1.js"></script>V

    
</body>
</html>
