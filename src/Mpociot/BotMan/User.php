<?php

namespace Mpociot\BotMan;

class User
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $first_name;

    /** @var string */
    protected $last_name;

    /** @var string */
    protected $username;

    /**
     * User constructor.
     * @param $id
     * @param $first_name
     * @param $last_name
     * @param $username
     */
    public function __construct($id, $first_name, $last_name, $username)
    {
        $this->id = $id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
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
}
