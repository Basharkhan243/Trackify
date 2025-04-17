<?php
// Your PHP code remains exactly the same
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
            $sql = "INSERT INTO `trackify` ( `username`, `password`, `cpassword`, `email`) VALUES ( '$username', '$password', '$cpassword', '$email');";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $showAlert = true;
                header("Location: /Trackify/Login.php");
                exit();
            }
        } else {
            $showError = "Passwords do not match";
        }
    }
}

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
            if ($password === $row['password']) {
                $login = true;
                session_start();
                $_SESSION['loggedin'] = true;
                $_SESSION['email'] = $email;
                $_SESSION['username'] = $row['username'];
                header("location:  /Trackify/Dashboard.php");
                exit();
            } else {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Login-Trackify</title>
    <link rel="shortcut icon" href="./favicon.svg" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/66c63b4ed2.js"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
        .right-panel-active #signInForm {
            transform: translateX(100%);
        }
        .right-panel-active #signUpForm {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
        }
        .right-panel-active #overlayCon {
            transform: translateX(-100%);
        }
        .right-panel-active #overlay {
            transform: translateX(50%);
        }
        .right-panel-active #overlayLeft {
            transform: translateX(0);
        }
        .right-panel-active #overlayRight {
            transform: translateX(20%);
        }
        
        /* Mobile-specific styles */
        @media (max-width: 767px) {
            #container {
                min-height: 100vh;
                border-radius: 0;
                box-shadow: none;
                background: #222;;
                margin-top:30%:
            }
            #signInForm, #signUpForm {
                width: 100%;
                height: auto;
                position: relative;
                padding: 20px 0;
                transform: none !important;
                opacity: 1 !important;
            }
            #overlayCon {
                display: none;
            }
            .mobile-hidden {
                display: none;
            }
            .mobile-visible {
                display: block;
            }
        }
    </style>
</head>
<body class="bg-[#000] font-kanit flex justify-center items-center flex-col min-h-screen m-0 p-0">
    <!-- Main Container -->
    <div id="container" class="bg-[#54aa4e] rounded-xl shadow-[0_14px_28px_rgba(0,0,0,0.25),0_10px_10px_rgba(0,0,0,0.22)] relative overflow-hidden w-full max-w-[768px] min-h-[480px] md:min-h-[480px]">
        <!-- Sign In Form -->
        <div id="signInForm" class="absolute top-0 left-0 w-full md:w-1/2 h-full z-[2] transition-all duration-300 ease-in-out bg-[#222] mobile-visible">
            <form action="./Login.php" method="POST" class="flex items-center justify-center flex-col p-[0_20px] md:p-[0_50px] h-full text-center text-white">
                <h1 class="font-bold text-2xl md:text-3xl p-[10px] m-0">Sign In</h1>
                
                <?php if($showError): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 animate-fade-in w-full max-w-xs">
                        <?php echo $showError; ?>
                    </div>
                <?php endif; ?>
                
                <div class="flex justify-center items-center w-full mb-4 max-w-xs">
                    <i class="fas fa-envelope bg-primary text-white p-3 rounded-l-lg"></i>
                    <input type="email" placeholder="Email" name="email" required 
                           class="bg-[#111] border border-[#333] text-white p-3 w-full rounded-r-lg focus:ring-2 focus:ring-primary text-sm md:text-base">
                </div>
    
                <div class="flex justify-center items-center w-full mb-6 max-w-xs">
                    <i class="fas fa-lock bg-primary text-white p-3 rounded-l-lg"></i>
                    <input type="password" placeholder="Password" name="password" required 
                           class="bg-[#111] border border-[#333] text-white p-3 w-full rounded-r-lg focus:ring-2 focus:ring-primary text-sm md:text-base">
                </div>
                
                <button type="submit" 
                        class="rounded-lg border border-primary bg-primary text-white font-bold py-2 px-6 md:py-3 md:px-8 uppercase tracking-wider transition-all duration-300 hover:bg-[#222] hover:scale-105 focus:outline-none text-sm md:text-base">
                    Sign In
                </button>
                
                <a href="#" class="text-gray-300 text-xs md:text-sm mt-4 hover:text-[#66bb60]">Forgot your password?</a>
            </form>
        </div>

        <!-- Sign Up Form -->
        <div id="signUpForm" class="absolute top-0 left-0 w-full md:w-1/2 h-full opacity-0 z-[1] transition-all duration-600 ease-in-out bg-[#131212] mobile-hidden">
            <form action="./Login.php" method="POST" class="flex items-center justify-center flex-col p-[0_20px] md:p-[0_50px] h-full text-center text-white">
                <h1 class="font-bold text-2xl md:text-3xl p-[12px]">Create Account</h1>
                
                <?php if($showError): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 animate-fade-in w-full max-w-xs">
                        <?php echo $showError; ?>
                    </div>
                <?php endif; ?>
                
                <div class="flex justify-center items-center w-full mb-4 max-w-xs">
                    <i class="fas fa-user bg-primary text-white p-3 rounded-l-lg"></i>
                    <input type="text" placeholder="Name" name="name" required 
                           class="bg-[#222] border border-[#333] text-white p-3 w-full rounded-r-lg focus:ring-2 focus:ring-primary text-sm md:text-base">
                </div>
                
                <div class="flex justify-center items-center w-full mb-4 max-w-xs">
                    <i class="fas fa-envelope bg-primary text-white p-3 rounded-l-lg"></i>
                    <input type="email" placeholder="Email" name="email" required 
                           class="bg-[#222] border border-[#333] text-white p-3 w-full rounded-r-lg focus:ring-2 focus:ring-primary text-sm md:text-base">
                </div>
                
                <div class="flex justify-center items-center w-full mb-4 max-w-xs">
                    <i class="fas fa-lock bg-primary text-white p-3 rounded-l-lg"></i>
                    <input type="password" placeholder="Password" name="password" required 
                           class="bg-[#222] border border-[#333] text-white p-3 w-full rounded-r-lg focus:ring-2 focus:ring-primary text-sm md:text-base">
                </div>
                
                <div class="flex justify-center items-center w-full mb-6 max-w-xs">
                    <i class="fas fa-lock bg-primary text-white p-3 rounded-l-lg"></i>
                    <input type="password" placeholder="Confirm Password" name="cpassword" required 
                           class="bg-[#222] border border-[#333] text-white p-3 w-full rounded-r-lg focus:ring-2 focus:ring-primary text-sm md:text-base">
                </div>
                
                <button type="submit" 
                        class="rounded-lg border border-primary bg-primary text-white font-bold py-2 px-6 md:py-3 md:px-8 uppercase tracking-wider transition-all duration-200 hover:bg-[#222] hover:scale-105 focus:outline-none text-sm md:text-base">
                    Sign Up
                </button>
            </form>
        </div>

        <!-- Overlay Container (Desktop only) -->
        <div id="overlayCon" class="hidden md:block absolute top-0 left-1/2 w-1/2 h-full overflow-hidden transition-transform duration-200 ease-in-out z-0">
            <div id="overlay" class="relative -left-full w-[200%] h-full bg-gradient-to-r from-primary to-[#316c1a] transition-transform duration-200 ease-in-out">
                <!-- Left Panel -->
                <div id="overlayLeft" class="absolute bg-green-700 left-0 w-1/2 h-full flex items-center justify-center flex-col p-[0_40px] text-center text-white transform translate-x-[-20%] transition-transform duration-600 ease-in-out">
                    <h1 class="font-bold text-2xl mb-4">Welcome Back!</h1>
                    <p class="text-sm mb-6">
                        To keep connected with us please login with your personal info
                    </p>
                    <button id="signIn" 
                            class="bg-transparent border-2 border-white rounded-full px-6 py-2 font-bold uppercase tracking-wider hover:scale-105 transition-all duration-400">
                        Sign In
                    </button>
                </div>

                <!-- Right Panel -->
                <div id="overlayRight" class="absolute right-0 w-1/2 h-full flex items-center justify-center flex-col p-[0_40px] text-center text-white transform translate-x-0 transition-transform duration-600 ease-in-out bg-green-700">
                    <h1 class="font-bold text-2xl mb-4">Hello, Friend!</h1>
                    <p class="text-sm mb-6">
                        Enter your personal details and start your journey with us
                    </p>
                    <button id="signUp" 
                            class="bg-transparent border-2 border-white rounded-full px-6 py-2 font-bold uppercase tracking-wider hover:scale-105 transition-all duration-400">
                        Sign Up
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Toggle Buttons -->
        <div class="md:hidden flex justify-center space-x-4 mt-4 mb-6 w-full px-4">
            <button id="mobileSignIn" class="bg-[#414141]  text-white px-4 py-2 rounded-full font-medium text-sm w-32 mobile-active">
                Sign In
            </button>
            <button id="mobileSignUp" class="bg-[#414141] text-white px-4 py-2 rounded-full font-medium text-sm w-32">
                Sign Up
            </button>
        </div>
    </div>

    <script>
        // Toggle between forms
        const signUpBtn = document.getElementById('signUp');
        const signInBtn = document.getElementById('signIn');
        const mobileSignUpBtn = document.getElementById('mobileSignUp');
        const mobileSignInBtn = document.getElementById('mobileSignIn');
        const container = document.getElementById('container');
        const signInForm = document.getElementById('signInForm');
        const signUpForm = document.getElementById('signUpForm');

        // Desktop version
        if (signUpBtn && signInBtn) {
            signUpBtn.addEventListener('click', () => {
                container.classList.add('right-panel-active');
            });

            signInBtn.addEventListener('click', () => {
                container.classList.remove('right-panel-active');
            });
        }

        // Mobile version
        mobileSignUpBtn.addEventListener('click', () => {
            signInForm.classList.add('mobile-hidden');
            signInForm.classList.remove('mobile-visible');
            signUpForm.classList.remove('mobile-hidden');
            signUpForm.classList.add('mobile-visible');
            mobileSignInBtn.classList.remove('mobile-active');
            mobileSignUpBtn.classList.add('mobile-active');
        });

        mobileSignInBtn.addEventListener('click', () => {
            signUpForm.classList.add('mobile-hidden');
            signUpForm.classList.remove('mobile-visible');
            signInForm.classList.remove('mobile-hidden');
            signInForm.classList.add('mobile-visible');
            mobileSignUpBtn.classList.remove('mobile-active');
            mobileSignInBtn.classList.add('mobile-active');
        });

        // Add active class to mobile buttons
        function toggleMobileActive() {
            mobileSignInBtn.classList.toggle('mobile-active');
            mobileSignUpBtn.classList.toggle('mobile-active');
        }

        // Set initial state for mobile
        if (window.innerWidth < 768) {
            mobileSignInBtn.classList.add('mobile-active');
            mobileSignUpBtn.classList.remove('mobile-active');
        }

        // Style for active mobile button
        const style = document.createElement('style');
        style.innerHTML = `
            .mobile-active {
                background-color: #316c1a !important;
                transform: scale(1.05);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>