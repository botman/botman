<?php

namespace Mpociot\BotMan\Tests;

use Illuminate\Support\Arr;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Facebook\ReceiptAddress;
use Mpociot\BotMan\Facebook\ReceiptElement;
use Mpociot\BotMan\Facebook\ReceiptSummary;
use Mpociot\BotMan\Facebook\ReceiptTemplate;
use Mpociot\BotMan\Facebook\ReceiptAdjustment;

class ReceiptTemplateTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $template = new ReceiptTemplate;
        $this->assertInstanceOf(ReceiptTemplate::class, $template);
    }

    /**
     * @test
     **/
    public function it_can_set_recipient_name()
    {
        $template = new ReceiptTemplate;
        $template->recipientName('BotMan AI');

        $this->assertEquals('BotMan AI', Arr::get($template->toArray(), 'attachment.payload.recipient_name'));
    }

    /**
     * @test
     **/
    public function it_can_set_can_set_merchant_name()
    {
        $template = new ReceiptTemplate();
        $template->merchantName('Marcel');

        $this->assertSame('Marcel', Arr::get($template->toArray(), 'attachment.payload.merchant_name'));
    }

    /**
     * @test
     **/
    public function it_can_set_order_number()
    {
        $tem = new ReceiptTemplate();
        $tem->orderNumber('12345');

        $this->assertSame('12345', Arr::get($tem->toArray(), 'attachment.payload.order_number'));
    }

    /**
     * @test
     **/
    public function it_can_set_currency()
    {
        $template = new ReceiptTemplate();
        $template->currency('EUR');

        $this->assertSame('EUR', Arr::get($template->toArray(), 'attachment.payload.currency'));
    }

    /**
     * @test
     **/
    public function it_can_set_payment_method()
    {
        $template = new ReceiptTemplate();
        $template->paymentMethod('VISA');

        $this->assertSame('VISA', Arr::get($template->toArray(), 'attachment.payload.payment_method'));
    }

    /**
     * @test
     **/
    public function it_can_set_order_url()
    {
        $template = new ReceiptTemplate();
        $template->orderUrl('http://test.at');

        $this->assertSame('http://test.at', Arr::get($template->toArray(), 'attachment.payload.order_url'));
    }

    /**
     * @test
     **/
    public function it_can_set_timestamp()
    {
        $template = new ReceiptTemplate();
        $template->timestamp('1428444852');

        $this->assertSame('1428444852', Arr::get($template->toArray(), 'attachment.payload.timestamp'));
    }

    /**
     * @test
     **/
    public function it_can_add_an_element()
    {
        $template = new ReceiptTemplate;
        $template->addElement(ReceiptElement::create('BotMan Documentation'));

        $this->assertSame('BotMan Documentation',
            Arr::get($template->toArray(), 'attachment.payload.elements.0.title'));
    }

    /**
     * @test
     **/
    public function it_can_add_multiple_elements()
    {
        $template = new ReceiptTemplate;
        $template->addElements([
            ReceiptElement::create('BotMan Documentation'),
            ReceiptElement::create('BotMan Laravel Starter'),
        ]);

        $this->assertSame('BotMan Documentation',
            Arr::get($template->toArray(), 'attachment.payload.elements.0.title'));
        $this->assertSame('BotMan Laravel Starter',
            Arr::get($template->toArray(), 'attachment.payload.elements.1.title'));
    }

    /**
     * @test
     **/
    public function it_cant_add_an_address()
    {
        $template = new ReceiptTemplate;
        $template->addAddress(ReceiptAddress::create()->state('Botland'));

        $this->assertSame('Botland', Arr::get($template->toArray(), 'attachment.payload.address.state'));
    }

    /**
     * @test
     **/
    public function it_cant_add_a_summary()
    {
        $template = new ReceiptTemplate;
        $template->addSummary(ReceiptSummary::create()->totalCost(99));

        $this->assertSame(99, Arr::get($template->toArray(), 'attachment.payload.summary.total_cost'));
    }

    /**
     * @test
     **/
    public function it_can_add_an_adjustment()
    {
        $template = new ReceiptTemplate;
        $template->addAdjustment(ReceiptAdjustment::create('Bonus'));

        $this->assertSame('Bonus', Arr::get($template->toArray(), 'attachment.payload.adjustments.0.name'));
    }

    /**
     * @test
     **/
    public function it_can_add_adjustments()
    {
        $template = new ReceiptTemplate;
        $template->addAdjustments([ReceiptAdjustment::create('Bonus'), ReceiptAdjustment::create('Voucher')]);

        $this->assertSame('Bonus', Arr::get($template->toArray(), 'attachment.payload.adjustments.0.name'));
        $this->assertSame('Voucher', Arr::get($template->toArray(), 'attachment.payload.adjustments.1.name'));
    }
}
