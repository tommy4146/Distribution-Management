<?php
$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) die("DB error");

$sql = "
SELECT 
    c.CustomerID,
    c.Shop_Name,
    c.Contact_Number,
    c.Email,
    o.OrderID,
    o.OrderDate,
    o.status,
    o.payment_status
FROM customer_details c
JOIN orders o ON o.CustomerID = c.CustomerID
JOIN (
    SELECT CustomerID, MAX(OrderDate) AS LatestOrderDate
    FROM orders
    GROUP BY CustomerID
) latest 
    ON latest.CustomerID = o.CustomerID
   AND latest.LatestOrderDate = o.OrderDate
ORDER BY o.OrderDate DESC
";


$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Customer Orders</title>
    <link rel="stylesheet" href="all_orders.css">
</head>
<body>
<nav class="navbar">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="all_orders.php">Undelivered Orders</a></li>
        <li><a href="every_order.php">All Orders</a></li>
        <li><a href="all_comments.php">Comments</a></li>
    </ul>
</nav>

<h2>Latest Order per Customer</h2>

<table>
    <tr>
        <th>Shop</th>
        <th>Latest Order ID</th>
        <th>Date</th>
        <th>Status</th>
        <th>Payment Status</th>
        <th>Contact</th>
    </tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr onclick="window.location='customer_orders.php?CustomerID=<?= $row['CustomerID'] ?>'"
    style="cursor:pointer;">
    <td><?= htmlspecialchars($row['Shop_Name']) ?></td>
    <td>#<?= $row['OrderID'] ?></td>
    <td><?= date('d-m-Y', strtotime($row['OrderDate'])) ?></td>
    <td>
        <?= $row['status'] === 'delivered'
            ? '<span style="color:green;">Delivered</span>'
            : '<span style="color:red;">Undelivered</span>' ?>
    </td>
    <td>
    <?= $row['payment_status'] === 'paid'
        ? '<span style="color:green;font-weight:bold;">Paid</span>'
        : '<span style="color:orange;font-weight:bold;">Unpaid</span>' ?>
</td>
    <td>
        <?= htmlspecialchars($row['Contact_Number']) ?><br>
    </td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>