<?php

$host_name = "localhost";
$sql_name = "root";
$sql_pass = "";
$database = "learn_quest";


$conn = mysqli_connect($host_name, $sql_name, $sql_pass, $database);

// Check connection
if (!$conn) {
    die("Connection failed");
} else {
    //echo "Connected successfully";
}
