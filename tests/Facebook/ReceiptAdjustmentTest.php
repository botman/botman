<?php

namespace Mpociot\BotMan\Tests;

use Illuminate\Support\Arr;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Facebook\ReceiptAdjustment;

class ReceiptAdjustmentTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $adjustment = new ReceiptAdjustment('Voucher');
        $this->assertInstanceOf(ReceiptAdjustment::class, $adjustment);
    }

    /**
     * @test
     **/
    public function it_can_set_name()
    {
        $adjustment = new ReceiptAdjustment('Voucher');

        $this->assertSame('Voucher', Arr::get($adjustment->toArray(), 'name'));
    }

    /**
     * @test
     **/
    public function it_can_set_amount()
    {
        $adjustment = new ReceiptAdjustment('Voucher');
        $adjustment->amount(19.00);

        $this->assertSame(19.00, Arr::get($adjustment->toArray(), 'amount'));
    }
}
