<?php
// view_order.php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = include 'config.php';

// Connect to DB
include 'connection.php';
$OrderID = isset($_GET['OrderID']) ? (int)$_GET['OrderID'] : 0;

if (!$OrderID) {
    die("OrderID not specified.");
}
$stmt = $conn->prepare("
    SELECT o.OrderID, o.OrderDate, c.CustomerID, c.Shop_Name, c.Address, c.Contact_Number, c.Email
    FROM orders o
    JOIN customer_details c ON o.CustomerID = c.CustomerID
    WHERE o.OrderID = ?
");
$stmt->bind_param("i", $OrderID);
$stmt->execute();
$stmt->bind_result($OrderID, $OrderDate, $CustomerID, $Shop_Name, $Address, $Contact_Number, $Email);
if (!$stmt->fetch()) die("Order not found.");
$stmt->close();

// Fetch order items
$itemStmt = $conn->prepare("
    SELECT oi.OrderItemID, oi.ProductID, oi.Quantity, oi.Price, p.ProductName
    FROM order_items oi
    JOIN products p ON oi.ProductID = p.ProductID
    WHERE oi.OrderID = ?
");
$itemStmt->bind_param("i", $OrderID);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();
$orderItems = [];
$totalAmount = 0;
while ($row = $itemResult->fetch_assoc()) {
    $row['Subtotal'] = $row['Quantity'] * $row['Price'];
    $totalAmount += $row['Subtotal'];
    $orderItems[] = $row;
}
$itemStmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>View Order #<?= $OrderID ?></title>
    <link rel="stylesheet" href="all_orders.css?v=<?= time(); ?>">
</head>
<body>
<nav class="navbar">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="all_orders.php">All Orders</a></li>
        <li><a href="view_customer.php?CustomerID=<?= $CustomerID ?>">Back to Customer</a></li>
        <li><a href="all_comments.php">Comments</a></li>
        <li><a href="stock_table.php">Stock Management</a></li>
    </ul>
</nav>

<div class="page-container">
    <h2>Order #<?= $OrderID ?></h2>

    <div class="customer-info">
        <p><strong>Shop Name:</strong> <?= htmlspecialchars($Shop_Name) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($Address) ?></p>
        <p><strong>Contact Number:</strong> <?= htmlspecialchars($Contact_Number) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($Email) ?></p>
        <p><strong>Order Date:</strong> <?= date('d-m-Y', strtotime($OrderDate)) ?></p>
    </div>

    <div class="order-actions" style="margin-bottom:20px;">
        <!-- Send Invoice -->
        <form action="send_invoice.php" method="post" style="display:inline;">
            <input type="hidden" name="CustomerID" value="<?= $CustomerID ?>">
            <input type="hidden" name="OrderID" value="<?= $OrderID ?>">
            <button type="submit" class="btn">Send Invoice</button>
        </form>

        <!-- View Invoice -->
        <form action="generate_invoice.php" method="get" target="_blank" style="display:inline;">
            <input type="hidden" name="CustomerID" value="<?= $CustomerID ?>">
            <input type="hidden" name="OrderID" value="<?= $OrderID ?>">
            <button type="submit" class="btn">View Invoice</button>
        </form>

        
    </div>

    <h3>Items</h3>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price (per)</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orderItems as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['ProductName']) ?></td>
                <td><?= $item['Quantity'] ?></td>
                <td>£<?= number_format($item['Price'],2) ?></td>
                <td>£<?= number_format($item['Subtotal'],2) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="3" style="text-align:right;font-weight:bold;">Total:</td>
            <td>£<?= number_format($totalAmount,2) ?></td>
        </tr>
        </tbody>
    </table>
</div>
</body>
</html>
