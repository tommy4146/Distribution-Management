<?php
// update_stock.php
// $conn = mysqli connection
// $orderItems = array of order items: ['ProductName' => ..., 'Quantity' => ...]
include 'connection.php'; // include your DB connection
require 'config.php'; // include your DB connection

$lowStock = [];
$criticalStock = [];

foreach ($orderItems as $item) {
    // Get current stock
    $stmt = $conn->prepare("SELECT Quantity FROM stock WHERE ProductName = ?");
    $stmt->bind_param("s", $item['ProductName']);
    $stmt->execute();
    $stmt->bind_result($currentQty);
    $stmt->fetch();
    $stmt->close();

    // Update stock
    $newQty = $currentQty - $item['Quantity']; // allow negative stock
    $updateStmt = $conn->prepare("UPDATE stock SET Quantity = ?, LastUpdated = NOW() WHERE ProductName = ?");
    $updateStmt->bind_param("is", $newQty, $item['ProductName']);
    $updateStmt->execute();
    $updateStmt->close();

    // Track low stock
    if ($newQty < 15 && $newQty >= 7) {
        $lowStock[] = ['ProductName' => $item['ProductName'], 'Quantity' => $newQty];
    } elseif ($newQty < 7) {
        $criticalStock[] = ['ProductName' => $item['ProductName'], 'Quantity' => $newQty];
    }
}

// Return notifications (optional: display on orders page)
if (!empty($lowStock)) {
    echo "<div style='color:orange;'><strong>Low stock warning:</strong><ul>";
    foreach ($lowStock as $p) {
        echo "<li>{$p['ProductName']} - {$p['Quantity']} remaining</li>";
    }
    echo "</ul></div>";
}

if (!empty($criticalStock)) {
    echo "<div style='color:red;'><strong>Critical stock warning:</strong><ul>";
    foreach ($criticalStock as $p) {
        echo "<li>{$p['ProductName']} - {$p['Quantity']} remaining</li>";
    }
    echo "</ul></div>";
}
?>
