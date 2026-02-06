<table>
    <thead>
        <tr>
            <th>Delivered</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price (per)</th>
            <th>Line Total</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $orderTotal = 0;
        foreach ($order['items'] as $item):
            $lineTotal = $item['Quantity'] * $item['Price'];
            $orderTotal += $lineTotal;
        ?>
        <tr>
            <td>
                <input type="checkbox" name="delivered_items[]" value="<?= htmlspecialchars($item['ProductName']) ?>"
                    <?= $item['delivered'] ?? false ? 'checked' : '' ?>>
            </td>
            <td><?= htmlspecialchars($item['ProductName']) ?></td>
            <td><?= $item['Quantity'] ?></td>
            <td>£<?= number_format($item['Price'], 2) ?></td>
            <td>£<?= number_format($lineTotal, 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="4" style="text-align:right; font-weight:bold;">Total:</td>
            <td>£<?= number_format($orderTotal, 2) ?></td>
        </tr>
    </tbody>
</table>
