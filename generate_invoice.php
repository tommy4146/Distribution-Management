<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
require 'vendor/autoload.php';
use Dompdf\Dompdf;

$conn = new mysqli("localhost", "root", "", "customer_details");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$orderID = isset($_GET['OrderID']) ? (int)$_GET['OrderID'] : 0;
if (!$orderID) die("Error: No Order ID provided.");

// ---------- Fetch order + customer ----------
$stmt = $conn->prepare("
    SELECT 
        c.Shop_Name, c.Customer_Name, c.Address, c.Contact_Number, c.Email,
        o.OrderID, o.OrderDate, o.OrderAmount
    FROM orders o
    JOIN customer_details c ON o.CustomerID = c.CustomerID
    WHERE o.OrderID = ?
");
$stmt->bind_param("i", $orderID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Order not found.");
$order = $result->fetch_assoc();
$stmt->close();

// ---------- Fetch order items ----------
$itemStmt = $conn->prepare("
    SELECT p.ProductName, oi.Quantity, oi.Price
    FROM order_items oi
    JOIN products p ON oi.ProductID = p.ProductID
    WHERE oi.OrderID = ?
");
$itemStmt->bind_param("i", $orderID);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();
$orderItems = [];
$totalAmount = 0;
while ($row = $itemResult->fetch_assoc()) {
    $row['Subtotal'] = $row['Quantity'] * $row['Price'];
    $totalAmount += $row['Subtotal'];
    $orderItems[] = $row;
}
$itemStmt->close();
$conn->close();

// ---------- Build styled invoice HTML ----------
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice #' . $orderID . '</title>
<style>
body { font-family: Arial, sans-serif; color: #333; margin:0; padding:0; }
.container { max-width: 800px; margin: 20px auto; padding: 30px; border:1px solid #ccc; border-radius:8px; background:#fff; }
.header { text-align:center; margin-bottom:30px; }
.header h1 { margin:0; color:#2c3e50; }
.header p { margin:2px 0; color:#555; font-size:13px; }
.customer-details, .invoice-details { margin-bottom:20px; }
.customer-details h3, .invoice-details h3 { margin-bottom:5px; color:#34495e; }
table { width:100%; border-collapse: collapse; margin-top:15px; }
table th, table td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
table th { background:#f4f4f4; color:#2c3e50; }
.total { text-align:right; font-weight:bold; font-size:16px; margin-top:10px; }
.footer { text-align:center; margin-top:40px; color:#888; font-size:12px; }
</style>
</head>
<body>
<div class="container">

    <!-- Business Info -->
    <div class="header">
        <h1>PatblacksOnline</h1>
        <p>30 Upper High Street</p>
        <p>Phone: 07896174219 | Email: accounts@patblacksonline.co.uk</p>
    </div>

    <div class="invoice-header" style="text-align:center; margin-bottom:20px;">
        <h2>Invoice #' . $orderID . '</h2>
        <p>Order Date: ' . date("d-m-Y", strtotime($order['OrderDate'])) . '</p>
    </div>

    <div class="customer-details">
        <h3>Customer Details</h3>
        <p><strong>Shop Name:</strong> ' . htmlspecialchars($order['Shop_Name']) . '</p>
        <p><strong>Customer Name:</strong> ' . htmlspecialchars($order['Customer_Name']) . '</p>
        <p><strong>Address:</strong> ' . htmlspecialchars($order['Address']) . '</p>
        <p><strong>Contact:</strong> ' . htmlspecialchars($order['Contact_Number']) . '</p>
        <p><strong>Email:</strong> ' . htmlspecialchars($order['Email']) . '</p>
    </div>

    <div class="invoice-details">
        <h3>Order Items</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price (£)</th>
                    <th>Subtotal (£)</th>
                </tr>
            </thead>
            <tbody>';
foreach ($orderItems as $item) {
    $html .= '<tr>
        <td>' . htmlspecialchars($item['ProductName']) . '</td>
        <td>' . $item['Quantity'] . '</td>
        <td>' . number_format($item['Price'],2) . '</td>
        <td>' . number_format($item['Subtotal'],2) . '</td>
    </tr>';
}
$html .= '
            </tbody>
        </table>
        <p class="total">Total Amount: £' . number_format($totalAmount,2) . '</p>
    </div>

    <div class="footer">
        Thank you for your business!
    </div>

</div>
</body>
</html>';

// ---------- Render PDF ----------
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("invoice_$orderID.pdf", ["Attachment" => false]);
?>
