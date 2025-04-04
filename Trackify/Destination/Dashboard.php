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
    <style>
        *{
            padding:0px;
            margin:0px;
            box-sizing:border-box;
        }
        body{
            height:90vh;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="back">
                <a href="./landing.html" target="_blank"><i class="fa-solid fa-arrow-left"></i></a>
        </div>

        <div class="sidebar">
            <div class="logo">Time<span>ify</span></div>

            <a href="./Dashboard.html"><i class="fa fa-columns"></i>&nbspDashboard</a>
            <a href="./Tracking.html"><i class="fas fa-clock"></i>&nbspTime Tracker</a>
            <a href="#"><i class="fa fa-chart-line"></i>&nbspReport</a>
            <a href="#"><i class="fas fa-user"></i>&nbspClients</a>
            <a href="#"><i class="fas fa-file-invoice-dollar"></i>&nbspInvoice</a>
        </div>

         <div class="heading_dashboard">
            <h1>Dashboard</h1>
         </div>
        <div class="content_dashboard">
            <div class="container1">
                <div class="box_1">hi</div>
                <div class="box_2">hi</div>
            </div>
            <div class="container1">
                <div class="box_3">hello</div>
                <div class="box_4">hello</div>
            </div>
            

            

        </div>
    </div>
</body>
</html>
