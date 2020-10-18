<?php


namespace BotMan\BotMan\Middleware\DialogFlowV2;


class Response
{
    /**
     * @var string
     */
    private $reply;

    /**
     * @var string
     */
    private $action;

    /**
     * @var bool
     */
    private $isComplete;

    /**
     * @var string
     */
    private $intent;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var array
     */
    private $contexts;

    /**
     * @return string
     */
    public function getReply(): string
    {
        return $this->reply;
    }

    /**
     * @param string $reply
     * @return Response
     */
    public function setReply(string $reply): Response
    {
        $this->reply = $reply;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return Response
     */
    public function setAction(string $action): Response
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->isComplete;
    }

    /**
     * @param bool $isComplete
     * @return Response
     */
    public function setIsComplete(bool $isComplete): Response
    {
        $this->isComplete = $isComplete;
        return $this;
    }

    /**
     * @return string
     */
    public function getIntent(): string
    {
        return $this->intent;
    }

    /**
     * @param string $intent
     * @return Response
     */
    public function setIntent(string $intent): Response
    {
        $this->intent = $intent;
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return Response
     */
    public function setParameters(array $parameters): Response
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @return array
     */
    public function getContexts(): array
    {
        return $this->contexts;
    }

    /**
     * @param array $contexts
     * @return Response
     */
    public function setContexts(array $contexts): Response
    {
        $this->contexts = $contexts;
        return $this;
    }


}