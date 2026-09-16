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

    public function testMultiItemCsvExportRowFormatting()
    {
        $mockBillRow = [
            'invoice_number' => 102,
            'created_on' => '2026-09-14 12:00:00',
            'buyer_company' => 'Test Comp XYZ',
            'buyer_address' => '123 Test St',
            'vehicle_number' => 'VEH-999',
            'vehicle_freight' => 300.00,
            'payment_received' => 0.00,
            'balance' => 18300.00
        ];

        $mockItems = [
            ['item_name' => 'RAGI HSN:10082031', 'bag' => 44480.03, 'quantity' => 30.00, 'price' => 600.00],
            ['item_name' => 'Ragi Flour HSN:10082031', 'bag' => 10.00, 'quantity' => 20.00, 'price' => 50.00]
        ];

        $exportedRows = [];
        $first = true;
        foreach ($mockItems as $item) {
            $itemTotal = floatval($item['quantity']) * floatval($item['price']);
            $exportedRows[] = [
                $mockBillRow['invoice_number'],
                date('Y-m-d', strtotime($mockBillRow['created_on'])),
                $mockBillRow['buyer_company'],
                $mockBillRow['buyer_address'],
                $item['item_name'],
                $item['bag'],
                $item['quantity'],
                formatIndianCurrency($item['price']),
                $mockBillRow['vehicle_number'],
                $first ? formatIndianCurrency($mockBillRow['vehicle_freight']) : formatIndianCurrency(0),
                formatIndianCurrency($itemTotal + ($first ? floatval($mockBillRow['vehicle_freight']) : 0)),
                $first ? formatIndianCurrency($mockBillRow['payment_received']) : formatIndianCurrency(0),
                $first ? formatIndianCurrency($mockBillRow['balance']) : formatIndianCurrency(0)
            ];
            $first = false;
        }

        $this->assertCount(2, $exportedRows);
        $this->assertEquals('RAGI HSN:10082031', $exportedRows[0][4]);
        $this->assertEquals('₹ 300.00', $exportedRows[0][9]);
        $this->assertEquals('Ragi Flour HSN:10082031', $exportedRows[1][4]);
        $this->assertEquals('₹ 0.00', $exportedRows[1][9]);
    }
}
