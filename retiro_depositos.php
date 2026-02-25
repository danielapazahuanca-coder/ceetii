<?php
include("php/dbconnect.php");
include("php/checklogin.php");
$errormsg = '';
$action = "add";

$id="";
$emailid='';
$proveedor='';
$joindate = '';
$remark='';
$nro_recibo='';
$monto = 0;
$fees='';
$fecha_recibo = '';
$detalle='';
$branch='';
$sucursal=$_SESSION['rainbow_sucursal'];
$usuario=$_SESSION['rainbow_username'];
if(isset($_POST['save']))
{

  
}


if(isset($_GET['action']) && $_GET['action']=="retiro"){
$fechahora=date("Y-m-d H:i:s");
$conn->query("UPDATE  fees_transaction set usuario='$usuario', otros_ingresos = 0, paid = '".$_GET['monto']."', submitdate='$fechahora' WHERE stdid='".$_GET['codEstu']."'  and sucursal_varchar='$sucursal'  and otros_ingresos = 1 and paid_deposito = '".$_GET['monto']."'");	


$conn->query("UPDATE  depositos_estudiantes set estado = 2  WHERE sucursal_varchar='$sucursal' and id='".$_GET['id']."'");	
header("location: retiro_depositos.php?action=buscar&tipo=1&act=4");

}

if(isset($_GET['action']) && $_GET['action']=="delete"){

$conn->query("UPDATE  depositos_estudiantes set usuario='$usuario', estado = 0  WHERE sucursal_varchar='$sucursal' and id='".$_GET['id']."'");	
header("location: retiro_depositos.php?action=buscar&tipo=1&act=3");

}


$action = "add";
if(isset($_GET['action']) && $_GET['action']=="edit" ){
$id = isset($_GET['id'])?mysqli_real_escape_string($conn,$_GET['id']):'';

$sqlEdit = $conn->query("SELECT * FROM egresos WHERE sucursal_varchar='$sucursal' and idegreso='".$id."'");
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
$errormsg = "<div class='alert alert-success'> <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong>Excelente!</strong> Comprobante Agregado Exitósamente</div>";
}else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="2")
{
$errormsg = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a> <strong>Excelente!</strong> Comprobante Editado Exitósamente</div>";
}
else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="3")
{
$errormsg = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong>Excelente!</strong> Comprobante Eliminado Exitósamente</div>";
}
else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="4")
{
$errormsg = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong>Excelente!</strong> Actualizacion de Datos correctamente</div>";
}
//*****BUSCAR***
if(isset($_GET['action']) && $_GET['action']=="buscar"){
$tip=$_GET['tipo'];
if ($tip=1){
	/*echo "<script>console.log('111');</script>";*/
	$actual=date("Y-m-d");							
	$sql = "select de.id,de.codEstu, db.nombre_banco,s.sname,de.fecha_deposito,de.monto,de.detalle, de.fecha_registro,de.usuario FROM depositos_estudiantes as de inner join student as s ON de.codEstu=s.Cod_Estu INNER JOIN depositos_banco as db on de.banco_id=db.id WHERE de.estado=1 and DATE(de.fecha_registro)='$actual' and de.sucursal_varchar='$sucursal' and db.sucursal_varchar='$sucursal'";
	$sqlSum = "select sum(monto) as total from depositos_estudiantes where estado=1 and DATE(fecha_registro)='$actual' and sucursal_varchar='$sucursal'";
}
else{
	
	
}

$q = $conn->query($sql);
}
if (isset($_GET['buscardate']))
{
	$fechaBus=$_GET['buscardate'];
	$fechaBus2=$_GET['buscardate2'];
	/*$fecha=date("Y-m-d",$fechaBus);*/
	$BancoB=$_GET['bancoB'];

	if(empty($BancoB))
	{
	$sql = "select de.id,de.codEstu, db.nombre_banco,s.sname,de.fecha_deposito,de.monto,de.detalle, de.fecha_registro,de.usuario  FROM depositos_estudiantes as de inner join student as s ON de.codEstu=s.Cod_Estu INNER JOIN depositos_banco as db on de.banco_id=db.id WHERE de.estado=1 and DATE(de.fecha_registro)>='$fechaBus' and date(de.fecha_registro)<='$fechaBus2' and de.sucursal_varchar='$sucursal' and db.sucursal_varchar='$sucursal'";

	$sqlSum = "select sum(monto) as total from depositos_estudiantes where estado=1 and DATE(fecha_registro)>='$fechaBus' and date(fecha_registro)<='$fechaBus2' and sucursal_varchar='$sucursal'";
	}
	if($BancoB>0){
		$sql = "select  de.id,de.codEstu, db.nombre_banco,s.sname,de.fecha_deposito,de.monto,de.detalle, de.fecha_registro,de.usuario  FROM depositos_estudiantes as de inner join student as s ON de.codEstu=s.Cod_Estu INNER JOIN depositos_banco as db on de.banco_id=db.id WHERE de.estado=1 and DATE(de.fecha_registro)>='$fechaBus' and date(de.fecha_registro)<='$fechaBus2' and de.banco_id='$BancoB' and de.sucursal_varchar='$sucursal' and db.sucursal_varchar='$sucursal'";

		$sqlSum = "select sum(monto) as total from depositos_estudiantes where estado=1 and DATE(fecha_registro)>='$fechaBus' and date(fecha_registro)<='$fechaBus2' and banco_id='$BancoB' and sucursal_varchar='$sucursal'";
	}
}  

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistema de Pago - Retiro Depositos</title>

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
   
	
</head>
<?php
include("php/header.php");
?>
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="page-head-line">Retiros Depositos 
						<!-- <?php
						echo (isset($_GET['action']) && @$_GET['action']=="add" || @$_GET['action']=="edit")?
						' <a href="egresos.php?action=buscar&tipo=1" class="btn btn-primary btn-sm pull-right">Volver <i class="glyphicon glyphicon-arrow-right"></i></a>':'<a href="egresos.php?action=add" class="btn btn-primary btn-sm pull-right"><i class="glyphicon glyphicon-plus"></i> Agregar Compras </a>';
						?> -->
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
                           <?php echo ($action=="add")? "Agregar Egreso": "Editar Egreso"; ?>
                        </div>
						<form action="egresos.php" method="post" id="signupForm1" class="form-horizontal">
                        <div class="panel-body">
						<fieldset class="scheduler-border" >
						 <legend  class="scheduler-border">Datos de Comprobante:</legend>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Proveedor* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="proveedor" name="proveedor" value="<?php echo $proveedor;?>"  />
								</div>
						</div>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Numero Recibo* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="numero" name="numero" value="<?php echo $nro_recibo;?>"  />
								</div>
						</div>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Fecha Recibo* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="joindate" name="joindate" value="<?php echo  ($fecha_recibo!='')?date("Y-m-d", strtotime($fecha_recibo)):'';?>" style="background-color: #fff;" readonly />
								</div>
						</div>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Total Monto* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="fees" name="fees" value="<?php echo $monto;?>"   />
								</div>
						</div>

						<div class="form-group">
								<label class="col-sm-2 control-label" for="Password">Detalle </label>
								<div class="col-sm-10">
	                        <textarea class="form-control" id="remark" name="remark"><?php echo $detalle;?></textarea >
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
					proveedor: "required",
					joindate: "required",
					
					
					/*contact: {
						required: true,
						digits: true
					},*/
					
					fees: {
						required: true
						//digits: true
					},
					
					
					/*advancefees: {
						required: true,
						digits: true
					},*/
				
					
				},
			<?php
			}else
			{
			?>
			
			$( "#signupForm1" ).validate( {
				rules: {
					proveedor: "required",
					joindate: "required",
					
					
					/*
					contact: {
						required: true,
						digits: true
					}*/
					
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
                        	<form action="retiro_depositos.php" method="get" id="signupForm1" class="form-horizontal"> 
                        		<label class="col-sm-1 control-label" for="Old">Banco </label>
								<div class="col-sm-2">
									<select  class="form-control" id="bancoB" name="bancoB" >
									<option value="" >Todos</option>
                                    <?php
									$sql55 = "select * from depositos_banco where sucursal_varchar='$sucursal' and estado=1 order by id asc";
									$q55 = $conn->query($sql55);
									
									while($r55 = $q55->fetch_assoc())
									{
									
									echo '<option value="'.$r55['id'].'"  '.(($branch==$r55['id'])?'selected="selected"':'').'>'.$r55['nombre_banco'].'</option>';
									}
									?>									
									
									</select>
                        	  </div>
                        		<label class="col-sm-2 control-label" for="Old">Fecha </label>
								<div class="col-sm-2">
									<input type="text" class="form-control" id="buscardate" name="buscardate"  style="background-color: #fff;" readonly  />
								</div>
								<div class="col-sm-2">
									<input type="text" class="form-control" id="buscardate2" name="buscardate2"  style="background-color: #fff;" readonly  />
								</div>
								<div class="col-sm-1">
									<!-- <button id="GenerarPDF" class="btn btn-default">Buscar</button> -->
									
									<!-- <a href="ingresos.php?action=buscar" class="btn btn-primary "><span class="glyphicon .glyphicon-search">Buscar</span></a> -->
									<button type="submit" name="buscar" id="buscar" class="btn btn-primary">Buscar </button>
									
								</div>
								<label class="col-sm-1 control-label" for="Old">Total </label>
								<div class="col-sm-2">
									<?php 
										$qs = $conn->query($sqlSum);
									
										while($rs = $qs->fetch_assoc())
										{
										echo '<input type="text" class="form-control" id="total" name="total"  style="background-color: #fff;" readonly value="'.number_format($rs['total'],2,',',' ').'"  />';
										}
									?>
									
								</div>
							</form>
                        	</div>
                        	
                        	<div class="col-md-1">
                        			<!-- <button id="GenerarPDF" class="btn btn-default">Crear PDF</button> -->
                        			<a href="php/ImprimirDepositos.php?action=imprimir&fechaB=<?php echo $fechaBus; ?>&fechaB2=<?php echo $fechaBus2; ?>" target="_blank" class="btn btn-success "><span class="glyphicon .glyphicon-print">PDF</span></a> 
                        	</div>
                            
                        </div>
                        <div class="panel-body">
                            <div class="table-sorting table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="tSortable22">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Banco</th>
                                            <th>Estudiante</th>
                                            <th>Fecha Deposito</th>
                                            <th>Monto</th>
											<th>Detalle</th>
											<th>Fecha de Registro </th>
											<th>Usuario Registro </th>
											<th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
									<?php
									//$sql = "select * from egresos where estado=1";
									$q = $conn->query($sql);
									$i=1;
									while($r = $q->fetch_assoc())
									{
									
									echo '<tr '.(($r['nombre_banco']>0)?'class="danger"':'').'>
                                            <td>'.$i.'</td>
                                            <td>'.$r['nombre_banco'].'</td>
                                            <td>'.$r['sname'].'</td>
                                            <td>'.date("d M y", strtotime($r['fecha_deposito'])).'</td>
                                            <td>'.$r['monto'].'</td>
											<td>'.$r['detalle'].'</td>
											<td>'.$r['fecha_registro'].'</td>
											<td>'.$r['usuario'].'</td>
											<td>
											
											

											<a onclick="return confirm(\'DESEAS INGRESAR ESTE MONTO EN CAJA, este proceso es irreversible\');" href="retiro_depositos.php?action=retiro&id='.$r['id'].'&codEstu='.$r['codEstu'].'&monto='.$r['monto'].'&fecha='.$r['fecha_registro'].'" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-edit"></span></a> 
											
											<a onclick="return confirm(\'Deseas realmente eliminar este registro, este proceso es irreversible\');" href="retiro_depositos.php?action=delete&id='.$r['id'].'" class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove"></span></a> </td>
											
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
		 
	$( "#buscardate" ).datepicker({
dateFormat:"yy-mm-dd",
changeMonth: true,
changeYear: true,
yearRange: "1970:<?php echo date('Y');?>"
});	
	$( "#buscardate2" ).datepicker({
dateFormat:"yy-mm-dd",
changeMonth: true,
changeYear: true,
yearRange: "1970:<?php echo date('Y');?>"
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
