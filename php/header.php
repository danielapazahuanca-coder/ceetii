
<link rel="stylesheet" href="../css/AdminLTE.min.css">
<link rel="icon" type="image/png" href="img/logoIcono.ico" />
<body>
    <div id="wrapper">
        <nav class="navbar navbar-default navbar-cls-top " role="navigation" style="margin-bottom: 0">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="index.php" style="background: white;">
                    <img src="img/LogoLoginCeti1.jpeg" class="img" style="width: 95%;margin-top: -25px;">
                </a>

            </div>
                       
                            <div class="" style="font-size: 20px;font-weight: bold;color: white;text-align: center;padding-top: 20px;">
                                Usuario: 
                                <?php echo $_SESSION['rainbow_name'];?>
                            <br />
                               Sucursal: 
                                <?php echo $_SESSION['rainbow_sucursal'];?>
                            </div>
        </nav>
        <!-- /. NAV TOP  -->
        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li>
                         
                    </li>


                    <li>
                        <a class="active-menu" href="index.php"><i class="fa fa-dashboard "></i>Panel de Control</a>
                    </li>
                    <li>
                        <a href="student.php?action=buscar&tipo=1"><i class="fa fa-users "></i>Estudiantes</a>
                    </li>
                    <li><a href="#"><i class="fa fa-cogs "></i>CAJA</a>
                        <ul class="nav" id="main-menu">
                            <li>
                                <a href="fees.php"><i class="fa fa-usd "></i>Pagos</a>
                            </li>
                            <li>
                                <a href="ingresos.php?action=buscar&tipo=1"><i class="fa fa-usd "></i>Ingresos</a>
                            </li>
                            <li>
                                <a href="egresos.php?action=buscar&tipo=1"><i class="fa fa-usd "></i>Egresos</a>
                            </li>
                             <li>
                                <a href="report.php"><i class="fa fa-file-text "></i>Reporte </a>
                            </li>
                            <li>
                                <a href="retiro_depositos.php?action=buscar&tipo=1"><i class="fa fa-usd "></i>Retiro Depositos</a>
                            </li>
                        </ul>
                    </li>
                    <li><a href="#"><i class="fa fa-cogs "></i>Administracion</a>
                        <ul class="nav" id="main-menu">
                            <li><a href="branch.php"><i class="fa fa-university "></i>Bancos</a></li>
                            <li><a href="carreras.php"><i class="fa fa-cogs "></i>Carreras</a></li>
                            <li><a href="setting.php"><i class="fa fa-cogs "></i>Cambio Contraseña</a></li>
                            <li><a href="php/bakupBD.php"><i class="fa fa-cogs "></i>Generar Bakup BD </a></li>
                        </ul>
                    </li>
					<li><a href="#"><i class="fa fa-cogs "></i>Activos Fijos</a>
                        <ul class="nav" id="main-menu">
                            <li><a href="activos.php?action=buscar&tipo=1"><i class="fa fa-file-text"></i>Registro de Activos</a></li>
                        </ul>
                    </li>
					 <li>
                        <a href="logout.php"><i class="fa fa-power-off "></i>Cerrar Sesión</a>
                    </li>
					
			
                </ul>

            </div>

        </nav>
        <!-- /. NAV SIDE  -->