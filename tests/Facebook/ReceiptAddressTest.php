<?php

namespace Mpociot\BotMan\Tests;

use Illuminate\Support\Arr;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Facebook\ReceiptAddress;

class ReceiptAddressTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $address = new ReceiptAddress;
        $this->assertInstanceOf(ReceiptAddress::class, $address);
    }

    /**
     * @test
     **/
    public function it_can_set_street1()
    {
        $address = new ReceiptAddress;
        $address->street1('Botstreet 1');

        $this->assertSame('Botstreet 1', Arr::get($address->toArray(), 'street_1'));
    }

    /**
     * @test
     **/
    public function it_can_set_street2()
    {
        $address = new ReceiptAddress;
        $address->street2('Botstreet 2');

        $this->assertSame('Botstreet 2', Arr::get($address->toArray(), 'street_2'));
    }

    /**
     * @test
     **/
    public function it_can_set_city()
    {
        $address = new ReceiptAddress;
        $address->city('AI City');

        $this->assertSame('AI City', Arr::get($address->toArray(), 'city'));
    }

    /**
     * @test
     **/
    public function it_can_set_postal_code()
    {
        $address = new ReceiptAddress;
        $address->postalCode('12345');

        $this->assertSame('12345', Arr::get($address->toArray(), 'postal_code'));
    }

    /**
     * @test
     **/
    public function it_can_set_stat()
    {
        $address = new ReceiptAddress();
        $address->state('Botstate');

        $this->assertSame('Botstate', Arr::get($address->toArray(), 'state'));
    }

    /**
     * @test
     **/
    public function it_can_set_country()
    {
        $address = new ReceiptAddress();
        $address->country('Botland');

        $this->assertSame('Botland', Arr::get($address->toArray(), 'country'));
    }
}
