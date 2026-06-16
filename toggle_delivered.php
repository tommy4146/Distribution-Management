<?php
include 'connection.php';

if (!isset($_POST['OrderID'], $_POST['OrderitemID'], $_POST['CustomerID'])) {
    die("Required data missing.");
}

$OrderID = (int)$_POST['OrderID'];
$OrderitemID = (int)$_POST['OrderitemID']; // matches your DB column
$CustomerID = (int)$_POST['CustomerID'];
$Delivered = isset($_POST['Delivered']) ? 1 : 0;

$stmt = $conn->prepare("UPDATE order_items SET Delivered = ? WHERE OrderitemID = ? AND OrderID = ?");
$stmt->bind_param("iii", $Delivered, $OrderitemID, $OrderID);
$stmt->execute();
$stmt->close();
$conn->close();

// Redirect back to the customer orders page
header("Location: customer_orders.php?CustomerID=$CustomerID");
exit;
