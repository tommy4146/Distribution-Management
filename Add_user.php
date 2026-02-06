<?php
$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$CustomerID = $_GET['CustomerID'] ?? 0;

// Default empty values
$Shop_Name = $Address = $Contact_Number = $Next_Contact_Date = $Email = $Customer_Name = "";

/* -------------------------------------------------------
   LOAD CUSTOMER IF EDITING
------------------------------------------------------- */
if ($CustomerID) {
    $stmt = $conn->prepare("
        SELECT Shop_Name, Customer_Name, Address, Contact_Number, NextContactDate, Email
        FROM customer_details WHERE CustomerID=?
    ");
    $stmt->bind_param("i", $CustomerID);
    $stmt->execute();
    $stmt->bind_result($Shop_Name, $Customer_Name, $Address, $Contact_Number, $Next_Contact_Date, $Email);
    $stmt->fetch();
    $stmt->close();
}

/* -------------------------------------------------------
   HANDLE FORM SUBMISSION
------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Collect POST data
    $Shop_Name = $_POST['Shop_Name'];
    $Customer_Name = $_POST['Customer_Name'];
    $Address = $_POST['Address'];
    $Contact_Number = $_POST['Contact_Number'];
    $Next_Contact_Date = $_POST['Next_Contact_Date'];
    $Email = $_POST['Email'];

    /* ---------- UPDATE EXISTING CUSTOMER ---------- */
    if ($CustomerID) {
        $stmt = $conn->prepare("
            UPDATE customer_details 
            SET Shop_Name=?, Customer_Name=?, Address=?, Contact_Number=?, NextContactDate=?, Email=?
            WHERE CustomerID=?
        ");
        $stmt->bind_param("ssssssi",
            $Shop_Name, $Customer_Name, $Address, $Contact_Number, $Next_Contact_Date, $Email, $CustomerID
        );
        $stmt->execute();
        $stmt->close();

        header("Location: Add_user.php?CustomerID=" . $CustomerID);
        exit;
    }

    /* ---------- INSERT NEW CUSTOMER ---------- */
    else {
        $stmt = $conn->prepare("
            INSERT INTO customer_details 
            (Shop_Name, Customer_Name, Address, Contact_Number, NextContactDate, Email)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssss",
            $Shop_Name, $Customer_Name, $Address, $Contact_Number, $Next_Contact_Date, $Email
        );
        $stmt->execute();

        $newID = $stmt->insert_id;
        $stmt->close();

        header("Location: Add_user.php?CustomerID=" . $newID);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $CustomerID ? "Edit Customer" : "Add Customer" ?></title>
    <link rel="stylesheet" href="add_user.css?v=<?= time(); ?>">
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
    <h2><?= $CustomerID ? "Edit Customer" : "Add New Customer" ?></h2>

    <form method="POST">

        <label>Shop Name:</label>
        <input type="text" name="Shop_Name" value="<?= htmlspecialchars($Shop_Name) ?>" required>

        <label>Customer Name:</label>
        <input type="text" name="Customer_Name" value="<?= htmlspecialchars($Customer_Name) ?>" >

        <label>Address:</label>
        <input type="text" name="Address" value="<?= htmlspecialchars($Address) ?>" required>

        <label>Contact Number:</label>
        <input type="text" name="Contact_Number" value="<?= htmlspecialchars($Contact_Number) ?>">

        <label>Email:</label>
        <input type="email" name="Email" value="<?= htmlspecialchars($Email) ?>">

        <label>Next Contact Date:</label>
        <input type="date" name="Next_Contact_Date"
               value="<?= htmlspecialchars($Next_Contact_Date) ?>">

        <input type="submit" value="<?= $CustomerID ? "Update Customer" : "Add Customer" ?>" class="btn">
    </form>

    <br>

    <?php if ($CustomerID): ?>
        <a href="new_order.php?CustomerID=<?= $CustomerID ?>">
            <button class="btn">Add Order</button>
        </a>

        <a href="add_comment.php?CustomerID=<?= $CustomerID ?>">
            <button class="btn">Add Comment</button>
        </a>
    <?php endif; ?>

    <br><br>
    <button onclick="window.location='index.php'">Back to Customers</button>
</div>

</body>
</html>
