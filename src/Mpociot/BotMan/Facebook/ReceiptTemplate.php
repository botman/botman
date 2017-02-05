<?php

namespace Mpociot\BotMan\Facebook;

use JsonSerializable;

class ReceiptTemplate implements JsonSerializable
{
    /** @var string */
    protected $recipient_name;

    /** @var string */
    protected $merchant_name;

    /** @var string */
    protected $order_number;

    /** @var string */
    protected $currency;

    /** @var string */
    protected $payment_method;

    /** @var string */
    protected $order_url;

    /** @var string */
    protected $timestamp;

    /** @var array */
    protected $elements = [];

    /** @var array */
    protected $address;

    /** @var array */
    protected $summary;

    /** @var array */
    protected $adjustments = [];

    /**
     * @return static
     */
    public static function create()
    {
        return new static;
    }

    /**
     * @param $name
     * @return $this
     */
    public function recipientName($name)
    {
        $this->recipient_name = $name;

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function merchantName($name)
    {
        $this->merchant_name = $name;

        return $this;
    }

    /**
     * @param $orderNumber
     * @return $this
     */
    public function orderNumber($orderNumber)
    {
        $this->order_number = $orderNumber;

        return $this;
    }

    /**
     * @param $currency
     * @return $this
     */
    public function currency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @param $paymentMethod
     * @return $this
     */
    public function paymentMethod($paymentMethod)
    {
        $this->payment_method = $paymentMethod;

        return $this;
    }

    /**
     * @param $orderUrl
     * @return $this
     */
    public function orderUrl($orderUrl)
    {
        $this->order_url = $orderUrl;

        return $this;
    }

    /**
     * @param $timestamp
     * @return $this
     */
    public function timestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * @param ReceiptElement $element
     * @return $this
     */
    public function addElement(ReceiptElement $element)
    {
        $this->elements[] = $element->toArray();

        return $this;
    }

    /**
     * @param array $elements
     * @return $this
     */
    public function addElements(array $elements)
    {
        foreach ($elements as $element) {
            if ($element instanceof ReceiptElement) {
                $this->elements[] = $element->toArray();
            }
        }

        return $this;
    }

    /**
     * @param ReceiptAddress $address
     * @return $this
     */
    public function addAddress(ReceiptAddress $address)
    {
        $this->address = $address->toArray();

        return $this;
    }

    /**
     * @param ReceiptSummary $summary
     * @return $this
     */
    public function addSummary(ReceiptSummary $summary)
    {
        $this->summary = $summary->toArray();

        return $this;
    }

    /**
     * @param ReceiptAdjustment $adjustment
     * @return $this
     */
    public function addAdjustment(ReceiptAdjustment $adjustment)
    {
        $this->adjustments[] = $adjustment->toArray();

        return $this;
    }

    /**
     * @param array $adjustments
     * @return $this
     */
    public function addAdjustments(array $adjustments)
    {
        foreach ($adjustments as $adjustment) {
            if ($adjustment instanceof ReceiptAdjustment) {
                $this->adjustments[] = $adjustment->toArray();
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'receipt',
                    'recipient_name' => $this->recipient_name,
                    'merchant_name' => $this->merchant_name,
                    'order_number' => $this->order_number,
                    'currency' => $this->currency,
                    'payment_method' => $this->payment_method,
                    'order_url' => $this->order_url,
                    'timestamp' => $this->timestamp,
                    'elements' => $this->elements,
                    'address' => $this->address,
                    'summary' => $this->summary,
                    'adjustments' => $this->adjustments,
                ],
            ],
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
