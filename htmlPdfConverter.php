<?php
require_once realpath(__DIR__ . '/vendor/autoload.php');// Include the AWS SDK for PHP


use Dompdf\Dompdf;

function fileCreate($preName, $html){
    //Load Signature
    $imagePath = __DIR__ . '/template/Devraj_Sign.png';
    $imageData = @file_get_contents($imagePath);
    if ($imageData !== false) {
        $base64 = base64_encode($imageData);
        $imageSrc = 'data:image/png;base64,' . $base64;
    } else {
        // Fallback: Empty 1x1 pixel to prevent the "Big X" box
        $imageSrc = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    }

    // replace signature in html
    $html = str_replace('IMAGE_SRC', $imageSrc, $html);
    // Create a new Dompdf instance
    $dompdf = new Dompdf();

    // Load the HTML content
    $dompdf->loadHtml($html);

    // (Optional) Set any options or configurations
    $dompdf->setPaper(array(0, 0, 595.28, 850.89), 'portrait');

    // Render the HTML to PDF
    $dompdf->render();

    // Output the PDF to the browser
    $randomCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

    $preName = basename($preName);
    $preName = str_replace(array('/', '\\'), '', $preName);

    $outputFilePath = $preName."".$randomCode.'.pdf';
    file_put_contents($outputFilePath, $dompdf->output());
    return $outputFilePath;
}

function getUpdatedPdf($bill) {
    // Get the HTML content from the file
    $htmlContent = file_get_contents(__DIR__ . "/template/billTemplate.html");

    // Normalize items array
    $itemsList = [];
    if (isset($bill['items']) && is_array($bill['items']) && count($bill['items']) > 0) {
        $itemsList = $bill['items'];
    } else {
        // Fallback for single item structure
        $itemsList[] = [
            'item_name' => isset($bill['itemName']) ? $bill['itemName'] : '',
            'bag' => isset($bill['bag']) ? $bill['bag'] : 0,
            'quantity' => isset($bill['quantity']) ? $bill['quantity'] : 0,
            'price' => isset($bill['price']) ? $bill['price'] : 0
        ];
    }

    $subtotal = 0;
    $itemRowsHtml = '';
    $rowIndex = 1;

    foreach ($itemsList as $it) {
        $itemName = isset($it['item_name']) ? $it['item_name'] : (isset($it['itemName']) ? $it['itemName'] : '');
        $bag = isset($it['bag']) ? floatval($it['bag']) : 0;
        $qty = isset($it['quantity']) ? floatval($it['quantity']) : 0;
        $price = isset($it['price']) ? floatval($it['price']) : 0;
        $lineAmount = $qty * $price;
        $subtotal += $lineAmount;

        $itemRowsHtml .= '<tr class="details">';
        $itemRowsHtml .= '<td>' . $rowIndex++ . '</td>';
        $itemRowsHtml .= '<td>' . htmlspecialchars($itemName) . '</td>';
        $itemRowsHtml .= '<td>' . htmlspecialchars($bag) . '</td>';
        $itemRowsHtml .= '<td>' . htmlspecialchars($qty) . '</td>';
        $itemRowsHtml .= '<td>' . htmlspecialchars($price) . '</td>';
        $itemRowsHtml .= '<td>' . htmlspecialchars(number_format($lineAmount, 2)) . '</td>';
        $itemRowsHtml .= '</tr>';
    }

    // Keep minimum 3 rows for template layout aesthetic if fewer items
    while ($rowIndex <= 3) {
        $itemRowsHtml .= '<tr class="details"><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
        $rowIndex++;
    }

    $freight = isset($bill['vehicleFreight']) ? floatval($bill['vehicleFreight']) : 0;
    $totalAmount = $subtotal + $freight;
    $dateFormatInDDMMYYYY = $bill['createdOn'];

    $dynamicContent = str_replace(
        array(
            "BUYER_NAME",
            "BUYER_COMPANY",
            "BUYER_ADDRESS",
            "ITEM_ROWS",
            "VEHICLE_NUMBER",
            "INVOICE_NUMBER",
            "DATE",
            "VEHICLE_FREIGHT",
            "TOTAL_AMT"
        ),
        array(
            isset($bill['buyerName']) ? $bill['buyerName'] : "",
            isset($bill['buyerCompany']) ? $bill['buyerCompany'] : "",
            isset($bill['buyerAddress']) ? $bill['buyerAddress'] : "",
            $itemRowsHtml,
            isset($bill['vehicleNumber']) ? $bill['vehicleNumber'] : "",
            $bill['invoiceNumber'],
            $dateFormatInDDMMYYYY,
            number_format($freight, 2),
            number_format($totalAmount, 2)
        ),
        $htmlContent
    );
    $specialChars = array('!', '@', '#', ' ', '&', '^');
    $buyerName = str_replace($specialChars, "", $bill['buyerCompany']);
    return fileCreate($buyerName, $dynamicContent);
}

?>