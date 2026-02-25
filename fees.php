<?php
include("php/dbconnect.php");
include("php/checklogin.php");
$errormsg= '';
$sucursal=$_SESSION['rainbow_sucursal'];
if(isset($_POST['save']))
{
    $paid = mysqli_real_escape_string($conn,$_POST['paid']);
    //$submitdate = mysqli_real_escape_string($conn,$_POST['submitdate']);
    $estu=mysqli_real_escape_string($conn,$_POST['nameEstu']);
    $transcation_remark = mysqli_real_escape_string($conn,$_POST['transcation_remark']);
    $sid = mysqli_real_escape_string($conn,$_POST['sid']);

    $sql = "select fees,balance  from student where Cod_Estu = '$sid' and sucursal_varchar='$sucursal'";
    $sq = $conn->query($sql);
    $sr = $sq->fetch_assoc();
    $totalfee = $sr['fees'];
    $user= $_SESSION['rainbow_username'];
    if($sr['balance']>0)
    {
    $sql = "insert into fees_transaction(stdid,transcation_remark,paid,paid_deposito,nombre_estudiante,estado,usuario,otros_ingresos,sucursal_varchar) values('$sid','$transcation_remark','$paid',0,'$estu',1,'$user',0,'$sucursal') ";
    $conn->query($sql);
    $sql = "SELECT sum(paid) as totalpaid, sum(paid_deposito) as totaldepo FROM fees_transaction WHERE estado=1 and stdid = '$sid' and sucursal_varchar='$sucursal'";
    $tpq = $conn->query($sql);
    $tpr = $tpq->fetch_assoc();
    $totalpaid = $tpr['totalpaid']+$tpr['totaldepo'];
    $tbalance = $totalfee - $totalpaid;

    $sql = "update student set balance='$tbalance' where Cod_Estu = '$sid' and sucursal_varchar='$sucursal' ";
    $conn->query($sql);

     /*echo '<script type="text/javascript">window.location="fees.php?act=1";</script>';*/
     //echo '<script type="text/javascript">window.location="php/ImprimirComprobante.php?idEstu=17;</script>';
     header("Location:php/ImprimirComprobante.php?Imprime=0&idEstu='$sid'");
    }
}
if(isset($_POST['saveOtros']))
{
   
    $estuD=mysqli_real_escape_string($conn,$_POST['nameEstuD']);
    $sidD = mysqli_real_escape_string($conn,$_POST['sid']);
    $bancoD=mysqli_real_escape_string($conn,$_POST['bancoD']);
    $submitdateD = mysqli_real_escape_string($conn,$_POST['submitdateD']);
    $paidD = mysqli_real_escape_string($conn,$_POST['paidD']);
    $detalle = mysqli_real_escape_string($conn,$_POST['detalleD']);


    $sql = "select fees,balance  from student where Cod_Estu = '$sidD' and sucursal_varchar='$sucursal'";
    $sq = $conn->query($sql);
    $sr = $sq->fetch_assoc();
    $totalfeeD = $sr['fees'];
    $user= $_SESSION['rainbow_username'];
    //if($sr['balance']>0)
    //{
    $sql = "insert into fees_transaction(stdid,transcation_remark,paid,paid_deposito,nombre_estudiante,estado,usuario,otros_ingresos,sucursal_varchar) values('$sidD','$detalle',0,'$paidD','$estuD',1,'$user',1,'$sucursal') ";
    $conn->query($sql);
    $sql = "SELECT sum(paid) as totalpaid, sum(paid_deposito) as totaldepo FROM fees_transaction WHERE estado=1 and stdid = '$sidD' and sucursal_varchar='$sucursal' ";
    $tpq = $conn->query($sql);
    $tpr = $tpq->fetch_assoc();
    $totalpaidD = $tpr['totalpaid']+$tpr['totaldepo'];
    $tbalanceD = $totalfeeD - $totalpaidD;

    $sqlD = "update student set balance='$tbalanceD' where Cod_Estu = '$sidD' and sucursal_varchar='$sucursal'";
    $conn->query($sqlD);

     /*echo '<script type="text/javascript">window.location="fees.php?act=1";</script>';*/
     //echo '<script type="text/javascript">window.location="php/ImprimirComprobante.php?idEstu=17;</script>';
    $sqlDEP = "insert into depositos_estudiantes(codEstu,banco_id,fecha_deposito,monto,detalle,estado,usuario,sucursal_varchar) values('$sidD','$bancoD','$submitdateD','$paidD','$detalle',1,'$user','$sucursal') ";
    $conn->query($sqlDEP);

     header("Location:php/ImprimirComprobanteDepo.php?idEstu='$sidD'");
    //}
}
if(isset($_REQUEST['act']) && @$_REQUEST['act']=="1")
{
$errormsg = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong>Excelente!</strong> Pago realizado exitósamente</div>";
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
	<link href="css/jquery-ui-1.10.3.custom.min.css" rel="stylesheet" />	
	<link href="css/datepicker.css" rel="stylesheet" />	
	   <link href="css/datatable/datatable.css" rel="stylesheet" />
	   
    <script src="js/jquery-1.10.2.js"></script>	
    <script type='text/javascript' src='js/jquery/jquery-ui-1.10.1.custom.min.js'></script>
   <script type="text/javascript" src="js/validation/jquery.validate.min.js"></script>
 
		 <script src="js/dataTable/jquery.dataTables.min.js"></script>
		
		 
	
</head>
<?php
include("php/header.php");
?>
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="page-head-line">Pagos  
						
						</h1>

                    </div>
                </div>
				
				
				
    	<?php
		echo $errormsg;
		?>
		
		

<div class="row" style="margin-bottom:20px;">
<div class="col-md-12">
<fieldset class="scheduler-border" >
    <legend  class="scheduler-border">Búsqueda:</legend>
<form class="form-inline" role="form" id="searchform">
  <div class="form-group">
    <label for="email">Nombre</label>
    <input type="text" class="form-control" id="student" name="student">
  </div>
  
   <div class="form-group">
    <label for="email"> Fecha de Ingreso </label>
    <input type="text" class="form-control" id="doj" name="doj" >
  </div>
  
  <div class="form-group">
    <label for="email"> Bancos </label>
    <select  class="form-control" id="branch" name="branch" >
		<option value="" >Selecciona Banco</option>
                                    <?php
									$sql = "select * from branch where delete_status='0' order by branch.branch asc";
									$q = $conn->query($sql);
									
									while($r = $q->fetch_assoc())
									{
									echo '<option value="'.$r['id'].'"  '.(($branch==$r['id'])?'selected="selected"':'').'>'.$r['branch'].'</option>';
									}
									?>
	</select>
  </div>
  
   <button type="button" class="btn btn-success btn-sm" id="find" > Búsqueda </button>
  <button type="reset" class="btn btn-danger btn-sm" id="clear" > Limpiar </button>
</form>
</fieldset>

</div>
</div>

<script type="text/javascript">
$(document).ready( function() {

/*
$('#doj').datepicker( {
        changeMonth: true,
        changeYear: true,
        showButtonPanel: false,
        dateFormat: 'mm/yy',
        onClose: function(dateText, inst) { 
            $(this).datepicker('setDate', new Date(inst.selectedYear, inst.selectedMonth, 1));
        }
    });
	
*/
	
/******************/	
	 $("#doj").datepicker({
         
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        dateFormat: 'mm/yy',
        onClose: function(dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).val($.datepicker.formatDate('MM yy', new Date(year, month, 1)));
        }
    });

    $("#doj").focus(function () {
        $(".ui-datepicker-calendar").hide();
        $("#ui-datepicker-div").position({
            my: "center top",
            at: "center bottom",
            of: $(this)
        });
    });

/*****************/
	
$('#student').autocomplete({
		      	source: function( request, response ) {
		      		$.ajax({
		      			url : 'ajx.php',
		      			dataType: "json",
						data: {
						   name_startsWith: request.term,
						   type: 'studentname'
						},
						 success: function( data ) {
						 
							 response( $.map( data, function( item ) {
							
								return {
									label: item,
									value: item
								}
							}));
						}
						
						
						
		      		});
		      	}
				/*,
		      	autoFocus: true,
		      	minLength: 0,
                 select: function( event, ui ) {
						  var abc = ui.item.label.split("-");
						  //alert(abc[0]);
						   $("#student").val(abc[0]);
						   return false;

						  },
                 */
  

						  
		      });
	

$('#find').click(function () {
mydatatable();
        });


$('#clear').click(function () {

$('#searchform')[0].reset();
mydatatable();
        });
		
function mydatatable()
{
        
              $("#subjectresult").html('<table class="table table-striped table-bordered table-hover" id="tSortable22"><thead><tr><th>Name/Contact</th><th>Fees</th><th>Balance</th><th>Curso</th><th>DOJ</th><th>Action</th></tr></thead><tbody></tbody></table>');
			  
			    $("#tSortable22").dataTable({
							      'sPaginationType' : 'full_numbers',
							     "bLengthChange": false,
                  "bFilter": false,
                  "bInfo": false,
							       'bProcessing' : true,
							       'bServerSide': true,
							       'sAjaxSource': "datatable.php?"+$('#searchform').serialize()+"&type=feesearch",
							       'aoColumnDefs': [{
                                   'bSortable': false,
                                   'aTargets': [-1] /* 1st one, start by the right */
                                                }]
                                   });


}
		
////////////////////////////
 $("#tSortable22").dataTable({
			     
                  'sPaginationType' : 'full_numbers',
				  "bLengthChange": false,
                  "bFilter": false,
                  "bInfo": false,
                  
                  'bProcessing' : true,
				  'bServerSide': true,
                  'sAjaxSource': "datatable.php?type=feesearch",
				  
			      'aoColumnDefs': [{
                  'bSortable': false,
                  'aTargets': [-1] /* 1st one, start by the right */
              }]
            });

///////////////////////////		


	
});


function GetFeeForm(sid)
{

$.ajax({
            type: 'post',
            url: 'getfeeform.php',
            data: {student:sid,req:'1'},
            success: function (data) {
              $('#formcontent').html(data);
			  $("#myModal").modal({backdrop: "static"});
            }
          });


}
function GetFeeFormOtros(sid)
{

$.ajax({
            type: 'post',
            url: 'getfeeformOtros.php',
            data: {student:sid,req:'1'},
            success: function (data) {
              $('#formcontent').html(data);
              $("#myModal").modal({backdrop: "static"});
            }
          });


}

</script>


		

<style>
#doj .ui-datepicker-calendar
{
display:none;
}

</style>
		
		<div class="panel panel-default">
                        <div class="panel-heading">
                            Gestionar Pagos  
                        </div>
                        <div class="panel-body">
                            <div class="table-sorting table-responsive" id="subjectresult">
                                <table class="table table-striped table-bordered table-hover" id="tSortable22">
                                    <thead>
                                        <tr>
                                          
                                            <th>Nombre</th>                                            
                                            <th>Pagos</th>
											<th>Balance</th>
											<th>Carrera</th>
											<th>Fecha Ingreso</th>
											<th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
								    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                 
	
	<!-------->
	
	<!-- Modal -->
  <div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Tomar Pago</h4>
        </div>
        <div class="modal-body" id="formcontent">
        
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

	
    <!--------->
    			
            
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
    <script src="js/custom1.js"></script>

    
</body>
</html>