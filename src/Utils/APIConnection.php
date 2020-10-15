<?php

namespace App\Utils;

use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class APIConnection
{
    private const INBENTAKEY = "nyUl7wzXoKtgoHnd2fB0uRrAv0dDyLC+b4Y6xngpJDY=";
    private const INBENTASECRET = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJwcm9qZWN0IjoieW9kYV9jaGF0Ym90X2VuIn0.anf_eerFhoNq6J8b36_qbD4VqngX79-yyBKWih_eA1-HyaMe2skiJXkRNpyWxpjmpySYWzPGncwvlwz5ZRE7eg";
    private const BASE_API_URL = "https://api.inbenta.io/v1/auth";
    private const HEROKUAPP_BASEURL = "https://inbenta-graphql-swapi-prod.herokuapp.com/api";
    private const HEROKUAPP_URI = "/api";
    private const HEROKUAPP_CHAR_QUERY = "{allPeople(first: 5){people{name}}}";
    private const HEROKUAPP_FILM_QUERY = "{allFilms(first: 5){films{title}}}";
    private const API_VERSION = "v1";
    private const MSG_ENDP = "conversation/message";
    private const CONV_ENDP = "conversation";
    private const HIST_ENDP = "history";
    private const AUTH_EXPIRE= "X-INBENTA-AUTH-EXPIRATION";

    private $httpRequestLib;
    private $session;

    public function __construct(HttpRequestLib $httpRequestLib, SessionInterface $session)  {
        $this->httpRequestLib = $httpRequestLib;
        $this->session = $session;
    }

    /**
     * @param string $listName
     * @return array
     * returns an array with some star wars elements depending on the list name
     * @throws GuzzleException
     */
    public function getHerokuList(string $listName) : array {
        $body = null;
        if ($listName == "characters") {
            $body = ['query' => self::HEROKUAPP_CHAR_QUERY];
        }
        else if ($listName == "films") {
            $body = ['query' => self::HEROKUAPP_FILM_QUERY];
        }

        if ($body != null) {
            $response = $this->httpRequestLib->sendJsonPost(self::HEROKUAPP_BASEURL, self::HEROKUAPP_URI, $body);
            if ($listName == "characters") {
                return $response["body"]->data->allPeople->people;
            }
            else if ($listName == "films") {
                return $response["body"]->data->allFilms->films;
            }
        }

        return [];
    }

    /**
     * @param string $message
     * @return array
     * @throws Exception
     * @throws GuzzleException
     * sends the given message to the active conversation and returns the bot response and a flag if received
     */
    public function sendMessageToCurrentConversation(string $message) : array {
        $messageResponse = ["message" => "", "flag" => ""];

        //first validates that a conversation is started
        if (!$this->isConversationActive()) {
            $this->startConversation();
        }

        //second sends the message to the API
        $token = $this->getApiToken();
        $conversationId = $this->session->get("CONVERSATION_ID");
        $headers = $this->getYodaBotHeaders(self::INBENTAKEY, false, $token, $conversationId);
        $body = $this->getYodaBotBody("", $message);
        $url = $this->session->get("API_URL")."/".self::API_VERSION."/".self::MSG_ENDP;
        $jsonResp = $this->httpRequestLib->sendPost($url, $headers, $body);
        $jsonResp = $jsonResp["body"];

        //if any response is received back, returns it
        if (isset($jsonResp->answers) and sizeof($jsonResp->answers) > 0) {
            $answer = $jsonResp->answers[0];
            $messageResponse["message"] = $answer->message;
            if (isset($answer->flags) and sizeof($answer->flags) > 0) {
                $messageResponse["flag"] = $answer->flags[0];
            }
        }
        else if (isset($jsonResp->error)) {
            $messageResponse["flag"] = "error";
        }

        return $messageResponse;
    }

    /**
     * @return array
     * Returns an array with the object messages of the active conversation if any
     * @throws GuzzleException
     */
    public function getConversationHistory() : array {
        $histMessages = [];
        if ($this->isConversationActive()) {
            $token = $this->getApiToken();
            $conversationId = $this->session->get("CONVERSATION_ID");
            $headers = $this->getYodaBotHeaders(self::INBENTAKEY, false, $token, $conversationId);
            $url = $this->session->get("API_URL")."/".self::API_VERSION."/".self::CONV_ENDP."/".self::HIST_ENDP;
            $jsonResp = $this->httpRequestLib->sendGet($url, $headers);
            $histMessages = $jsonResp["body"];
        }

        return $histMessages;
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     * starts a conversation with YodaBot
     */
    private function startConversation() : void {
        //starting conversation
        $token = $this->getApiToken();
        $headers = $this->getYodaBotHeaders(self::INBENTAKEY, false, $token);
        $url = $this->session->get("API_URL")."/".self::API_VERSION."/".self::CONV_ENDP;
        $jsonResp = $this->httpRequestLib->sendPost($url, $headers, []);
        $this->session->set("CONVERSATION_ID", $jsonResp["body"]->sessionToken);

        //saving conversation expire date
        $timeStamp = $jsonResp["headers"][self::AUTH_EXPIRE][0];
        $expiration = date('Y-m-d H.i:s', $timeStamp);
        $this->session->set("CONVERSATION_EXPIRATION", $expiration);
    }

    /**
     * returns true if there is an active conversation with YodaBot
     */
    private function isConversationActive() : bool
    {
        $isActive = false;
        //validates session id currently active
        if ($this->session->get("CONVERSATION_ID")) {
            $now = date('Y-m-d H.i:s');
            $expirationDate = $this->session->get("CONVERSATION_EXPIRATION");
            if ($expirationDate > $now) {
                $isActive = true;
            }
        }

        return $isActive;
    }

    /**
     * @return string
     * returns a valid API token
     * @throws GuzzleException
     */
    private function getApiToken() : string {
        $token = "";

        //validates the session stored token if any
        if ($this->isValidToken()) {
            $token = $this->session->get("API_TOKEN");
        }
        else {
            //requests a new valid token and refreshes the API url
            $headers = $this->getYodaBotHeaders(self::INBENTAKEY);
            $data = $this->getYodaBotBody(self::INBENTASECRET);
            $jsonResp = $this->httpRequestLib->sendPost(self::BASE_API_URL, $headers, $data);
            $jsonResp = $jsonResp["body"];
            $token = $jsonResp->accessToken;
            $expirationDate = date('Y-m-d H.i:s', $jsonResp->expiration);
            $this->session->set("TOKEN_EXPIRATION", $expirationDate);
            $this->session->set("API_TOKEN", $token);
            $this->session->set("API_URL", $jsonResp->apis->chatbot);
            $this->session->set("TOKEN_EXPIRATION", $jsonResp->expiration);
        }

        return $token;
    }

    /**
     * @return bool
     * returns true if there is a valid token stored in session, false if not
     */
    private function isValidToken(): bool {
        $validToken = false;

        //validates the session stored token if any
        if ($this->session->get("API_TOKEN")) {
            $now = date('Y-m-d H.i:s');
            $expirationDate = date('Y-m-d H.i:s', $this->session->get("TOKEN_EXPIRATION"));
            if ($expirationDate > $now) {
                $validToken = true;
            }
        }

        return $validToken;
    }

    /**
     * @param string $secret
     * @param string $message
     * @return array
     * returns an array with the body parameters for an htpRequest to YodaBot
     */
    private function getYodaBotBody(string $secret = "", string $message = "") : array {
        $bodyArr = array();

        if (strlen($secret)) {
            $bodyArr["secret"] = $secret;
        }
        if (strlen($message)) {
            $bodyArr["message"] = $message;
        }

        return $bodyArr;
    }

    /**
     * @param string $inbKey
     * @param bool $addContentType
     * @param string $authorization
     * @param string $inbSession
     * @return array
     * returns an array with the http request headers from the incoming params
     */
    private function getYodaBotHeaders(string $inbKey, bool $addContentType = true, string $authorization = null, string $inbSession = null) : array {
        $headerArr = [];
        $headerArr["x-inbenta-key"] = $inbKey;

        if ($addContentType) {
            $headerArr["Content-type"] = "application/x-www-form-urlencoded";
        }

        if (isset($authorization)) {
            $headerArr["Authorization"] = "Bearer " .$authorization;
        }

        if (isset($inbSession)) {
            $headerArr["x-inbenta-session"] = "Bearer " .$inbSession;
        }

        return $headerArr;
    }
}