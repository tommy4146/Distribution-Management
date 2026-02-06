<?php
// update_item_delivered.php
require 'vendor/autoload.php';

$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_POST['OrderitemID'], $_POST['CustomerID'])) {
    die("Required data missing.");
}

$OrderitemID = (int)$_POST['OrderitemID'];
$CustomerID  = (int)$_POST['CustomerID'];
$delivered   = isset($_POST['delivered']) ? 1 : 0;

$stmt = $conn->prepare("UPDATE order_items SET Delivered = ? WHERE OrderitemID = ?");
$stmt->bind_param("ii", $delivered, $OrderitemID);
$stmt->execute();
$stmt->close();
$conn->close();

// Redirect back to the customer orders page
header("Location: customer_orders.php?CustomerID=$CustomerID");
exit;
?>
