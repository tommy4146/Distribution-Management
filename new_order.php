<?php
session_start();

/* ==========================
   DATABASE CONNECTION
========================== */
include 'connection.php';

/* ==========================
   CUSTOMER CHECK
========================== */
$CustomerID = isset($_GET['CustomerID']) ? (int)$_GET['CustomerID'] : 0;
if ($CustomerID <= 0) {
    die("Customer not specified.");
}

/* ==========================
   FETCH PRODUCTS
========================== */
$products = [];
$productQuery = $conn->query(
    "SELECT ProductID, ProductName, Price 
     FROM products 
     WHERE ProductName NOT LIKE '%online%'"
);
while ($row = $productQuery->fetch_assoc()) {
    $products[] = $row;
}

/* ==========================
   FETCH BUNDLE PRODUCTS
========================== */
$isBundle = false;
$bundleProducts = [];
$bundleID = 0;

if (isset($_POST['add_bundle'])) {
    $isBundle = true;
    $bundleID = (int)$_POST['BundleID'];

    $stmt = $conn->prepare(
        "SELECT bp.ProductID, p.ProductName, bp.BundlePrice, bp.Quantity
         FROM bundle_products bp
         JOIN products p ON bp.ProductID = p.ProductID
         WHERE bp.BundleID = ?"
    );
    $stmt->bind_param("i", $bundleID);
    $stmt->execute();
    $bundleProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

/* ==========================
   ORDER PROCESSING
========================== */
$showConfirmation = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['add_bundle'])) {

    $CustomerID  = (int)$_POST['CustomerID'];
    $OrderDate   = $_POST['OrderDate'];
    $OrderAmount = (float)$_POST['orderAmount'];

    // Insert order
    $stmt = $conn->prepare(
        "INSERT INTO orders (CustomerID, OrderDate, OrderAmount)
         VALUES (?, ?, ?)"
    );
    $stmt->bind_param("isd", $CustomerID, $OrderDate, $OrderAmount);
    $stmt->execute();
    $OrderID = $stmt->insert_id;
    $stmt->close();

    // Insert order items
    foreach ($_POST['ProductID'] as $index => $ProductID) {
        $ProductID = (int)$ProductID;
        $Quantity  = (int)$_POST['Quantity'][$index];
        $Price     = (float)$_POST['Price'][$index]; // bundle or product price

        // Ensure stock exists
        $stockCheck = $conn->prepare(
            "SELECT Quantity FROM stock WHERE ProductID = ?"
        );
        $stockCheck->bind_param("i", $ProductID);
        $stockCheck->execute();
        $result = $stockCheck->get_result();
        if ($result->num_rows === 0) {
            $insertStock = $conn->prepare(
                "INSERT INTO stock (ProductID, Quantity, LastUpdated) VALUES (?, 0, NOW())"
            );
            $insertStock->bind_param("i", $ProductID);
            $insertStock->execute();
            $insertStock->close();
        }
        $stockCheck->close();

        // Update stock
        $updateStock = $conn->prepare(
            "UPDATE stock SET Quantity = Quantity - ?, LastUpdated = NOW() WHERE ProductID = ?"
        );
        $updateStock->bind_param("ii", $Quantity, $ProductID);
        $updateStock->execute();
        $updateStock->close();

        // Insert order item
        $itemStmt = $conn->prepare(
            "INSERT INTO order_items (OrderID, ProductID, Quantity, Price)
             VALUES (?, ?, ?, ?)"
        );
        $itemStmt->bind_param("iiid", $OrderID, $ProductID, $Quantity, $Price);
        $itemStmt->execute();
        $itemStmt->close();
    }

    $showConfirmation = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>New Order</title>
    <link rel="stylesheet" href="new_order.css?v=<?= time(); ?>">
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

<div class="page-container">

<?php if ($showConfirmation): ?>
    <div class="confirmation-container">
        <h2>&#10004; Order successfully added!</h2>
        <p>Customer ID: <?= htmlspecialchars($CustomerID) ?></p>
        <p>Total Price: £<?= number_format($OrderAmount, 2) ?></p>
        <a href="view_customer.php?CustomerID=<?= $CustomerID ?>">
            <button class="btn">Back to Customer</button>
        </a>
    </div>
<?php else: ?>

<h2>Add New Order</h2>

<!-- Bundle Buttons -->
<form method="post" style="display:inline-block; margin-right:10px;">
    <input type="hidden" name="CustomerID" value="<?= $CustomerID ?>">
    <input type="hidden" name="BundleID" value="1">
    <button type="submit" name="add_bundle" class="btn btn-success">
        Add Non-Vegan Bundle
    </button>
</form>

<form method="post" style="display:inline-block;">
    <input type="hidden" name="CustomerID" value="<?= $CustomerID ?>">
    <input type="hidden" name="BundleID" value="2">
    <button type="submit" name="add_bundle" class="btn btn-success">
        Add Vegan Bundle
    </button>
</form>

<form method="post" style="margin-top:20px;">
    <input type="hidden" name="CustomerID" value="<?= $CustomerID ?>">

    <label>Order Date:</label>
    <input type="date" name="OrderDate" required>

    <h3>Products</h3>
    <div id="productList">

    <?php if ($isBundle && !empty($bundleProducts)): ?>
        <?php foreach ($bundleProducts as $item): ?>
            <div class="productRow">
                <select name="ProductID[]" class="productSelect" required>
                    <option value="<?= $item['ProductID'] ?>" selected>
                        <?= htmlspecialchars($item['ProductName']) ?>
                    </option>
                </select>

                <input type="hidden" name="Price[]" value="<?= $item['BundlePrice'] ?>">

                <input type="number" name="Quantity[]" class="quantityInput"
                       min="1" value="<?= $item['Quantity'] ?>">

                <input type="text" class="lineTotal"
                       value="£<?= number_format($item['BundlePrice'] * $item['Quantity'], 2) ?>" readonly>

                <button type="button" class="removeRow btn">X</button>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="productRow">
            <select name="ProductID[]" class="productSelect" required>
                <option value="">Select Product</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['ProductID'] ?>" data-price="<?= $p['Price'] ?>">
                        <?= htmlspecialchars($p['ProductName']) ?> (£<?= $p['Price'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="hidden" name="Price[]" value="0">


            <input type="number" name="Quantity[]" class="quantityInput" min="1" value="1">
            <input type="text" class="lineTotal" readonly>
            <button type="button" class="removeRow btn">X</button>
        </div>
    <?php endif; ?>

    </div>

    <button type="button" id="addRow" class="btn">Add Another Product</button>

    <label>Total Price:</label>
    <input type="text" id="orderAmountDisplay" readonly>
    <input type="hidden" id="orderAmount" name="orderAmount">

    <input type="submit" value="Submit Order" class="btn">
</form>

<?php endif; ?>

</div>
<template id="normalProductRow">
    <div class="productRow">
        <select name="ProductID[]" class="productSelect" required>
            <option value="">Select Product</option>
            <?php foreach ($products as $p): ?>
                <option value="<?= $p['ProductID'] ?>" data-price="<?= $p['Price'] ?>">
                    <?= htmlspecialchars($p['ProductName']) ?> (£<?= $p['Price'] ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <input type="hidden" name="Price[]" value="0">

        <input type="number" name="Quantity[]" class="quantityInput" min="1" value="1">

        <input type="text" class="lineTotal" readonly>

        <button type="button" class="removeRow btn">X</button>
    </div>
</template>

<script>
function updateTotals() {
    let total = 0;

    document.querySelectorAll(".productRow").forEach(row => {
        const select = row.querySelector(".productSelect");
        const qty = parseInt(row.querySelector(".quantityInput").value) || 0;
        const priceInput = row.querySelector('input[name="Price[]"]');

        let price = 0;

        // Manual product selected
        if (select && select.selectedOptions.length > 0) {
            const selectedOption = select.selectedOptions[0];
            price = parseFloat(selectedOption.dataset.price) || parseFloat(priceInput.value) || 0;
            priceInput.value = price; // IMPORTANT
        }

        const lineTotal = price * qty;
        row.querySelector(".lineTotal").value = "£" + lineTotal.toFixed(2);
        total += lineTotal;
    });

    document.getElementById("orderAmountDisplay").value = "£" + total.toFixed(2);
    document.getElementById("orderAmount").value = total.toFixed(2);
}

document.getElementById("addRow").addEventListener("click", () => {
    document.getElementById("addRow").addEventListener("click", () => {
    const template = document.getElementById("normalProductRow");
    const clone = template.content.cloneNode(true);
    document.getElementById("productList").appendChild(clone);
});
    row.querySelector(".productSelect").value = "";
    row.querySelector(".quantityInput").value = 1;
    row.querySelector(".lineTotal").value = "";
    row.querySelector('input[name="Price[]"]').value = 0;
    document.getElementById("productList").appendChild(row);
});

document.addEventListener("change", updateTotals);

document.addEventListener("click", e => {
    if (e.target.classList.contains("removeRow")) {
        if (document.querySelectorAll(".productRow").length > 1) {
            e.target.parentElement.remove();
            updateTotals();
        }
    }
});

updateTotals();
</script>

</body>
</html>
