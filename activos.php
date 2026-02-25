<?php
include("php/dbconnect.php");
include("php/checklogin.php");
$errormsg = '';
$action = "add";

$id="";
$cantidad='';
$nombreActivo='';
$descripcion = '';
$estadoActivo='';
$precioCompra='';
$fechaCompra = 0;
$vidaUtil='';


if(isset($_POST['save']))
{

$categoria = mysqli_real_escape_string($conn,$_POST['categoria']);
$cantidad = mysqli_real_escape_string($conn,$_POST['cantidad']);

$nombreActivo = mysqli_real_escape_string($conn,$_POST['nombreactivo']);
$descripcion = mysqli_real_escape_string($conn,$_POST['descripcion']);
$estadoActivo = mysqli_real_escape_string($conn,$_POST['estadoactivo']);
$precioCompra = mysqli_real_escape_string($conn,$_POST['preciocompra']);
$fechaCompra=mysqli_real_escape_string($conn,$_POST['fechacompra']);
$vidaUtil=mysqli_real_escape_string($conn,$_POST['vidautil']);

 if($_POST['action']=="add")
 {

 
  $q1 = $conn->query("INSERT INTO activos (categoria_id,cantidad,	nombre,descripcion,estado_activo_id,precio_compra,fecha_ingreso,depresiacion,codigo_activo,usuario,estado) VALUES ('$categoria','$cantidad','$nombreActivo','$descripcion','$estadoActivo','$precioCompra','$fechaCompra','$vidaUtil','000-1','admin',1)") ;
  
  $sid = $conn->insert_id;
  
 //$conn->query("INSERT INTO  fees_transaction (stdid,paid,submitdate,transcation_remark) VALUES ('$sid','$advancefees','$joindate','$remark')") ;
    
   echo '<script type="text/javascript">window.location="activos.php?action=buscar&tipo=1&act=1";</script>';
 
 }else
  if($_POST['action']=="update")
 {
 $id = mysqli_real_escape_string($conn,$_POST['id']);	
   $sql = $conn->query("UPDATE  activos  SET categoria_id='$categoria',cantidad='$cantidad', nombre ='$nombreActivo',descripcion='$descripcion', estado_activo_id  = '$estadoActivo',precio_compra='$precioCompra',fecha_ingreso='$fechaCompra',depresiacion='$vidaUtil'  WHERE  id  = '$id'");
   echo '<script type="text/javascript">window.location="activos.php?action=buscar&tipo=1&act=2";</script>';
 }



}




if(isset($_GET['action']) && $_GET['action']=="delete"){

$conn->query("UPDATE  activos set estado = 0  WHERE id='".$_GET['id']."'");	
header("location: activos.php?action=buscar&tipo=1&act=3");

}


$action = "add";
if(isset($_GET['action']) && $_GET['action']=="edit" ){
$id = isset($_GET['id'])?mysqli_real_escape_string($conn,$_GET['id']):'';

$sqlEdit = $conn->query("SELECT * FROM activos WHERE id='".$id."'");
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
$errormsg = "<div class='alert alert-success'> <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong>Excelente!</strong> Activo Agregado Exitósamente</div>";
}else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="2")
{
$errormsg = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a> <strong>Excelente!</strong> Activo Editado Exitósamente</div>";
}
else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="3")
{
$errormsg = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong>Excelente!</strong> Activo Eliminado Exitósamente</div>";
}

//*****BUSCAR***
if(isset($_GET['action']) && $_GET['action']=="buscar"){
$tip=$_GET['tipo'];
if ($tip=1){
	/*echo "<script>console.log('111');</script>";*/
	$actual=date("Y-m-d");							
	$sql = "select ac.id, ac.nombre_categoria, a.nombre,a.cantidad,ae.nombre_estado,a.precio_compra,a.fecha_ingreso,a.depresiacion
from activos as a inner join activos_categoria as ac on a.categoria_id=ac.id INNER JOIN activos_estado as ae on a.estado_activo_id=ae.id where a.estado=1  and DATE(fecha_registro)='$actual'";
}
}
//buscar con parametros
if (isset($_GET['Bcategoria']))
{
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
                        <h1 class="page-head-line" >Activos Fijos  
						<?php
						echo (isset($_GET['action']) && @$_GET['action']=="add" || @$_GET['action']=="edit")?
						' <a href="activos.php?action=buscar&tipo=1" class="btn btn-primary btn-sm pull-right">Volver <i class="glyphicon glyphicon-arrow-right"></i></a>':'<a href="activos.php?action=add" class="btn btn-primary btn-sm pull-right"><i class="glyphicon glyphicon-plus"></i> Agregar Activo </a>';
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
                           <?php echo ($action=="add")? "Agregar Activos": "Editar Activos"; ?>
                        </div>
						<form action="activos.php" method="post" id="signupForm1" class="form-horizontal">
                        <div class="panel-body">
						<fieldset class="scheduler-border" >
						 <legend  class="scheduler-border">Información del Activo:</legend>
						 <div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Categoria* </label>
								<div class="col-sm-10">
									<select  class="form-control" id="categoria" name="categoria" >
									<option value="" >Selecciona Categoria</option>
                                    <?php
									$sql = "select * from activos_categoria where estado=1";
									$q = $conn->query($sql);
									
									while($r = $q->fetch_assoc())
									{
									echo '<option value="'.$r['id'].'"  '.(($branch==$r['id'])?'selected="selected"':'').'>'.$r['nombre_categoria'].'</option>';
									}
									?>									
									
									</select>
								</div>
						</div>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Cantidad* </label>
								<div class="col-sm-10">
									<input type="number" class="form-control" id="cantidad" name="cantidad" value="<?php echo $cantidad;?>"  />
								</div>
						</div>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Nombre Activo* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="nombreactivo" name="nombreactivo" value="<?php echo $nombreActivo;?>"  />
								</div>
						</div>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Descripcion* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="descripcion" name="descripcion" value="<?php echo $descripcion;?>" maxlength="350" />
								</div>
						</div>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Estado del Activo* </label>
								<div class="col-sm-10">
									<select  class="form-control" id="estadoactivo" name="estadoactivo" >
									<option value="" >Selecciona Estado</option>
                                    <?php
									$sql = "select * from activos_estado where estado=1";
									$q = $conn->query($sql);
									
									while($r = $q->fetch_assoc())
									{
									echo '<option value="'.$r['id'].'"  '.(($branch==$r['id'])?'selected="selected"':'').'>'.$r['nombre_estado'].'</option>';
									}
									?>									
									
									</select>
								</div>
						</div>	
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Precio de Compra* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="preciocompra" name="preciocompra" value="<?php echo $precioCompra;?>"  />
								</div>
						</div>
						
						
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Fecha de Compra* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="fechacompra" name="fechacompra" value="<?php echo  ($fechaCompra!='')?date("Y-m-d", strtotime($fechaCompra)):date("Y-m-d");?>" style="background-color: #fff;" readonly />
								</div>
						</div>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Años de Vida Util* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="vidautil" name="vidautil" value="<?php echo $vidaUtil;?>"/>
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
			
		$( "#fechacompra" ).datepicker({
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
					joindate: "required",
					emailid: "email",
					branch: "required",
					
					
					contact: {
						required: true,
						digits: true
					},
					
					fees: {
						required: true,
						digits: true
					},
					
					
					advancefees: {
						required: true,
						digits: true
					},
				
					
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
                        	<form action="activos.php" method="get" id="signupForm1" class="form-horizontal"> 
                        	
                        	
                        		<label class="col-sm-1 control-label" for="Old">Categoria </label>
								<div class="col-sm-3">
									<select  class="form-control" id="Bcategoria" name="Bcategoria" >
									<option value="" >Todos</option>
                                    <?php
									$sql5 = "select * from activos_categoria where estado=1 order by id asc";
									$q5 = $conn->query($sql5);
									
									while($r5 = $q5->fetch_assoc())
									{
									echo '<option value="'.$r5['id'].'"  '.(($branch==$r5['id'])?'selected="selected"':'').'>'.$r5['nombre_categoria'].'</option>';
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
                        		<a href="php/ImprimirActivos.php?action=imprimir&Bcategoria=<?php echo $carreB;?>" target="_blank" class="btn btn-success "><span class="glyphicon .glyphicon-print">PDF</span></a> 
                        	</div>
                            
                        </div>
                        <div class="panel-body">
                            <div class="table-sorting table-responsive">
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
									
									echo '<tr '.(($r['nombre_categoria']>0)?'class="danger"':'').'>
                                            <td>'.$i.'</td>
                                            <td>'.$r['nombre_categoria'].'</td>
                                            <td>'.$r['cantidad'].'</td>
                                            <td>'.$r['nombre'].'</td>
                                            <td>'.$r['nombre_estado'].'</td>
                                            <td>'.$r['precio_compra'].'</td>
                                            <td>'.$r['depresiacion'].'</td>
											
											<td>
											
											

											<a href="activos.php?action=edit&id='.$r['id'].'" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-edit"></span></a>
											
											<a onclick="return confirm(\'Deseas realmente eliminar este registro, este proceso es irreversible\');" href="activos.php?action=delete&id='.$r['id'].'" class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove"></span></a> </td>
											
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
