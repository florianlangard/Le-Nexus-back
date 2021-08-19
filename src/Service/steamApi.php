<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class steamApi
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function fetchUserInfo(string $steamId): array
    {
        $response = $this->client->request(
            'GET',
            'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=8042AD2C22CFB15EE1A668BACFEB5D27&steamids='.$steamId);

        $content = $response->toArray();
        $content = $content["response"]["players"][0];

        return $content;
    }    
}