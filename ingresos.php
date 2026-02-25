<?php
include("php/dbconnect.php");
include("php/checklogin.php");



$fechaBus='';

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
/*$Busdate = mysqli_real_escape_string($conn,$_POST['buscardate']);//fecha comprobante*/
/*$dateB = $_POST['buscardate'];//fecha comprobante*/
$sucursal=$_SESSION['rainbow_sucursal'];
$usuario=$_SESSION['rainbow_username'];
if(isset($_POST['save']))
{


}




if(isset($_GET['action']) && $_GET['action']=="delete"){

$id = isset($_GET['id'])?mysqli_real_escape_string($conn,$_GET['id']):'';

$sqlEdit = $conn->query("SELECT * FROM fees_transaction WHERE sucursal_varchar='$sucursal' and usuario='$usuario' and id='".$id."' ");
$rowsTran = $sqlEdit->fetch_assoc();
	


$sql1=$conn->query("SELECT * from student where sucursal_varchar='$sucursal' and idRegistro='".$rowsTran['stdid']."'");
$rowsEstu=$sql1->fetch_assoc();
$balanceActual=$rowsEstu['balance'];

$montoSumar=$rowsTran['paid'];
$nuevoBalance=$balanceActual+$montoSumar;

$idTransac=$rowsTran['id'];
$sqlTransac="update fees_transaction set usuario='$usuario', estado=0,transcation_remark='Anulado desde el sistema' where id='$idTransac' and sucursal_varchar='$sucursal'";
$conn->query($sqlTransac);
//$conn->query("UPDATE  fees_transaction set estado = 0, transaction_remark='Anulado desde el sistema'  WHERE id='".$rowsTran['id']."'");	


$idestuu=$rowsTran['stdid'];
$sqlupp="update student set balance='$nuevoBalance' where sucursal_varchar='$sucursal' and idRegistro='$idestuu'";
$conn->query($sqlupp);


header("location: ingresos.php?action=buscar&tipo=1act=3");

}


$action = "add";
if(isset($_GET['action']) && $_GET['action']=="edit" ){
$id = isset($_GET['id'])?mysqli_real_escape_string($conn,$_GET['id']):'';

$sqlEdit = $conn->query("SELECT * FROM fees_transaction WHERE sucursal_varchar='$sucursal' and usuario='$usuario' and id='".$id."'");
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

if(isset($_GET['action']) && $_GET['action']=="buscar"){
/*$dateB = "<script>document.getElementById('buscardate')||{}).value ||'';</script>";//fecha comprobante
$dateA=strtotime($dateB);
$dateNew=date('Y-m-d',$dateA);
$sql = "select * from fees_transaction where estado=1 and DATE(submitdate)='$dateNew'";
$q = $conn->query($sql);
echo $dateB;*/
$tip=$_GET['tipo'];
if ($tip=1){
	/*echo "<script>console.log('111');</script>";*/
	$actual=date("Y-m-d");							
	$sql = "select * from fees_transaction where estado=1 and DATE(submitdate)='$actual' and sucursal_varchar='$sucursal' and usuario='$usuario'";
	$sqlSum = "select sum(paid) as total from fees_transaction where estado=1 and DATE(submitdate)='$actual' and sucursal_varchar='$sucursal' and usuario='$usuario'";
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
	
	$sql = "select * from fees_transaction where estado=1 and DATE(submitdate)>='$fechaBus' and date(submitdate)<='$fechaBus2' and sucursal_varchar='$sucursal' and usuario='$usuario'";
	$sqlSum = "select sum(paid) as total from fees_transaction where estado=1 and DATE(submitdate)>='$fechaBus' and date(submitdate)<='$fechaBus2' and sucursal_varchar='$sucursal' and usuario='$usuario'";
}
//para CREAR PDF********
if(isset($_GET['action']) && $_GET['action']=="imprimir"){
	
	$fechaBus=$_GET['buscardate'];
	$pdf->loadHtml($fechaBus);
	$pdf->setPaper("A4","landscape");
	$pdf->render();
	$pdf->stream();
}
///FIN DE CREAR PDF
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistema de Pago - Ingresos</title>

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
                        <h1 class="page-head-line">Ingresos  
						<?php
						echo (isset($_GET['action']) && @$_GET['action']=="add" || @$_GET['action']=="edit")?
						' <a href="ingresos.php" class="btn btn-primary btn-sm pull-right">Volver <i class="glyphicon glyphicon-arrow-right"></i></a>':'<a href="fees.php" class="btn btn-primary btn-sm pull-right"><i class="glyphicon glyphicon-plus"></i> Agregar Pagos </a>';
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
                           <?php echo ($action=="add")? "Agregar Ingreso": "Editar Ingreso"; ?>
                        </div>
						<form action="ingresos.php" method="post" id="signupForm1" class="form-horizontal">
                        <div class="panel-body">
						<fieldset class="scheduler-border" >
						 <legend  class="scheduler-border">Datos de Comprobante:</legend>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">idEstudiante* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="proveedor" name="proveedor" value="<?php echo $proveedor;?>"  />
								</div>
						</div>
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Estudiante* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="numero" name="numero" value="<?php echo $nro_recibo;?>"  />
								</div>
						</div>
						<!--<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Fecha Recibo* </label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="joindate" name="joindate" value="<?php echo  ($fecha_recibo!='')?date("Y-m-d", strtotime($fecha_recibo)):'';?>" style="background-color: #fff;" readonly />
								</div>
						</div>-->
						<div class="form-group">
								<label class="col-sm-2 control-label" for="Old">Monto* </label>
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
                        	<div class="col-md-10">
                        	<form action="ingresos.php" method="get" id="signupForm1" class="form-horizontal"> 
                        	
                        	
                        		<label class="col-sm-2 control-label" for="Old">Fechas </label>
								<div class="col-sm-2">
									<input type="text" class="form-control" id="buscardate" name="buscardate"  style="background-color: #fff;" readonly  />
								</div>
								<div class="col-sm-2">
									<input type="text" class="form-control" id="buscardate2" name="buscardate2"  style="background-color: #fff;" readonly  />
								</div>
								<div class="col-sm-2">
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
										echo '<input type="text" class="form-control" id="total" name="total"  style="background-color: #fff;" readonly value="'.number_format( $rs['total'],2,',',' ').'"  />';
										}
									?>
									
								</div>

							</form>
                        	</div>
                        	
                        	<div class="col-md-2">
                        			<!-- <button id="GenerarPDF" class="btn btn-default">Crear PDF</button> -->
                        			<a href="php/ImprimirIngresos.php?action=imprimir&fechaB=<?php echo $fechaBus; ?>&fechaB2=<?php echo $fechaBus2; ?>" target="_blank" class="btn btn-success "><span class="glyphicon .glyphicon-print">PDF</span></a> 
                        	</div>
                            
                        </div>
                        <div class="panel-body">
                            <div class="table-sorting table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="tSortable22">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>IdIngreso</th>
                                            <th>IdEstudiante</th>
                                            <th>Estudiante</th>
                                            <th>Monto</th>
											<th>Detalle</th>
											<th>Fecha de Registro </th>
											<th>Acción</th>
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
										if ($r['otros_ingresos']==0)
										{
										echo '<tr '.(($r['transcation_remark']>0)?'class="danger"':'').'>
                                            <td>'.$i.'</td>
                                            <td>'.$r['id'].'</td>
                                            <td>'.$r['stdid'].'</td>
                                            <td>'.$r['nombre_estudiante'].'</td>
                                            <td>'.$r['paid'].'</td>
                                            <td>'.$r['transcation_remark'].'</td>
                                            <td>'.$r['submitdate'].'</td>					
											<td>
											
											

											<a target="_blank" href="php/ImprimirComprobante.php?Imprime=2&idEstu='.$r['id'].'" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-print"></span></a>
											
											<a onclick="return confirm(\'Deseas realmente eliminar este registro, este proceso es irreversible\');" href="ingresos.php?action=delete&id='.$r['id'].'" class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove"></span></a> </td>
											
                                        </tr>';
										$i++;
									}
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
