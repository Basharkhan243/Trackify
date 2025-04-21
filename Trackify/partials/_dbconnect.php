<?php
$username="root";
$password="";
$server="localhost";
$database="trackify_users";
$conn=mysqli_connect($server,$username,$password,$database);
if(!$conn){
    // echo "Successfully connected";
    die("Connection failed: " . mysqli_connect_error());
}


    