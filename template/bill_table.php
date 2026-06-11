<!-- Table to display the data -->
<div class="table-container">
<table class="table">
<thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Buyer Name</th>
                    <th>Buyer Company</th>
                    <th>Buyer Address</th>
                    <th>Item Name</th>
                    <th>Bag</th>
                    <th>Quantity (KG)</th>
                    <th>Price (per KG)</th>
                    <th>Amount</th>
                    <th>Vehicle Number</th>
                    <th>Vehicle Freight</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
        <?php if (!empty($result)): foreach ($result as $row) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['invoice_number']); ?></td>
                <td><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                <td><?php echo htmlspecialchars($row['buyer_company']); ?></td>
                <td><?php echo htmlspecialchars($row['buyer_address']); ?></td>
                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                <td><?php echo htmlspecialchars($row['bag']); ?></td>
                <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                <td><?php echo htmlspecialchars($row['price']); ?></td>
                <td><?php echo htmlspecialchars($row['price']*$row['quantity']); ?></td>
                <td><?php echo htmlspecialchars($row['vehicle_number']); ?></td>
                <td><?php echo htmlspecialchars($row['vehicle_freight']); ?></td>
                <td>
                    <a class="file-download" href="<?php echo htmlspecialchars($row['url']); ?>" target="_blank" download>Download</a>
                    <button class="btn btn-warning edit-bill-button" data-bill='<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>'>Edit</button>
                </td>
            </tr>
        <?php } endif; ?>
        </tbody>
    </table>
        </div>

    <div class="pagination">
    <?php
    if ($total_pages > 0) { // Only display pagination if there are pages
        // Determine the range of page links to display
        $num_links = 10; // Number of page links to show
        $start = max(1, $page - floor($num_links / 2));
        $end = min($start + $num_links - 1, $total_pages);

        // Display the first page link
        if ($start > 1) {
            echo '<a href="?page=1">1</a>';
            if ($start > 2) { // Add ellipsis if there's a gap
                echo '<span>&hellip;</span>';
            }
        }

        // Display the page links within the range
        for ($i = $start; $i <= $end; $i++) {
            echo '<a href="?page=' . $i . '"';
            if ($i == $page) {
                echo ' class="active"';
            }
            echo '>' . $i . '</a>';
        }

        // Display the last page link
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) { // Add ellipsis if there's a gap
                echo '<span>&hellip;</span>';
            }
            echo '<a href="?page=' . $total_pages . '">' . $total_pages . '</a>';
        }
    }
    ?>
</div>
