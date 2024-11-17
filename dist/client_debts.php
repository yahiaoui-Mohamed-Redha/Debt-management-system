<?php
include 'db.php';
session_start(); // Start the session

$client_id = $_GET['id'];

if (isset($_POST['add_debt'])) {
    $client_id = $_POST['client_id'];
    $quantity = $_POST['quantity'];
    $new_product_name = $_POST['new_product_name'];
    $new_unit_price = $_POST['new_unit_price'];

    // Step 1: Check if the product already exists
    $checkProductQuery = "SELECT id FROM products WHERE name = :name";
    $stmt = $conn->prepare($checkProductQuery);
    $stmt->execute(['name' => $new_product_name]);
    $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingProduct) {
        // Product exists, get the product ID
        $product_id = $existingProduct['id'];
    } else {
        // Product does not exist, insert it into the products table
        $addProductQuery = "INSERT INTO products (name, unit_price) VALUES (:name, :unit_price)";
        $stmt = $conn->prepare($addProductQuery);
        $stmt->execute([
            'name' => $new_product_name,
            'unit_price' => $new_unit_price
        ]);
        // Get the ID of the newly created product
        $product_id = $conn->lastInsertId();
    }

    // Step 2: Calculate total price for the debt
    $total_price = $quantity * $new_unit_price;

    // Step 3: Add the debt to the debts table
    $addDebtQuery = "INSERT INTO debts (client_id, product_id, quantity, unit_price, total_price, date_added) 
                     VALUES (:client_id, :product_id, :quantity, :unit_price, :total_price, NOW())";
    $stmt = $conn->prepare($addDebtQuery);
    $stmt->execute([
        'client_id' => $client_id,
        'product_id' => $product_id,
        'quantity' => $quantity,
        'unit_price' => $new_unit_price,
        'total_price' => $total_price
    ]);

    // Store the client_id in the session
    $_SESSION['client_id'] = $client_id;

    // Redirect back to the client debts page
    header("Location: client_debts.php?id=" . $client_id);
    exit();
}

if (isset($_POST['update_debt'])) {
    $debt_id = $_POST['debt_id'];
    $product_name = trim($_POST['new_product_name']);
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']); // This should be the unit price
    $total_price = floatval($_POST['total_price']); // Ensure this is the total price

    // Check for valid inputs
    if (empty($product_name)) {
        echo 'Error: Product name is required. ';
    } elseif ($quantity <= 0) {
        echo 'Error: Quantity must be greater than 0. ';
    } elseif ($unit_price <= 0) {
        echo 'Error: Price must be greater than 0. ';
    } else {
        // Update the debt in the database
        $stmt = $conn->prepare("UPDATE debts SET product_name = ?, quantity = ?, unit_price = ?, total_price = ? WHERE id = ? AND client_id = ?");
        $stmt->execute([$product_name, $quantity, $unit_price, $total_price, $debt_id, $client_id]); // Ensure total price is included
        echo 'Debt updated successfully! ';
    }
}

// Fetch client details
$query = "SELECT * FROM clients WHERE id = :client_id";
$stmt = $conn->prepare($query);
$stmt->execute(['client_id' => $client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);


// Fetch debts with product names
$query_debts = " 
    SELECT debts.*, products.name AS product_name 
    FROM debts 
    JOIN products ON debts.product_id = products.id 
    WHERE debts.client_id = :client_id 
    ORDER BY added_time DESC, date_added DESC";
$stmt_debts = $conn->prepare($query_debts);
$stmt_debts->execute(['client_id' => $client_id]);
$debts = $stmt_debts->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['add_payment'])) {
    $client_id = $_POST['client_id'];
    $amount = $_POST['amount'];
    $payment_date = date('Y-m-d'); // Automatically set to current date

    try {
        // Insert the payment into the payments table
        $query = "INSERT INTO payments (client_id, amount, payment_date, added_time) VALUES (:client_id, :amount, :payment_date, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            'client_id' => $client_id,
            'amount' => $amount,
            'payment_date' => $payment_date,
        ]);

        // Redirect back to the client debts page
        header("Location: client_debts.php?id=" . $client_id);
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Update payment
if (isset($_POST['update_payment'])) { 
    $payment_id = $_POST['payment_id']; 
    $payment_amount = floatval($_POST['payment_amount']); 

    if ($payment_amount > 0) { 
        $stmt = $conn->prepare("UPDATE payments SET amount = ? WHERE id = ? AND client_id = ? "); 
        $stmt->execute([$payment_amount, $payment_id, $client_id]); 
        echo 'Payment updated successfully! '; 
    } else { 
        echo "<p>Error: Invalid payment amount.</p>"; 
    } 
}


// Fetch payments
$query_payments = "SELECT * FROM payments WHERE client_id = :client_id ORDER BY added_time DESC, payment_date DESC";
$stmt_payments = $conn->prepare($query_payments);
$stmt_payments->execute(['client_id' => $client_id]);
$payments = $stmt_payments->fetchAll(PDO::FETCH_ASSOC);

// Calculate total debts 
$total_debts = 0; 
foreach ($debts as $debt) { 
    $total_debts += $debt['total_price']; 
} 

// Calculate total payments 
$total_payments = 0; 
foreach ($payments as $payment) { 
    $total_payments += $payment['amount']; 
} 

// Calculate balance and invert its sign
$balance = -($total_debts - $total_payments); // Inverted balance calculation

// Delete payment
if (isset($_POST['delete_payment'])) {
    $payment_id = $_POST['payment_id'];
    $stmt = $conn->prepare("DELETE FROM payments WHERE id = ? AND client_id = ?");
    $stmt->execute([$payment_id, $client_id]);
    echo "Payment deleted successfully";
    exit;
}

// Delete debt
if (isset($_POST['delete_debt'])) {
    $debt_id = $_POST['debt_id'];
    $stmt = $conn->prepare("DELETE FROM debts WHERE id = ? AND client_id = ?");
    $stmt->execute([$debt_id, $client_id]);
    echo "Debt deleted successfully";
    exit;
}

// New Operation logic
if (isset($_POST['new_operation'])) {
    // Step 1: Save current debts and payments to the old operations table
    $operation_number = 'OP-' . time(); // Example operation number, you can customize it

    // Insert into old operations
    $stmt = $conn->prepare("INSERT INTO old_operations (client_id, operation_number, date_from, date_to) VALUES (:client_id, :operation_number, NOW(), NOW())");
    $stmt->execute(['client_id' => $client_id, 'operation_number' => $operation_number]);
    $old_operation_id = $conn->lastInsertId(); // Get the ID of the newly created old operation

    // Save debts
    foreach ($debts as $debt) {
        $stmt = $conn->prepare("INSERT INTO operation_debts_details (operation_id, product_name, unit_price, quantity, total_price, date_added) VALUES (:operation_id, :product_name, :unit_price, :quantity, :total_price, :date_added)");
        $stmt->execute([
            'operation_id' => $old_operation_id,
            'product_name' => $debt['product_name'],
            'unit_price' => $debt['unit_price'],
            'quantity' => $debt['quantity'],
            'total_price' => $debt['total_price'],
            'date_added' => $debt['date_added'], // Add this line to include date_added
        ]);
    }

    // Save payments
    foreach ($payments as $payment) {
        $stmt = $conn->prepare("INSERT INTO operation_payment_details (operation_id, payment_date, amount) VALUES (:operation_id, :payment_date, :amount)");
        $stmt->execute([
            'operation_id' => $old_operation_id,
            'payment_date' => $payment['payment_date'],
            'amount' => $payment['amount'],
        ]);
    }

    // Step 2: Clear current debts and payments
    $stmt = $conn->prepare("DELETE FROM debts WHERE client_id = ?");
    $stmt->execute([$client_id]);

    $stmt = $conn->prepare("DELETE FROM payments WHERE client_id = ?");
    $stmt->execute([$client_id]);

    // Redirect to the same page to refresh the data
    header("Location: client_debts.php?id=" . $client_id);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/x-icon" href="/assets/icon/logo.svg">
  <!-- font-awesomes -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- link css -->
  <link rel="stylesheet" href="/css/style.css" />
  <link rel="stylesheet" href="output.css">
  <script src="dist/js/script.js" defer></script>
  <title>systéme de crédit</title>

  <style>
    .table {
    direction: ltr;
    border-collapse: collapse;
    margin: 12px;
    }

    .th, 
    .td, 
    .td, 
    .input {
        border: 1px solid black;
        background-color: white;
        padding: 5px 8px;
        font-size: 1.1em;
        text-align: left;
        font-size: 1em
    }

    .th {
        background-color: hsl(240, 76%, 97%);
    }

    #product_suggestions {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    border: 1px solid #cacaca;
    color: black;
    padding: 10px;
    width: 200px;
    top: 80px;
    }

    #product_suggestions div {
        padding: 10px;
        cursor: pointer;
    }

    #product_suggestions div:hover {
        background-color: #dbdbdb;
    }

    .print {
            display: none;
    }

    /* .dont-show {
        display: none;
    } */

    

  </style>

</head>
<body dir="rtl" class="bg-slate-100">

    <header class=" dont-show z-[100] fixed top-0 right-0 bg-white flex items-center justify-between w-full py-5 shadow-shalg mb-8 p-5">
        <h1 class="font-semibold text-2xl text-gray-900">إسم العميل : <?php echo htmlspecialchars($client['name']); ?></h1>
        <div class="flex justify-between items-center">
            <a class="flex items-center justify-between text-white bg-gray-600 w-[8.5rem] p-[10px] rounded-md font-semibold text-base hover:bg-gray-400 transition-all  ml-4" href="old_op.php?id=<?php echo $client_id; ?>">العمليات القديمة</a>
            <a class="flex items-center justify-between text-white bg-gray-800 w-[8.5rem] p-[10px] rounded-md font-semibold text-base hover:bg-gray-600 transition-all" href="/unit-15/index.php"><span class="mr-1">العودة</span> <i class="fa-solid fa-arrow-left ml-1"></i> </a>
        </div>
    </header>

    <nav dir="ltr" class=" dont-show z-[100]  fixed flex justify-between items-center flex-col left-0 top-20 bg-white h-[88%] p-5">

        <table dir="ltr" class="text-right text-base text-gray-900 font-semibold border-separate border-spacing-y-2 border-spacing-x-0">
            <tr class="border-b border-gray-800">
                <th class="uppercase bg-gray-800 text-white rounded-t-lg rounded-tr-lg p-1">: إجمالي الديون</th>
            </tr>
            <tr>
                <td class="bg-debt1 rounded-b-lg rounded-br-lg font-bold text-xl p-2"><?php echo number_format($total_debts, 2); ?></td>
            </tr>
            <tr class="border-b border-gray-800">
                <th class="uppercase bg-gray-800 text-white rounded-t-lg rounded-tr-lg p-1">: إجمالي المدفوعات</th>
            </tr>
            <tr>
                <td class="bg-debt1 rounded-b-lg rounded-br-lg font-bold text-xl p-2"><?php echo number_format($total_payments, 2); ?></td>
            </tr>
            <tr class="border-b border-gray-800">
                <th class="uppercase bg-gray-800 text-white rounded-t-lg rounded-tr-lg p-1">: الصافي</th>
            </tr>
            <tr>
                <td class="bg-debt1 rounded-b-lg rounded-br-lg font-bold text-xl p-2"><?php echo number_format($balance, 2); ?></td>
            </tr>
        </table>

        <div>
            <?php if ($balance >= 0 && $total_debts > 0): ?>
                <form action="" method="POST">
                    <button type="submit" dir="rtl" class="flex items-center justify-between text-white bg-blue-600 w-[8.5rem] p-[10px] rounded-md font-semibold text-base hover:bg-blue-700 transition-all mb-4 text-right" name="new_operation">عملية جديدة <i class="fa-solid fa-plus"></i></button>
                </form>
            <?php endif; ?>
            <button type="button" dir="rtl" class="print-btn flex items-center justify-between text-white bg-green-600 w-[8.5rem] p-[10px] rounded-md font-semibold text-base hover:bg-green-700 transition-all mb-4 text-right" onclick="printContent()">إطبع أو إحفظ <i class="fa-solid fa-print"></i></button>
        </div>

    </nav>



    <div class="dont-show relative top-[8rem] mb-10">

        <h2 class="text-gray-900 text-2xl font-bold m-4 ">إضافت الديون</h2>

        <form action="" method="POST" class=" relative flex justify-between items-center bg-debt1 text-gray-900 w-[80%] p-2 rounded mr-3">
            <input type="hidden" id="product_name" name="client_id" value="<?php echo $client_id; ?>">

            <div class="flex flex-col items-start justify-between">
                <label for="name" class="mb-2 font-semibold text-gray-900">اسم المنتح:</label>
                <input class="p-1 text-gray-900 font-semibold bg-white rounded" type="text" id="new_product_name"  name="new_product_name" placeholder="إسم المنتج" autocomplete="off" required>
                <div id="product_suggestions" class="suggestions"></div>
            </div>

            <div class="flex flex-col items-start justify-between">
                <label for="quantity" class="mb-2 font-semibold text-gray-900">الكمية:</label>
                <input  class="p-1 text-gray-900 font-semibold bg-white rounded" type="number" id="quantity" name="quantity" value="1" min="1" required>
            </div>

            <div class="flex flex-col items-start justify-between">
                <label for="price" class="mb-2 font-semibold text-gray-900">السعر:</label>
                <input class="p-1 text-gray-900 font-semibold bg-white rounded" type="number" id="price" name="new_unit_price" placeholder="السعر" step="0.01" required>
            </div>

            <button class="text-white bg-gray-900 font-semibold text-base px-5 hover:bg-gray-800 py-3 rounded" type="submit" name="add_debt">أضف منتج</button>
        </form>


        <section class="operation" id="operation">
            <table class="table w-[80%]">
                <tr class="tr">
                    <th class="th">إسم المنتح</th>
                    <th class="th">سعر الوحدة</th>
                    <th class="th">الكمية</th>
                    <th class="th">السعر الإجمالي</th>
                    <th class="th">التريخ</th>
                    <th class="th"></th>
                </tr>
                <?php foreach ($debts as $debt): ?>
                <tr class="tr">
                    <td class="td">
                        <input type="text" name="product_name_<?php echo $debt['id']; ?>" value="<?php echo htmlspecialchars($debt['product_name']); ?>" readonly>
                    </td>
                    <td class="td">
                        <input type="number" name="price_<?php echo $debt['id']; ?>" value="<?php echo htmlspecialchars($debt['unit_price']); ?>" readonly>
                    </td>
                    <td class="td">
                        <input type="number" name="quantity_<?php echo $debt['id']; ?>" value="<?php echo htmlspecialchars($debt['quantity']); ?>" readonly>
                    </td>
                    <td class="td total_price total_price_<?php echo $debt['id']; ?>">
                        <?php echo htmlspecialchars($debt['total_price']); ?>
                    </td>
                    <td class="td">
                        <?php echo htmlspecialchars($debt['date_added']); ?></td>
                    <td class="td">
                        <button type="button" class="modify-btn px-4 py-1 rounded text-white bg-blue-600 hover:shadow-shalg hover:bg-blue-700 transition-all ml-3" data-debt-id="<?php echo $debt['id']; ?>"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" class="save-btn px-4 py-1 rounded text-white bg-green-500  hover:shadow-shalg hover:bg-green-700 transition-all ml-3" data-debt-id="<?php echo $debt['id']; ?>" style="display: none;"><i class="fa-solid fa-check"></i></button>
                        <button type="button" class="delete-btn px-4 py-1 rounded text-white bg-red-600 hover:shadow-shalg hover:bg-red-700 transition-all ml-3" data-debt-id="<?php echo $debt['id']; ?>"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>



        <hr class="bg-gray-950 max-h-0.5 my-4">

        <h2 class="text-gray-900 text-2xl font-bold mb-4">إضافت المدفوعات</h2>
        <form action="" method="POST" class="flex justify-between items-center bg-debt1 text-gray-900 w-[80%] p-2 rounded mr-3">
            <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
            <input type="number" name="amount" placeholder="Amount" required class="w-[85%] p-1 text-gray-900 font-semibold bg-white rounded">
            <button type="submit" name="add_payment" class="text-white bg-gray-900 font-semibold text-base py-2 rounded px-10 ">إدفع</button>
        </form>

        <section class="operation mb-10" id="operation">
            <table class="table  w-[80%]">
                <tr class="tr">
                    <th class="th">المبلغ المدفوع</th>
                    <th class="th">التريخ</th>
                    <th class="th"></th>
                </tr>
                <?php foreach ($payments as $payment): ?>
                <tr class="tr">
                    <td class="td">
                        <input type="number" name="payment_amount_<?php echo $payment['id']; ?>" value="<?php echo htmlspecialchars($payment['amount']); ?>" readonly>
                    </td>
                    <td class="td"><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                    <td class="td">
                        <button type="button" class="modify-payment-btn px-4 py-1 rounded text-white bg-blue-600 hover:shadow-shalg hover:bg-blue-700 transition-all ml-3" data-debt-id="<?php echo $payment['id']; ?>"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" class="save-payment-btn  px-4 py-1 rounded text-white bg-green1 hover:shadow-shalg hover:bg-red-700 transition-all ml-3 "  data-debt-id="<?php echo $payment['id']; ?>" style="display: none;"><i class="fa-solid fa-check"></i></button>
                        <button type="button" name="delete_payment" class="delete_payment px-4 py-1 rounded text-white bg-red-600 hover:shadow-shalg hover:bg-red-700 transition-all ml-3" data-debt-id="<?php echo $payment['id']; ?>"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>

    </div>

    <div dir="ltr" class="print bg-white p-10">
        <div class="">
            <div class=" flex items-end justify-between">
                <div class=" flex justify-between items-start">
                    <div>
                        <h1 class=" font-medium text-base text-left">N°Téléphone : </h1>
                        <h1 class=" font-medium text-base text-left">De : </h1>
                        <h1 class="font-semibold text-base text-left">POUR : </h1>
                    </div>

                    <div>
                        <h2 class=" font-medium text-base text-right">0560-77-51-22</h2>
                        <h2 class=" font-medium text-base text-right">YAHIAOUI Mohamed</h2>
                        <h2 class=" font-medium text-base text-right"><?php echo htmlspecialchars($client['name']); ?></h2>
                    </div>
                </div>
                <div id="current-date" class="font-semibold text-base">Le : </div>
            </div>
        
            <div class="relative w-full my-6 items-center">
                <section class="operation" id="operation">
                    <table class="table w-[100%] border-dashed border-y-2 border-gray-700">
                        <tr class=" text-left font-semibold text-base">
                            <th style="text-align: left;" class=" text-left">Nom du produit</th>
                            <th class=" text-center" >Prix unitaire</th>
                            <th class=" text-center" >Quantité</th>
                            <th class=" text-center" >Prix total</th>
                            <th class=" text-center" >Date</th>
                        </tr>
                        <?php foreach ($debts as $debt): ?>
                        <tr class=" text-left font-medium  text-sm">
                            <td class=" text-left">
                                <?php echo htmlspecialchars($debt['product_name']); ?>
                            </td>
                            <td class=" text-center">
                                <?php echo htmlspecialchars($debt['unit_price'] , 2); ?> da
                            </td>
                            <td class=" text-center">
                                <?php echo htmlspecialchars($debt['quantity']); ?>
                            </td>
                            <td class=" total_price text-center total_price_<?php echo $debt['id']; ?>">
                                <?php echo htmlspecialchars($debt['total_price'] , 2); ?> da
                            </td>
                            <td class=" text-center">
                                <?php echo htmlspecialchars($debt['date_added']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </section>
            </div>

            <div class="flex items-start justify-between font-medium text-base">
                <div class="flex items-center justify-between">
                    <div class=" text-left mr-4">
                        <h2>TATAL CRÉDIT : </h2>
                        <h2>LE RESTE : </h2>
                    </div>
                    <div class="b1 text-right">
                        <h2><?php echo number_format($total_debts, 2); ?></h2>
                        <h2><?php echo number_format($balance, 2); ?></h2>
                    </div>
                </div>
                <div class="flex items-center justify-between">

                    <div class=" text-left mr-4">
                        <h2>MONTANT TOTAL : </h2>
                        <h2>NET A PAYER : </h2>
                    </div>

                    <div class=" text-right">
                        <h2><?php echo number_format($total_payments, 2); ?></h2>
                        <h2><?php echo number_format($total_debts, 2); ?></h2>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>

        const deleteBtns = document.querySelectorAll('.delete-btn');

        deleteBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const debtId = btn.dataset.debtId;
                if (confirm('Are you sure you want to delete this debt?')) {
                    const formData = new FormData();
                    formData.append('delete_debt', 'true');
                    formData.append('debt_id', debtId);

                    fetch('', {
                        method: 'POST',
                        body: formData,
                    })
                    .then((response) => response.text())
                    .then((message) => {
                        console.log(message);
                        location.reload(); // Refresh the page after successful deletion
                    })
                    .catch((error) => {
                        console.error(error);
                    });
                }
            });
        });

        const deletePaymentBtns = document.querySelectorAll('.delete_payment');

        deletePaymentBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const paymentId = btn.dataset.debtId; // Ensure this gets the correct data attribute
                if (confirm('Are you sure you want to delete this payment?')) {
                    const formData = new FormData();
                    formData.append('delete_payment', 'true');
                    formData.append('payment_id', paymentId);

                    fetch('', {
                        method: 'POST',
                        body: formData,
                    })
                    .then((response) => response.text())
                    .then((message) => {
                        console.log(message);
                        location.reload(); // Refresh the page after successful deletion
                    })
                    .catch((error) => {
                        console.error(error);
                    });
                }
            });
        });

        // JavaScript functionality for deleting debts and payments
        document.addEventListener("DOMContentLoaded", function() {
            const deleteBtns = document.querySelectorAll(".delete-btn");
            deleteBtns.forEach((btn) => {
                btn.addEventListener("click", function() {
                    const debtId = this.getAttribute("data-debt-id");
                    // Perform the deletion logic here
                });
            });

            const deletePaymentBtns = document.querySelectorAll(".delete-payment-btn");
            deletePaymentBtns.forEach((btn) => {
                btn.addEventListener("click", function() {
                    const paymentId = this.getAttribute("data-payment-id");
                    // Perform the deletion logic here
                });
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            const modifyBtns = document.querySelectorAll('.modify-btn');
            const saveBtns = document.querySelectorAll('.save-btn');

            modifyBtns.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const debtId = btn.dataset.debtId;
                    const productNameInput = document.querySelector(`input[name="product_name_${debtId}"]`);
                    const quantityInput = document.querySelector(`input[name="quantity_${debtId}"]`);
                    const priceInput = document.querySelector(`input[name="price_${debtId}"]`);
                    const totalPriceCell = document.querySelector(`.total_price_${debtId}`); // Ensure this references the correct total price element
                    const saveBtn = btn.nextElementSibling;

                    // Make inputs editable
                    productNameInput.readOnly = false;
                    quantityInput.readOnly = false;
                    priceInput.readOnly = false;

                    // Toggle button visibility
                    btn.style.display = 'none';
                    saveBtn.style.display = 'inline-block';

                    // Function to recalculate total price
                    function recalculateTotalPrice() {
                        const quantity = parseFloat(quantityInput.value) || 0;
                        const unitPrice = parseFloat(priceInput.value) || 0;
                        const totalPrice = quantity * unitPrice;
                        totalPriceCell.textContent = totalPrice.toFixed(2); // Update total price display
                    }

                    // Add event listeners to recalculate total price on input change
                    quantityInput.addEventListener('input', recalculateTotalPrice);
                    priceInput.addEventListener('input', recalculateTotalPrice);
                });
            });

            saveBtns.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const debtId = btn.dataset.debtId;
                    const productNameInput = document.querySelector(`input[name="product_name_${debtId}"]`);
                    const quantityInput = document.querySelector(`input[name="quantity_${debtId}"]`);
                    const priceInput = document.querySelector(`input[name="price_${debtId}"]`);
                    const modifyBtn = btn.previousElementSibling;

                    const productName = productNameInput.value;
                    const quantity = quantityInput.value;
                    const unitPrice = priceInput.value;

                    const totalPrice = (quantity * unitPrice).toFixed(2);

                    if (quantity > 0 && unitPrice > 0) {
                        const formData = new FormData();
                        formData.append('update_debt', 'true');
                        formData.append('debt_id', debtId);
                        formData.append('new_product_name', productName);
                        formData.append('quantity', quantity);
                        formData.append('unit_price', unitPrice);
                        formData.append('total_price', totalPrice);

                        fetch('', {
                            method: 'POST',
                            body: formData,
                        })
                        .then((response) => response.text())
                        .then((message) => {
                            console.log(message);
                            location.reload(); // Refresh the page after successful update
                        })
                        .catch((error) => {
                            console.error(error);
                        });

                        // Make inputs read-only again
                        productNameInput.readOnly = true;
                        quantityInput.readOnly = true;
                        priceInput.readOnly = true;

                        // Toggle button visibility
                        btn.style.display = 'none';
                        modifyBtn.style.display = 'inline-block';
                    } else {
                        alert('Error: Quantity and price must be greater than zero.');
                    }
                });
            });
        });


        document.addEventListener("DOMContentLoaded", function() { 
            const modifyPaymentBtns = document.querySelectorAll('.modify-payment-btn'); 
            const savePaymentBtns = document.querySelectorAll('.save-payment-btn'); 

            modifyPaymentBtns.forEach((btn) => { 
                btn.addEventListener('click', () => { 
                    const paymentId = btn.dataset.debtId; 
                    const paymentAmountInput = document.querySelector(`input[name="payment_amount_${paymentId}"]`); 
                    const saveBtn = btn.nextElementSibling; 

                    paymentAmountInput.readOnly = false; // Make the input editable 
                    btn.style.display = 'none'; // Hide modify button 
                    saveBtn.style.display = 'inline-block'; // Show save button 
                }); 
            }); 

            savePaymentBtns.forEach((btn) => { 
                btn.addEventListener('click', () => { 
                    const paymentId = btn.dataset.debtId; 
                    const paymentAmountInput = document.querySelector(`input[name="payment_amount_${paymentId}"]`); 
                    const modifyBtn = btn.previousElementSibling; 

                    const paymentAmount = paymentAmountInput.value; 

                    if (paymentAmount > 0) { 
                        const formData = new FormData(); 
                        formData.append('update_payment', 'true'); 
                        formData.append('payment_id', paymentId); 
                        formData.append('payment_amount', paymentAmount); 

                        fetch('', { 
                            method: 'POST', 
                            body: formData, 
                        }) 
                        .then((response) => response.text()) 
                        .then((message) => { 
                            console.log(message); 
                            location.reload(); // Refresh the page after successful update 
                        }) 
                        .catch((error) => { 
                            console.error(error); 
                        }); 

                        paymentAmountInput.readOnly = true; // Make inputs read-only again 
                        btn.style.display = 'none'; // Hide save button 
                        modifyBtn.style.display = 'inline-block'; // Show modify button 
                    } else { 
                        alert('Error: Invalid payment amount.'); 
                    } 
                }); 
            }); 
        });


        document.addEventListener("DOMContentLoaded", function() {
            const productNameInput = document.getElementById("new_product_name");
            const productPriceInput = document.getElementById("price");
            const productQuantityInput = document.getElementById("quantity");
            const productSuggestions = document.getElementById("product_suggestions");
            let currentIndex = -1; // Track the current index of the highlighted suggestion

            productNameInput.addEventListener("input", function() {
                const query = this.value;

                // Clear previous suggestions
                productSuggestions.innerHTML = '';

                // If the input is empty, do not fetch suggestions
                if (query.length < 1) {
                    productSuggestions.style.display = 'none';
                    return;
                }

                const productName = query.trim();
                // Fetch product suggestions from the server
                if (productName.length > 0) {
                    fetch(`fetch_product_suggestions.php?query=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            // Check if there are suggestions
                            if (data.length > 0) {
                                productSuggestions.style.display = 'block';
                                data.forEach(product => {
                                    const suggestionDiv = document.createElement("div");
                                    suggestionDiv.textContent = product.name; // Display product name
                                    suggestionDiv.dataset.price = product.unit_price; // Store product price

                                    // Add click event to set name and price
                                    suggestionDiv.addEventListener("click", function() {
                                        productNameInput.value = product.name; // Set the input value to the selected suggestion
                                        productPriceInput.value = product.unit_price; // Set the price input with the product price
                                        productSuggestions.style.display = 'none'; // Hide suggestions after selection
                                        productQuantityInput.focus(); // Focus on quantity input
                                    });

                                    productSuggestions.appendChild(suggestionDiv);
                                });
                            } else {
                                productSuggestions.style.display = 'none'; // Hide if no suggestions
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching product suggestions:', error);
                            productSuggestions.style.display = 'none'; // Hide suggestions on error
                        });
                } else {
                    productSuggestions.style.display = 'none';
                }
            });

            // Function to handle keyboard navigation
            productNameInput.addEventListener('keydown', function(event) {
                const suggestions = productSuggestions.children;

                if (event.key === 'ArrowDown') {
                    // Move down the suggestions
                    currentIndex = (currentIndex + 1) % suggestions.length;
                    highlightSuggestion(suggestions);
                } else if (event.key === 'ArrowUp') {
                    // Move up the suggestions
                    currentIndex = (currentIndex - 1 + suggestions.length) % suggestions.length;
                    highlightSuggestion(suggestions);
                } else if (event.key === 'Enter') {
                    // Prevent form submission and select the highlighted suggestion
                    event.preventDefault(); // Prevent the form from being submitted
                    if (currentIndex >= 0 && currentIndex < suggestions.length) {
                        const selectedSuggestion = suggestions[currentIndex];
                        productNameInput.value = selectedSuggestion.textContent; // Set product name
                        productPriceInput.value = selectedSuggestion.dataset.price; // Set product price
                        productSuggestions.style.display = 'none'; // Hide suggestions after selection
                        productQuantityInput.focus(); // Move focus to quantity input
                        currentIndex = -1; // Reset the current index
                    }
                }
            });

            // Focus on quantity input and submit form when Enter is pressed
            productQuantityInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    // Submit the form only if quantity is greater than 0
                    const quantityValue = parseInt(productQuantityInput.value);
                    if (quantityValue > 0) {
                        document.querySelector('form').submit(); // Assuming your form is the first form on the page
                    } else {
                        alert("Please enter a quantity greater than 0.");
                    }
                }
            });

            function highlightSuggestion(suggestions) {
                // Remove highlight from all suggestions
                for (let i = 0; i < suggestions.length; i++) {
                    suggestions[i].style.backgroundColor = ''; // Reset background
                }
                // Highlight the current suggestion
                if (currentIndex >= 0 && currentIndex < suggestions.length) {
                    suggestions[currentIndex].style.backgroundColor = '#dbdbdb'; // Highlight color
                }
            }

            // Click outside to close suggestions
            document.addEventListener('click', function(event) {
                // Check if the click is outside the input and suggestions
                if (!productNameInput.contains(event.target) && !productSuggestions.contains(event.target)) {
                    productSuggestions.style.display = 'none'; // Hide suggestions
                    currentIndex = -1; // Reset the current index
                }
            });
        });

        function printContent() {
            // Get the print div
            const printDiv = document.querySelector('.print');
            
            // Hide all other content
            document.body.childNodes.forEach(node => {
                if (node.nodeType === Node.ELEMENT_NODE && !node.classList.contains('print')) {
                    node.style.display = 'none';
                }
            });

            // Show the print div
            printDiv.style.display = 'block';

            // Use window.print() to print
            window.print();

            // After printing, restore the original display
            document.body.childNodes.forEach(node => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    node.style.display = ''; // Reset the display property
                }
            });
        }
        

        const today = new Date();
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').innerText = today.toLocaleDateString(undefined, options);

        // Assuming balance is calculated and stored in a variable
        let balance = total_debts - total_payments;

        // Check if the balance is positive or negative
        if (balance > 0) {
            balance = -balance; // Make it negative
        } else if (balance < 0) {
            balance = -balance; // Make it positive
        }

        // Now you can use the adjusted balance value as needed
        console.log(balance); // For debugging, to see the adjusted balance

    </script>

    
</body>

</html>