<?php

namespace Mpociot\BotMan\Attachments;

class Location extends Attachment
{
    /** @var string */
    protected $latitude;

    /** @var string */
    protected $longitude;

	/**
	 * Message constructor.
	 * @param string $latitude
	 * @param string $longitude
	 * @param mixed $payload
	 */
    public function __construct($latitude, $longitude, $payload = null)
    {
	    parent::__construct($payload);
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
