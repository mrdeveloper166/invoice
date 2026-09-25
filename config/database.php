<?php

$host = "localhost";
$user = "";
$password = "";
$database = "";
// $host = "localhost";
// $user = "root";
// $password = "";
// $database = "invoice";

$conn = mysqli_connect($host,$user,$password,$database);

if(!$conn){
    die("Database Connection Failed");
}

?>
