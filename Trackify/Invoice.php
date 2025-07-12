<?php
session_start();
require_once 'partials/_dbconnect.php';

if (!isset($_SESSION['loggedin'])) {
    header("location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get invoice parameters with validation
$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Initialize client data with defaults
$client = [
    'client_name' => '',
    'client_currency' => 'USD',
    'hourly_rate' => 0,
    'project_title' => ''
];

$time_entries = [];
$total_hours = 0;
$total_amount = 0;

if ($client_id) {
    // Get client details with user verification
    $sql = "SELECT client_name, client_currency, hourly_rate, project_title 
            FROM clients 
            WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $client_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result)) {
        $client = array_merge($client, mysqli_fetch_assoc($result));
    }

    // Get time entries for the period with user verification
    $sql = "SELECT t.entry_date, t.description, t.hours, t.amount_earned 
            FROM time_entries t
            JOIN clients c ON t.client_id = c.id
            WHERE t.client_id = ? 
            AND t.user_id = ?
            AND t.entry_date BETWEEN ? AND ?
            ORDER BY t.entry_date";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiss", $client_id, $user_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result) {
        $time_entries = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // Calculate totals
    foreach ($time_entries as $entry) {
        $total_hours += (float)$entry['hours'];
        $total_amount += (float)$entry['amount_earned'];
    }
}

// Generate invoice number
$invoice_number = date('Ymd') . '-' . $client_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= $invoice_number ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body {
                background-color: white !important;
                color: black !important;
            }
            .print-hide {
                display: none !important;
            }
            .invoice-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            table {
                page-break-inside: avoid;
            }
        }
        
        /* Mobile-first styles */
        .invoice-container {
            background-color: #222;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1rem auto;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .invoice-header {
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .invoice-header > div {
            width: 100%;
        }
        
        .client-info {
            margin-bottom: 1.5rem;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        
        .invoice-table th, 
        .invoice-table td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid #333;
            word-break: break-word;
        }
        
        .invoice-table th {
            background-color: #111;
            font-weight: 500;
        }
        
        .invoice-summary {
            width: 100%;
            margin-bottom: 1.5rem;
        }
        
        /* Tablet styles */
        @media (min-width: 640px) {
            .invoice-container {
                padding: 1.5rem;
            }
            
            .invoice-header {
                flex-direction: row;
                gap: 2rem;
            }
            
            .invoice-header > div {
                width: 50%;
            }
            
            .invoice-summary {
                width: 50%;
                margin-left: auto;
            }
        }
        
        /* Desktop styles */
        @media (min-width: 768px) {
            .invoice-container {
                padding: 2rem;
                max-width: 800px;
            }
            
            .invoice-table th, 
            .invoice-table td {
                padding: 0.75rem 1rem;
            }
        }
        
        /* Dark theme colors */
        body {
            background-color: #111;
            color: white;
        }
        
        .text-primary {
            color: #66bb60;
        }
        
        .bg-primary {
            background-color: #66bb60;
        }
        
        .hover\:bg-primary:hover {
            background-color: #55aa50;
        }
        
        .btn {
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Back button (hidden when printing) -->
    <a href="./Dashboard.php" class="print-hide text-white hover:text-primary text-lg mx-4 my-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back 
    </a>

    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header flex">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-primary">INVOICE</h1>
                <p class="text-gray-400">#<?= $invoice_number ?></p>
                <p class="text-gray-400">Date: <?= date('F j, Y') ?></p>
            </div>
            <div class="text-right md:text-left">
                <h2 class="text-xl font-semibold">Trackify</h2>
                <p class="text-gray-400">Trackify Business</p>
                <p class="text-gray-400">Bangalore, India</p>
                <p class="text-gray-400">contact@trackify.com</p>
            </div>
        </div>

        <!-- Client Information -->
        <div class="client-info">
            <p class="font-medium">Bill To: <?= htmlspecialchars($client['client_name']) ?></p>
            <?php if (!empty($client['project_title'])): ?>
                <p class="text-gray-400">Project: <?= htmlspecialchars($client['project_title']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Invoice Period -->
        <div class="mb-4">
            <p class="font-medium">Invoice Period: <?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?></p>
        </div>

        <!-- Time Entries Table -->
        <div class="overflow-x-auto">
            <?php if (!empty($time_entries)): ?>
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th class="text-left">Date</th>
                        <th class="text-left">Description</th>
                        <th class="text-right">Hours</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_entries as $entry): ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($entry['entry_date'])) ?></td>
                        <td><?= htmlspecialchars($entry['description']) ?></td>
                        <td class="text-right"><?= number_format($entry['hours'], 2) ?></td>
                        <td class="text-right"><?= htmlspecialchars($client['client_currency']) ?> <?= number_format($client['hourly_rate'], 2) ?></td>
                        <td class="text-right"><?= htmlspecialchars($client['client_currency']) ?> <?= number_format($entry['amount_earned'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
                <p>No time entries found for this period.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Invoice Summary -->
        <div class="invoice-summary">
            <div class="flex justify-between py-2 border-b border-gray-700">
                <span class="font-medium">Subtotal:</span>
                <span><?= htmlspecialchars($client['client_currency']) ?> <?= number_format($total_amount, 2) ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-700">
                <span class="font-medium">Tax (0%):</span>
                <span><?= htmlspecialchars($client['client_currency']) ?> 0.00</span>
            </div>
            <div class="flex justify-between py-2 font-bold text-lg">
                <span>Total:</span>
                <span><?= htmlspecialchars($client['client_currency']) ?> <?= number_format($total_amount, 2) ?></span>
            </div>
        </div>

        <!-- Payment Instructions -->
        <div class="mt-6 pt-4 border-t border-gray-700">
            <h3 class="font-semibold mb-2">Payment Instructions:</h3>
            <p>Please make payment to:</p>
            <p>Bank Name: Example Bank</p>
            <p>Account Number: 123456789</p>
            <p>Account Name: Trackify LLC</p>
            <p class="mt-2">Due Date: <?= date('F j, Y', strtotime('+15 days')) ?></p>
        </div>

        <!-- Print Button (hidden when printing) -->
        <div class="mt-6 text-center print-hide">
            <button onclick="window.print()" class="btn bg-primary text-white px-6 py-2 rounded-md">
                Print Invoice
            </button>
        </div>
    </div>
</body>
</html>