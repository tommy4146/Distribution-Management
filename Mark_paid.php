<?php
$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) die("DB error");

$OrderID = isset($_GET['OrderID']) ? (int)$_GET['OrderID'] : 0;
$CustomerID = isset($_GET['CustomerID']) ? (int)$_GET['CustomerID'] : 0;

if (!$OrderID || !$CustomerID) die("Invalid request");

$stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE OrderID = ?");
$stmt->bind_param("i", $OrderID);
$stmt->execute();
$stmt->close();

header("Location: view_customer.php?CustomerID=$CustomerID");
exit;