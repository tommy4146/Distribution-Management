<?php
// edit_stock.php
require 'vendor/autoload.php'; // if you need autoloaded libraries

// Database connection
include 'connection.php';

// Get StockID from GET
$StockID = isset($_GET['StockID']) ? (int)$_GET['StockID'] : 0;
if (!$StockID) die("Stock item not specified.");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = isset($_POST['Quantity']) ? (int)$_POST['Quantity'] : 0;

    $stmt = $conn->prepare("UPDATE stock SET Quantity = ?, LastUpdated = NOW() WHERE StockID = ?");
    $stmt->bind_param("ii", $quantity, $StockID);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: stock_table.php"); // redirect to stock table page
        exit;
    } else {
        echo "Error updating stock: " . $conn->error;
    }
}

// Fetch current stock data
$stmt = $conn->prepare("SELECT ProductName, Quantity FROM stock WHERE StockID = ?");
$stmt->bind_param("i", $StockID);
$stmt->execute();
$stmt->bind_result($productName, $quantity);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Stock — <?= htmlspecialchars($productName) ?></title>
    <link rel="stylesheet" href="add_user.css?v=<?= time(); ?>">
</head>
<body>
<nav class="navbar">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="Add_user.php">Add New Customer</a></li>
        <li><a href="all_orders.php">All Orders</a></li>
        <li><a href="all_comments.php">Comments</a></li>
        <li><a href="stock_table.php">Stock Management</a></li>
    </ul>
</nav>

<div class="page-container">
    <h2>Edit Stock for <?= htmlspecialchars($productName) ?></h2>
    <form method="post">
        <label>Quantity:</label>
        <input type="number" name="Quantity" value="<?= $quantity ?>" required>
        <button type="submit">Update</button>
    </form>
</div>
</body>
</html>
