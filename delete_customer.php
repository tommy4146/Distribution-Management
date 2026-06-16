<?php
include 'connection.php';

if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Check CustomerID
if (isset($_GET['CustomerID'])) {
    $CustomerID = (int)$_GET['CustomerID']; // cast to int for safety

    // Optional: delete related orders first
    $conn->query("DELETE FROM order_items WHERE OrderID IN (SELECT OrderID FROM orders WHERE CustomerID=$CustomerID)");
    $conn->query("DELETE FROM orders WHERE CustomerID=$CustomerID");

    // Delete customer
    $conn->query("DELETE FROM customer_details WHERE CustomerID=$CustomerID");

    // Redirect back to index page
    header("Location: index.php");
    exit();
}
$conn->close();
?>