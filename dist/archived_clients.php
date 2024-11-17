<?php
include 'db.php';
session_start();

// Restore client
if (isset($_POST['restore_client'])) {
    $client_id = $_POST['restore_client_id'];
    $stmt = $conn->prepare("UPDATE clients SET is_archived = 0 WHERE id = :id");
    $stmt->bindParam(':id', $client_id, PDO::PARAM_INT);
    $stmt->execute();
    header('Location: archived_clients.php'); // Redirect to the same page to refresh the list
    exit;
}

// Fetch all archived clients
$stmt = $conn->prepare("SELECT * FROM clients WHERE is_archived = 1");
$stmt->execute();
$archived_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);


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

    <header class="relative top-0 right-0 bg-white flex items-center justify-between w-full py-5 shadow-shalg mb-8 p-5">
        <h1 class="font-bold text-2xl text-gray-900">إسترجاع العملاء المحذوفين</h1>
        <div class="flex justify-between items-center">
            <a class="flex items-center justify-between text-white bg-gray-800 w-[8.5rem] p-[10px] rounded-md font-semibold text-base hover:bg-gray-600 transition-all" href="/unit-15/index.php"><span class="mr-1">العودة</span> <i class="fa-solid fa-arrow-left ml-1"></i> </a>
        </div>
    </header>


  <h1 class="text-gray-900 text-3xl font-bold relative mb-3 mx-5">كل العملاء :</h1>


  <hr>

  <div class=" relative w-full my-6 flex items-center justify-start overflow-x-auto">
    <table class="w-[60%] text-sm text-center rtl:text-right text-gray-50 font-medium border-separate border-spacing-y-3 border-spacing-x-0 shadow-md rounded-md" >
        <thead class="uppercase bg-gray-800 text-white font-semibold text-xl">
            <tr>
                <th scope="col" class="px-6  py-3">
                    إسم العميل
                </th>
                <th scope="col" class="px-6 py-3">
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($archived_clients as $client): ?>
                <?php
                $client_id = $client['id'];
                $client_name = $client['name'];
                ?>
              <tr class="border-b  bg-playstation border-gray-800 hover:bg-blue-700">
                <th scope="row" class="px-6 py-4 font-medium text-xl text-gray-900 whitespace-nowrap dark:text-white">
                <?php echo htmlspecialchars($client['name']); ?>
                </th>
                <td class="px-6">
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <input type="hidden" name="restore_client_id" value="<?php echo $client['id']; ?>">
                        <button class=" bg-green1 px-6 py-1 font-medium text-xl rounded-md hover:bg-green-700" type="submit" name="restore_client">Restore</button>
                    </form>
                </td>
              </tr>
            <?php endforeach; ?>

        </tbody>
    </table>
  </div>




</body>

</html>