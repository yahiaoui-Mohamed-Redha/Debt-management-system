<?php
include 'db.php';
session_start();

// Check if client ID is provided
if (!isset($_GET['id'])) {
    die("Client ID is missing.");
}

$client_id = $_GET['id'];

// Fetch client's name
$query_client = "SELECT name FROM clients WHERE id = :client_id"; 
$stmt_client = $conn->prepare($query_client);
$stmt_client->execute(['client_id' => $client_id]);
$client = $stmt_client->fetch(PDO::FETCH_ASSOC);

// Check if the client was found
if (!$client) {
    die("Client not found.");
}

// Fetch old operations
$query_operations = "SELECT * FROM old_operations WHERE client_id = :client_id ORDER BY date_from DESC";
$stmt_operations = $conn->prepare($query_operations);
$stmt_operations->execute(['client_id' => $client_id]);
$operations = $stmt_operations->fetchAll(PDO::FETCH_ASSOC);

// Prepare an array to hold debt dates indexed by operation_id
$debt_dates_by_operation = [];

// Loop through each operation to fetch debt dates
foreach ($operations as $operation) {
    $operation_id = $operation['id']; // Assuming 'id' is the operation_id

    // Fetch the first and last debt dates using operation_id
    $query_debts = "SELECT 
            o.id AS operation_id, 
            MIN(d.date_added) AS first_debt_date, 
            MAX(d.date_added) AS last_debt_date
        FROM 
            old_operations o
        LEFT JOIN 
            operation_debts_details d ON d.operation_id = o.id
        WHERE 
            o.client_id = :client_id AND o.id = :operation_id
        GROUP BY 
            o.id";

    // Execute the query
    $stmt_debts = $conn->prepare($query_debts);
    $stmt_debts->execute(['client_id' => $client_id, 'operation_id' => $operation_id]);
    $debt_dates = $stmt_debts->fetch(PDO::FETCH_ASSOC);

    // Store the debt dates in the array indexed by operation_id
    if ($debt_dates) {
        $debt_dates_by_operation[$operation_id] = $debt_dates;
    } else {
        $debt_dates_by_operation[$operation_id] = [
            'first_debt_date' => 'No debts found',
            'last_debt_date' => 'No debts found'
        ];
    }
}

// Fetch old operations
$query_operations = "SELECT * FROM old_operations WHERE client_id = :client_id ORDER BY date_from DESC";
$stmt_operations = $conn->prepare($query_operations);
$stmt_operations->execute(['client_id' => $client_id]);
$operations = $stmt_operations->fetchAll(PDO::FETCH_ASSOC);

// Check if any operations were found
if (empty($operations)) {
    $message = "No old operations found for this client.";
} else {
    $message = ""; // Reset message if operations are found
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/x-icon" href="/assets/icon/logo.svg">
  <!-- font-awesomes -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- link css -->
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="output.css">
  <script src="dist/js/script.js" defer></script>
  <title>Document</title>
</head>

<body class="bg-slate-100">

    <header class=" z-[100] fixed top-0 right-0 bg-white flex items-center justify-between w-full py-5 shadow-shalg mb-8 p-5">
        <h1 class="font-semibold text-2xl text-gray-900">إسم العميل : <?php echo htmlspecialchars($client['name']); ?></h1>
            <a class="flex items-center justify-between text-white bg-gray-800 w-[8.5rem] p-[10px] rounded-md font-semibold text-base hover:bg-gray-600 transition-all" href="client_debts.php?id=<?php echo $client_id; ?>"><span class="mr-1">العودة</span> <i class="fa-solid fa-arrow-left ml-1"></i> </a>
    </header>


  <h1 class="text-gray-900 text-3xl font-bold relative mb-3 mt-32 mx-5">كل العمليات :</h1>


  <div class=" relative w-[100%] my-6 flex items-center justify-center overflow-x-auto">
    <table class=" w-[90%] text-sm text-right rtl:text-right text-gray-50 font-medium border-separate border-spacing-y-3 border-spacing-x-0 shadow-md rounded-md" >
        <thead class="uppercase bg-gray-800 text-white font-semibold text-xl">
            <tr>
            <th scope="col" class="px-6 py-3">
                    رقم العملية
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    من (أول دين)
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    إلى (آخر دين)
                </th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $row_number = 1; // Initialize the counter
            foreach ($operations as $operation): ?>
            <tr class="scale-h border-b bg-playstation border-gray-800 transition-all duration-500 hover:bg-blue-900 font-semibold"
                onclick="window.location.href='operation_details.php?id=<?php echo $operation['id']; ?>'">
                <th scope="row" class="px-6 py-4 font-bold text-xl text-gray-900 whitespace-nowrap dark:text-white">
                    <?php echo $row_number; // Display the row number ?>
                </th>
                <td class="px-6 text-xl py-4 text-center bg-gray-800">
                    <?php echo htmlspecialchars($debt_dates_by_operation[$operation['id']]['first_debt_date']); ?>
                </td>
                <td class="px-6 py-4 bg-gray-800 text-xl text-center">
                    <?php echo htmlspecialchars($debt_dates_by_operation[$operation['id']]['last_debt_date']); ?>
                </td>
            </tr>
            <?php 
            $row_number++; // Increment the counter
            endforeach; ?>
        </tbody>
  </div>



</body>

</html>