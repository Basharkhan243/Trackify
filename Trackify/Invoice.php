<?php
require_once 'partials/_dbconnect.php';

// Get invoice parameters with validation
$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Initialize client data with defaults
$client = [
    'client_name' => '',
    'client_currency' => 'USD', // Default currency
    'hourly_rate' => 0,
    'project_title' => ''
];

$time_entries = [];
$total_hours = 0;
$total_amount = 0;

if ($client_id) {
    // Get client details with error handling
    $sql = "SELECT client_name, client_currency, hourly_rate, project_title FROM clients WHERE id = $client_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result)) {
        $client = array_merge($client, mysqli_fetch_assoc($result)); // Merge with defaults
    }

    // Get time entries for the period
    $sql = "SELECT entry_date, description, hours, amount_earned FROM time_entries 
            WHERE client_id = $client_id 
            AND entry_date BETWEEN '$start_date' AND '$end_date'
            ORDER BY entry_date";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $time_entries = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // Calculate totals
    foreach ($time_entries as $entry) {
        $total_hours += (float)$entry['hours'];
        $total_amount += (float)$entry['amount_earned'];
    }
}

// Generate invoice number (YYYYMMDD-clientid)
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
</head>
<body class="bg-[#111] min-h-screen">
<a href="./Dashboard.php" class="text-white hover:text-green-700  text-lg mx-8 my-4 inline-block mb-4">
                <i class="fas fa-arrow-left mr-1"></i> Back 
</a>
    <div class="max-w-3xl mx-auto p-8 bg-[#222] shadow-lg my-8">
        <!-- Invoice Header -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">INVOICE</h1>
                <p class="text-gray-600 text-white">#<?= $invoice_number ?></p>
                <p class="text-gray-600 text-white">Date: <?= date('F j, Y') ?></p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-semibold text-white">Trackify</h2>
                <p class="text-gray-600 text-white">Trackify_Business</p>
                <p class="text-gray-600 text-white">Bangalore, India</p>
                <p class="text-gray-600 text-white">contact@trackify.com</p>
            </div>
        </div>

        <!-- Client Information -->
        <div class="mb-8">
            
            <p class="font-medium text-white">Bill To: <?= htmlspecialchars($client['client_name']) ?></p>
            <?php if (!empty($client['project_title'])): ?>
                <p  class="text-white">Project: <?= htmlspecialchars($client['project_title']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Invoice Period -->
        <div class="mb-4 text-white">
            <p class="font-medium">Invoice Period: <?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?></p>
        </div>

        <!-- Time Entries Table -->
        <div class="mb-8 ">
            <?php if (!empty($time_entries)): ?>
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-2 px-4 text-white">Date</th>
                        <th class="text-left py-2 px-4 text-white">Description</th>
                        <th class="text-right py-2 px-4 text-white">Hours</th>
                        <th class="text-right py-2 px-4 text-white">Rate</th>
                        <th class="text-right py-2 px-4 text-white">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_entries as $entry): ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-4 text-white"><?= date('M j, Y', strtotime($entry['entry_date'])) ?></td>
                        <td class="py-2 px-4 text-white"><?= htmlspecialchars($entry['description']) ?></td>
                        <td class="py-2 px-4 text-right text-white"><?= number_format($entry['hours'], 2) ?></td>
                        <td class="py-2 px-4 text-right text-white"><?= htmlspecialchars($client['client_currency']) ?> <?= number_format($client['hourly_rate'], 2) ?></td>
                        <td class="py-2 px-4 text-right text-white"><?= htmlspecialchars($client['client_currency']) ?> <?= number_format($entry['amount_earned'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="bg-yellow-100 border-l-4 border-green-700 p-4">
                <p>No time entries found for this period.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Invoice Summary -->
        <div class="flex justify-end">
            <div class="w-64">
                <div class="flex justify-between py-2 border-b">
                    <span class="font-medium text-white">Subtotal:</span>
                    <span class="text-white"><?= htmlspecialchars($client['client_currency']) ?> <?= number_format($total_amount, 2) ?></span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="font-medium text-white">Tax (0%):</span>
                    <span class="text-white"><?= htmlspecialchars($client['client_currency']) ?> 0.00</span>
                </div>
                <div class="flex justify-between py-2 font-bold text-lg">
                    <span class="font-medium text-white">Total:</span>
                    <span class="text-white"><?= htmlspecialchars($client['client_currency']) ?> <?= number_format($total_amount, 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Instructions -->
        <div class="mt-8 pt-4 border-t">
            <h3 class="font-semibold mb-2 text-white">Payment Instructions:</h3>
            <p class="text-white">Please make payment to:</p>
            <p class="text-white">Bank Name: Example Bank</p>
            <p class="text-white">Account Number: 123456789</p>
            <p class="text-white">Account Name: Trackify LLC</p>
            <p class="mt-2 text-white">Due Date: <?= date('F j, Y', strtotime('+15 days')) ?></p>
        </div>

        <!-- Print Button -->
        <div class="mt-8 text-center">
            <button onclick="window.print()" class="bg-green-700 text-white px-4 py-2 rounded-md hover:bg-green-800">
                Print Invoice
            </button>
        </div>
    </div>
</body>
</html>