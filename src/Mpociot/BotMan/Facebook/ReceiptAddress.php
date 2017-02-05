<?php

namespace Mpociot\BotMan\Facebook;

use JsonSerializable;

class ReceiptAddress implements JsonSerializable
{
    /** @var string */
    protected $street_1;

    /** @var string */
    protected $street_2;

    /** @var string */
    protected $city;

    /** @var string */
    protected $postal_code;

    /** @var string */
    protected $state;

    /** @var string */
    protected $country;

    /**
     * @return static
     */
    public static function create()
    {
        return new static;
    }

    /**
     * @param string $street
     * @return $this
     */
    public function street1($street)
    {
        $this->street_1 = $street;

        return $this;
    }

    /**
     * @param string $street
     * @return $this
     */
    public function street2($street)
    {
        $this->street_2 = $street;

        return $this;
    }

    /**
     * @param $city
     * @return $this
     */
    public function city($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @param $postalCode
     * @return $this
     */
    public function postalCode($postalCode)
    {
        $this->postal_code = $postalCode;

        return $this;
    }

    /**
     * @param $state
     * @return $this
     */
    public function state($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @param string $country
     * @return $this
     */
    public function country($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'street_1' => $this->street_1,
            'street_2' => $this->street_2,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'state' => $this->state,
            'country' => $this->country,
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
