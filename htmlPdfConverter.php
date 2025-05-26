<?php
require_once realpath(__DIR__ . '/vendor/autoload.php');// Include the AWS SDK for PHP


use Dompdf\Dompdf;

function fileCreate($preName, $html){
    //Load Signature
    // Use environment variable for signature path with a default
    $imagePath = $_ENV['APP_SIGNATURE_IMAGE_PATH'] ?? 'templates/Devraj_Sign.jpeg';
    if (file_exists($imagePath)) {
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageSrc = 'data:image/jpeg;base64,' . $imageData;
    } else {
        // Fallback if image not found, or log an error
        $imageSrc = ''; // Or a placeholder image / error message
        error_log("Signature image not found at path: " . $imagePath);
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

    // Sanitize preName further for filesystem safety
    $safePreName = preg_replace('/[^A-Za-z0-9_-]/', '', $preName);
    if (empty($safePreName)) {
        $safePreName = 'invoice'; // Default if company name is empty or all special chars
    }

    // Generate a unique filename
    $uniqueId = uniqid($safePreName . '_', true); // Prefixed and more entropy
    $outputFilePath = $uniqueId . '.pdf';
    
    file_put_contents($outputFilePath, $dompdf->output());
    return $outputFilePath;
}

function getUpdatedPdf($bill) {
    // Get the HTML content from the file
    $htmlContent = file_get_contents("templates/billTemplate.html");

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
            // Existing placeholders
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
            "TOTAL_AMT",
            // New placeholders from template
            "APP_GSTIN_PLACEHOLDER",
            "APP_PHONE_PRIMARY_PLACEHOLDER",
            "APP_PHONE_SECONDARY_PLACEHOLDER",
            "APP_COMPANY_NAME_PLACEHOLDER",
            "APP_COMPANY_ADDRESS_PLACEHOLDER",
            "APP_BANK_ACCOUNT_NAME_PLACEHOLDER",
            "APP_BANK_NAME_PLACEHOLDER",
            "APP_BANK_ACC_NO_PLACEHOLDER",
            "APP_BANK_IFSC_PLACEHOLDER"
        ),
        array(
            // Existing values
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
            $totalAmount,
            // New values from $_ENV with defaults
            $_ENV['APP_GSTIN'] ?? 'YOUR_GSTIN_HERE',
            $_ENV['APP_PHONE_PRIMARY'] ?? '0000000000',
            $_ENV['APP_PHONE_SECONDARY'] ?? '0000000000',
            $_ENV['APP_COMPANY_NAME'] ?? 'Your Company Name',
            $_ENV['APP_COMPANY_ADDRESS'] ?? 'Your Company Address',
            $_ENV['APP_BANK_ACCOUNT_NAME'] ?? 'Your Bank Account Name',
            $_ENV['APP_BANK_NAME'] ?? 'Your Bank Name',
            $_ENV['APP_BANK_ACC_NO'] ?? '0000000000000',
            $_ENV['APP_BANK_IFSC'] ?? 'YOURIFSC000'
        ),
        $htmlContent
    );
    $specialChars = array('!', '@', '#', ' ', '&', '^');
    $buyerName = str_replace($specialChars, "", $bill['buyerCompany']);
    return fileCreate($buyerName, $dynamicContent);
}

?>