<?php
include 'db.php'; // Include your database connection

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    
    // Prepare and execute the SQL statement to fetch product names and prices
    $stmt = $conn->prepare("SELECT name, unit_price FROM products WHERE name LIKE :query");
    $stmt->execute(['query' => '%' . $query . '%']);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the results as a JSON array
    echo json_encode($products);
}
?>