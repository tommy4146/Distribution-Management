<?php
// Connect to DB
include 'connection.php';

$customerOptions = [];
$customerResult = $conn->query("SELECT CustomerID, Shop_Name FROM customer_details ORDER BY Shop_Name ASC");
while ($custRow = $customerResult->fetch_assoc()) {
    $customerOptions[] = $custRow;
}

// Filters
$where = [];
$params = [];
$types = "";


if (!empty($_GET['filter_Customer'])) {
    $where[] = "cm.CustomerID = ?";
    $params[] = (int)$_GET['filter_Customer'];
    $types .= "i";
}
// Filter by Shop Name
if (!empty($_GET['Shop_Name'])) {
    $where[] = "c.Shop_Name LIKE ?";
    $params[] = "%" . $_GET['Shop_Name'] . "%";
    $types .= "s";
}

// Filter by Comment Date
if (!empty($_GET['CommentDate'])) {
    $where[] = "DATE(cm.CommentDate) = ?";
    $params[] = $_GET['CommentDate'];
    $types .= "s";
}

// Build SQL
$sql = "
SELECT
    cm.CommentID,
    cm.CommentText,
    cm.CommentDate,
    c.CustomerID,
    c.Shop_Name
FROM comments cm
JOIN customer_details c ON cm.CustomerID = c.CustomerID
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY cm.CommentDate DESC, cm.CommentID DESC";

// Prepare + execute
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
    <title>All Comments</title>
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
    </ul>
</nav>
<div class="page-container">
<h2>All Comments</h2>

<!-- Filters -->
<form method="GET" style="margin-bottom:20px;">
    <label>Shop Name:</label>
    <select name="filter_Customer" onchange="this.form.submit()">
        <option value="">-- Select Customer --</option>
        <?php foreach ($customerOptions as $customer): ?>
            <option value="<?php echo $customer['CustomerID']; ?>" 
                <?php if (isset($_GET['filter_Customer']) && $_GET['filter_Customer'] == $customer['CustomerID']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($customer['Shop_Name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <label>Comment Date:</label>
    <input type="date" name="CommentDate" value="<?php echo htmlspecialchars($_GET['CommentDate'] ?? ''); ?>">

    <input type="submit" value="Filter">
    <a href="all_comments.php">Reset</a>
</form>

<!-- Comments Table -->
<table border="1">
    <tr>
        <th>Comment ID</th>
        <th>Shop Name</th>
        <th>Comment</th>
        <th>Comment Date</th>
    </tr>
  <?php while ($row = $result->fetch_assoc()): ?>
        <tr 
    onclick="window.location='view_customer.php?CustomerID=<?= $row['CustomerID'] ?>'"
    style="cursor:pointer;"
>
    <td><?= htmlspecialchars($row['CommentID']) ?></td>
    <td><?= htmlspecialchars($row['Shop_Name']) ?></td>
    <td><?= htmlspecialchars($row['CommentText']) ?></td>
    <td><?= date('d-m-Y', strtotime($row['CommentDate'])) ?></td>
</tr>
    <?php endwhile; ?>
</table>

</div>
<br>
<a href="index.php"><button>Back to Customers</button></a>

</body>
</html>

