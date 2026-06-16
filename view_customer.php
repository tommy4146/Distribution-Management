<?php
require 'vendor/autoload.php';

include 'connection.php';

$CustomerID = isset($_GET['CustomerID']) ? (int)$_GET['CustomerID'] : 0;
if (!$CustomerID) die("Customer not specified.");

/* ---------- Customer details ---------- */
$stmt = $conn->prepare("
    SELECT Shop_Name, Customer_Name, Address, Contact_Number, Email, NextContactDate
    FROM customer_details
    WHERE CustomerID = ?
");
$stmt->bind_param("i", $CustomerID);
$stmt->execute();
$stmt->bind_result($Shop_Name, $Customer_Name, $Address, $Contact_Number, $Email, $NextContactDate);
if (!$stmt->fetch()) die("Customer not found.");
$stmt->close();

/* ---------- Fetch orders ---------- */
function fetchAllOrders($conn, $CustomerID) {
    $stmt = $conn->prepare("
        SELECT 
            o.OrderID, o.OrderDate, o.status, o.payment_status,
            oi.OrderItemID, oi.Quantity, oi.Price,
            p.ProductName
        FROM orders o
        LEFT JOIN order_items oi ON o.OrderID = oi.OrderID
        LEFT JOIN products p ON oi.ProductID = p.ProductID
        WHERE o.CustomerID = ?
        ORDER BY o.OrderDate DESC, o.OrderID DESC
    ");
    $stmt->bind_param("i", $CustomerID);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $orders = [];
    foreach ($rows as $row) {
        $oid = $row['OrderID'];

        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'OrderID' => $oid,
                'OrderDate' => $row['OrderDate'],
                'status' => strtolower($row['status']),
                'payment_status' => strtolower($row['payment_status'] ?? 'unpaid'),
                'items' => []
            ];
        }

        if (!empty($row['OrderItemID'])) {
            $orders[$oid]['items'][] = [
                'ProductName' => $row['ProductName'],
                'Quantity' => $row['Quantity'],
                'Price' => $row['Price']
            ];
        }
    }
    return $orders;
}

$allOrders = fetchAllOrders($conn, $CustomerID);
$undeliveredOrders = array_filter($allOrders, fn($o) => $o['status'] !== 'delivered');
$deliveredOrders   = array_filter($allOrders, fn($o) => $o['status'] === 'delivered');

/* ---------- Comments ---------- */
$commentStmt = $conn->prepare("
    SELECT CommentID, CommentText, CommentDate
    FROM comments
    WHERE CustomerID = ?
    ORDER BY CommentDate DESC
");
$commentStmt->bind_param("i", $CustomerID);
$commentStmt->execute();
$comments = $commentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$commentStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>View Customer — <?= htmlspecialchars($Shop_Name) ?></title>
<link rel="stylesheet" href="all_orders.css">
</head>
<body>

<nav class="navbar">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="index.php">Customers</a></li>
        <li><a href="all_orders.php">Undelivered Orders</a></li>
        <li><a href="every_order.php">All Orders</a></li>
        <li><a href="all_comments.php">Comments</a></li>
        <li><a href="stock_table.php">Stock Management</a></li>
    </ul>
</nav>

<div class="page-container">

<h2>Customer Details</h2>
<p><strong>Shop Name:</strong> <?= htmlspecialchars($Shop_Name) ?></p>
<p><strong>Customer Name:</strong> <?= htmlspecialchars($Customer_Name) ?></p>
<p><strong>Address:</strong> <?= htmlspecialchars($Address) ?></p>
<p><strong>Contact:</strong> <?= htmlspecialchars($Contact_Number) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($Email) ?></p>
<p><strong>Next Contact:</strong> <?= htmlspecialchars($NextContactDate ?: 'Not set') ?></p>

<!-- buttons unchanged -->
<div class="controls">
    <a href="new_order.php?CustomerID=<?= $CustomerID ?>"><button class="btn">Add Order</button></a>
    <a href="add_comment.php?CustomerID=<?= $CustomerID ?>"><button class="btn">Add Comment</button></a>
    <a href="Add_user.php?CustomerID=<?= $CustomerID ?>"><button class="btn">Edit Customer</button></a>
    <a href="index.php"><button class="btn">Back to Customers</button></a>
</div>

<hr>

<h3>Undelivered Orders</h3>

<?php foreach ($undeliveredOrders as $order): ?>
<div class="order-block">
    <div class="order-header">
        Order #<?= $order['OrderID'] ?> | <?= $order['OrderDate'] ?>
        <a href="generate_invoice.php?OrderID=<?= $order['OrderID'] ?>" target="_blank"><button class="btn">View Invoice</button></a>
                <form action="send_invoice.php" method="post" style="display:inline;">
                    <input type="hidden" name="OrderID" value="<?= $order['OrderID'] ?>">
                    <input type="hidden" name="CustomerID" value="<?= $CustomerID ?>">
                    <button type="submit" class="btn">Send Invoice</button>
                </form>

        <?php if ($order['payment_status'] === 'paid'): ?>
            <span style="color:green;font-weight:bold;">Paid</span>
            <a href="Mark_unpaid.php?OrderID=<?= $order['OrderID'] ?>&CustomerID=<?= $CustomerID ?>">
                <button class="btn" style="background:#dc3545;">Mark Unpaid</button>
            </a>
        <?php else: ?>
            <span style="color:red;font-weight:bold;">Unpaid</span>
            <a href="Mark_paid.php?OrderID=<?= $order['OrderID'] ?>&CustomerID=<?= $CustomerID ?>">
                <button class="btn" style="background:#2c7be5;">Mark Paid</button>
            </a>
        <?php endif; ?>

        <a href="mark_delivered.php?OrderID=<?= $order['OrderID'] ?>&CustomerID=<?= $CustomerID ?>">
            <button class="btn" style="background:green;color:white;">Mark Delivered</button>
        </a>

        <a href="edit_order.php?OrderID=<?= $order['OrderID'] ?>">
            <button class="btn" style="background:#ffc107;color:black;">Edit Order</button>
        </a>
    </div>

    <?php include 'order_items_table.php'; ?>
</div>
<?php endforeach; ?>

<hr>

<h3>Delivered Orders</h3>

<?php foreach ($deliveredOrders as $order): ?>
<div class="order-block" style="background:#f0fff0;">
    <div class="order-header">
        Order #<?= $order['OrderID'] ?> | <?= $order['OrderDate'] ?>

        <?php if ($order['payment_status'] === 'paid'): ?>
            <span style="color:green;font-weight:bold;">Paid</span>
            <a href="Mark_unpaid.php?OrderID=<?= $order['OrderID'] ?>&CustomerID=<?= $CustomerID ?>">
                <button class="btn" style="background:#dc3545;">Mark Unpaid</button>
            </a>
        <?php else: ?>
            <span style="color:red;font-weight:bold;">Unpaid</span>
            <a href="Mark_paid.php?OrderID=<?= $order['OrderID'] ?>&CustomerID=<?= $CustomerID ?>">
                <button class="btn" style="background:#2c7be5;">Mark Paid</button>
            </a>
        <?php endif; ?>

        <a href="mark_undelivered.php?OrderID=<?= $order['OrderID'] ?>&CustomerID=<?= $CustomerID ?>">
            <button class="btn" style="background:#dc3545;color:white;">Mark Undelivered</button>
        </a>

        <form action="send_invoice.php" method="post" style="display:inline;">
            <input type="hidden" name="CustomerID" value="<?= $CustomerID ?>">
            <input type="hidden" name="OrderID" value="<?= $order['OrderID'] ?>">
            <button class="btn">Send Invoice</button>
        </form>

        <a href="generate_invoice.php?OrderID=<?= $order['OrderID'] ?>" target="_blank">
            <button class="btn">View Invoice</button>
        </a>
    </div>

    <?php include 'order_items_table.php'; ?>
</div>
<?php endforeach; ?>

<hr>

<h3>Comments</h3>

<?php if ($comments): ?>
<table>
<tr><th>ID</th><th>Comment</th><th>Date</th></tr>
<?php foreach ($comments as $c): ?>
<tr>
    <td><?= $c['CommentID'] ?></td>
    <td><?= nl2br(htmlspecialchars($c['CommentText'])) ?></td>
    <td><?= $c['CommentDate'] ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p>No comments.</p>
<?php endif; ?>

</div>
</body>
</html>
