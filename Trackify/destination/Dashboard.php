<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
  header("location: /Trackify/destination/landing.html");

  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="wrapper">


    <div class="back">
            <a href="./landing.html"><i class="fa-solid fa-arrow-left"></i></a>
    </div>
        

        <div class="sidebar">
            <div class="logo">Time<span>ify</span></div>
            <a href="#">Dashboard</a>
            <a href="#">Time Tracker</a>
            <a href="#">Report</a>
            <a href="#">Clients</a>
            <a href="#">Invoice</a>
        </div>
        <div class="content">
             <div style="width: 400px; height: 400px; border: 2px black solid; display: flex; align-items: center; justify-content: center;">
            <?php  Echo "Welcome to Our Website ".$_SESSION['username']?>
        </div>
        </div>
    </div>
</body>
</html>
