<?php
// delete_order.php

$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['OrderID'])) {
    $OrderID = (int)$_POST['OrderID'];

    // Delete order items first (foreign key constraint)
    $stmt1 = $conn->prepare("DELETE FROM order_items WHERE OrderID = ?");
    $stmt1->bind_param("i", $OrderID);
    $stmt1->execute();
    $stmt1->close();

    // Delete the order
    $stmt2 = $conn->prepare("DELETE FROM orders WHERE OrderID = ?");
    $stmt2->bind_param("i", $OrderID);
    $stmt2->execute();
    $stmt2->close();

    $conn->close();

    header("Location: all_orders.php?msg=Order+deleted+successfully");
    exit;
}

$conn->close();
header("Location: all_orders.php?msg=Invalid+request");
exit;
