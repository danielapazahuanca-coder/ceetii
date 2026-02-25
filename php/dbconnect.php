<?php
//error_reporting(0);
ob_start();
session_start();
$siteName = "Cipet.in";

//DEFINE("BASE_URL","http://cipetbhopal.com/");
//DEFINE("BASE_URL","http://localhost/");

DEFINE ('DB_USER', 'root');
DEFINE ('DB_PSWD', ''); 
DEFINE ('DB_HOST', 'localhost'); 
DEFINE ('DB_NAME', 'cetilp');

/*DEFINE ('DB_USER', 'ceetiico_alvaro');
DEFINE ('DB_PSWD', '63141303Daniel'); 
DEFINE ('DB_HOST', 'localhost'); 
DEFINE ('DB_NAME', 'ceetiico_cetilp');*/

date_default_timezone_set('America/La_Paz'); 
$conn =  new mysqli(DB_HOST,DB_USER,DB_PSWD,DB_NAME);
if($conn->connect_error)
die("Failed to connect database ".$conn->connect_error );

$conn->set_charset("utf8");