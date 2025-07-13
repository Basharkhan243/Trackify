<?php
session_start();
require './partials/_dbconnect.php';

if (!isset($_SESSION['loggedin'])) {
    header("location: /Trackify/Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch dashboard data with user_id filtering
$total_clients = $conn->query("SELECT COUNT(*) FROM clients WHERE user_id = $user_id")->fetch_row()[0];
$total_hours = $conn->query("SELECT SUM(total_hours_spent) FROM clients WHERE user_id = $user_id")->fetch_row()[0];
$total_revenue = $conn->query("SELECT SUM(total_amount_earned) FROM clients WHERE user_id = $user_id")->fetch_row()[0];
$active_projects = $conn->query("SELECT COUNT(*) FROM clients WHERE expected_completion >= CURDATE() AND user_id = $user_id")->fetch_row()[0];

// Recent time entries with user_id filtering
$recent_entries = $conn->query("
    SELECT c.client_name, t.hours, t.entry_date, t.description 
    FROM time_entries t
    JOIN clients c ON t.client_id = c.id
    WHERE t.user_id = $user_id
    ORDER BY t.entry_date DESC
    LIMIT 5
");

// Upcoming deadlines with user_id filtering
$deadlines = $conn->query("
    SELECT client_name, project_title, expected_completion 
    FROM clients 
    WHERE expected_completion >= CURDATE() AND user_id = $user_id
    ORDER BY expected_completion ASC
    LIMIT 5
");

// Data for charts with user_id filtering
$revenue_chart_data = $conn->query("
    SELECT client_name, total_amount_earned 
    FROM clients 
    WHERE user_id = $user_id
    ORDER BY total_amount_earned DESC 
    LIMIT 5
");

$hours_chart_data = $conn->query("
    SELECT client_name, total_hours_spent 
    FROM clients 
    WHERE user_id = $user_id
    ORDER BY total_hours_spent DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
                        sans: ['Kanit', 'sans-serif']
                    },
                    colors: {
                        primary: '#66bb60',
                        primaryHover: '#55aa50',
                        darkBg: '#131212',
                        darkGray: '#1e1e1e',
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
        
        /* Mobile styles */
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
                background-color: #1e1e1e;
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
                padding: 0.5rem;
                font-size: 0.7rem;
                text-align: center;
            }
            .sidebar-nav i {
                margin-right: 0;
                margin-bottom: 0.25rem;
                font-size: 1rem;
                display: block;
            }
            .sidebar-brand {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
                margin-bottom: 70px;
            }
            .dashboard-card {
                height: auto;
                min-height: 300px;
                max-height: 400px;
                padding: 1rem;
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
            .chart-container {
                height: 220px;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            .stat-card {
                padding: 0.75rem;
            }
            .stat-value {
                font-size: 1.25rem;
            }
            .stat-label {
                font-size: 0.7rem;
            }
        }
        
        /* Desktop styles */
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
            .main-content {
                margin-left: 16rem;
                padding: 2rem;
                max-width: 1800px;
                margin-right: auto;
            }
            .dashboard-card {
                height: 400px;
                max-height: 400px;
                padding: 1.5rem;
            }
            .chart-container {
                height: 300px;
            }
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
        }

        /* Common styles */
        .dashboard-card {
            background: rgba(65, 65, 65, 0.28);
            border-radius: 0.75rem;
            box-shadow: 2.2px -2.2px 20px #66bb60;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .dashboard-card:hover {
            box-shadow: 2.2px -2.2px 30px #66bb60;
        }
        .stat-card {
            background: rgba(65, 65, 65, 0.28);
            border-radius: 0.75rem;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #66bb60;
            margin-bottom: 0.25rem;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #a1a1aa;
        }
        .content-container {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body class="bg-[#131212] text-white font-kanit min-h-screen">
    <!-- Preloader -->
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
            <a href="./Tracking.php" class="flex items-center px-6 py-3 text-gray-300 hover:text-primary hover:bg-black/30 transition-colors">
                <i class="fas fa-clock mr-3"></i>
                TimeTracker
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
    <div id="mobileSidebar" class="mobile-sidebar sidebar hidden">
        <nav class="sidebar-nav">
            <a href="./Dashboard.php" class="flex flex-col items-center text-gray-300 hover:text-primary transition-colors">
                <i class="fas fa-columns"></i>
                <span>Dashboard</span>
            </a>
            <a href="./Tracking.php" class="flex flex-col items-center text-gray-300 hover:text-primary transition-colors">
                <i class="fas fa-clock"></i>
                <span>Tracker</span>
            </a>
            <a href="./Client.php" class="flex flex-col items-center text-gray-300 hover:text-primary transition-colors">
                <i class="fas fa-user"></i>
                <span>Clients</span>
            </a>
            <a href="./Invoice.php" class="flex flex-col items-center text-gray-300 hover:text-primary transition-colors">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Invoice</span>
            </a>
        </nav>
    </div>

    <main class="main-content flex-1">
        <!-- Header with logout -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl ml-16 mt-1 md:text-4xl md:ml-5 font-bold animate-fade-in">Dashboard</h1>
            <a href="./index.html" class="text-red-500 hover:text-red-400 transition-colors text-sm md:text-base">
                <i class="fas fa-sign-out-alt mr-1"></i> Log out
            </a>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid grid mb-6">
            <div class="stat-card">
                <div class="stat-value"><?= $total_clients ?></div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $total_hours ? $total_hours : '0' ?></div>
                <div class="stat-label">Hours Tracked</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?= $total_revenue ? $total_revenue : '0' ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $active_projects ?></div>
                <div class="stat-label">Active Projects</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            <!-- Project Overview Card -->
            <div class="dashboard-card animate-fade-in">
                <h2 class="text-lg md:text-xl font-semibold mb-4">Project Overview</h2>
                <div class="content-container">
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="dashboard-card animate-fade-in">
                <h2 class="text-lg md:text-xl font-semibold mb-4">Recent Activity</h2>
                <div class="content-container overflow-y-auto">
                    <?php while($entry = $recent_entries->fetch_assoc()): ?>
                    <div class="flex items-start border-b border-gray-700 pb-3 mb-3">
                        <div class="bg-primary/20 p-2 rounded-lg mr-3">
                            <i class="fas fa-clock text-primary text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium truncate"><?= htmlspecialchars($entry['client_name']) ?></p>
                            <p class="text-sm text-gray-300 truncate"><?= htmlspecialchars($entry['description']) ?></p>
                            <p class="text-xs text-primary mt-1">
                                <?= $entry['hours'] ?> hours • <?= date('M j', strtotime($entry['entry_date'])) ?>
                            </p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Time Analysis Card -->
            <div class="dashboard-card animate-fade-in">
                <h2 class="text-lg md:text-xl font-semibold mb-4">Time Analysis</h2>
                <div class="content-container">
                    <div class="chart-container">
                        <canvas id="hoursChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Upcoming Tasks Card -->
            <div class="dashboard-card animate-fade-in">
                <h2 class="text-lg md:text-xl font-semibold mb-4">Upcoming Tasks</h2>
                <div class="content-container overflow-y-auto">
                    <?php while($project = $deadlines->fetch_assoc()): ?>
                    <div class="flex items-start border-b border-gray-700 pb-3 mb-3">
                        <div class="bg-primary/20 p-2 rounded-lg mr-3">
                            <i class="fas fa-calendar-alt text-primary text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium truncate"><?= htmlspecialchars($project['project_title']) ?></p>
                            <p class="text-sm text-gray-300 truncate"><?= htmlspecialchars($project['client_name']) ?></p>
                            <p class="text-xs text-primary mt-1">
                                Due <?= date('M j, Y', strtotime($project['expected_completion'])) ?>
                            </p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Preloader
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.getElementById('preloader').style.opacity = '0';
                setTimeout(() => {
                    document.getElementById('preloader').style.display = 'none';
                }, 500);
            }, 800);
        });

        // Mobile menu toggle
        document.getElementById('mobileMenuButton').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.toggle('hidden');
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
                                '#66bb60',
                                '#4a9e46',
                                '#2e813a',
                                '#1a5e2e',
                                '#0d3b21'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: window.innerWidth < 768 ? 'bottom' : 'right',
                                labels: {
                                    color: '#e2e8f0',
                                    font: {
                                        family: 'Kanit, sans-serif'
                                    }
                                }
                            }
                        },
                        cutout: window.innerWidth < 768 ? '60%' : '65%'
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
                                display: false,
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

            // Animation for cards
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
            }, { threshold: 0.1 });

            animatedElements.forEach(el => observer.observe(el));
        });

        // Responsive adjustments on resize
        window.addEventListener('resize', function() {
            // You could add chart redraw logic here if needed
        });
    </script>
</body>
</html>