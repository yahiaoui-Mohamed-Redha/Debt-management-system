<?php
include 'db.php';
session_start();

$operation_id = $_GET['id'];

// Fetch operation details and payment information
$query_details = "
    SELECT od.*, o.client_id, o.operation_number 
    FROM operation_debts_details od 
    JOIN old_operations o ON od.operation_id = o.id 
    WHERE od.operation_id = :operation_id";
$query_payments = "
    SELECT payment_date, amount 
    FROM operation_payment_details 
    WHERE operation_id = :operation_id";

// Fetch operation details
$stmt_details = $conn->prepare($query_details);
$stmt_details->execute(['operation_id' => $operation_id]);
$details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

// Fetch payment details
$stmt_payments = $conn->prepare($query_payments);
$stmt_payments->execute(['operation_id' => $operation_id]);
$payments = $stmt_payments->fetchAll(PDO::FETCH_ASSOC);

// Check if operation details exist
if (empty($details)) {
    die("No details found for this operation.");
}

// Fetch operation info
$operation_info = $details[0];

// Initialize total debts and total payments
$total_debts = 0;
$total_payments = 0;

// Calculate total debts from the details fetched
foreach ($details as $detail) {
    $total_debts += $detail['total_price']; // Assuming 'total_price' is a field in the details
}

// Calculate total payments from the payments fetched
foreach ($payments as $payment) {
    $total_payments += $payment['amount'];
}

// Calculate balance
$balance = -($total_debts - $total_payments); // Inverted balance calculation

// Fetch client information
$client_id = $operation_info['client_id']; // Get client_id from the operation info
$query_client = "
    SELECT name 
    FROM clients 
    WHERE id = :client_id";

$stmt_client = $conn->prepare($query_client);
$stmt_client->execute(['client_id' => $client_id]);
$client = $stmt_client->fetch(PDO::FETCH_ASSOC);

// Check if client information exists
if (!$client) {
    die("No client found for this operation.");
}

// Use htmlspecialchars to safely output the client's name
$client_name = htmlspecialchars($client['name']);

// Fetch debts related to the operation
$query_debts = "
    SELECT product_name, unit_price, quantity, total_price, date_added 
    FROM operation_debts_details 
    WHERE operation_id = :operation_id";

$stmt_debts = $conn->prepare($query_debts);
$stmt_debts->execute(['operation_id' => $operation_id]);
$debts = $stmt_debts->fetchAll(PDO::FETCH_ASSOC);

// Check if debts exist
if (empty($debts)) {
    $debts = []; // Initialize as an empty array if no debts are found
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

    .table1 {
        width: 80%;
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

  </style>

</head>
<body dir="rtl" class="bg-slate-100">

    <header class=" z-[100] fixed top-0 right-0 bg-white flex items-center justify-between w-full py-5 shadow-shalg mb-8 p-5">
        <h1 class="font-semibold text-2xl text-gray-900">إسم العميل : <?php echo htmlspecialchars($client['name']); ?></h1>
        <div class="flex justify-between items-center">
            <a class="flex items-center justify-between text-white bg-gray-600 w-[8.5rem] p-[10px] rounded-md font-semibold text-base hover:bg-gray-400 transition-all  ml-4" href="old_op.php?id=<?php echo $client_id; ?>">العمليات القديمة</a>
            <a class="flex items-center justify-between text-white bg-gray-800 w-[8.5rem] p-[10px] rounded-md font-semibold text-base hover:bg-gray-600 transition-all" href="old_op.php?id=<?php echo $operation_info['client_id']; ?>"><span class="mr-1">العودة</span> <i class="fa-solid fa-arrow-left ml-1"></i> </a>
        </div>
    </header>

    <nav dir="ltr" class=" z-[100]  fixed flex justify-between items-center flex-col left-0 top-20 bg-white h-[88%] p-5">

        <table dir="ltr" class=" w-[80%] text-right text-base text-gray-900 font-semibold border-separate border-spacing-y-2 border-spacing-x-0">
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
            <button type="button" dir="rtl" class="print-btn flex items-center justify-between text-white bg-green-600 w-[8.5rem] p-[10px] rounded-md font-semibold text-base hover:bg-green-700 transition-all mb-4 text-right" onclick="printContent()">إطبع أو إحفظ <i class="fa-solid fa-print"></i></button>
        </div>

    </nav>



    <div class=" relative top-[8rem] mb-10">

        <h2 class="text-gray-900 text-2xl font-bold m-4 ">الديون</h2>

        <section class="operation" id="operation">
            <table class="table w-[80%]">
                <tr class="tr">
                    <th class="th">إسم المنتح</th>
                    <th class="th">سعر الوحدة</th>
                    <th class="th">الكمية</th>
                    <th class="th">السعر الإجمالي</th>
                    <th class="th">التريخ</th>
                </tr>
                <?php foreach ($debts as $debt): ?>
                <tr class="tr">
                    <td class="td">
                        <?php echo htmlspecialchars($detail['product_name']); ?>
                    </td>
                    <td class="td">
                        <?php echo number_format($detail['unit_price'], 2); ?>
                    </td>
                    <td class="td">
                        <?php echo htmlspecialchars($detail['quantity']); ?>
                    </td>
                    <td class="td total_price">
                        <?php echo number_format($detail['total_price'], 2); ?>
                    </td>
                    <td class="td">
                        <?php echo htmlspecialchars($detail['date_added']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>



        <hr class="bg-gray-950 max-h-0.5 my-4">

        <h2 class="text-gray-900 text-2xl font-bold mb-4">المدفوعات</h2>


        <section class="operation mb-10" id="operation">
            <table class="table w-[80%]">
                <tr class="tr">
                    <th class="th">المبلغ المدفوع</th>
                    <th class="th">التريخ</th>
                </tr>
                <?php 
                // Initialize a flag to check if there are any payments
                $has_payments = false; 

                foreach ($payments as $payment): 
                    $has_payments = true; // Set the flag to true if there are payments
                ?>
                <tr class="tr">
                    <td class="td">
                    <?php echo number_format($payment['amount'], 2); ?>
                    </td>
                    <td class="td"><?php echo htmlspecialchars($payment['payment_date']); ?></td>
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
                <div id="current-date" class="font-semibold text-lg">Le : </div>
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
                        <tr class=" text-left font-medium text-xs">
                            <td class=" text-left">
                                <?php echo htmlspecialchars($detail['product_name']); ?>
                            </td>
                            <td class=" text-center">
                                <?php echo htmlspecialchars($detail['unit_price'], 2); ?> da
                            </td>
                            <td class=" text-center">
                                <?php echo htmlspecialchars($detail['quantity']); ?>
                            </td>
                            <td class=" total_price text-center">
                                <?php echo htmlspecialchars($detail['total_price'], 2); ?> da
                            </td>
                            <td class=" text-center">
                                <?php echo htmlspecialchars($detail['date_added']); ?>
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
    </script>

    
</body>
</html>