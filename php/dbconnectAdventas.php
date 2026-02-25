<?php
//error_reporting(0);
/*ob_start();
session_start();*/
$siteName = "Cipet.in";

//DEFINE("BASE_URL","http://cipetbhopal.com/");
//DEFINE("BASE_URL","http://localhost/");

DEFINE ('DB_USER2', 'ceetiico_alvaro');
DEFINE ('DB_PSWD2', '63141303Daniel'); 
DEFINE ('DB_HOST2', 'localhost'); 
DEFINE ('DB_NAME2', 'ceetiico_adventaslp'); 

date_default_timezone_set('America/La_Paz'); 
$connAdventas =  new mysqli(DB_HOST,DB_USER2,DB_PSWD2,DB_NAME2);
if($connAdventas->connect_error)
die("Failed to connect database ".$connAdventas->connect_error );
$conn->set_charset("utf8");