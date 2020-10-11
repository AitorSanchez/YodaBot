<?php


namespace App\Controller;


class HttpRequestLib
{
    /**
     * @param string $url
     * @param $headers
     * @param array $data
     * @return object
     * sends an http POST Request to the given $url passing $data and returns the web response if any
     */
    public static function sendPost(string $url, array $headers, array $data) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = json_decode(curl_exec($curl));
        curl_close($curl);

        return $response;
    }

    /**
     * @param string $secret
     * @param string $message
     * @return array
     * returns an array with the body parameters for an htpRequest to YodaBot
     */
    public static function getYodaBotBody(string $secret = "", string $message = "") : array {
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
    public static function getYodaBotHeaders(string $inbKey, bool $addContentType = true, string $authorization = "", string $inbSession = "") : array {
        $headerArr = array(
            "x-inbenta-key: " .$inbKey. "",
        );

        if ($addContentType) {
            $header = "Content-type: application/x-www-form-urlencoded";
            array_push($headerArr, $header);
        }

        if (strlen($authorization) > 0) {
            $header = "Authorization: Bearer " .$authorization;
            array_push($headerArr, $header);
        }

        if (strlen($inbSession) > 0) {
            $header = "x-inbenta-session: Bearer " .$inbSession;
            array_push($headerArr, $header);
        }

        return $headerArr;
    }
}