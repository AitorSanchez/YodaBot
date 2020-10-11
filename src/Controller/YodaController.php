<?php


namespace App\Controller;


use Symfony\Component\HttpFoundation\Response;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class YodaController extends AbstractController
{
    private $INBENTAKEY = "nyUl7wzXoKtgoHnd2fB0uRrAv0dDyLC+b4Y6xngpJDY=";
    private $INBENTASECRET = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJwcm9qZWN0IjoieW9kYV9jaGF0Ym90X2VuIn0.anf_eerFhoNq6J8b36_qbD4VqngX79-yyBKWih_eA1-HyaMe2skiJXkRNpyWxpjmpySYWzPGncwvlwz5ZRE7eg";
    private $BASE_API_URL = "https://api.inbenta.io/v1/auth";

    /**
     * @Route("/", name="default")
     * returns the view of the home page
     */
    public function index() {
        return $this->render("base.html.twig");
    }

    /**
     * @Route("/sendMessageToYoda", name="sendMessage")
     * @return Response
     * sends the $message to the YodaBot and returns its response if any is received
     */
    public function sendMessageToYoda() : Response
    {
        //validates POST parameters
        $message = "Hello";
        if (isset($_POST["message"])) {
            $message = $_POST["message"];
        }

        //if message is received, starts the process
        $response = "";
        if (strlen($message) > 0) {
            if (!$this->isConversationActive()) {
                $this->startConversation();
            }
            $response = $this->sendMessageToCurrentConversation($message);
        }

        return new Response($response);
    }

    /**
     * @param string $message
     * @return string
     * sends the given message to the active conversation
     */
    private function sendMessageToCurrentConversation(string $message) :string {
        $messageResponse = "";

        //first sends the message to the API
        $token = $this->getApiToken();
        $conversationId = $this->getConversationId();
        $headers = HttpRequestLib::getYodaBotHeaders($this->INBENTAKEY, false, $token, $conversationId);
        $body = HttpRequestLib::getYodaBotBody("", $message);
        $jsonResp = HttpRequestLib::sendPost($_SESSION["API_URL"]. "/v1/conversation/message", $headers, $body);

        //if any response is received back, returns it
        if (sizeof($jsonResp->answers) > 0) {
            $messageResponse = $jsonResp->answers[0]->message;
        }
        return $messageResponse;
    }

    /**
     * stats a conversation with YodaBot and stores in session the conversation id*/
    private function startConversation() : void {
        $token = $this->getApiToken();
        $headers = HttpRequestLib::getYodaBotHeaders($this->INBENTAKEY, false, $token);
        $body = array();
        $jsonResp = HttpRequestLib::sendPost($_SESSION["API_URL"]. "/v1/conversation", $headers, $body);
        $_SESSION["CONVERSATION_ID"] = $jsonResp->sessionToken;
    }

    /**
     * @return string
     * returns the current conversation id
     */
    private function getConversationId() : string {
        return $_SESSION["CONVERSATION_ID"];
    }

    /**
     * returns true if there is an active conversation with YodaBot
    */
    private function isConversationActive() : bool
    {
        $isActive = false;
        if (isset($_SESSION["CONVERSATION_ID"])) {
            $isActive = true;
        }

        return $isActive;
    }

    /**
     * @return string
     * returns a valid API token
     */
    private function getApiToken() : string {
        $token = "";

        //validates the session stored token if any
        if ($this->isValidToken()) {
            $token = $_SESSION["API_TOKEN"];
        }
        else {
            //requests a new valid token and refreshes the API url
            $headers = HttpRequestLib::getYodaBotHeaders($this->INBENTAKEY);
            $data = HttpRequestLib::getYodaBotBody($this->INBENTASECRET);
            $jsonResp = HttpRequestLib::sendPost($this->BASE_API_URL, $headers, $data);
            $token = $jsonResp->accessToken;
            $_SESSION["API_TOKEN"] = $token;
            $_SESSION["API_URL"] = $jsonResp->apis->chatbot;
            $_SESSION["TOKEN_EXPIRATION"] = $jsonResp->expiration;
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
        if (isset($_SESSION["API_TOKEN"])) {
            //FIXME AITOR: validar duración del token!!!!!!!!!!!!!!!!!
            $validToken = true;
        }

        return $validToken;
    }
}