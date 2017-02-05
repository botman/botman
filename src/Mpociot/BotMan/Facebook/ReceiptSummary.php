<?php

namespace Mpociot\BotMan\Facebook;

use JsonSerializable;

class ReceiptSummary implements JsonSerializable
{

    /** @var integer */
    protected $subtotal;

    /** @var integer */
    protected $shipping_cost;

    /** @var integer */
    protected $total_tax;

    /** @var integer */
    protected $total_cost;

    /**
     * @return static
     */
    public static function create()
    {
        return new static;
    }

    /**
     * @param string $subtotal
     * @return $this
     */
    public function subtotal($subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * @param string $shippingCost
     * @return $this
     */
    public function shippingCost($shippingCost)
    {
        $this->shipping_cost = $shippingCost;

        return $this;
    }

    /**
     * @param $totalTax
     * @return $this
     */
    public function totalTax($totalTax)
    {
        $this->total_tax = $totalTax;

        return $this;
    }

    /**
     * @param $totalCost
     * @return $this
     */
    public function totalCost($totalCost)
    {
        $this->total_cost = $totalCost;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'total_tax' => $this->total_tax,
            'total_cost' => $this->total_cost
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
