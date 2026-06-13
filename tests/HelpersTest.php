<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers.php';

class HelpersTest extends TestCase
{
    public function testFormatIndianCurrencyPositiveNumbers()
    {
        $this->assertEquals('₹ 1,50,000.00', formatIndianCurrency(150000));
        $this->assertEquals('₹ 1,00,00,000.00', formatIndianCurrency(10000000));
        $this->assertEquals('₹ 500.00', formatIndianCurrency(500));
        $this->assertEquals('₹ 1,000.00', formatIndianCurrency(1000));
    }

    public function testFormatIndianCurrencyWithDecimals()
    {
        $this->assertEquals('₹ 1,50,000.50', formatIndianCurrency(150000.5));
        $this->assertEquals('₹ 1,50,000.55', formatIndianCurrency(150000.55));
        $this->assertEquals('₹ 1,50,000.56', formatIndianCurrency(150000.556)); // sprintf("%.2f", ...) rounds it
        $this->assertEquals('₹ 0.50', formatIndianCurrency(0.5));
    }

    public function testFormatIndianCurrencyNegativeNumbers()
    {
        $this->assertEquals('-₹ 1,50,000.00', formatIndianCurrency(-150000));
        $this->assertEquals('-₹ 1,00,00,000.00', formatIndianCurrency(-10000000));
        $this->assertEquals('-₹ 500.00', formatIndianCurrency(-500));
    }

    public function testFormatIndianCurrencyZero()
    {
        $this->assertEquals('₹ 0.00', formatIndianCurrency(0));
    }

    public function testFormatIndianCurrencyStrings()
    {
        $this->assertEquals('₹ 1,50,000.00', formatIndianCurrency("150000"));
        $this->assertEquals('₹ 1,50,000.50', formatIndianCurrency("150000.50"));
    }

    public function testFormatCurrencyAlias()
    {
        $this->assertEquals('₹ 1,50,000.00', formatCurrency(150000));
        $this->assertEquals('-₹ 500.00', formatCurrency(-500));
    }
}
