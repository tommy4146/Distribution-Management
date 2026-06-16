<?php
// send_invoice.php?CustomerID=123&OrderID=456
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load config
$config = include 'config.php';

// Database connection
include 'connection.php';

// Get IDs from GET
$CustomerID = isset($_POST['CustomerID']) ? (int)$_POST['CustomerID'] : 0;
$OrderID    = isset($_POST['OrderID']) ? (int)$_POST['OrderID'] : 0;

if (!$CustomerID || !$OrderID) {
    die("CustomerID and OrderID required.");
}
// ---------- Fetch customer info ----------
$stmt = $conn->prepare("SELECT Shop_Name, Address, Contact_Number, Email FROM customer_details WHERE CustomerID=?");
$stmt->bind_param("i", $CustomerID);
$stmt->execute();
$stmt->bind_result($Shop_Name, $Address, $Contact_Number, $Email);
if (!$stmt->fetch()) die("Customer not found.");
$stmt->close();

// ---------- Fetch order info ----------
$orderStmt = $conn->prepare("SELECT OrderID, OrderDate FROM orders WHERE OrderID=? AND CustomerID=?");
$orderStmt->bind_param("ii", $OrderID, $CustomerID);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
if ($orderResult->num_rows == 0) die("Order not found for this customer.");
$order = $orderResult->fetch_assoc();
$orderStmt->close();
$OrderDate = $order['OrderDate'];

// ---------- Fetch order items ----------
$itemStmt = $conn->prepare("
    SELECT oi.ProductID, oi.Quantity, oi.Price, p.ProductName
    FROM order_items oi
    JOIN products p ON oi.ProductID = p.ProductID
    WHERE oi.OrderID = ?
");
$itemStmt->bind_param("i", $OrderID);
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

// ---------- Generate PDF HTML ----------
$html = '
<style>
body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 12px; color: #333; margin:0; padding:0; }
.container { max-width:800px; margin:20px auto; padding:30px; border:1px solid #e6e6e6; border-radius:8px; background:#fff; }
h1,h2,h3,h4 { margin:0; padding:0; }
.header { text-align:center; margin-bottom:40px; }
.header .business-name { font-size:24px; font-weight:bold; color:#2c3e50; }
.header .business-contact { font-size:14px; color:#7f8c8d; margin-top:4px; line-height:1.5; }
.invoice-details { margin-bottom:30px; }
.invoice-details .invoice-title { font-size:18px; font-weight:bold; color:#2c3e50; }
.invoice-details p { margin:4px 0; font-size:13px; }
.section { margin-bottom:25px; }
.section-title { font-weight:bold; font-size:14px; border-bottom:1px solid #ccc; padding-bottom:4px; margin-bottom:10px; color:#34495e; }
.two-col { width:100%; display:flex; justify-content:space-between; margin-bottom:20px; }
.col { width:48%; }
.info-block { line-height:1.5em; font-size:13px; color:#555; }
table.items { width:100%; border-collapse:collapse; margin-top:10px; }
table.items th, table.items td { padding:10px; text-align:left; border-bottom:1px solid #e6e6e6; font-size:13px; }
table.items th { background:#f5f5f5; font-weight:bold; color:#2c3e50; }
table.items td { color:#555; }
.summary-table { width:300px; float:right; margin-top:20px; border-collapse:collapse; }
.summary-table td { padding:6px; font-size:13px; color:#333; }
.summary-table .total-row td { font-weight:bold; font-size:14px; border-top:2px solid #2c3e50; }
.thank-you { clear:both; margin-top:50px; font-size:12px; text-align:center; color:#7f8c8d; }
</style>

<div class="container">
    <div class="header">
        <div class="business-name">'.htmlspecialchars($config['business']['name']).'</div>
        <div class="business-contact">
            '.htmlspecialchars($config['business']['address']).'<br>
            Phone: '.htmlspecialchars($config['business']['phone']).'<br>
            Email: '.htmlspecialchars($config['business']['email']).'
        </div>
    </div>

    <div class="invoice-details">
        <div class="invoice-title">Invoice #'.$OrderID.'</div>
        <p>Order Number: '.$OrderID.'</p>
        <p>Date: '.date("M j, Y", strtotime($OrderDate)).'</p>
    </div>

    <div class="two-col">
        <div class="col">
            <div class="section-title">Ship To</div>
            <div class="info-block">
                '.htmlspecialchars($Shop_Name).'<br>
                '.htmlspecialchars($Address).'<br>
                '.htmlspecialchars($Contact_Number).'
            </div>
        </div>
        <div class="col">
            <div class="section-title">Buyer</div>
            <div class="info-block">
                '.htmlspecialchars($Email).'
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Items</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>';
foreach ($orderItems as $item) {
    $html .= '<tr>
        <td>'.htmlspecialchars($item['ProductName']).'</td>
        <td>£'.number_format($item['Price'],2).'</td>
        <td>'.$item['Quantity'].'</td>
        <td>£'.number_format($item['Subtotal'],2).'</td>
    </tr>';
}
$html .= '
            </tbody>
        </table>
    </div>

    <table class="summary-table">
        <tr><td>Items Total</td><td>£'.number_format($totalAmount,2).'</td></tr>
        <tr class="total-row"><td>Total</td><td>£'.number_format($totalAmount,2).'</td></tr>
    </table>

    <div class="thank-you">Thank you for your order!</div>
</div>';

// ---------- Generate PDF ----------
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfOutput = $dompdf->output();

// ---------- Send email ----------
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    $mail->SMTPSecure = $config['smtp']['secure'];
    $mail->Port = $config['smtp']['port'];

    $mail->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);
    $mail->addAddress($Email, $Shop_Name);

    $mail->Subject = "Invoice #$OrderID";
    $mail->Body = "Hello $Shop_Name,\n\nPlease find attached your invoice.\n\nRegards,\n".$config['business']['name'];

    // Attach PDF directly from memory
    $mail->addStringAttachment($pdfOutput, "invoice_$OrderID.pdf");

    $mail->send();
    echo "Invoice sent successfully to $Email!";
} catch (Exception $ex) {
    echo "Mailer Error: " . $mail->ErrorInfo;
}
?>
