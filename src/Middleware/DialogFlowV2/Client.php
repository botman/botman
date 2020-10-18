<?php


namespace BotMan\BotMan\Middleware\DialogFlowV2;


use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Middleware\DialogFlowV2\Exception\DialogFlowV2NoIntentException;
use BotMan\BotMan\Middleware\DialogFlowV2\Exception\DialogFlowV2NoResultException;
use Google\ApiCore\ApiException;
use Google\Cloud\Dialogflow\V2\DetectIntentResponse;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\QueryResult;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;

class Client
{

    /**
     * @var SessionsClient
     */
    private $sessionsClient;
    /**
     * @var string
     */
    private $lang;

    /**
     * Client constructor.
     * @param string $lang
     * @param SessionsClient|null $sessionClient
     */
    public function __construct(string $lang, SessionsClient $sessionClient = null)
    {
        $this->lang = $lang;
        $this->sessionsClient = $sessionClient ?? new SessionsClient();
    }


    /**
     * @param IncomingMessage $message
     * @return Response
     * @throws ApiException
     */
    public function getResponse(IncomingMessage $message): Response
    {
        $queryInput = $this->queryInput($message->getText(), $this->lang);

        $intentResponse = $this->getIntentResponse(md5($message->getConversationIdentifier()), $queryInput);

        $queryResult = $intentResponse->getQueryResult();

        if(null === $queryResult){
            throw new DialogFlowV2NoResultException('No result from DialogFlow api.');
        }

        if(null === $queryResult->getIntent()){
            throw new DialogFlowV2NoIntentException('No intent detected.');
        }

        $response = new Response();
        $response
            ->setIntent($queryResult->getIntent()->getDisplayName())
            ->setParameters($this->getParameters($queryResult))
            ->setContexts($this->getContexts($queryResult))
            ->setAction($queryResult->getAction())
            ->setReply($queryResult->getFulfillmentText())
            ->setIsComplete(!$queryResult->getAllRequiredParamsPresent())
        ;

        return $response;
    }

    /**
     * @param $text
     * @param string $languageCode
     * @return QueryInput
     */
    private function queryInput($text, string $languageCode): QueryInput
    {
        // create text input
        $textInput = new TextInput();
        $textInput->setText($text);
        $textInput->setLanguageCode($languageCode);

        // create query input
        $queryInput = new QueryInput();
        $queryInput->setText($textInput);
        return $queryInput;
    }

    /**
     * @param $sessionId
     * @param $queryInput
     * @return DetectIntentResponse
     * @throws ApiException
     */
    private function getIntentResponse($sessionId, $queryInput): DetectIntentResponse
    {
        $sessionName = $this->sessionsClient::sessionName(getenv('GOOGLE_CLOUD_PROJECT'),
            $sessionId ?: uniqid('', true));

        // get response and relevant info
        $response = $this->sessionsClient->detectIntent($sessionName, $queryInput);

        $this->sessionsClient->close();

        return $response;
    }

    /**
     * @param QueryResult $queryResult
     * @return array
     */
    private function getParameters(QueryResult $queryResult): array
    {
        $parameters = [];
        $queryParameters = $queryResult->getParameters();
        if (null !== $queryParameters) {
            foreach ($queryParameters->getFields() as $name => $field) {
                $parameters[$name] = $field->getStringValue();
            }
        }

        return $parameters;
    }

    /**
     * @param QueryResult|null $queryResult
     * @return array
     */
    private function getContexts(QueryResult $queryResult): array
    {
        $contexts = [];
        foreach ($queryResult->getOutputContexts() as $context) {
            $cparams = [];
            $parameters = $context->getParameters();
            if ($parameters !== null) {
                foreach ($parameters->getFields() as $name => $field) {
                    $cparams[$name] = $field->getStringValue();
                }
            }

            $contexts[] = [
                'name' => substr(strrchr($context->getName(), '/'), 1),
                'parameters' => $cparams,
                'lifespan' => $context->getLifespanCount(),
            ];
        }
        return $contexts;
    }
}