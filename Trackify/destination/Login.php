<?php
$showAlert = false;
$showError = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'partials/_dbconnect.php';
    $username = $_POST["name"];
    $password = $_POST["password"];
    $email = $_POST["email"];
    $cpassword = $_POST["cpassword"];

    $existSql = "SELECT * FROM `trackify` WHERE `username` = '$username'";
    $result = mysqli_query($conn, $existSql);
    $numRows = mysqli_num_rows($result);

    if ($numRows > 0) {
        $showError = "User Already Exists";
    } else {
        if ($password == $cpassword) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO `trackify` ( `username`, `password`, `cpassword`, `email`, `dt`) VALUES ( '$username', '$password', '$cpassword', '$email', current_timestamp());";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $showAlert = true;
            }
        } else {
            $showError = "Passwords do not match";
        }
    }
}
?>



<?php
$login = false;
$showError = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {


  include 'partials/_dbconnect.php';
  $email = $_POST["email"];
  $password = $_POST["password"];


  $sql = "SELECT * FROM `trackify` WHERE `email` = '$email'";
  $result = mysqli_query($conn, $sql);
  $num = mysqli_num_rows($result);
  if ($num == 1) {
    while ($row = mysqli_fetch_assoc($result)) {
      if ($password=== $row['password']) {
        $login = true;
        session_start();
        $_SESSION['loggedin'] = true;
        $_SESSION['email'] = $email;
        $_SESSION['username'] = $row['username'];

        header("location:  Dashboard.php");
        exit();
      }else {
        $showError = "Invalid Credentials";
      }
    }
  } else {
    $showError = "Invalid Credentials";
  }
}
?>

















<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <link rel="stylesheet" href="./Login.css" />
    
    <script src="https://kit.fontawesome.com/66c63b4ed2.js"></script>
    <title>Login-Trackify</title>
    <link rel="shortcut icon" href="./favicon.svg" type="image/svg+xml">
  </head>
  <body>
    <!-- SignUp -->
    <div class="container" id="container">
      <div class="form-container sign-up-container">
        <form action="./Login.php" method="POST">
          <h1 id="heading">Create Account</h1>
          <hr />
          <div class="group-input">
            <i class="fa fa-lock"></i>
            <input type="text" placeholder="Name" name="name"/>
          </div>
          <div class="group-input">
            <i class="fa fa-lock"></i>
            <input type="email" placeholder="Email" name="email"/>
          </div>
          <div class="group-input">
            <i class="fa fa-lock"></i>
            <input type="password" placeholder="Password" name="password"/>
          </div>
          <div class="group-input">
            <i class="fa fa-lock"></i>
            <input type="password" placeholder="Confirm Password" name="cpassword"/>
          </div>
          <button type="submit"><a href="./Login.php">Sign up</a></button>
        </form>
      </div>

<!-- SignIn -->
      <div class="form-container sign-in-container">
        <form action="./Login.php" method="POST">
          <h1>Sign in</h1>
          <hr />
          <div class="group-input">
            <i class="fa fa-lock"></i>
            <input type="email" placeholder="Email" name="email"/>
          </div>

          <div class="group-input">
            <i class="fa fa-lock"></i>
            <input type="password" placeholder="Password" name="password"/>
          </div>
          <button>Sign In</button>
          <a href="#">Forgot your password?</a>
        </form>
      </div>

      <div class="side-element-container">
        <div class="side-element">
          <div class="side-element-panel side-element-left">
            <h1 >Hii..</h1>
            <p>Enter your personal details to get the best advices</p>
            <button class="ghost" id="signIn">Sign In</button>
          </div>
          <div class="side-element-panel side-element-right">
            <h1>Welcome!</h1>
            <p>
              After sign up you can login at any moment...
            </p>
            <button class="ghost" id="signUp"><a href="./Login.php">Sign Up</a></button>
          </div>
        </div>
      </div>
    </div>

    <footer>
      <p>
        Created with <i class="fa fa-heart"></i> by
        <a target="_blank" href="https://www.linkedin.com/in/abhinavt00001/">TEAM BITE WIZARDS</a>
      </p>
    </footer>
    <script src="./login.js"></script>
  </body>
</html>
