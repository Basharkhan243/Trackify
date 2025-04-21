<?php
session_start();
require_once 'partials/_dbconnect.php';


if (isset($_GET['client_id'])) {
    $_SESSION['selected_client_id'] = (int)$_GET['client_id'];
    $client_id = $_SESSION['selected_client_id'];
} elseif (isset($_SESSION['selected_client_id'])) {
    $client_id = $_SESSION['selected_client_id'];
} else {
    $_SESSION['return_to'] = 'Tracking.php';
    header("Location: ./Client.php");
    exit;
}


$client = [];
$result = mysqli_query($conn, "SELECT id, client_name, hourly_rate, client_currency, project_title, 
                              total_hours_spent, total_amount_earned FROM clients WHERE id = $client_id");
if ($result && mysqli_num_rows($result) > 0) {
    $client = mysqli_fetch_assoc($result);
} else {
    unset($_SESSION['selected_client_id']);
    $_SESSION['return_to'] = 'Tracking.php';
    header("Location: ./Client.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_time'])) {
    $hours = (float)$_POST['hours'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $date = mysqli_real_escape_string($conn, $_POST['date'] ?? date('Y-m-d'));
    $amount_earned = $hours * $client['hourly_rate'];
    
    mysqli_begin_transaction($conn);
    
    try {
        $sql = "INSERT INTO time_entries (client_id, hours, amount_earned, description, entry_date)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iddss", $client_id, $hours, $amount_earned, $description, $date);
        mysqli_stmt_execute($stmt);
        
        $sql = "UPDATE clients SET 
                total_hours_spent = total_hours_spent + ?,
                total_amount_earned = total_amount_earned + ?
                WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ddi", $hours, $amount_earned, $client_id);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        header("Location: Tracking.php?saved=1");
        exit;
        
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($conn);
        $error = "Error saving time entry: " . $e->getMessage();
    }
}


$time_entries = [];
$result = mysqli_query($conn, "SELECT * FROM time_entries WHERE client_id = $client_id ORDER BY entry_date DESC");
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
        @media (max-width: 640px) {
            .timer-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
            .timer-buttons button {
                width: 100%;
            }
            .client-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }
            .client-info span.separator {
                display: none;
            }
            .time-entry-grid {
                grid-template-columns: 1fr;
            }
            .invoice-form {
                flex-direction: column;
                gap: 0.5rem;
            }
            .invoice-form div {
                width: 100%;
            }
            .invoice-form button {
                width: 100%;
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body class="bg-[#111] min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-4 md:py-8">
      
        <div class="mb-6">
            <a href="./Dashboard.php" class="text-white hover:text-green-600 inline-block mb-4 text-base md:text-lg">
                <i class="fas fa-arrow-left mr-1"></i> Back 
            </a>
            <h1 class="text-2xl md:text-4xl font-bold text-white">
                <i class="fas fa-clock mr-2 text-green-700"></i> Time Tracker
            </h1>
            <?php if ($client): ?>
            <div class="mt-2">
                <div class="client-info flex flex-wrap items-center gap-1 md:gap-2 text-sm md:text-base">
                    <span class="text-white">Client:</span>
                    <span class="font-medium text-white"><?= htmlspecialchars($client['client_name']) ?></span>
                    <span class="separator text-white">•</span>
                    <span class="text-white">Project:</span>
                    <span class="font-medium text-white"><?= htmlspecialchars($client['project_title']) ?></span>
                    <span class="separator text-white">•</span>
                    <span class="text-white">Rate:</span>
                    <span class="font-medium text-white"><?= $client['client_currency'] ?> <?= number_format($client['hourly_rate'], 2) ?>/hr</span>
                </div>
                <div class="mt-2 text-sm md:text-base">
                    <span class="text-white">Total Hours:</span>
                    <span class="font-medium text-white"><?= number_format($client['total_hours_spent'], 2) ?></span>
                    <span class="mx-1 md:mx-2 text-white">•</span>
                    <span class="text-white">Total Earned:</span>
                    <span class="font-medium text-white"><?= $client['client_currency'] ?> <?= number_format($client['total_amount_earned'], 2) ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>

        
        <div class="bg-[#222] rounded-lg shadow-md p-4 md:p-6 mb-6">
            <div class="text-center mb-4 md:mb-6">
                <div id="timerDisplay" class="text-3xl md:text-5xl font-mono font-bold text-white mb-3 md:mb-4">00:00:00</div>
                <div class="timer-buttons flex flex-col md:flex-row justify-center space-y-2 md:space-y-0 md:space-x-4">
                    <button id="startBtn" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 md:px-6 rounded-md">
                        <i class="fas fa-play mr-1"></i> Start
                    </button>
                    <button id="pauseBtn" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 md:px-6 rounded-md" disabled>
                        <i class="fas fa-pause mr-1"></i> Pause
                    </button>
                    <button id="stopBtn" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 md:px-6 rounded-md" disabled>
                        <i class="fas fa-stop mr-1"></i> Stop
                    </button>
                </div>
            </div>

         
            <form id="timeEntryForm" method="POST" class="hidden">
                <div class="time-entry-grid grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4 mb-3 md:mb-4">
                    <div>
                        <label class="block text-white mb-1 md:mb-2 text-sm md:text-base">Date</label>
                        <input type="date" name="date" value="<?= date('Y-m-d') ?>" 
                               class="w-full p-2 text-sm md:text-base border border-gray-300 rounded-md text-white bg-[#111]">
                    </div>
                    <div>
                        <label class="block text-white mb-1 md:mb-2 text-sm md:text-base">Hours</label>
                        <input type="number" step="0.25" name="hours" id="recordedHours" 
                               class="w-full p-2 text-sm md:text-base border border-gray-300 rounded-md text-white bg-[#111]" readonly>
                    </div>
                    <div>
                        <label class="block text-white mb-1 md:mb-2 text-sm md:text-base">Amount Earned</label>
                        <input type="text" id="amountEarned" readonly
                               class="w-full p-2 text-sm md:text-base border border-gray-300 rounded-md text-white bg-[#111]">
                    </div>
                </div>
                <div class="mb-3 md:mb-4">
                    <label class="block text-white mb-1 md:mb-2 text-sm md:text-base">Work Description*</label>
                    <textarea name="description" rows="3" placeholder="Work Description" required
                              class="w-full p-2 text-sm md:text-base border border-gray-300 rounded-md text-white bg-[#111]"></textarea>
                </div>
                <div class="flex flex-col md:flex-row justify-end space-y-2 md:space-y-0 md:space-x-3">
                    <button type="button" id="cancelEntry" class="border border-gray-300 text-white py-2 px-4 md:px-6 rounded-md hover:bg-green-700 text-sm md:text-base">
                        Cancel
                    </button>
                    <button type="submit" name="save_time" class="bg-green-600 hover:bg-green-800 text-white py-2 px-4 md:px-6 rounded-md text-sm md:text-base">
                        <i class="fas fa-save mr-1"></i> Save Entry
                    </button>
                </div>
            </form>
        </div>

        
        <div class="mt-4 p-4 bg-[#222] rounded-lg mb-6">
            <div class="flex justify-center items-center flex-col">
                <h3 class="font-semibold mb-2 text-white text-xl md:text-2xl">Generate Invoice</h3>
                <form action="invoice.php" method="get" target="_blank" class="invoice-form flex flex-wrap gap-2 md:gap-4 items-end w-full">
                    <input type="hidden" name="client_id" value="<?= $client_id ?>">
                    
                    <div class="flex-1 min-w-[120px]">
                        <label class="block text-white mb-1 text-sm md:text-base">Start Date</label>
                        <input type="date" name="start_date" value="<?= date('Y-m-01') ?>" 
                               class="w-full p-2 text-sm md:text-base border border-gray-300 rounded-md bg-[#111] text-white">
                    </div>
                    
                    <div class="flex-1 min-w-[120px]">
                        <label class="block text-white mb-1 text-sm md:text-base">End Date</label>
                        <input type="date" name="end_date" value="<?= date('Y-m-t') ?>" 
                               class="w-full p-2 text-sm md:text-base border border-gray-300 rounded-md bg-[#111] text-white">
                    </div>
                    
                    <button type="submit" class="bg-green-700 text-white px-4 py-2 rounded-md hover:bg-green-800 text-sm md:text-base">
                        Generate Invoice
                    </button>
                </form>
            </div>
        </div>

        
        <div class="bg-[#222] rounded-lg shadow-md p-4 md:p-6">
            <h2 class="text-lg md:text-xl font-semibold text-white mb-3 md:mb-4">
                <i class="fas fa-history mr-2 text-green-700"></i> Recent Time Entries
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#111]">
                        <tr>
                            <th class="px-2 py-2 md:px-4 md:py-3 text-left text-xs md:text-sm font-medium text-white uppercase">Date</th>
                            <th class="px-2 py-2 md:px-4 md:py-3 text-left text-xs md:text-sm font-medium text-white uppercase">Hours</th>
                            <th class="px-2 py-2 md:px-4 md:py-3 text-left text-xs md:text-sm font-medium text-white uppercase">Amount</th>
                            <th class="px-2 py-2 md:px-4 md:py-3 text-left text-xs md:text-sm font-medium text-white uppercase">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($client_id): 
                            $sql = "SELECT * FROM time_entries 
                                    WHERE client_id = $client_id 
                                    ORDER BY entry_date DESC LIMIT 5";
                            $result = mysqli_query($conn, $sql);
                            while ($entry = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="px-2 py-2 md:px-4 md:py-3 whitespace-nowrap text-white text-xs md:text-sm"><?= htmlspecialchars($entry['entry_date']) ?></td>
                                <td class="px-2 py-2 md:px-4 md:py-3 whitespace-nowrap text-white text-xs md:text-sm"><?= number_format($entry['hours'], 2) ?></td>
                                <td class="px-2 py-2 md:px-4 md:py-3 whitespace-nowrap text-white text-xs md:text-sm">
                                    <?= $client['client_currency'] ?> <?= number_format($entry['amount_earned'], 2) ?>
                                </td>
                                <td class="px-2 py-2 md:px-4 md:py-3 text-white text-xs md:text-sm"><?= htmlspecialchars($entry['description']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
     
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
            
            
            const hours = (seconds / 3600).toFixed(2);
            recordedHours.value = hours;
            calculateEarnings(hours);
            
          
            timeEntryForm.classList.remove('hidden');
            
          
            startBtn.disabled = false;
            pauseBtn.disabled = true;
            stopBtn.disabled = true;
        });
        
        cancelEntry.addEventListener('click', () => {
            timeEntryForm.classList.add('hidden');
            seconds = 0;
            timerDisplay.textContent = formatTime(seconds);
        });

       
        window.addEventListener('resize', function() {
            if (window.innerWidth < 640) {
               
                if (timeEntryForm && !timeEntryForm.classList.contains('hidden')) {
                  
                    timeEntryForm.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    </script>
</body>
</html>
