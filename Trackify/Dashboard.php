<?php
session_start();
require './partials/_dbconnect.php';

if (!isset($_SESSION['loggedin'])) {
    header("location: /Trackify/Login.php");
    exit();
}

// Fetch dashboard data
$total_clients = $conn->query("SELECT COUNT(*) FROM clients")->fetch_row()[0];
$total_hours = $conn->query("SELECT SUM(total_hours_spent) FROM clients")->fetch_row()[0];
$total_revenue = $conn->query("SELECT SUM(total_amount_earned) FROM clients")->fetch_row()[0];
$active_projects = $conn->query("SELECT COUNT(*) FROM clients WHERE expected_completion >= CURDATE()")->fetch_row()[0];

// Recent time entries
$recent_entries = $conn->query("
    SELECT c.client_name, t.hours, t.entry_date, t.description 
    FROM time_entries t
    JOIN clients c ON t.client_id = c.id
    ORDER BY t.entry_date DESC
    LIMIT 5
");

// Upcoming deadlines
$deadlines = $conn->query("
    SELECT client_name, project_title, expected_completion 
    FROM clients 
    WHERE expected_completion >= CURDATE()
    ORDER BY expected_completion ASC
    LIMIT 5
");

// Data for charts
$revenue_chart_data = $conn->query("SELECT client_name, total_amount_earned FROM clients ORDER BY total_amount_earned DESC LIMIT 5");
$hours_chart_data = $conn->query("SELECT client_name, total_hours_spent FROM clients ORDER BY total_hours_spent DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Your EXACT original head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        kanit: ['Kanit', 'sans-serif'],
                    },
                    colors: {
                        primary: '#66bb60',
                        primaryHover: '#55aa50',
                        darkBg: '#131212',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-out forwards',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: 0, transform: 'translateY(20px)' },
                            '100%': { opacity: 1, transform: 'translateY(0)' }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #131212;
        }
        ::-webkit-scrollbar-thumb {
            background: #66bb60;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: '#55aa50';
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: fixed;
                bottom: 0;
                left: 0;
                z-index: 50;
                display: flex;
                flex-direction: row;
                justify-content: space-around;
                padding: 0.5rem 0;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.5);
            }
            .sidebar-nav {
                display: flex;
                flex-direction: row;
                width: 100%;
            }
            .sidebar-nav a {
                flex: 1;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 0.75rem;
                font-size: 0.75rem;
                text-align: center;
            }
            .sidebar-nav i {
                margin-right: 0;
                margin-bottom: 0.25rem;
                font-size: 1rem;
            }
            .sidebar-brand {
                display: none;
            }
            .main-content {
                margin-left: 0;
                margin-top:15%;
                padding: 1rem;
                margin-bottom: 70px;
            }
            .dashboard-card {
                height: auto;
                min-height: 200px;
            }
            .mobile-menu-button {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 60;
                background: #66bb60;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }
            .desktop-sidebar {
                display: none;
            }
            .mobile-sidebar {
                display: flex;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-menu-button {
                display: none;
            }
            .desktop-sidebar {
                display: block;
            }
            .mobile-sidebar {
                display: none;
            }
        }

        /* Custom chart container styling */
        .chart-container {
            position: relative;
            height: calc(100% - 40px);
            width: 100%;
        }
    </style>
</head>
<body class="bg-[#131212] text-white font-kanit min-h-screen">
    <!-- Your original preloader -->
    <div id="preloader" class="fixed inset-0 bg-darkBg flex items-center justify-center z-[9999] transition-opacity duration-500">
        <div class="w-16 h-16 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Mobile menu button -->
    <button id="mobileMenuButton" class="mobile-menu-button md:hidden">
        <i class="fas fa-bars"></i>
    </button>

      <!-- Desktop Sidebar -->
      <div class="desktop-sidebar w-64 bg-darkBg fixed h-full z-10 shadow-lg">
            <div class="p-6 text-3xl font-semibold">
                Track<span class="text-primary">ify</span>
            </div>
            <nav class="mt-8">
                <a href="./Dashboard.php" class="flex items-center px-6 py-3 text-gray-300 hover:text-primary hover:bg-black/30 transition-colors">
                    <i class="fas fa-columns mr-3"></i>
                    Dashboard
                </a>
                <a href="./Tracking.php" class="flex items-center px-6 py-3 text-gray-300 hover:text-primary hover:bg-black/30 transition-colors" onclick="window.location.href='./Tracking.php'">
                    <i class="fas fa-clock mr-3"></i>
                    Time Tracker
                </a>
                <a href="./Client.php" class="flex items-center px-6 py-3 text-gray-300 hover:text-primary hover:bg-black/30 transition-colors">
                    <i class="fas fa-user mr-3"></i>
                    Clients
                </a>
                <a href="./Invoice.php" class="flex items-center px-6 py-3 text-gray-300 hover:text-primary hover:bg-black/30 transition-colors">
                    <i class="fas fa-file-invoice-dollar mr-3"></i>
                    Invoice
                </a>
            </nav>
        </div>

        <!-- Mobile Sidebar -->
        <div id="mobileSidebar" class="mobile-sidebar sidebar bg-darkBg">
            <nav class="sidebar-nav">
                <a href="./Dashboard.php" class="flex items-center text-gray-300 hover:text-primary transition-colors">
                    <i class="fas fa-columns"></i>
                    <span>Dashboard</span>
                </a>
                <a href="./Tracking.php" class="flex items-center text-gray-300 hover:text-primary transition-colors" onclick="window.location.href='./Tracking.php'">
                    <i class="fas fa-clock"></i>
                    <span>Tracker</span>
                </a>
                <a href="./Client.php" class="flex items-center text-gray-300 hover:text-primary transition-colors">
                    <i class="fas fa-user"></i>
                    <span>Clients</span>
                </a>
                <a href="./Invoice.php" class="flex items-center text-gray-300 hover:text-primary transition-colors">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Invoice</span>
                </a>
            </nav>
        </div>


        <div class="main-content flex-1 ml-0 md:ml-64 p-4 md:p-8">
            <!-- Back button -->
            <div class="mb-6">
                <a href="./landing.html" target="_blank" class="text-lg text-white hover:text-primary hover transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Landing page
                </a>
            </div>

            <h1 class="text-2xl md:text-4xl font-bold mb-6 animate-fade-in">Dashboard</h1>

            <!-- Project Overview Card -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8 mb-6">
                <div class="dashboard-card bg-[#41414148] rounded-xl p-4 h-80 md:p-6 shadow-[2.2px_-2.2px_20px_#66bb60] hover:scale-[1.02] transition-all duration-300 animate-fade-in">
                    <h2 class="text-lg md:text-xl font-semibold mb-4">Project Overview</h2>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activity Card -->
                <div class="dashboard-card bg-[#41414148] rounded-xl p-4 h-80 md:p-6 shadow-[2.2px_-2.2px_20px_#66bb60] hover:scale-[1.02] transition-all duration-300 animate-fade-in">
                    <h2 class="text-lg md:text-xl font-semibold mb-4">Recent Activity</h2>
                    <div class="overflow-y-auto h-56">
                        <?php while($entry = $recent_entries->fetch_assoc()): ?>
                        <div class="flex items-start border-b border-gray-700 pb-3 mb-3">
                            <div class="bg-primary/20 p-2 rounded-lg mr-3">
                                <i class="fas fa-clock text-primary text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($entry['client_name']) ?></p>
                                <p class="text-sm text-gray-300"><?= htmlspecialchars($entry['description']) ?></p>
                                <p class="text-xs text-primary mt-1">
                                    <?= $entry['hours'] ?> hours • <?= date('M j', strtotime($entry['entry_date'])) ?>
                                </p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Second Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                <!-- Time Analysis Card -->
                <div class="dashboard-card bg-[#41414148] rounded-xl h-80 p-4 md:p-6 shadow-[2.2px_-2.2px_20px_#66bb60] hover:scale-[1.02] transition-all duration-300 animate-fade-in">
                    <h2 class="text-lg md:text-xl font-semibold mb-4">Time Analysis</h2>
                    <div class="chart-container">
                        <canvas id="hoursChart"></canvas>
                    </div>
                </div>

                <!-- Upcoming Tasks Card -->
                <div class="dashboard-card bg-[#41414148] rounded-xl h-80 p-4 md:p-6 shadow-[2.2px_-2.2px_20px_#66bb60] hover:scale-[1.02] transition-all duration-300 animate-fade-in">
                    <h2 class="text-lg md:text-xl font-semibold mb-4">Upcoming Tasks</h2>
                    <div class="overflow-y-auto h-56">
                        <?php while($project = $deadlines->fetch_assoc()): ?>
                        <div class="flex items-start border-b border-gray-700 pb-3 mb-3">
                            <div class="bg-primary/20 p-2 rounded-lg mr-3">
                                <i class="fas fa-calendar-alt text-primary text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($project['project_title']) ?></p>
                                <p class="text-sm text-gray-300"><?= htmlspecialchars($project['client_name']) ?></p>
                                <p class="text-xs text-primary mt-1">
                                    Due <?= date('M j, Y', strtotime($project['expected_completion'])) ?>
                                </p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Your original preloader and mobile menu code
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            setTimeout(() => {
                preloader.style.transition = 'opacity 0.3s ease-out, visibility 0.3s';
                preloader.style.opacity = '0';
                preloader.style.visibility = 'hidden';
                setTimeout(() => {
                    document.body.style.overflow = 'auto';
                }, 500);
            }, 800);
        });

        // Initialize charts after DOM loads
        document.addEventListener('DOMContentLoaded', function() {
            // Revenue Chart (Doughnut)
            new Chart(
                document.getElementById('revenueChart'),
                {
                    type: 'doughnut',
                    data: {
                        labels: [
                            <?php 
                            $revenue_chart_data->data_seek(0);
                            while($row = $revenue_chart_data->fetch_assoc()) {
                                echo "'".addslashes($row['client_name'])."',";
                            }
                            ?>
                        ],
                        datasets: [{
                            data: [
                                <?php 
                                $revenue_chart_data->data_seek(0);
                                while($row = $revenue_chart_data->fetch_assoc()) {
                                    echo $row['total_amount_earned'].",";
                                }
                                ?>
                            ],
                            backgroundColor: [
                                '#dc2626',
                                '#2563eb',
                                '#f87171',
                                '#3a7d36',
                                '#2a5c26'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: '#e2e8f0',
                                    font: {
                                        family: 'Kanit, sans-serif'
                                    }
                                }
                            }
                        },
                        cutout: '65%'
                    }
                }
            );

            // Hours Chart (Bar)
            new Chart(
                document.getElementById('hoursChart'),
                {
                    type: 'bar',
                    data: {
                        labels: [
                            <?php 
                            $hours_chart_data->data_seek(0);
                            while($row = $hours_chart_data->fetch_assoc()) {
                                echo "'".addslashes($row['client_name'])."',";
                            }
                            ?>
                        ],
                        datasets: [{
                            label: 'Hours Worked',
                            data: [
                                <?php 
                                $hours_chart_data->data_seek(0);
                                while($row = $hours_chart_data->fetch_assoc()) {
                                    echo $row['total_hours_spent'].",";
                                }
                                ?>
                            ],
                            backgroundColor: '#66bb60',
                            borderColor: '#55aa50',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#e2e8f0'
                                },
                                grid: {
                                    color: '#2d374850'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#e2e8f0'
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#e2e8f0',
                                    font: {
                                        family: 'Kanit, sans-serif'
                                    }
                                }
                            }
                        }
                    }
                }
            );

            // Your original animation code
            const animatedElements = document.querySelectorAll('.animate-fade-in');
            animatedElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
            });

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

            animatedElements.forEach(el => observer.observe(el));

            if (window.innerWidth < 768) {
                document.getElementById('mobileSidebar').style.transform = 'translateY(100%)';
            }
        });

        // Your original scroll and resize handlers
        window.addEventListener('scroll', function() {
            // ... (keep your original scroll to top button code)
        });

        window.addEventListener('resize', function() {
            // ... (keep your original resize handler code)
        });
    </script>
</body>
</html>