<?php
include 'connection.php';

$OrderID = $_GET['OrderID'] ?? 0;
if (!$OrderID) die("Order not specified.");

// Fetch order info
$stmt = $conn->prepare("SELECT CustomerID, OrderDate, OrderAmount FROM orders WHERE OrderID=?");
$stmt->bind_param("i", $OrderID);
$stmt->execute();
$stmt->bind_result($CustomerID, $OrderDate, $OrderAmount);
if (!$stmt->fetch()) die("Order not found.");
$stmt->close();

// Fetch products (exclude 'online' products)
$productQuery = $conn->query("SELECT * FROM products WHERE ProductName NOT LIKE '%online%'");
$products = [];
while ($p = $productQuery->fetch_assoc()) $products[] = $p;

// Fetch current order items
$orderItemsStmt = $conn->prepare("SELECT ProductID, Quantity, Price FROM order_items WHERE OrderID=?");
$orderItemsStmt->bind_param("i", $OrderID);
$orderItemsStmt->execute();
$orderResult = $orderItemsStmt->get_result();
$orderItems = $orderResult->fetch_all(MYSQLI_ASSOC);
$orderItemsStmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $OrderDate = $_POST['OrderDate'];
    $OrderAmount = floatval($_POST['orderAmount']);

    // Revert stock for old items
    foreach ($orderItems as $item) {
        $conn->query("UPDATE stock 
                      SET Quantity = Quantity + {$item['Quantity']}, LastUpdated = NOW() 
                      WHERE ProductID = {$item['ProductID']}");
    }

    // Update order
    $stmt = $conn->prepare("UPDATE orders SET OrderDate=?, OrderAmount=? WHERE OrderID=?");
    $stmt->bind_param("sdi", $OrderDate, $OrderAmount, $OrderID);
    $stmt->execute();
    $stmt->close();

    // Delete old order items
    $conn->query("DELETE FROM order_items WHERE OrderID=$OrderID");

    // Insert new items and update stock
    foreach ($_POST['ProductID'] as $i => $productID) {
        $quantity = $_POST['Quantity'][$i];

        // Get product price
        $stmtPrice = $conn->prepare("SELECT Price FROM products WHERE ProductID=?");
        $stmtPrice->bind_param("i", $productID);
        $stmtPrice->execute();
        $stmtPrice->bind_result($price);
        $stmtPrice->fetch();
        $stmtPrice->close();

        // Insert order item
        $stmtItem = $conn->prepare("INSERT INTO order_items (OrderID, ProductID, Quantity, Price) VALUES (?, ?, ?, ?)");
        $stmtItem->bind_param("iiid", $OrderID, $productID, $quantity, $price);
        $stmtItem->execute();
        $stmtItem->close();

        // Update stock
        $conn->query("UPDATE stock 
                      SET Quantity = Quantity - $quantity, LastUpdated = NOW() 
                      WHERE ProductID = $productID");
    }

    header("Location: view_customer.php?CustomerID=$CustomerID");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Order</title>
    <link rel="stylesheet" href="new_order.css?v=<?= time(); ?>">
</head>
<body>
<nav class="navbar">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="view_customer.php?CustomerID=<?= $CustomerID ?>">Customer</a></li>
        <li><a href="all_orders.php">All Orders</a></li>
    </ul>
</nav>

<div class="page-container">
    <h2>Edit Order #<?= $OrderID ?></h2>

    <form method="POST">
        <label>Order Date:</label>
        <input type="date" name="OrderDate" value="<?= htmlspecialchars($OrderDate) ?>" required>

        <h3>Products:</h3>
        <div class="table-container" id="productList">
            <?php foreach ($orderItems as $item): ?>
            <div class="productRow">
                <select name="ProductID[]" class="productSelect" required>
                    <option value="">Select Product</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['ProductID'] ?>" data-price="<?= $p['Price'] ?>"
                        <?= $p['ProductID'] == $item['ProductID'] ? "selected" : "" ?>>
                        <?= $p['ProductName'] ?> (£<?= $p['Price'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="Quantity[]" class="quantityInput" min="1" value="<?= $item['Quantity'] ?>">
                <input type="text" class="lineTotal" readonly placeholder="£0.00">
                <button type="button" class="removeRow btn">X</button>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" id="addRow" class="btn">Add Another Product</button>

        <label>Total Price:</label>
        <input type="text" id="orderAmountDisplay" readonly placeholder="£0.00">
        <input type="hidden" id="orderAmount" name="orderAmount">

        <input type="submit" value="Update Order" class="btn">
        <a href="view_customer.php?CustomerID=<?= $CustomerID ?>"><button type="button" class="btn">Cancel</button></a>
    </form>
</div>

<script>
function updateTotals() {
    let orderTotal = 0;
    document.querySelectorAll("#productList .productRow").forEach(row => {
        const select = row.querySelector(".productSelect");
        const qty = parseFloat(row.querySelector(".quantityInput").value) || 0;
        const price = parseFloat(select.options[select.selectedIndex].getAttribute("data-price")) || 0;
        const lineTotal = price * qty;
        row.querySelector(".lineTotal").value = "£" + lineTotal.toFixed(2);
        orderTotal += lineTotal;
    });
    document.getElementById("orderAmountDisplay").value = "£" + orderTotal.toFixed(2);
    document.getElementById("orderAmount").value = orderTotal.toFixed(2);
}

document.getElementById("addRow").addEventListener("click", function () {
    const firstRow = document.querySelector(".productRow");
    const clone = firstRow.cloneNode(true);
    clone.querySelector(".productSelect").value = "";
    clone.querySelector(".quantityInput").value = 1;
    clone.querySelector(".lineTotal").value = "";
    document.getElementById("productList").appendChild(clone);
});

document.addEventListener("change", function(e) {
    if (e.target.classList.contains("productSelect") || e.target.classList.contains("quantityInput")) updateTotals();
});

document.addEventListener("click", function(e) {
    if (e.target.classList.contains("removeRow") && document.querySelectorAll(".productRow").length > 1) {
        e.target.parentElement.remove();
        updateTotals();
    }
});

updateTotals();
</script>
</body>
</html>
