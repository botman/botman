<?php

namespace Mpociot\BotMan\Attachments;

class Location extends Attachment
{

	/**
	 * Pattern that messages use to identify location attachment.
	 */
	const PATTERN = '%%%_LOCATION_%%%';

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
	 * @param string $latitude
	 * @param string $longitude
	 * @return Location
	 */
	public static function url($latitude, $longitude){
		return new self($latitude, $longitude);
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
