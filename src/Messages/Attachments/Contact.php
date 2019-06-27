<?php

namespace BotMan\BotMan\Messages\Attachments;

class Contact extends Attachment
{
    /**
     * Pattern that messages use to identify contact attachment.
     */
    const PATTERN = '%%%_CONTACT_%%%';

    /** @var string */
    protected $phone_number;

    /** @var string */
    protected $first_name;

    /** @var string */
    protected $last_name;

    /** @var string */
    protected $user_id;

    /** @var string */
    protected $vcard;

    /**
     * Message constructor.
     *
     * @param string $phone_number
     * @param string $first_name
     * @param string $last_name
     * @param string $user_id
     * @param string $vcard
     * @param mixed  $payload
     */
    public function __construct($phone_number, $first_name, $last_name, $user_id, $vcard = null, $payload = null)
    {
        parent::__construct($payload);
        $this->phone_number = $phone_number;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->user_id = $user_id;
        $this->vcard = $vcard;
    }

    /**
     * @param string $phone_number
     * @param string $first_name
     * @param string $last_name
     * @param string $user_id
     * @param string $vcard
     *
     * @return Contact
     */
    public static function create($phone_number, $first_name, $last_name, $user_id, $vcard = null)
    {
        return new self($phone_number, $first_name, $last_name, $user_id, $vcard);
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return string
     */
    public function getVcard()
    {
        return $this->vcard;
    }

    /**
     * Get the instance as a web accessible array.
     * This will be used within the WebDriver.
     *
     * @return array
     */
    public function toWebDriver()
    {
        return [
            'type' => 'contact',
            'phone_number' => $this->phone_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'user_id' => $this->user_id,
            'vcard' => $this->vcard,
        ];
    }
}
