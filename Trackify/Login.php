<?php
session_start();

$showAlert = false;
$showError = false;
$errorMessage = '';

// Database connection
include 'partials/_dbconnect.php';

// Handle Signup
if (isset($_POST['signup'])) {
    $username = trim($_POST["name"]);
    $password = trim($_POST["password"]);
    $email = trim($_POST["email"]);
    $cpassword = trim($_POST["cpassword"]);
    
    // Validate inputs
    if (empty($username) || empty($password) || empty($email) || empty($cpassword)) {
        $showError = true;
        $errorMessage = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $showError = true;
        $errorMessage = "Invalid email format";
    } elseif ($password !== $cpassword) {
        $showError = true;
        $errorMessage = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $showError = true;
        $errorMessage = "Password must be at least 8 characters";
    } else {
        // Check if username or email already exists
        $existSql = "SELECT * FROM `trackify` WHERE `username` = ? OR `email` = ?";
        $stmt = mysqli_prepare($conn, $existSql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $numRows = mysqli_num_rows($result);
        
        if ($numRows > 0) {
            $showError = true;
            $errorMessage = "Username or Email already exists";
        } else {
            // Hash the password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO `trackify` (`username`, `password`, `email`) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $username, $hash, $email);
            $result = mysqli_stmt_execute($stmt);
            
            if ($result) {
                $_SESSION['signup_success'] = true;
                header("Location: Login.php");
                exit();
            } else {
                $showError = true;
                $errorMessage = "Something went wrong. Please try again later.";
            }
        }
    }
}

// Handle Login
if (isset($_POST['login'])) {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    
    if (empty($email) || empty($password)) {
        $showError = true;
        $errorMessage = "Both email and password are required";
    } else {
        $sql = "SELECT * FROM `trackify` WHERE `email` = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);
        
        if ($num == 1) {
            $row = mysqli_fetch_assoc($result);
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['loggedin'] = true;
                $_SESSION['email'] = $email;
                $_SESSION['username'] = $row['username'];
                 $_SESSION['user_id'] = $row['id'];
                header("location: Dashboard.php");
                exit();
            } else {
                $showError = true;
                $errorMessage = "Invalid credentials";
            }
        } else {
            $showError = true;
            $errorMessage = "Invalid credentials";
        }
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
                min-height: 32vh;
                border-radius: 2;
                box-shadow: none;
                background: #222;
                margin-top: 25%;
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
            .mobile-active {
                background-color: #316c1a !important;
                transform: scale(1.05);
            }
        }
    </style>
</head>
<body class="bg-[#000] font-kanit flex justify-center items-center flex-col h-full md:min-h-screen m-0 p-0">
    <!-- Main Container -->
    <div id="container" class="bg-[#54aa4e] rounded-xl shadow-[0_14px_28px_rgba(0,0,0,0.25),0_10px_10px_rgba(0,0,0,0.22)] relative overflow-hidden w-full max-w-[380px] md:max-w-[768px] h-200 border md:min-h-[480px]">
        <!-- Sign In Form -->
        <div id="signInForm" class="absolute top-0 left-0 right-2 w-full md:w-1/2 h-10 md:h-full z-[2] transition-all duration-300 ease-in-out bg-[#222] mobile-visible">
            <form action="Login.php" method="POST" class="flex items-center justify-center flex-col p-[0_20px] md:p-[0_50px] h-full text-center text-white">
                <h1 class="font-bold text-2xl md:text-3xl p-[10px] m-0">Sign In</h1>
                
                <?php if(isset($_SESSION['signup_success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 animate-fade-in w-full max-w-xs">
                        Signup successful! Please login.
                    </div>
                    <?php unset($_SESSION['signup_success']); ?>
                <?php endif; ?>
                
                <?php if($showError && isset($_POST['login'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 animate-fade-in w-full max-w-xs">
                        <?php echo $errorMessage; ?>
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
                
                <button type="submit" name="login"
                        class="rounded-lg border border-primary bg-primary text-white font-bold py-2 px-6 md:py-3 md:px-8 uppercase tracking-wider transition-all duration-300 hover:bg-[#222] hover:scale-105 focus:outline-none text-sm md:text-base">
                    Sign In
                </button>
                
                <a href="#" class="text-gray-300 text-xs md:text-sm mt-4 hover:text-[#66bb60]">Forgot your password?</a>
            </form>
        </div>

        <!-- Sign Up Form -->
        <div id="signUpForm" class="absolute top-0 left-0 w-full md:w-1/2 h-full opacity-0 z-[1] transition-all duration-600 ease-in-out bg-[#131212] mobile-hidden">
            <form action="Login.php" method="POST" class="flex items-center justify-center flex-col p-[0_20px] md:p-[0_50px] h-full text-center text-white">
                <h1 class="font-bold text-2xl md:text-3xl p-[12px]">Create Account</h1>
                
                <?php if($showError && isset($_POST['signup'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 animate-fade-in w-full max-w-xs">
                        <?php echo $errorMessage; ?>
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
                    <input type="password" placeholder="Password (min 8 chars)" name="password" required minlength="8"
                           class="bg-[#222] border border-[#333] text-white p-3 w-full rounded-r-lg focus:ring-2 focus:ring-primary text-sm md:text-base">
                </div>
                
                <div class="flex justify-center items-center w-full mb-6 max-w-xs">
                    <i class="fas fa-lock bg-primary text-white p-3 rounded-l-lg"></i>
                    <input type="password" placeholder="Confirm Password" name="cpassword" required minlength="8"
                           class="bg-[#222] border border-[#333] text-white p-3 w-full rounded-r-lg focus:ring-2 focus:ring-primary text-sm md:text-base">
                </div>
                
                <button type="submit" name="signup"
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
        <div class="md:hidden flex justify-center space-x-4 mt-4 mb-6 w-full px-4 ">
            <button id="mobileSignIn" class="bg-[#414141] text-white px-4 py-2 rounded-full font-medium text-sm w-32 mobile-active">
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

        // Set initial state for mobile
        if (window.innerWidth < 768) {
            mobileSignInBtn.classList.add('mobile-active');
            mobileSignUpBtn.classList.remove('mobile-active');
        }
    </script>
</body>
</html>