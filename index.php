<?php
$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$today = date('Y-m-d');
$searchTerm = $_GET['search'] ?? '';

// --- SQL QUERY WITH CUSTOMER NAME INCLUDED ---
$sql = "SELECT CustomerID, Shop_Name, Customer_Name, Address, Contact_Number, NextContactDate 
        FROM customer_details";

if ($searchTerm) {
    $searchTermEscaped = $conn->real_escape_string($searchTerm);
    $sql .= " WHERE 
                Shop_Name LIKE '%$searchTermEscaped%' 
             OR Customer_Name LIKE '%$searchTermEscaped%' 
             OR Address LIKE '%$searchTermEscaped%'
             OR Contact_Number LIKE '%$searchTermEscaped%'";
}

// Sort by soonest next contact first, nulls last
$sql .= " ORDER BY 
            CASE WHEN NextContactDate IS NULL OR NextContactDate = '' THEN 1 ELSE 0 END,
            NextContactDate ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="all_orders.css?v=<?= time(); ?>">
    <title>Customers</title>
</head>
<body>

<nav class="navbar">
    <ul>
        <li><a href="Add_user.php">Add New Customer</a></li>
        <li><a href="all_orders.php">Undelivered Orders</a></li>
        <li><a href="every_order.php">All Orders</a></li>
        <li><a href="all_comments.php">Comments</a></li>
        <li><a href="stock_table.php">Stock Management</a></li>
    </ul>
</nav>

<h2>Customers Table</h2>

<div class="search-container">
    <form method="GET">
        <input type="text" name="search" placeholder="Search by shop, customer, address, or contact" 
               value="<?= htmlspecialchars($searchTerm) ?>">
        <input type="submit" value="Search">

        <?php if ($searchTerm): ?>
            <a href="index.php"><button type="button">Clear</button></a>
        <?php endif; ?>
    </form>
</div>

<a href="Add_user.php">
    <button>Add New Customer</button>
</a>

<div class="page-container">
<table>
    <tr>
        <th>ID</th>
        <th>Shop Name</th>
        <th>Customer Name</th>
        <th>Address</th>
        <th>Contact Number</th>
        <th>Next Contact Date</th>
        <th>Actions</th>
    </tr>

    <?php
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $CustomerID = $row['CustomerID'];

            // --- Highlight contact due within 2 days ---
            $highlightClass = "";
            if (!empty($row['NextContactDate'])) {
                $nextContact = strtotime($row['NextContactDate']);
                $diffDays = ($nextContact - strtotime($today)) / (60*60*24);

                if ($diffDays >= 0 && $diffDays <= 2) {
                    $highlightClass = "highlight";
                }
            }

            echo "<tr class='{$highlightClass}' 
                      onclick=\"window.location='view_customer.php?CustomerID={$CustomerID}'\" 
                      style='cursor:pointer'>
                    <td>{$row['CustomerID']}</td>
                    <td>{$row['Shop_Name']}</td>
                    <td>{$row['Customer_Name']}</td>
                    <td>{$row['Address']}</td>
                    <td>{$row['Contact_Number']}</td>
                    <td>{$row['NextContactDate']}</td>
                    <td>
                        <a href='Add_user.php?CustomerID={$CustomerID}'><button>Edit</button></a>

                        <br><br>

                        <button onclick=\"event.stopPropagation(); 
                                if(confirm('Are you sure you want to delete this customer?')) { 
                                    window.location='delete_customer.php?CustomerID={$CustomerID}'; 
                                }\">
                            Delete
                        </button>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='7'>No records found</td></tr>";
    }
    $conn->close();
    ?>
</table>
</div>

</body>
</html>
