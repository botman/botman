<?php

namespace Mpociot\BotMan\Tests;

use Illuminate\Support\Arr;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Facebook\ReceiptSummary;

class ReceiptSummaryTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $element = new ReceiptSummary;
        $this->assertInstanceOf(ReceiptSummary::class, $element);
    }

    /**
     * @test
     **/
    public function it_can_set_subtotal()
    {
        $summary = new ReceiptSummary;
        $summary->subtotal(99.99);

        $this->assertSame(99.99, Arr::get($summary->toArray(), 'subtotal'));
    }

    /**
     * @test
     **/
    public function it_can_set_shipping_cost()
    {
        $summary = new ReceiptSummary;
        $summary->shippingCost(55.55);

        $this->assertSame(55.55, Arr::get($summary->toArray(), 'shipping_cost'));
    }

    /**
     * @test
     **/
    public function it_can_set_total_tax()
    {
        $summary = new ReceiptSummary;
        $summary->totalTax(12.12);

        $this->assertSame(12.12, Arr::get($summary->toArray(), 'total_tax'));
    }

    /**
     * @test
     **/
    public function it_can_set_total_cost()
    {
        $summary = new ReceiptSummary;
        $summary->totalCost(99.11);

        $this->assertSame(99.11, Arr::get($summary->toArray(), 'total_cost'));
    }
}
