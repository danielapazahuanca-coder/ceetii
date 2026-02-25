<?php
include("php/dbconnect.php");

$sucursal=$_SESSION['rainbow_sucursal'];

if(isset($_POST['req']) && $_POST['req']=='1') 
{

$sid = (isset($_POST['student']))?mysqli_real_escape_string($conn,$_POST['student']):'';

 $sql = "select s.Cod_Estu,s.sname,s.balance,s.fees,s.contact,b.branch,s.joindate from student as s,branch as b where b.id=s.branch and  s.delete_status='0' and s.sucursal_varchar='$sucursal' and s.Cod_Estu='".$sid."'";
$q = $conn->query($sql);
if($q->num_rows>0)
{

$res = $q->fetch_assoc();

									$sql2 = "select * from depositos_banco where estado=1 and sucursal_varchar='$sucursal' order by id asc";
									$q2 = $conn->query($sql2);


?>
<form class="form-horizontal" id ="signupForm1" action="fees.php" method="post">
  <div class="form-group">
    <label class="control-label col-sm-2" for="email">Nombre:</label>
    <div class="col-sm-10">
      <input type="text" id="nameEstuD" name="nameEstuD" class="form-control" readonly=false  value="<?php echo $res['sname'];?>" >
    </div>
  </div>
  
    <div class="form-group">
    <label class="control-label col-sm-2" for="email">Cod. Estu.:</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" name="codestuD"  id="codestuD" value="<?php echo $res['Cod_Estu'];?>" disabled />
	  <input type="hidden" value="'.$res['Cod_Estu'].'" name="sid">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-2" for="email">Pago Total:</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" name="totalfeeD" id="totalfeeD"   value="<?php echo $res['fees'];?>" disabled />
    </div>
  </div>
  
  
  <div class="form-group">
    <label class="control-label col-sm-2" for="email">Balance:</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" name="balanceD"  id="balanceD" value="<?php echo $res['balance'];?>" disabled />
	  <input type="hidden" value="<?php echo $res['Cod_Estu'];?>" name="sid">
    </div>
  </div>
   <div class="form-group">
						 		<label class="col-sm-2 control-label" for="Old">Banco* </label>
		<div class="col-sm-10">
									<select  class="form-control" id="bancoD" name="bancoD" >
														
									<?php 
									while($res2 = $q2->fetch_assoc())
									{ echo '
									<option value="'.$res2['id'].'">'.$res2['nombre_banco'].'</option>'									
									;} ?>'
									</select>
		</div>
	</div>
	<div class="form-group">
    <label class="control-label col-sm-2" for="email">Fecha Deposito:</label>
    <div class="col-sm-10">
	
      <input type="text" class="form-control" name="submitdateD"  id="submitdateD" style="background:#fff;"  readonly  />
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-2" for="email">Monto Deposito:</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" name="paidD"  id="paidD" placeholder="Ej. 2.80" />
    </div>
  </div>

   <div class="form-group">
    <label class="control-label col-sm-2" for="email">Detalle:</label>
    <div class="col-sm-10">
      <textarea class="form-control" name="detalleD" id="detalleD" placeholder="Ej. Nro. Deposito, FechaDeposito, Detalle"></textarea>
    </div>
  </div>
 
 
 
 
 
  <div class="form-group"> 
    <div class="col-sm-offset-2 col-sm-10">
      <button type="submit" class="btn btn-primary" name="saveOtros">Guardar</button>
    </div>
  </div>
</form>

<script type="text/javascript">
$(document).ready( function() {
$("#submitdateD").datepicker( {
        changeMonth: true,
        changeYear: true,
       
        dateFormat: "yy-mm-dd",
      
    });
	
	
///////////////////////////

$( "#signupForm1" ).validate( {
				rules: {
					submitdateD: "required",
					paidD: "required",
					detalleD: "required",
					
					submitdateD: {required: true,},
					paidD: {required: true,},
					detalleD: {required: true,}
					
					
				},
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

					
					if ( !element.next( "span" )[ 0 ] ) {
						$( "<span class=\'glyphicon glyphicon-remove form-control-feedback\'></span>" ).insertAfter( element );
					}
				},
				success: function ( label, element ) {
					if ( !$( element ).next( "span" )[ 0 ] ) {
						$( "<span class=\'glyphicon glyphicon-ok form-control-feedback\'></span>" ).insertAfter( $( element ) );
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


//////////////////////////	
	
	
	
});

</script>
<?php

}else
{
echo "Something Goes Wrong! Try After sometime.";
}


}

if(isset($_POST['req']) && $_POST['req']=='2') 
{

$sid = (isset($_POST['student']))?mysqli_real_escape_string($conn,$_POST['student']):'';
$sql = "select paid,submitdate,transcation_remark from fees_transaction  where sucursal_varchar='$sucursal' and stdid='".$sid."'";
$fq = $conn->query($sql);
if($fq->num_rows>0)
{


 $sql = "select s.Cod_Estu,s.sname,s.balance,s.fees,s.contact,b.branch,s.joindate from student as s,branch as b where b.id=s.branch  and s.Cod_Estu='".$sid."' and s.sucursal_varchar='$sucursal' and b.sucursal_varchar='$sucursal'";
$sq = $conn->query($sql);
$sr = $sq->fetch_assoc();

echo '
<h4>Información del Estudiante</h4>
<div class="table-responsive">
<table class="table table-bordered">
<tr>
<th>Nombre</th>
<td>'.$sr['sname'].'</td>
<th>Banco</th>
<td>'.$sr['branch'].'</td>
</tr>
<tr>
<th>Contacto</th>
<td>'.$sr['contact'].'</td>
<th>Fecha de Ingreso</th>
<td>'.date("d-m-Y", strtotime($sr['joindate'])).'</td>
</tr>


</table>
</div>
';


echo '
<h4>Información de Pagos</h4>
<div class="table-responsive">
<table class="table table-bordered">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Pago</th>
        <th>Observaciones</th>
      </tr>
    </thead>
    <tbody>';
	$totapaid = 0;
while($res = $fq->fetch_assoc())
{
$totapaid+=$res['paid'];
        echo '<tr>
        <td>'.date("d-m-Y", strtotime($res['submitdate'])).'</td>
        <td>'.$res['paid'].'</td>
        <td>'.$res['transcation_remark'].'</td>
      </tr>' ;
}
      
echo '	  
    </tbody>
  </table>
 </div> 
 
<table style="width:150px;" >
<tr>
<th>Total Adeudado: 
</th>
<td>'.$sr['fees'].'
</td>
</tr>

<tr>
<th>Total Pagado: 
</th>
<td>'.$totapaid.'
</td>
</tr>

<tr>
<th>Balance: 
</th>
<td>'.$sr['balance'].'
</td>
</tr>
</table>
 ';


 }
else
{
echo 'Sin pagos ingresados.';
}
 
}
		
		 
			
			
	

?>