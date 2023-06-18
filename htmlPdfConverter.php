<?php
require_once realpath(__DIR__ . '/vendor/autoload.php');// Include the AWS SDK for PHP
require_once('aws_s3.php');


use Dompdf\Dompdf;

function fileCreate($preName, $html){
    //Load Signature
    $imagePath = 'template/Devraj_Sign.jpeg';
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

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

    $outputFilePath = $preName."".$randomCode.'.pdf';
    file_put_contents($outputFilePath, $dompdf->output());
    return $outputFilePath;
}

function getUpdatedPdf($bill) {
    // Get the HTML content from the file
    $htmlContent = file_get_contents("template/billTemplate.html");

    $amount = 0;
    if (isset($bill['quantity']) && isset($bill['price'])) {
        $amount = $bill['quantity'] * $bill['price'];
    }

    $totalAmount = $amount;
    if (isset($bill['vehicleFreight'])) {
        $totalAmount += intval($bill['vehicleFreight']);
    }

    $dateFormatInDDMMYYYY = $bill['createdOn'];

    $dynamicContent = str_replace(
        array(
            "BUYER_NAME",
            "BUYER_COMPANY",
            "BUYER_ADDRESS",
            "ITEM_NAME",
            "BAG",
            "QUANTITY",
            "PRICE",
            "AMOUNT",
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
            isset($bill['itemName']) ? $bill['itemName'] : "",
            isset($bill['bag']) ? $bill['bag'] : "",
            isset($bill['quantity']) ? $bill['quantity'] : "",
            isset($bill['price']) ? $bill['price'] : "",
            $amount,
            isset($bill['vehicleNumber']) ? $bill['vehicleNumber'] : "",
            $bill['invoiceNumber'],
            $dateFormatInDDMMYYYY,
            isset($bill['vehicleFreight']) ? $bill['vehicleFreight'] : "",
            $totalAmount
        ),
        $htmlContent
    );
    $buyerName = str_replace(" ", "", $bill['buyerCompany']);
    return fileCreate($buyerName, $dynamicContent);
}

?>