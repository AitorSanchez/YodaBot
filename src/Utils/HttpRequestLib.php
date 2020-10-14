<?php

namespace App\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HttpRequestLib
{
    /**
     * @param string $url
     * @param $headers
     * @param array $data
     * @return array
     * sends an http POST Request to the given $url passing $data and returns the web response if any
     * @throws GuzzleException
     */
    public static function sendPost(string $url, array $headers, array $data) {
        $client = new Client(['headers' => $headers]);
        $req = $client->request('POST', $url , ["form_params" => $data]);
        $response = json_decode($req->getBody()->getContents());

        return ["headers" => $req->getHeaders(), "body" => $response];
    }

    /**
     * @param string $baseUrl
     * @param string $uri
     * @param array $data
     * @return array
     * sends an http POST Request to the given $url passing JSON $data and returns the web response if any
     * @throws GuzzleException
     */
    public static function sendJsonPost(string $baseUrl, string $uri, array $data) {
        $client = new Client(["base_uri" => $baseUrl]);
        $req = $client->request('POST', $uri, ['json' => $data]);
        $response = json_decode($req->getBody()->getContents());

        return ["headers" => $req->getHeaders(), "body" => $response];
    }

    /**
     * @param string $url
     * @param $headers
     * @return array
     * sends an http GET Request to the given $url and returns the web response if any
     * @throws GuzzleException
     */
    public static function sendGet(string $url, array $headers) {
        $client = new Client(['headers' => $headers]);
        $req = $client->request('GET', $url );
        $response = json_decode($req->getBody()->getContents());

        return ["headers" => $req->getHeaders(), "body" => $response];
    }
}