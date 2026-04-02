<?php
include("php/dbconnect.php");
include("php/checklogin.php");

$usuario=$_SESSION['rainbow_username'];
$sucursal=$_SESSION['rainbow_sucursal'];
$dateAct=date("Y-m-d");
$sql = "SELECT sum(paid) as totalingreso FROM fees_transaction WHERE estado = 1 and date(submitdate)='$dateAct' and sucursal_varchar='$sucursal' and usuario='$usuario'";

$ingreso = $conn->query($sql);
$TotalIngreso= $ingreso->fetch_assoc();
$tIn=$TotalIngreso['totalingreso'];

$sql2="SELECT sum(monto) as totalegreso FROM egresos where estado=1 and date(fecha)='$dateAct' and sucursal_varchar='$sucursal' and usuario='$usuario'";
$egreso=$conn->query($sql2);
$TotalEgreso=$egreso->fetch_assoc();
$tEgr=$TotalEgreso['totalegreso'];

$SaldoActual= $tIn-$tEgr;

//para buscar por fechas
if (isset($_GET['buscardate']))
{
    $fechaBus=$_GET['buscardate'];
    $fechaBus2=$_GET['buscardate2'];
    $sql = "SELECT sum(paid) as totalingreso FROM fees_transaction WHERE estado = 1 and date(submitdate)>='$fechaBus' and date(submitdate)<='$fechaBus2' and sucursal_varchar='$sucursal' and usuario='$usuario'";

    $ingreso = $conn->query($sql);
    $TotalIngreso= $ingreso->fetch_assoc();
    $tIn=$TotalIngreso['totalingreso'];

    $sql2="SELECT sum(monto) as totalegreso FROM egresos where estado=1 and date(fecha)>='$fechaBus'  and date(fecha)<='$fechaBus2' and sucursal_varchar='$sucursal' and usuario='$usuario'";
    $egreso=$conn->query($sql2);
    $TotalEgreso=$egreso->fetch_assoc();
    $tEgr=$TotalEgreso['totalegreso'];

    $SaldoActual= $tIn-$tEgr;
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistema de Pago Integrado</title>

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

    <link rel="stylesheet" type='text/css' href="css/AdminLTE.min.css">

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
                        <h1 class="page-head-line">Control de Ventas</h1>
                        <!-- <h2 style="text-align:center;"> Resumen Diario de  <strong> CAJA</strong> </h2> -->

                    </div>
                </div>
                <div class="col-md-6">
                            <form action="index.php" method="get" id="signupForm1" class="form-horizontal"> 
                            
                                <div class="col-md-14">
                                <label class="col-sm-2 control-label" for="Old">Fechas </label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="buscardate" name="buscardate"  style="background-color: #fff;" readonly  />
                                </div>

                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="buscardate2" name="buscardate2"  style="background-color: #fff;" readonly  />
                                </div>

                                <div class="col-sm-2">
                                    <!-- <button id="GenerarPDF" class="btn btn-default">Buscar</button> -->
                                    
                                    <!-- <a href="ingresos.php?action=buscar" class="btn btn-primary "><span class="glyphicon .glyphicon-search">Buscar</span></a> -->
                                    <button type="submit" name="buscar" id="buscar" class="btn btn-primary">Ver Saldos </button>
                                    
                                </div>
                                </div>
                            </form>
                            </div>
                            
                            <div class="col-md-2">
                                    <!-- <button id="GenerarPDF" class="btn btn-default">Crear PDF</button> -->
                                    <a href="php/ImprimirCaja.php?action=imprimir&fechaB=<?php echo $fechaBus;?>&fechaB2=<?php echo $fechaBus2; ?>" target="_blank" class="btn btn-success "><span class="glyphicon .glyphicon-print">IMPRIMIR PDF</span></a> 
                            </div>
                <div class="row">

                    <div class="col-md-12">
                        <div class="panel-body">
                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                          <div class="small-box bg-aqua">
                              <div class="inner">
                                <h4 style="font-size:30px;">
                                  <!--<strong>S/ <?php echo $totalc; ?></strong>-->
                                  <strong><?php echo number_format($TotalIngreso['totalingreso'],2,',',' ');?></strong>
                                </h4>
                                <p>TOTAL BS.</p>
                              </div>
                              <div class="icon">
                                <i class="ion ion-bag"></i>
                              </div>
                              <a href="ingresos.php?action=buscar&tipo=1" class="small-box-footer">INGRESOS <i class="fa fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                          <div class="small-box bg-green">
                              <div class="inner">
                                <h4 style="font-size:30px;">
                                  <!--<strong>S/ <?php echo $totalv; ?></strong>-->
                                  <strong><?php echo number_format($TotalEgreso['totalegreso'],2,',',' '); ?></strong>
                                </h4>
                                <p>TOTAL BS.</p>
                              </div>
                              <div class="icon">
                                <i class="ion ion-bag"></i>
                              </div>
                              <a href="egresos.php?action=buscar&tipo=1" class="small-box-footer">EGRESOS <i class="fa fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                          <div class="small-box bg-blue" >
                              <div class="inner">
                                <h4 style="font-size:30px;">
                                  <!--<strong>S/ <?php echo $totalv; ?></strong>-->
                                  <strong><?php echo number_format($SaldoActual,2,',',' '); ?></strong>
                                </h4>
                                <p>Saldo a la fecha</p>
                              </div>
                              <div class="icon">
                                <i class="ion ion-bag"></i>
                              </div>
                              <a href="#" class="small-box-footer">SALDO ACTUAL <i class="fa fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
                <!-- /. ROW  -->
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="page-head-line">Accesos Directos</h1>
                        
                    </div>

				  <div class="col-md-4">
                        <div class="main-box mb-pink">
                            <a href="student.php?action=buscar&tipo=1">
                                <i class="fa fa-users fa-5x"></i>
                                <h5>Estudiantes</h5>
                            </a>
                        </div>
                    </div>
				
				
                   
					
                    <div class="col-md-4">
                        <div class="main-box mb-dull">
                            <a href="fees.php">
                                <i class="fa fa-usd fa-5x"></i>
                                <h5>Recibir Pagos</h5>
                            </a>
                        </div>
                    </div>
					
					
					 <div class="col-md-4">
                        <div class="main-box mb-red">
                            <a href="report.php">
                                <i class="fa fa-file-text fa-5x"></i>
                                <h5>Reportes</h5>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="main-box mb-dull" style="background-color: #8d191d;">
                            <a href="inventario/activos.php">
                                <i class="fa fa-archive fa-5x"></i>
                                <h5>Inventario Activos</h5>
                            </a>
                        </div>
                    </div>
                  

                </div>
                <!-- /. ROW  -->
                <script>
         
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
            
            </div>
            <!-- /. PAGE INNER  -->
        </div>
        <!-- /. PAGE WRAPPER  -->
    </div>
    <!-- /. WRAPPER  -->

    <div id="footer-sec">
    Para más desarrollos, llama el siguiente numero <a href="#" target="_blank">73247852 - Fernando Apaza</a>
    </div>
   
   <script src="js/jquery-1.10.2.js"></script>	
    <!-- BOOTSTRAP SCRIPTS -->
    <script src="js/bootstrap.js"></script>
    <!-- METISMENU SCRIPTS -->
    <script src="js/jquery.metisMenu.js"></script>
       <!-- CUSTOM SCRIPTS -->
    <script src="js/custom1.js"></script>
    


</body>
</html>

 