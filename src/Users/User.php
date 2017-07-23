<?php

namespace BotMan\BotMan\Users;

use BotMan\BotMan\Interfaces\UserInterface;

class User implements UserInterface
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $first_name;

    /** @var string */
    protected $last_name;

    /** @var string */
    protected $username;

    /** @var array */
    protected $user_info;

    /**
     * User constructor.
     * @param $id
     * @param $first_name
     * @param $last_name
     * @param $username
     * @param $user_info
     */
    public function __construct($id = null, $first_name = null, $last_name = null, $username = null, $user_info = [])
    {
        $this->id = $id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->username = $username;
        $this->user_info = (array) $user_info;
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

    /**
     * {@inheritdoc}
     */
    public function getInfo()
    {
        return $this->user_info;
    }
}
