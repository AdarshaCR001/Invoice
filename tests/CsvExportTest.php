<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers.php';

class CsvExportTest extends TestCase
{
    public function testCsvExportRowFormatting()
    {
        $mockBillRow = [
            'invoice_number' => 101,
            'created_on' => '2023-05-15 10:30:00',
            'buyer_company' => 'Acme Corp',
            'buyer_address' => '123 Tech Street',
            'item_name' => 'Steel Rods',
            'bag' => 50,
            'quantity' => 1000.00,
            'price' => 45.50,
            'vehicle_number' => 'MH-12-AB-1234',
            'vehicle_freight' => 1500.00,
            'payment_received' => 42000.00,
            'balance' => 5000.00
        ];

        $invoiceDate = date('Y-m-d', strtotime($mockBillRow['created_on']));
        $amount = $mockBillRow['price'] * $mockBillRow['quantity'];
        $balance = $mockBillRow['balance'] !== null ? $mockBillRow['balance'] : 0.00;

        $formattedRow = [
            $mockBillRow['invoice_number'],
            $invoiceDate,
            $mockBillRow['buyer_company'],
            $mockBillRow['buyer_address'],
            $mockBillRow['item_name'],
            $mockBillRow['bag'],
            $mockBillRow['quantity'],
            formatIndianCurrency($mockBillRow['price']),
            formatIndianCurrency($amount),
            $mockBillRow['vehicle_number'],
            formatIndianCurrency($mockBillRow['vehicle_freight']),
            formatIndianCurrency($mockBillRow['payment_received'] !== null ? $mockBillRow['payment_received'] : 0.00),
            formatIndianCurrency($balance)
        ];

        $expectedRow = [
            101,
            '2023-05-15',
            'Acme Corp',
            '123 Tech Street',
            'Steel Rods',
            50,
            1000.00,
            '₹ 45.50',
            '₹ 45,500.00',
            'MH-12-AB-1234',
            '₹ 1,500.00',
            '₹ 42,000.00',
            '₹ 5,000.00'
        ];

        $this->assertEquals($expectedRow, $formattedRow);
    }
}
