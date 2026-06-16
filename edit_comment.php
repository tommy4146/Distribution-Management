<?php
include 'connection.php';

$CommentID = $_GET['CommentID'] ?? 0;
if (!$CommentID) die("Comment not specified.");

// Fetch comment info
$stmt = $conn->prepare("SELECT CustomerID, CommentText FROM comments WHERE CommentID=?");
$stmt->bind_param("i", $CommentID);
$stmt->execute();
$stmt->bind_result($CustomerID, $CommentText);
if (!$stmt->fetch()) die("Comment not found.");
$stmt->close();

// Fetch customer shop name
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
    $updatedComment = $_POST['CommentText'] ?? '';
    if ($updatedComment !== "") {
        $stmt = $conn->prepare("UPDATE comments SET CommentText=? WHERE CommentID=?");
        $stmt->bind_param("si", $updatedComment, $CommentID);
        $stmt->execute();
        $stmt->close();

        // Redirect back to view comments for this customer
        header("Location: customer_comments.php?CustomerID=".$CustomerID);
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Comment</title>
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
    <h2>Edit Comment for Customer: <?= htmlspecialchars($Shop_Name) ?></h2>

    <form method="POST">
        <input type="hidden" name="CommentID" value="<?= $CommentID ?>">
        <textarea name="CommentText" required><?= htmlspecialchars($CommentText) ?></textarea><br><br>
        <input type="submit" value="Update Comment" class="btn">
        <a href="customer_comments.php?CustomerID=<?= $CustomerID ?>"><button type="button" class="btn">Cancel</button></a>
    </form>
</div>
</body>
</html>