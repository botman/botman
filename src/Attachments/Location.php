<?php

namespace Mpociot\BotMan\Attachments;

class Location
{
    /** @var string */
    protected $latitude;

    /** @var string */
    protected $longitude;

    /**
     * Message constructor.
     * @param string $latitude
     * @param string $longitude
     */
    public function __construct($latitude, $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }
}
