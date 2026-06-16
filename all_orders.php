<?php
// all_orders.php
require 'vendor/autoload.php';

// Connect to DB
include 'connection.php';

// Fetch all customers for filter dropdown
$customerOptions = [];
$customerResult = $conn->query("SELECT CustomerID, Shop_Name FROM customer_details ORDER BY Shop_Name ASC");
while ($row = $customerResult->fetch_assoc()) {
    $customerOptions[] = $row;
}

// Filters
$where = [];
$params = [];
$types = "";

if (!empty($_GET['filterCustomer'])) {
    $where[] = "c.CustomerID = ?";
    $params[] = (int)$_GET['filterCustomer'];
    $types .= "i";
}

if (!empty($_GET['OrderDate'])) {
    $where[] = "DATE(o.OrderDate) = ?";
    $params[] = $_GET['OrderDate'];
    $types .= "s";
}

// SQL: get the most recent undelivered order per customer using subquery
$sql = "
SELECT o.OrderID, o.OrderDate, c.CustomerID, c.Shop_Name, SUM(oi.Quantity * oi.Price) AS OrderTotal
FROM orders o
JOIN customer_details c ON o.CustomerID = c.CustomerID
JOIN order_items oi ON o.OrderID = oi.OrderID
JOIN (
    SELECT CustomerID, MAX(OrderDate) AS LatestDate
    FROM orders
    WHERE status = 'undelivered'
    GROUP BY CustomerID
) lo ON o.CustomerID = lo.CustomerID AND o.OrderDate = lo.LatestDate
WHERE o.status = 'undelivered'
";

if (!empty($where)) {
    $sql .= " AND " . implode(" AND ", $where);
}

$sql .= " GROUP BY o.OrderID, o.OrderDate, c.CustomerID, c.Shop_Name ORDER BY o.OrderDate ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>All Orders</title>
    <link rel="stylesheet" href="all_orders.css?v=<?= time(); ?>">
</head>
<body>
<nav class="navbar">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="Add_user.php">Add New Customer</a></li>
        <li><a href="all_orders.php">Undelivered Orders</a></li>
        <li><a href="every_order.php">All Orders</a></li>
        <li><a href="all_comments.php">Comments</a></li>
        <li><a href="stock_table.php">Stock Management</a></li>
    </ul>
</nav>

<div class="page-container">
    <h2>All Orders (Undelivered)</h2>

    <table>
    <tr>
        <th>Customer</th>
        <th>Most Recent Order ID</th>
        <th>Total (£)</th>
        <th>Order Date</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
    <tr onclick="window.location='customer_orders.php?CustomerID=<?= $row['CustomerID'] ?>';" style="cursor:pointer;">
        <td><?= htmlspecialchars($row['Shop_Name']) ?></td>
        <td><?= $row['OrderID'] ?></td>
        <td><?= number_format($row['OrderTotal'], 2) ?></td>
        <td><?= date('d-m-Y', strtotime($row['OrderDate'])) ?></td>
        <td>
            <a href="customer_orders.php?CustomerID=<?= $row['CustomerID'] ?>"><button class="btn">View All Orders</button></a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</div>
</body>
</html>
