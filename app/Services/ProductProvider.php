<?php

namespace App\Services;
use GuzzleHttp\Client;

class ProductProvider
{
    public function fetchProducts($offset = 0, $limit = 500)
    {
        $client = new Client([
            'base_uri' => env('OZON_API_URL'),
            'headers' => [
                'Client-Id' => env('OZON_CLIENT_ID'),
                'Api-Key' => env('OZON_API_KEY'),
                'Content-Type' => 'application/json',
            ]
        ]);

        $response = $client->post('/v3/product/list', [
            'json' => [
                'filter' => [
                    "visibility" => "VISIBILITY_ALL",
                ],
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
