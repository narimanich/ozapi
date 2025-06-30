<?php

namespace App\Services;

use GuzzleHttp\Client;

class ProductProvider
{
    public $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => env('OZON_API_URL'),
            'headers' => [
                'Client-Id' => env('OZON_CLIENT_ID'),
                'Api-Key' => env('OZON_API_KEY'),
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function fetchProducts($offset = 0, $limit = 500)
    {
        $response = $this->client->post('/v3/product/list', [
            'json' => [
                'filter' => [
                    "visibility" => "VISIBILITY_ALL",
                ],
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
        $data = json_decode($response->getBody()->getContents(), true);

        if (empty($data['result']['items'])) {
            return [];
        }

        $productsMap = [];
        foreach ($data['result']['items'] as $item) {
            $productsMap[$item['product_id']] = $item;
        }

        $productIdsChunk = array_chunk(array_keys($productsMap), 50);

        $result = [];
        foreach ($productIdsChunk as $chunk) {
            foreach ($this->getPricesByProductIds($chunk) as $id => $price) {
                if (isset($productsMap[$id])) {
                    $result[] = array_merge($productsMap[$id], $price);
                }
            }
        }

        return $result;
    }

    public function getPricesByProductIds($productIds)
    {
        $response = $this->client->post('/v5/product/info/prices', [
            'json' => [
                'filter' => [
                    "product_id" => $productIds,
                ],
                "limit" => count($productIds),
            ]
        ]);
        $data = json_decode($response->getBody(), true);

        $priceMap = [];
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $priceMap[$item['product_id']] = ['price' => $item['price']['price'] . ' ' . $item['price']['currency_code']];
            }
        }
        return $priceMap;
    }
}
