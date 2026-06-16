<?php
// customer_orders.php
require 'vendor/autoload.php';

// Connect to DB
include 'connection.php';

// Get CustomerID
$CustomerID = isset($_GET['CustomerID']) ? (int)$_GET['CustomerID'] : 0;
if (!$CustomerID) die("Customer not specified.");

// Fetch customer details
$stmt = $conn->prepare("
    SELECT Shop_Name, Customer_Name, Address, Contact_Number, Email, NextContactDate 
    FROM customer_details
    WHERE CustomerID = ?
");
$stmt->bind_param("i", $CustomerID);
$stmt->execute();
$stmt->bind_result($Shop_Name, $Customer_Name, $Address, $Contact_Number, $Email, $NextContactDate);
if (!$stmt->fetch()) {
    $stmt->close();
    $conn->close();
    die("Customer not found.");
}
$stmt->close();

// Fetch orders and items including delivered status
$orderStmt = $conn->prepare("
    SELECT 
        o.OrderID,
        o.OrderDate,
        o.status AS order_status,
        o.payment_status,
        oi.OrderitemID,
        oi.ProductID,
        oi.Quantity,
        oi.Price,
        oi.Delivered AS item_delivered,
        p.ProductName
    FROM orders o
    LEFT JOIN order_items oi ON o.OrderID = oi.OrderID
    LEFT JOIN products p ON oi.ProductID = p.ProductID
    WHERE o.CustomerID = ?
    ORDER BY o.OrderDate DESC, o.OrderID DESC
");
$orderStmt->bind_param("i", $CustomerID);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$ordersRaw = $orderResult->fetch_all(MYSQLI_ASSOC);
$orderStmt->close();

// Group orders by OrderID
$orders = [];
foreach ($ordersRaw as $row) {
    $oid = $row['OrderID'];
    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'OrderID' => $oid,
            'OrderDate' => $row['OrderDate'],
            'status' => strtolower($row['order_status']),
            'payment_status' => strtolower($row['payment_status'] ?? 'unpaid'),
            'items' => []
        ];
    }
    if (!empty($row['ProductID'])) {
        $orders[$oid]['items'][] = [
            'OrderitemID' => $row['OrderitemID'],
            'ProductName' => $row['ProductName'],
            'Quantity' => $row['Quantity'],
            'Price' => $row['Price'],
            'delivered' => $row['item_delivered'] == 1
        ];
    }
}

// Separate delivered vs undelivered
$undeliveredOrders = array_filter($orders, fn($o) => $o['status'] !== 'delivered');
$deliveredOrders   = array_filter($orders, fn($o) => $o['status'] === 'delivered');

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Customer Orders — <?= htmlspecialchars($Shop_Name) ?></title>
<link rel="stylesheet" href="all_orders.css?v=<?= time(); ?>">
<style>
    .order-block { border:1px solid #ccc; margin-bottom:15px; padding:10px; border-radius:5px; }
    .order-header { margin-bottom:10px; }
    table { width:100%; border-collapse:collapse; margin-bottom:10px; }
    th, td { border:1px solid #ccc; padding:6px 8px; text-align:left; }
    .btn { padding:6px 12px; cursor:pointer; margin-right:5px; }
</style>
</head>
<body>
<nav class="navbar">
    <ul>
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
<p><strong>Contact Number:</strong> <?= htmlspecialchars($Contact_Number) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($Email) ?></p>
<p><strong>Next Contact Date:</strong> <?= htmlspecialchars($NextContactDate ?: 'Not set') ?></p>

<div class="controls">
    <a href="new_order.php?CustomerID=<?= $CustomerID ?>"><button class="btn">Add Order</button></a>
    <a href="add_comment.php?CustomerID=<?= $CustomerID ?>"><button class="btn">Add Comment</button></a>
    <a href="Add_user.php?CustomerID=<?= $CustomerID ?>"><button class="btn">Edit Customer</button></a>
    <a href="index.php"><button class="btn">Back to Customers</button></a>
</div>

<hr>
<h3>Undelivered Orders</h3>
<?php if (!empty($undeliveredOrders)): ?>
    <?php foreach ($undeliveredOrders as $order): ?>
    <div class="order-block">
        <div class="order-header">
            <strong>Order #<?= $order['OrderID'] ?></strong> | <?= date('d-m-Y', strtotime($order['OrderDate'])) ?>
            | Status: <span style="color:red;font-weight:bold;">Undelivered</span>
            | Payment: <?= $order['payment_status'] === 'paid' ? '<span style="color:green;font-weight:bold;">Paid</span>' : '<span style="color:orange;font-weight:bold;">Unpaid</span>' ?>
            <div style="margin-top:5px;">
                <a href="generate_invoice.php?OrderID=<?= $order['OrderID'] ?>" target="_blank"><button class="btn">View Invoice</button></a>
                <form action="send_invoice.php" method="post" style="display:inline;">
                    <input type="hidden" name="OrderID" value="<?= $order['OrderID'] ?>">
                    <input type="hidden" name="CustomerID" value="<?= $CustomerID ?>">
                    <button type="submit" class="btn">Send Invoice</button>
                </form>
                <?php if ($order['payment_status'] === 'unpaid'): ?>
                <a href="mark_paid.php?OrderID=<?= $order['OrderID'] ?>&CustomerID=<?= $CustomerID ?>"><button class="btn" style="background:#2c7be5;color:white;">Mark Paid</button></a>
                <?php endif; ?>
                <a href="mark_delivered.php?OrderID=<?= $order['OrderID'] ?>&CustomerID=<?= $CustomerID ?>"><button class="btn" style="background:green;color:white;">Mark Delivered</button></a>
                <form action="delete_order.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                    <input type="hidden" name="OrderID" value="<?= $order['OrderID'] ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
                <a href="edit_order.php?OrderID=<?= $order['OrderID'] ?>"><button class="btn" style="background:#ffc107;color:black;">Edit Order</button></a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price (per)</th>
                    <th>Line Total</th>
                    <th>Delivered</th>
                </tr>
            </thead>
            <tbody>
            <?php $orderTotal = 0; ?>
            <?php foreach ($order['items'] as $item): 
                $lineTotal = $item['Quantity'] * $item['Price'];
                $orderTotal += $lineTotal;
            ?>
                <tr>
                    <td><?= htmlspecialchars($item['ProductName']) ?></td>
                    <td><?= $item['Quantity'] ?></td>
                    <td>£<?= number_format($item['Price'], 2) ?></td>
                    <td>£<?= number_format($lineTotal, 2) ?></td>
                    <td>
                        <form action="toggle_delivered.php" method="post" style="margin:0;">
                            <input type="hidden" name="OrderID" value="<?= $order['OrderID'] ?>">
                            <input type="hidden" name="OrderitemID" value="<?= $item['OrderitemID'] ?>">
                            <input type="hidden" name="CustomerID" value="<?= $CustomerID ?>">
                            <input type="checkbox" name="Delivered" value="1" <?= $item['delivered'] ? 'checked' : '' ?> onchange="this.form.submit()">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3" style="text-align:right; font-weight:bold;">Total:</td>
                <td colspan="2">£<?= number_format($orderTotal, 2) ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
<?php else: ?>
<p>No undelivered orders.</p>
<?php endif; ?>

<hr>
<h3>Delivered Orders</h3>
<?php if (!empty($deliveredOrders)): ?>
    <?php foreach ($deliveredOrders as $order): ?>
    <div class="order-block" style="background:#f0fff0;">
        <div class="order-header">
            <strong>Order #<?= $order['OrderID'] ?></strong> | <?= date('d-m-Y', strtotime($order['OrderDate'])) ?>
            | Status: <span style="color:green;font-weight:bold;">Delivered</span>
            | Payment: <?= $order['payment_status'] === 'paid' ? '<span style="color:green;font-weight:bold;">Paid</span>' : '<span style="color:orange;font-weight:bold;">Unpaid</span>' ?>
            <div style="margin-top:5px;">
                <a href="mark_undelivered.php?OrderID=<?= $order['OrderID'] ?>&CustomerID=<?= $CustomerID ?>"><button class="btn" style="background:#dc3545;color:white;">Mark Undelivered</button></a>
                <form action="delete_order.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                    <input type="hidden" name="OrderID" value="<?= $order['OrderID'] ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
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
                <a href="edit_order.php?OrderID=<?= $order['OrderID'] ?>"><button class="btn" style="background:#ffc107;color:black;">Edit Order</button></a>

                 <form action="send_invoice.php" method="post" style="display:inline;">
            <input type="hidden" name="CustomerID" value="<?= $CustomerID ?>">
            <input type="hidden" name="OrderID" value="<?= $order['OrderID'] ?>">
            <button class="btn">Send Invoice</button>
        </form>

        <a href="generate_invoice.php?OrderID=<?= $order['OrderID'] ?>&t=<?= time() ?>" target="_blank">
    <button class="btn">View Invoice</button>
</a>
    </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price (per)</th>
                    <th>Line Total</th>
                    <th>Delivered</th>
                </tr>
            </thead>
            <tbody>
            <?php $orderTotal = 0; ?>
            <?php foreach ($order['items'] as $item): 
                $lineTotal = $item['Quantity'] * $item['Price'];
                $orderTotal += $lineTotal;
            ?>
                <tr>
                    <td><?= htmlspecialchars($item['ProductName']) ?></td>
                    <td><?= $item['Quantity'] ?></td>
                    <td>£<?= number_format($item['Price'], 2) ?></td>
                    <td>£<?= number_format($lineTotal, 2) ?></td>
                    <td>
                        <form action="toggle_delivered.php" method="post" style="margin:0;">
                            <input type="hidden" name="OrderID" value="<?= $order['OrderID'] ?>">
                            <input type="hidden" name="OrderitemID" value="<?= $item['OrderitemID'] ?>">
                            <input type="hidden" name="CustomerID" value="<?= $CustomerID ?>">
                            <input type="checkbox" name="Delivered" value="1" <?= $item['delivered'] ? 'checked' : '' ?> onchange="this.form.submit()">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3" style="text-align:right; font-weight:bold;">Total:</td>
                <td colspan="2">£<?= number_format($orderTotal, 2) ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
<?php else: ?>
<p>No delivered orders.</p>
<?php endif; ?>

</div>
</body>
</html>
