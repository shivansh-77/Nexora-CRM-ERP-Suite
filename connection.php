<?php

$hostname = "localhost";
$username = "root";
$password = "";
$database = "lead_management";

$connection = mysqli_connect($hostname , $username , $password , $database);

if($connection){
  //echo "Database Established !";
}
else{
  echo "Database not Established".mysqli_connect_error();
}





 ?>
