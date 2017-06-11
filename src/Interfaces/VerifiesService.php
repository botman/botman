<?php

namespace BotMan\BotMan\Interfaces;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface VerifiesService
{
    public function verifyRequest(Request $request) : ?Response;
}