<?php
// Primero incluimos la base de datos antigua. Ella se encargará de ejecutar session_start() y ob_start()
include("php/dbconnect.php");
// Si por alguna razón la base de datos no inició la sesión, la iniciamos nosotros de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ================================================================
// URL BASE DE LA API MODULAR
// Dejá SOLO UNA línea activa (sin comentar) según el entorno.
// Al subir al hosting: comentar la línea LOCAL y descomentar HOSTING
// con el dominio real donde quede publicada la carpeta api_ceti.
// ================================================================
// ---- LOCAL (XAMPP) ----
define('API_BASE_URL', 'http://localhost/api_ceti/public');
// ---- HOSTING (descomentar y poner el dominio/ruta real) ----
// define('API_BASE_URL', 'https://tudominio.com/api_ceti/public');
// define('API_BASE_URL', 'https://tudominio.com/api'); // si la carpeta se sube con otro nombre
$error = '';
if(isset($_POST['login']))
{
    // Usamos la conexión antigua ($conn) que viene de dbconnect.php para desinfectar strings del formulario antiguo
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    if($username == '' || $password == '') {
        $error = 'Todos los campos son requeridos';
    } else {
        // ---- CONSUMO DE LA API MODULAR INTERNA ----
        $url = API_BASE_URL . '/auth/login';
        $data = array('username' => $username, 'password' => $password);
        $options = array(
            'http' => array(
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            )
        );
        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            // Si falla la conexión con la API (ej: allow_url_fopen deshabilitado en el hosting,
            // o la URL de API_BASE_URL está mal configurada), avisamos claro en vez de un error genérico.
            $error = 'No se pudo conectar con el servicio de autenticación. Verificar API_BASE_URL y que allow_url_fopen esté habilitado en el hosting.';
        } else {
            $response = json_decode($result, true);
            if ($response && $response['status'] === 'success') {
                $user = $response['user'];
                if ($user['role_id'] !== null) {
                    // Variables de sesión del nuevo sistema académico
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['role_name'] = $user['role_name'];
                    $_SESSION['sucursal']  = $user['sucursal'];
                    // Redirección por Rol
                    switch ($user['role_id']) {
                        case 1:
                            echo '<script type="text/javascript">window.location="academico/dashboard_admin.php";</script>';
                            exit();
                        case 2:
                            echo '<script type="text/javascript">window.location="academico/dashboard_secretaria.php";</script>';
                            exit();
                        case 3:
                            echo '<script type="text/javascript">window.location="academico/dashboard_docente.php";</script>';
                            exit();
                        default:
                            echo '<script type="text/javascript">window.location="academico/error_rol.php";</script>';
                            exit();
                    }
                } else {
                    // ---- LOGIN USUARIO ANTIGUO (Sin Rol) ----
                    $_SESSION['rainbow_username'] = $user['username'];
                    $_SESSION['rainbow_uid'] = $user['id'];
                    $_SESSION['rainbow_name'] = $user['name'];
                    $_SESSION['rainbow_sucursal'] = $user['sucursal'];
                    echo '<script type="text/javascript">window.location="index.php";</script>';
                    exit();
                }
            } else {
                $error = isset($response['message']) ? $response['message'] : 'Usuario o Contraseña inválidos';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistema de Pago Integrado</title>
    <link href="css/bootstrap.css" rel="stylesheet" />
    <link href="css/font-awesome.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <link rel="icon" type="image/png" href="img/logoIcono.ico" />
    <style>
        :root{
            --rojo-elegante:#8d191d;
            --rojo-hover:#701315;
        }
        html, body{ height:100%; }
        body{
            font-family:'Open Sans', sans-serif;
            background: linear-gradient(135deg, #f4f5f7 0%, #e9ebee 100%);
        }
        .myhead{ margin-top:0px; margin-bottom:0px; text-align:center; }

        .login-wrapper{
            min-height: 100vh;
            display:flex;
            align-items:center;
            padding: 30px 0;
        }

        .panel-login{
            background-color:#ffffff;
            border:none;
            border-top:5px solid var(--rojo-elegante);
            border-radius:10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            padding: 30px 30px 25px;
        }

        .panel-login img{
            display:block;
            width: 200px;
            height: auto;
            margin: 0 auto 10px;
            border-radius: 6px;
        }

        .panel-login hr{
            border-top: 1px solid #eceef1;
            margin: 18px 0;
        }

        .panel-login .form-group{ margin-bottom: 16px; }

        .panel-login .input-group-addon{
            background-color: #f8f9fa;
            border-color: #dfe3e8;
            color: var(--rojo-elegante);
        }

        .panel-login .form-control{
            border-color: #dfe3e8;
            box-shadow: none;
        }
        .panel-login .form-control:focus{
            border-color: var(--rojo-elegante);
            box-shadow: 0 0 0 3px rgba(141,25,29,0.12);
        }

        .btn-ingresar{
            background-color: var(--rojo-elegante);
            border-color: var(--rojo-elegante);
            color:#fff;
            width:100%;
            padding: 10px 0;
            font-weight:600;
            letter-spacing: 0.3px;
            border-radius:6px;
            transition: background-color .15s ease;
        }
        .btn-ingresar:hover,
        .btn-ingresar:focus{
            background-color: var(--rojo-hover);
            border-color: var(--rojo-hover);
            color:#fff;
        }

        .btn-consulta-notas{
            border-radius:6px;
            border-color:#dfe3e8;
            font-weight:500;
        }
        .btn-consulta-notas:hover{
            background-color:#f8f9fa;
            color: var(--rojo-elegante);
            border-color: var(--rojo-elegante);
        }

        @media (max-width: 480px){
            .panel-login{ padding: 22px 18px 18px; }
            .panel-login img{ width: 150px; }
        }
    </style>
</head>
<body>
    <div class="container login-wrapper">
        <div class="row" style="width:100%;">
            <div class="col-md-4 col-md-offset-4 col-sm-6 col-sm-offset-3 col-xs-10 col-xs-offset-1">
                <div class="panel-login">
                    <img src="img/LogoLoginCeti.jpeg" alt="Logo CEETII">
                    <form role="form" action="login.php" method="post">
                        <hr />
                        <?php
                        if($error != '') {
                            echo '<h5 class="text-danger text-center">'.$error.'</h5>';
                        }
                        ?>
                        <div class="form-group input-group">
                            <span class="input-group-addon"><i class="fa fa-tag"></i></span>
                            <input type="text" class="form-control" placeholder="Tu Usuario " name="username" required />
                        </div>
                        <div class="form-group input-group">
                            <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                            <input type="password" class="form-control" placeholder="Tu Contraseña " name="password" required />
                        </div>
                        <button class="btn btn-ingresar" type="submit" name="login">Ingresar</button>
                    </form>
                    <hr />
                    <div class="text-center">
                        <!-- Se agregó target="_blank" para abrir en una página aparte -->
                        <a href="academico/consulta_notas.php" target="_blank" class="btn btn-default btn-block btn-consulta-notas">
                            <i class="fa fa-graduation-cap"></i> Consultar mis Notas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>