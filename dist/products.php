<?php
include 'db.php';


$query = "SELECT * FROM products ORDER BY name ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Delete product
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    echo "Product deleted successfully";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];

    // Prepare and execute the insert statement
    $stmt = $conn->prepare("INSERT INTO products (name, unit_price) VALUES (?, ?)");
    $stmt->execute([$name, $price]);

    // Redirect back to the products page after adding
    header("Location: products.php");
    exit;
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

    <header class="relative top-0 right-0 bg-white flex items-center justify-between w-full py-5 shadow-shalg mb-8 p-5">
        <h1 class="font-bold text-2xl text-gray-900">المنتجات</h1>
        <div class="flex justify-between items-center">
            <a class="flex items-center justify-between text-white bg-gray-800 w-[8.5rem] p-[10px] rounded-md font-semibold text-base hover:bg-gray-600 transition-all" href="/unit-15/index.php"><span class="mr-1">العودة</span> <i class="fa-solid fa-arrow-left ml-1"></i> </a>
        </div>
    </header>


  <h1 class="text-gray-900 text-3xl font-bold relative mb-3 mx-5">كل المنتجات :</h1>

  <form action="" method="POST" class="flex items-center justify-between w-[90%] mx-5 bg-white shadow-shalg rounded-md">
      <div class=" m-4 ml-0 p-2 flex flex-col items-start justify-between ">
        <label for="name" class="text-gray-900 text-2xl font-semibold ml-2 ">أضف منتجات جديدة:</label>
        <input type="text" id="name" name="name" class="text-gray-800 text-xl font-semibold w-[78%] border-b-2 p-2" placeholder="إكتب إسم المنتج..." required>
      </div>

      <div class=" m-4 ml-0 p-2 flex flex-col items-start justify-between">
        <label for="name" class="text-gray-900 text-2xl font-semibold ml-2 ">السعر:</label>

        <input type="number" name="price" id="price" class="text-gray-800 text-xl font-semibold w-[78%] border-b-2 p-2" placeholder="إكتب إسم السعر..." required>
      </div>

      <button class="text-white bg-gray-900 font-semibold text-base px-3 py-5 ml-3  rounded" type="submit" name="add_debt">أضف منتج</button>
  </form>

  <hr>

  <div class=" relative w-full my-6 ml-6 flex items-center justify-start overflow-x-auto">
    <table class=" w-[90%] text-sm text-center rtl:text-right text-gray-50 font-medium border-separate border-spacing-y-3 border-spacing-x-0 shadow-md rounded-md" >
        <thead class="uppercase bg-gray-800 text-white font-semibold text-xl">
            <tr>
                <th style="width: 40%" scope="col" class="px-6  py-3">
                    إسم المنتج
                </th>
                <th style="width: 40%" scope="col" class="px-6  py-3">
                    سعر المنتج
                </th>
                <th scope="col" class="px-6 py-3">
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
              <tr class="border-b  bg-playstation border-gray-800 hover:bg-blue-700">
                <th scope="row" class="px-6 py-4 font-medium text-xl text-gray-900 whitespace-nowrap dark:text-white">
                    <?php echo htmlspecialchars($product['name']); ?>
                </th>
                <th scope="row" class="px-6 py-4 font-medium text-xl text-gray-900 whitespace-nowrap dark:text-white">
                    <?php echo htmlspecialchars($product['unit_price']); ?>
                 </th>
                <td class="px-6 bg-gray-800 ">
                    <button type="button" class="px-4 py-1 text-xl rounded text-white bg-blue-600 hover:shadow-shalg hover:bg-blue-700 transition-all ml-3"><i class="fa-solid fa-pen"></i></button>
                    <button type="button" class="delete-btn px-4 py-1 text-xl rounded text-white bg-red-600 hover:shadow-shalg hover:bg-red-700 transition-all ml-3" data-product-id="<?php echo $product['id']; ?>"><i class="fa-solid fa-trash"></i></button>
                </td>
              </tr>
            <?php endforeach; ?>

        </tbody>
    </table>
  </div>


  <script>
        const deleteBtns = document.querySelectorAll('.delete-btn');

        deleteBtns.forEach((deleteBtn) => {
            deleteBtn.addEventListener('click', (e) => {
                const productId = e.target.dataset.productId;

                fetch('products.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `delete_product=1&product_id=${productId}`
                })
                .then(response => response.text())
                .then((message) => {
                    alert(message);
                    location.reload();
                });
            });
        });

    </script>




</body>

</html>