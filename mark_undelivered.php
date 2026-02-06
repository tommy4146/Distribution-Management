<?php
// mark_undelivered.php
$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$OrderID = isset($_GET['OrderID']) ? (int)$_GET['OrderID'] : 0;
$CustomerID = isset($_GET['CustomerID']) ? (int)$_GET['CustomerID'] : 0;

if (!$OrderID || !$CustomerID) {
    die("Required data missing.");
}

// 1. Update order status
$stmt = $conn->prepare("UPDATE orders SET status = 'undelivered' WHERE OrderID = ?");
$stmt->bind_param("i", $OrderID);
$stmt->execute();
$stmt->close();

// 2. Update all order items delivered flag
$stmt = $conn->prepare("UPDATE order_items SET Delivered = 0 WHERE OrderID = ?");
$stmt->bind_param("i", $OrderID);
$stmt->execute();
$stmt->close();

$conn->close();

// Redirect back to customer orders
header("Location: customer_orders.php?CustomerID=$CustomerID");
exit;
?>
