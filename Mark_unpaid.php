<?php
$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) die("DB error");

$OrderID = (int)($_GET['OrderID'] ?? 0);
$CustomerID = (int)($_GET['CustomerID'] ?? 0);

if (!$OrderID || !$CustomerID) die("Invalid request");

$stmt = $conn->prepare("UPDATE orders SET payment_status='unpaid' WHERE OrderID=?");
$stmt->bind_param("i", $OrderID);
$stmt->execute();
$stmt->close();

header("Location: view_customer.php?CustomerID=$CustomerID");
exit;
