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
        $message = "Hi";
        if (isset($_POST["message"])) {
            $message = $_POST["message"];
        }

        //if message is received, starts the process
        $respStr= "";
        if (strlen($message) > 0) {
            /*try {
                $response = $this->apiConnection->sendMessageToCurrentConversation($message);
                $respStr = $response["message"];

                //if no response flag is set
                if ($response["flag"] == "no-results") {
                    $numNoResp = $this->session->get("CONSEC_NO_RESP");
                    $numNoResp++;
                    if ($numNoResp == 2) {
                        //if it is the second consecutive no-result response
                        $respStr = "second consecutive!!";
                        $numNoResp = 0;
                    }
                    $this->session->set("CONSEC_NO_RESP", $numNoResp);
                }
                else {
                    $this->session->set("CONSEC_NO_RESP", 0);
                }
            }
            catch (GuzzleException $e) {
                $respStr = "ERROR";
            } catch (Exception $e) {
                $respStr = "ERROR";
            }*/
            try {
                $response = $this->apiConnection->getCharactersList();
            } catch (GuzzleException $e) {
            }

        }

        return new Response($respStr);
    }

    /*
     * {
  allPeople(first: 5) {
    people {
      name
    }
  }
    {
  allFilms(first: 5) {
    films {
      title
    }
  }
}
}*/
}