<?php
// stock_table.php
include 'connection.php';

// --- Ensure all products have a stock row ---
$conn->query("
    INSERT IGNORE INTO stock (ProductID, Quantity, LastUpdated)
    SELECT ProductID, 0, NOW()
    FROM products
");

// --- Fetch stock with product names ---
$stockResult = $conn->query("
    SELECT s.StockID, s.ProductID, s.Quantity, s.LastUpdated, p.ProductName
    FROM stock s
    JOIN products p ON s.ProductID = p.ProductID
    ORDER BY p.ProductName ASC
");

// --- Handle updates ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['StockID'], $_POST['Quantity'])) {
    $StockID = (int)$_POST['StockID'];
    $Quantity = (int)$_POST['Quantity'];

    $stmt = $conn->prepare("UPDATE stock SET Quantity = ?, LastUpdated = NOW() WHERE StockID = ?");
    $stmt->bind_param("ii", $Quantity, $StockID);
    $stmt->execute();
    $stmt->close();

    // Refresh page to show updated values
    header("Location: stock_table.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <link href="all_orders.css" rel="stylesheet" type="text/css">
    <title>Stock Management</title>
    <style>
        tr:hover { background-color: #1067e8ff; }
        .low { background-color: yellow; }
        .critical { background-color: red; color:black; }
        .btn { padding:6px 12px; cursor:pointer; }
    </style>
</head>
<body>
<nav class="navbar">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="Add_user.php">Add New Customer</a></li>
        <li><a href="all_orders.php">All Orders</a></li>
        <li><a href="all_comments.php">Comments</a></li>
    </ul>
</nav>

<h2>Stock Management</h2>

<table>
    <thead>
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Last Updated</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $stockResult->fetch_assoc()): 
            $class = '';
            if ($row['Quantity'] < 10) $class = 'critical';
            elseif ($row['Quantity'] < 15) $class = 'low';
        ?>
        <tr class="<?= $class ?>">
            <td><?= htmlspecialchars($row['ProductName']) ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="StockID" value="<?= $row['StockID'] ?>">
                    <input type="number" name="Quantity" value="<?= $row['Quantity'] ?>" min="0">
            </td>
            <td><?= date('d-m-Y H:i', strtotime($row['LastUpdated'])) ?></td>
            <td>
                    <button type="submit" class="btn">Update</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
