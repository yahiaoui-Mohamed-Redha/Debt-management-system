<?php
include 'dist/db.php';

// Handle form submission for adding a new client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    $name = $_POST['name'];
    $balance = 0; // Default to 0
    $total_debts = 0; // Default to 0
    $status = 'new'; // Default status for a new client

    $query = "INSERT INTO clients (name, balance, status) VALUES (:name, :balance, :status)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        'name' => $name,
        'balance' => $balance,
        'status' => $status
    ]);

    // Redirect to the same page to see the updated list
    header("Location: index.php");
    exit();
}

// Delete client (soft delete)
if (isset($_POST['delete_client'])) {
  $client_id = $_POST['delete_client_id'];
  $stmt = $conn->prepare("UPDATE clients SET is_archived = 1 WHERE id = :id");
  $stmt->bindParam(':id', $client_id, PDO::PARAM_INT);
  $stmt->execute();
}

$client_id = isset($_GET['id']) ;

$query = "SELECT c.id, c.name, 
           COALESCE(d.total_debts, 0) AS total_debts, 
           COALESCE(p.total_payments, 0) AS total_payments,
           (COALESCE(d.total_debts, 0) - COALESCE(p.total_payments, 0)) AS balance
    FROM clients c
    LEFT JOIN (
        SELECT client_id, SUM(total_price) AS total_debts
        FROM debts
        GROUP BY client_id
    ) d ON c.id = d.client_id
    LEFT JOIN (
        SELECT client_id, SUM(amount) AS total_payments
        FROM payments
        GROUP BY client_id
    ) p ON c.id = p.client_id
    WHERE c.is_archived = 0
    ORDER BY c.name ASC";

$stmt = $conn->prepare($query);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
  <link rel="stylesheet" href="dist/css/style.css" />
  <link rel="stylesheet" href="dist/output.css">
  <script src="dist/js/script.js" defer></script>
  <title>Document</title>
</head>

<body class="bg-slate-100">

  <header class="relative top-0 right-0 bg-white flex flex-col items-center justify-center w-full py-5 shadow-shalg mb-8">
    <span class="text-gray-900 text-3xl font-bold">مرحبا بك في نظام تسيير الديون</span>
    <nav class=" flex w-full px-6 justify-start items-center text-right mt-3">
      <a class="text-white bg-gray-800 p-2 rounded-md font-semibold text-base ml-3 hover:bg-gray-600" href="dist/archived_clients.php">إسترجاع العملاء المحذوفين</a>
      <a class="text-white bg-gray-800 p-2 rounded-md font-semibold text-base ml-3 hover:bg-gray-600" href="dist/products.php">المنتجات</a>
    </nav>
  </header>


  <h1 class="text-gray-900 text-3xl font-bold relative mb-3 mx-5">كل العملاء :</h1>

  <form action="index.php" method="POST" class="flex items-center justify-between w-[90%] mx-5 bg-white shadow-shalg rounded-md">
      <div class=" w-[85%] m-4 ml-0 p-2">
        <label for="name" class="text-gray-900 text-2xl font-semibold ml-2 ">إضف عملاء جدد:</label>
        <input type="text" id="name" name="name" class="text-gray-800 text-xl font-semibold w-[78%] border-b-2 p-2" placeholder="إكتب إسم العميل هنا..." required>
      </div>
      <button type="submit" name="add_client" class="m-4 mr-0 p-2 text-gray-50 text-xl font-semibold bg-green-500">إضف العميل</button>
  </form>

  <hr>

  <div class=" relative w-[100%] my-6 flex items-center justify-center overflow-x-auto">
    <table class=" w-[90%] text-sm text-right rtl:text-right text-gray-50 font-medium border-separate border-spacing-y-3 border-spacing-x-0 shadow-md rounded-md" >
        <thead class="uppercase bg-gray-800 text-white font-semibold text-xl">
            <tr>
                <th scope="col" class="px-6 py-3">
                    إسم العميل
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    الصافي
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    الحالة
                </th>
                <th scope="col" class="px-6 py-3">
                </th>
            </tr>
        </thead>
        <tbody>
          <?php foreach ($clients as $client): ?>
              <tr class="scale-h border-b bg-playstation  border-gray-800 transition-all duration-500 hover:bg-blue-900  font-semibold"
              onclick="window.location.href='dist/client_debts.php?id=<?php echo $client['id']; ?>'">
                <th scope="row" class=" px-6 py-4 font-bold text-xl text-gray-900 whitespace-nowrap dark:text-white">
                  <?php echo htmlspecialchars($client['name']); ?>
                </th>
                <td class="px-6 text-xl py-4 text-center bg-gray-800">
                  <span ><?php echo htmlspecialchars($client['balance']); ?></span>
                </td>
                <td class="px-6 py-4 bg-gray-800 text-xl text-center">
                  <span >
                    <?php 
                    // Determine the status based on balance and total debts
                    if ($client['balance'] == 0 && $client['total_debts'] == 0) {
                        echo 'جديد';
                    } elseif ($client['balance'] == 0 && $client['total_debts'] > 0) {
                        echo 'تم الدفع';
                    } else {
                        echo 'نشط'; // or any other status you want to show for active clients
                    }
                    ?>
                  </span>
                </td>
                <td class="px-6 bg-gray-800 text-center">
                <form action="index.php" method="POST">
                  <input type="hidden" name="delete_client_id" value="<?php echo $client['id']; ?>">
                  <button class="bg-red-600 px-6 py-1 font-medium text-xl rounded-md hover:bg-red-700" type="submit" name="delete_client">حذف <i class="fa-solid fa-trash"></i></button>

                </form>
                </td>
              </tr>
          <?php endforeach; ?>

        </tbody>
    </table>
  </div>



</body>

</html>