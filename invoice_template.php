<?php
// invoice_template.php
// Expects $invoice (array) and $order (array) and $items (array) and $business (array)

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"/>
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; color:#000; }
  .wrap { max-width: 800px; margin:0 auto; padding:20px; }
  .header { text-align:right; }
  .brand { text-align:left; font-size:18px; font-weight:700; color:#000; }
  .meta { margin-top:10px; }
  table { width:100%; border-collapse: collapse; margin-top:20px; }
  table th, table td { border:1px solid #ddd; padding:8px; text-align:left; }
  table th { background:#f5f5f5; }
  .right { text-align:right; }
  .total-row td { font-weight:700; }
  footer { margin-top:30px; font-size:11px; color:#555; text-align:center; }
</style>
</head>
<body>
  <div class="wrap">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <div class="brand"><?= htmlspecialchars($business['name']) ?></div>
      <div class="header">
        <div>Invoice: <?= htmlspecialchars($invoice['InvoiceNumber']) ?></div>
        <div>Date: <?= htmlspecialchars(date('Y-m-d', strtotime($invoice['CreatedAt'] ?? $order['OrderDate']))) ?></div>
      </div>
    </div>

    <div class="meta">
      <strong>Bill To:</strong><br>
      <?= htmlspecialchars($order['Shop_Name'] ?? '') ?><br>
      <?= htmlspecialchars($order['Address'] ?? '') ?><br>
      <?= htmlspecialchars($order['Contact_Number'] ?? '') ?>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width:60%;">Product</th>
          <th style="width:10%;">Qty</th>
          <th style="width:15%;">Unit</th>
          <th style="width:15%;">Line</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): 
            $line = $it['Quantity'] * $it['Price'];
        ?>
        <tr>
          <td><?= htmlspecialchars($it['ProductName']) ?></td>
          <td class="right"><?= $it['Quantity'] ?></td>
          <td class="right">£<?= number_format($it['Price'],2) ?></td>
          <td class="right">£<?= number_format($line,2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="total-row">
          <td colspan="3" class="right">Total</td>
          <td class="right">£<?= number_format($order['OrderAmount'],2) ?></td>
        </tr>
      </tfoot>
    </table>

    <footer>
      <?= htmlspecialchars($business['name']) ?> — <?= htmlspecialchars($business['email']) ?> <?= $business['phone'] ? ' | '.$business['phone'] : '' ?>
    </footer>
  </div>
</body>
</html>
