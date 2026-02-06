 <?php
// Connect to the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "customer_details";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
$CustomerID = (int)$_GET['CustomerID'];
$result = $conn->query("SELECT Shop_Name FROM customer_details WHERE CustomerID=$CustomerID");
$Shop_Name = $result->fetch_assoc()['Shop_Name'];
 
 
 
 
 
 $comments = [];
if (isset($CustomerID)) {
    $commentQuery = $conn->query("SELECT * FROM comments WHERE CustomerID=$CustomerID ORDER BY CommentDate DESC");
    while ($commentRow = $commentQuery->fetch_assoc()) {
        $comments[] = $commentRow;
    }
}
?>

 
 
 
 <!DOCTYPE html>
 <html>
    
 <head>
     <title>Customer Comments</title>
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

 
 <h3>Comments</h3>
    <table border="1" cellpadding = "5">
        <tr>
            <th>Comment</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
        <?php if (!empty($comments)) { 
             foreach ($comments as $comment){ 
                echo "<tr>
                    <td>".$comment['CommentText']."</td>
                    <td>".htmlspecialchars($comment['CommentDate']). "</td>
                <td>
                        <button onclick=\"if(confirm('Delete comment?')) { window.location='delete_comment.php?CommentID=".$comment['CommentID']."&CustomerID=".$CustomerID."'; }\">Delete</button>
                    </td>
                </tr>";
            }
        }else{
            echo "<tr><td colspan='3'>No comments found</td></tr>";
    
        } ?>
    </table>
    
    <form action="add_comment.php" method="get" style="display:inline;">
    <input type="hidden" name="CustomerID" value="<?php echo $CustomerID; ?>">
    <button type="submit">Add comment</button>
    </form>
    <br>
    <br>
    <form action="add_user.php" method="get" style="display:inline;">
    <input type="hidden" name="CustomerID" value="<?php echo $CustomerID; ?>">
    <button type="submit">Back to customer details</button>
</form>
</body>
    
    </html>