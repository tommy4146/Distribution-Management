<?php
$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) die("Connection failed");

$OrderItemID = (int)($_POST['OrderItemID'] ?? 0);
if (!$OrderItemID) die("Invalid item");

$conn->query("
    UPDATE order_items
    SET delivered = NOT delivered
    WHERE OrderItemID = $OrderItemID
");

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
