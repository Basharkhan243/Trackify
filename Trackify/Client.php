<?php
session_start();
require_once 'partials/_dbconnect.php';

if (!isset($_SESSION['loggedin'])) {
    header("location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_client'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $currency = 'USD'; 
        $hourly_rate = (float)$_POST['hourly_rate'];
        $project_title = mysqli_real_escape_string($conn, $_POST['project_title'] ?? '');
        $filing_date = !empty($_POST['filing_date']) ? $_POST['filing_date'] : null;
        $expected_date = !empty($_POST['expected_date']) ? $_POST['expected_date'] : null;
        $project_description = mysqli_real_escape_string($conn, $_POST['project_description'] ?? '');

        $sql = "INSERT INTO clients (client_name, client_currency, hourly_rate, project_title, filing_date, expected_completion, project_description, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssdssssi", $name, $currency, $hourly_rate, $project_title, $filing_date, $expected_date, $project_description, $user_id);
        mysqli_stmt_execute($stmt);
    } elseif (isset($_POST['delete_client'])) {
        $id = (int)$_POST['client_id'];
        // Verify the client belongs to the user before deleting
        $verify_sql = "SELECT id FROM clients WHERE id = ? AND user_id = ?";
        $verify_stmt = mysqli_prepare($conn, $verify_sql);
        mysqli_stmt_bind_param($verify_stmt, "ii", $id, $user_id);
        mysqli_stmt_execute($verify_stmt);
        $result = mysqli_stmt_get_result($verify_stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $sql = "DELETE FROM clients WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
        }
    }
}

// Get all clients for the current user
$clients = [];
$sql = "SELECT id, client_name, client_currency, hourly_rate, project_title, 
        DATE_FORMAT(filing_date, '%Y-%m-%d') as filing_date, 
        DATE_FORMAT(expected_completion, '%Y-%m-%d') as expected_completion,
        project_description 
        FROM clients 
        WHERE user_id = ? 
        ORDER BY client_name";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($result) {
    $clients = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media (max-width: 640px) {
            .client-card {
                border: 1px solid #444;
                border-radius: 0.5rem;
                padding: 1rem;
                margin-bottom: 1rem;
                background: #222;
            }
            .client-card div {
                margin-bottom: 0.5rem;
            }
            .client-label {
                font-weight: bold;
                color: #aaa;
                display: block;
            }
        }
    </style>
</head>
<body class="bg-[#131212] min-h-screen p-4 md:p-6">
    <a href="Dashboard.php" class="text-white hover:text-green-700 text-lg md:mx-8 my-4 inline-block mb-4">
        <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
    </a>
    
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl md:text-4xl font-bold text-[#fff] mb-6 md:mb-8">Client Management</h1>

        <!-- Add Client Form -->
        <div class="bg-[#222] rounded-lg shadow-md p-4 md:p-6 mb-6">
            <h2 class="text-lg md:text-xl font-semibold mb-3 md:mb-4 text-white">Add New Client</h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                <div>
                    <label class="block text-white mb-1 md:mb-2 text-sm md:text-base">Client Name*</label>
                    <input type="text" name="name" placeholder="Client Name" required
                           class="bg-[#111] text-white w-full p-2 text-sm md:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <input type="hidden" name="currency" value="USD">
                </div>
                <div>
                    <label class="block text-white mb-1 md:mb-2 text-sm md:text-base">Hourly Rate (USD)*</label>
                    <input type="number" step="0.01" name="hourly_rate" placeholder="0.00" required
                           class="bg-[#111] text-white w-full p-2 text-sm md:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-white mb-1 md:mb-2 text-sm md:text-base">Project Title</label>
                    <input type="text" name="project_title" placeholder="Project Title"
                           class="bg-[#111] text-white w-full p-2 text-sm md:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-white mb-1 md:mb-2 text-sm md:text-base">Filing Date</label>
                    <input type="date" name="filing_date"
                           class="bg-[#111] text-white hover:bg-gray-200 hover:text-[#111] w-full p-2 text-sm md:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-white mb-1 md:mb-2 text-sm md:text-base">Expected Completion</label>
                    <input type="date" name="expected_date"
                           class="bg-[#111] text-white hover:bg-gray-200 hover:text-[#111] w-full p-2 text-sm md:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-white mb-1 md:mb-2 text-sm md:text-base">Project Description</label>
                    <textarea name="project_description" rows="3" placeholder="Project Description"
                              class="bg-[#111] text-white w-full p-2 text-sm md:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" name="add_client"
                            class="bg-green-700 text-white px-4 py-2 text-sm md:text-base rounded-md hover:bg-green-800 transition w-full md:w-auto">
                        Add Client
                    </button>
                </div>
            </form>
        </div>

        <!-- Clients Table - Desktop -->
        <div class="hidden md:block rounded-lg shadow-md overflow-hidden">
            <div class="p-4 border-b">
                <h2 class="text-2xl md:text-4xl font-bold text-white">Client List</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#111]">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Dates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-[#222] divide-y divide-gray-200">
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <a href="Tracking.php?client_id=<?= $client['id'] ?>" class="text-green-600 hover:text-green-800 hover:underline">
                                    <i class="fas fa-clock mr-1"></i> <?= htmlspecialchars($client['client_name']) ?>
                                </a>
                                <div class="text-xs text-gray-400 mt-1"><?= htmlspecialchars(substr($client['project_description'], 0, 50)) ?><?= strlen($client['project_description']) > 50 ? '...' : '' ?></div>
                            </td>
                            <td class="px-6 py-4 text-white"><?= number_format($client['hourly_rate'], 2) ?> USD</td>
                            <td class="px-6 py-4 text-white"><?= htmlspecialchars($client['project_title']) ?></td>
                            <td class="px-6 py-4 text-white text-sm">
                                <div>Filed: <?= $client['filing_date'] ? htmlspecialchars($client['filing_date']) : '-' ?></div>
                                <div>Expected: <?= $client['expected_completion'] ? htmlspecialchars($client['expected_completion']) : '-' ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                    <button type="submit" name="delete_client" class="text-red-600 hover:text-red-900 text-sm" onclick="return confirm('Are you sure?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No clients found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Clients Cards - Mobile -->
        <div class="md:hidden">
            <h2 class="text-2xl font-bold text-white mb-4">Client List</h2>
            
            <?php if (empty($clients)): ?>
                <div class="text-gray-500 text-center py-4">No clients found</div>
            <?php else: ?>
                <?php foreach ($clients as $client): ?>
                    <div class="client-card">
                        <div>
                            <span class="client-label">Name:</span>
                            <a href="Tracking.php?client_id=<?= $client['id'] ?>" class="text-white hover:text-green-600">
                                <?= htmlspecialchars($client['client_name']) ?>
                            </a>
                        </div>
                        <div>
                            <span class="client-label">Rate:
                            <?= number_format($client['hourly_rate'], 2) ?> USD </span>
                        </div>
                        <div>
                            <span class="client-label">Project:
                            <?= htmlspecialchars($client['project_title']) ?></span>
                        </div>
                        <div>
                            <span class="client-label">Filed:
                            <?= $client['filing_date'] ? htmlspecialchars($client['filing_date']) : '-' ?></span>
                        </div>
                        <div>
                            <span class="client-label">Expected:
                            <?= $client['expected_completion'] ? htmlspecialchars($client['expected_completion']) : '-' ?></span>
                        </div>
                        <div>
                            <span class="client-label">Description:
                            <?= htmlspecialchars(substr($client['project_description'], 0, 50)) ?><?= strlen($client['project_description']) > 50 ? '...' : '' ?></span>
                        </div>
                        <div class="mt-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                <button type="submit" name="delete_client" class="text-red-600 hover:text-red-900 text-sm" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash-alt mr-1"></i> Delete Client
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Simple confirmation for delete actions
        document.querySelectorAll('button[name="delete_client"]').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this client?')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>