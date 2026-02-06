<?php
// Connect to the database
$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get CustomerID from GET or POST
$CustomerID = 0;
if (isset($_GET['CustomerID'])) {
    $CustomerID = (int)$_GET['CustomerID'];
} elseif (isset($_POST['CustomerID'])) {
    $CustomerID = (int)$_POST['CustomerID'];
}

// Fetch Shop Name if valid
$Shop_Name = "";
if ($CustomerID > 0) {
    $result = $conn->query("SELECT Shop_Name FROM customer_details WHERE CustomerID=$CustomerID");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $Shop_Name = $row['Shop_Name'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentText = $_POST['CommentText'] ?? '';
    if ($commentText !== "") {
        $stmt = $conn->prepare("INSERT INTO comments (CustomerID, CommentText) VALUES (?, ?)");
        $stmt->bind_param("is", $CustomerID, $commentText);
        $stmt->execute();
        $stmt->close();

        // Redirect back to the Add/Edit User page
        header("Location: customer_comments.php?CustomerID=".$CustomerID);
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Comment</title>
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
    </ul>
</nav>

    <div class="page-container">
    <h2>Add Comment for Customer: <?php echo htmlspecialchars($Shop_Name); ?></h2>

    <form method="POST">
        <input type="hidden" name="CustomerID" value="<?php echo $CustomerID; ?>">
        <textarea name="CommentText" required></textarea><br><br>
        <input type="submit" value="Add Comment">
    </form>

    <br>
    <br>

    <form action="customer_comments.php" method="get" style="display:inline;">
    <input type="hidden" name="CustomerID" value="<?php echo $CustomerID; ?>">
    <button type="submit">View Comments</button>
</form>

    </div>
</body>
</html>