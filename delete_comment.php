<?php
// Connect to the database
$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the CommentID and CustomerID from the URL
$CommentID = isset($_GET['CommentID']) ? (int)$_GET['CommentID'] : 0;
$CustomerID = isset($_GET['CustomerID']) ? (int)$_GET['CustomerID'] : 0;

// Delete the comment if valid
if ($CommentID > 0) {
    $stmt = $conn->prepare("DELETE FROM comments WHERE CommentID = ?");
    $stmt->bind_param("i", $CommentID);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the Add/Edit User page for this customer
header("Location: add_user.php?CustomerID=" . $CustomerID);
exit();
?>