<?php


namespace App\Controller;


use App\Utils\APIConnection;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;


class YodaController extends AbstractController
{
    private $apiConnection;
    private $session;

    /**
     * YodaController constructor.
     * @param APIConnection $apiConnection
     * @param SessionInterface $session
     */
    public function __construct(APIConnection $apiConnection, SessionInterface $session)  {
        $this->apiConnection = $apiConnection;
        $this->session = $session;
    }

    /**
     * @Route("/", name="default")
     * returns the view of the home page
     */
    public function index() {
        $this->session->set("CONSEC_NO_RESP", 0);

        try {
            //gets the current conversation history if any
            $conHist = "";
            $response = $this->apiConnection->getConversationHistory();
            //converting history to string
            foreach ($response as $mesObj) {
                if (strlen($conHist) > 0) $conHist .= "|";
                $conHist .= json_encode($mesObj);
            }
        } catch (GuzzleException $e) {
            $conHist = "";
        }

        return $this->render("base.html.twig", ["history" => $conHist]);
    }

    /**
     * @Route("/sendMessageToYoda", name="sendMessage")
     * @return Response
     * sends the $message to the YodaBot and returns its response if any is received
     */
    public function sendMessageToYoda() : Response
    {
        //validates POST parameters
        $message = "";
        if (isset($_POST["message"])) {
            $message = $_POST["message"];
        }

        //if message is received, starts the process
        $respStr= "";
        if (strlen($message) > 0) {
            try {
                //searches for the "force" word in the message
                if (strpos($message, "force") > -1) {
                    $charactersList = $this->apiConnection->getHerokuList("films");
                    $respStr = $this->getCharactersHtml($charactersList, 'title');
                }
                else {
                    $response = $this->apiConnection->sendMessageToCurrentConversation($message);
                    $respStr = $response["message"];

                    //if no response flag is set
                    if ($response["flag"] == "no-results") {
                        $numNoResp = $this->session->get("CONSEC_NO_RESP");
                        $numNoResp++;
                        if ($numNoResp == 2) {
                            //if it is the second consecutive no-result response get a characters list
                            $numNoResp = 0;
                            $charactersList = $this->apiConnection->getHerokuList("characters");
                            $respStr = $this->getCharactersHtml($charactersList, "name");
                        }
                        $this->session->set("CONSEC_NO_RESP", $numNoResp);
                    }
                    else {
                        $this->session->set("CONSEC_NO_RESP", 0);
                    }
                }
            }
            catch (GuzzleException $e) {
                $respStr = "ERROR";
            } catch (Exception $e) {
                $respStr = "ERROR";
            }
        }

        return new Response($respStr);
    }

    /**
     * @param array $characters
     * @param string $mainProp
     * @return string
     * returns an html list of the given characters in a string
     */
    private function getCharactersHtml(array $characters, string $mainProp) : string {
        $result = "<ul>";
        foreach($characters as $character) {
            $result .= "<li>". $character->$mainProp ."</li>";
        }
        $result .= "</ul>";

        return $result;
    }
}