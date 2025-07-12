<?php
session_start();
require_once 'partials/_dbconnect.php';

if (!isset($_SESSION['loggedin'])) {
    header("location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
    // Verify client belongs to user
    $verify_sql = "SELECT id FROM clients WHERE id = ? AND user_id = ?";
    $verify_stmt = mysqli_prepare($conn, $verify_sql);
    mysqli_stmt_bind_param($verify_stmt, "ii", $client_id, $user_id);
    mysqli_stmt_execute($verify_stmt);
    $result = mysqli_stmt_get_result($verify_stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $_SESSION['selected_client_id'] = $client_id;
    } else {
        header("Location: Client.php");
        exit;
    }
} elseif (isset($_SESSION['selected_client_id'])) {
    $client_id = $_SESSION['selected_client_id'];
} else {
    header("Location: Client.php");
    exit;
}

// Get client details with user verification
$client = [];
$sql = "SELECT id, client_name, hourly_rate, client_currency, project_title, 
        total_hours_spent, total_amount_earned 
        FROM clients 
        WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $client_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $client = mysqli_fetch_assoc($result);
} else {
    unset($_SESSION['selected_client_id']);
    header("Location: Client.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_time'])) {
    $hours = (float)$_POST['hours'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $date = mysqli_real_escape_string($conn, $_POST['date'] ?? date('Y-m-d'));
    $amount_earned = $hours * $client['hourly_rate'];
    
    mysqli_begin_transaction($conn);
    
    try {
        // Insert time entry with user_id
        $sql = "INSERT INTO time_entries (client_id, hours, amount_earned, description, entry_date, user_id)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iddssi", $client_id, $hours, $amount_earned, $description, $date, $user_id);
        mysqli_stmt_execute($stmt);
        
        // Update client totals
        $sql = "UPDATE clients SET 
                total_hours_spent = total_hours_spent + ?,
                total_amount_earned = total_amount_earned + ?
                WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ddii", $hours, $amount_earned, $client_id, $user_id);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        header("Location: Tracking.php?saved=1&client_id=$client_id");
        exit;
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($conn);
        $error = "Error saving time entry: " . $e->getMessage();
    }
}

// Get time entries for this client and user
$time_entries = [];
$sql = "SELECT * FROM time_entries WHERE client_id = ? AND user_id = ? ORDER BY entry_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $client_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($result) {
    $time_entries = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

$showInvoice = isset($_GET['saved']) && $_GET['saved'] == '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Time Tracker | Trackify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Mobile-first styles */
        .timer-container {
            background-color: #222;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .timer-display {
            font-size: 2rem;
            line-height: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .timer-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .timer-button {
            padding: 0.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            text-align: center;
            width: 100%;
        }
        
        .client-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            margin-bottom: 1rem;
        }
        
        .client-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .time-entry-form {
            display: none;
            background-color: #222;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .form-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .invoice-container {
            background-color: #222;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .invoice-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        
        .entries-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #222;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .entries-table th, 
        .entries-table td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        
        .entries-table th {
            background-color: #111;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.75rem;
        }
        
        /* Tablet styles */
        @media (min-width: 640px) {
            .timer-display {
                font-size: 3rem;
                line-height: 1;
                margin-bottom: 1.5rem;
            }
            
            .timer-buttons {
                flex-direction: row;
                justify-content: center;
                gap: 1rem;
            }
            
            .timer-button {
                width: auto;
                padding: 0.5rem 1rem;
            }
            
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .invoice-form {
                grid-template-columns: repeat(3, 1fr);
                align-items: end;
            }
            
            .form-actions {
                flex-direction: row;
                justify-content: flex-end;
            }
        }
        
        /* Desktop styles */
        @media (min-width: 768px) {
            .timer-display {
                font-size: 4rem;
            }
            
            .form-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .entries-table th, 
            .entries-table td {
                padding: 0.75rem 1rem;
            }
        }
        
        /* Dark theme colors */
        body {
            background-color: #111;
            color: white;
        }
        
        .bg-dark {
            background-color: #222;
        }
        
        .bg-darker {
            background-color: #111;
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
        
        .border-primary {
            border-color: #66bb60;
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
    <div class="max-w-4xl mx-auto px-4 py-6">
        <!-- Header and Back Button -->
        <div class="mb-6">
            <a href="./Dashboard.php" class="inline-flex items-center text-white hover:text-primary mb-4">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>Back to Dashboard</span>
            </a>
            
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">
                <i class="fas fa-clock text-primary mr-2"></i>
                Time Tracker
            </h1>
            
            <?php if ($client): ?>
            <div class="client-info">
                <div class="client-info-item">
                    <span class="text-gray-400">Client:</span>
                    <span class="font-medium"><?= htmlspecialchars($client['client_name']) ?></span>
                </div>
                <div class="client-info-item">
                    <span class="text-gray-400">Project:</span>
                    <span class="font-medium"><?= htmlspecialchars($client['project_title']) ?></span>
                </div>
                <div class="client-info-item">
                    <span class="text-gray-400">Rate:</span>
                    <span class="font-medium"><?= $client['client_currency'] ?> <?= number_format($client['hourly_rate'], 2) ?>/hr</span>
                </div>
                <div class="client-info-item">
                    <span class="text-gray-400">Total Hours:</span>
                    <span class="font-medium"><?= number_format($client['total_hours_spent'], 2) ?></span>
                </div>
                <div class="client-info-item">
                    <span class="text-gray-400">Total Earned:</span>
                    <span class="font-medium"><?= $client['client_currency'] ?> <?= number_format($client['total_amount_earned'], 2) ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Timer Section -->
        <div class="timer-container">
            <div class="text-center">
                <div id="timerDisplay" class="timer-display font-mono font-bold text-white">00:00:00</div>
                
                <div class="timer-buttons">
                    <button id="startBtn" class="timer-button bg-primary hover:bg-green-700 text-white">
                        <i class="fas fa-play mr-2"></i> Start
                    </button>
                    <button id="pauseBtn" class="timer-button bg-yellow-500 hover:bg-yellow-600 text-white" disabled>
                        <i class="fas fa-pause mr-2"></i> Pause
                    </button>
                    <button id="stopBtn" class="timer-button bg-red-600 hover:bg-red-700 text-white" disabled>
                        <i class="fas fa-stop mr-2"></i> Stop
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Time Entry Form (Hidden by default) -->
        <form id="timeEntryForm" method="POST" class="time-entry-form">
            <div class="form-grid">
                <div>
                    <label class="block text-gray-400 mb-1 text-sm">Date</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" 
                           class="w-full p-2 border border-gray-600 rounded bg-darker text-white">
                </div>
                <div>
                    <label class="block text-gray-400 mb-1 text-sm">Hours</label>
                    <input type="number" step="0.25" name="hours" id="recordedHours" 
                           class="w-full p-2 border border-gray-600 rounded bg-darker text-white" readonly>
                </div>
                <div>
                    <label class="block text-gray-400 mb-1 text-sm">Amount Earned</label>
                    <input type="text" id="amountEarned" readonly
                           class="w-full p-2 border border-gray-600 rounded bg-darker text-white">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-400 mb-1 text-sm">Work Description*</label>
                <textarea name="description" rows="3" placeholder="What did you work on?" required
                          class="w-full p-2 border border-gray-600 rounded bg-darker text-white"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" id="cancelEntry" class="btn border border-gray-600 text-white py-2 px-4 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" name="save_time" class="btn bg-primary hover:bg-green-700 text-white py-2 px-4 rounded">
                    <i class="fas fa-save mr-2"></i> Save Entry
                </button>
            </div>
        </form>
        
        <!-- Invoice Generator -->
        <div class="invoice-container">
            <h3 class="text-lg font-semibold text-white mb-3 text-center md:text-left">
                <i class="fas fa-file-invoice text-primary mr-2"></i>
                Generate Invoice
            </h3>
            
            <form action="Invoice.php" method="get" target="_blank" class="invoice-form">
                <input type="hidden" name="client_id" value="<?= $client_id ?>">
                
                <div>
                    <label class="block text-gray-400 mb-1 text-sm">Start Date</label>
                    <input type="date" name="start_date" value="<?= date('Y-m-01') ?>" 
                           class="w-full p-2 border border-gray-600 rounded bg-darker text-white">
                </div>
                
                <div>
                    <label class="block text-gray-400 mb-1 text-sm">End Date</label>
                    <input type="date" name="end_date" value="<?= date('Y-m-t') ?>" 
                           class="w-full p-2 border border-gray-600 rounded bg-darker text-white">
                </div>
                
                <button type="submit" class="btn bg-primary hover:bg-green-700 text-white py-2 px-4 rounded">
                    Generate Invoice
                </button>
            </form>
        </div>
        
        <!-- Recent Time Entries -->
        <div class="bg-dark rounded-lg shadow-md p-4 md:p-6 overflow-x-auto">
            <h2 class="text-lg md:text-xl font-semibold text-white mb-3 md:mb-4">
                <i class="fas fa-history text-primary mr-2"></i>
                Recent Time Entries
            </h2>
            
            <table class="entries-table w-full">
                <thead>
                    <tr>
                        <th class="text-gray-400 text-left">Date</th>
                        <th class="text-gray-400 text-left">Hours</th>
                        <th class="text-gray-400 text-left">Amount</th>
                        <th class="text-gray-400 text-left">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($client_id): 
                        $sql = "SELECT * FROM time_entries 
                                WHERE client_id = $client_id 
                                ORDER BY entry_date DESC LIMIT 5";
                        $result = mysqli_query($conn, $sql);
                        while ($entry = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="text-white"><?= htmlspecialchars($entry['entry_date']) ?></td>
                            <td class="text-white"><?= number_format($entry['hours'], 2) ?></td>
                            <td class="text-white">
                                <?= $client['client_currency'] ?> <?= number_format($entry['amount_earned'], 2) ?>
                            </td>
                            <td class="text-white"><?= htmlspecialchars($entry['description']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Timer functionality
        let timer;
        let seconds = 0;
        let isRunning = false;
        const hourlyRate = <?= $client ? $client['hourly_rate'] : 0 ?>;
        const currencySymbol = '<?= $client ? $client["client_currency"] : "" ?>';
        
        const timerDisplay = document.getElementById('timerDisplay');
        const startBtn = document.getElementById('startBtn');
        const pauseBtn = document.getElementById('pauseBtn');
        const stopBtn = document.getElementById('stopBtn');
        const timeEntryForm = document.getElementById('timeEntryForm');
        const recordedHours = document.getElementById('recordedHours');
        const amountEarned = document.getElementById('amountEarned');
        const cancelEntry = document.getElementById('cancelEntry');
        
        function formatTime(secs) {
            const hours = Math.floor(secs / 3600);
            const minutes = Math.floor((secs % 3600) / 60);
            const seconds = secs % 60;
            
            return [
                hours.toString().padStart(2, '0'),
                minutes.toString().padStart(2, '0'),
                seconds.toString().padStart(2, '0')
            ].join(':');
        }
        
        function updateTimer() {
            timerDisplay.textContent = formatTime(seconds);
            seconds++;
        }
        
        function calculateEarnings(hours) {
            const amount = hours * hourlyRate;
            amountEarned.value = currencySymbol + ' ' + amount.toFixed(2);
        }
        
        startBtn.addEventListener('click', () => {
            if (!isRunning) {
                isRunning = true;
                timer = setInterval(updateTimer, 1000);
                startBtn.disabled = true;
                pauseBtn.disabled = false;
                stopBtn.disabled = false;
            }
        });
        
        pauseBtn.addEventListener('click', () => {
            if (isRunning) {
                clearInterval(timer);
                isRunning = false;
                pauseBtn.disabled = true;
                startBtn.disabled = false;
            }
        });
        
        stopBtn.addEventListener('click', () => {
            clearInterval(timer);
            isRunning = false;
            
            // Calculate hours (seconds to hours)
            const hours = (seconds / 3600).toFixed(2);
            recordedHours.value = hours;
            calculateEarnings(hours);
            
            // Show the form
            timeEntryForm.style.display = 'block';
            
            // Scroll to form on mobile
            if (window.innerWidth < 640) {
                timeEntryForm.scrollIntoView({ behavior: 'smooth' });
            }
            
            // Reset buttons
            startBtn.disabled = false;
            pauseBtn.disabled = true;
            stopBtn.disabled = true;
        });
        
        cancelEntry.addEventListener('click', () => {
            timeEntryForm.style.display = 'none';
            seconds = 0;
            timerDisplay.textContent = formatTime(seconds);
        });

        // Auto-scroll to form on mobile when it appears
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'style') {
                    const display = timeEntryForm.style.display;
                    if (display === 'block' && window.innerWidth < 640) {
                        timeEntryForm.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });

        observer.observe(timeEntryForm, {
            attributes: true
        });
    </script>
</body>
</html>