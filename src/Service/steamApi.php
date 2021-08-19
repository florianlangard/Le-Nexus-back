<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class steamApi
{
    private $client;
    private $gameRepository;
    private $em;

    public function __construct(HttpClientInterface $client, GameRepository $gameRepository, EntityManagerInterface $em)
    {
        $this->client = $client;
        $this->gameRepository  = $gameRepository;
        $this->em = $em;
    }

    public function fetchUserInfo(string $steamId): array
    {
        $response = $this->client->request(
            'GET',
            'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=8042AD2C22CFB15EE1A668BACFEB5D27&steamids='.$steamId);

        $content = $response->toArray();
        $content = $content["response"]["players"][0];

        // Set to "true" or "false" the "communityvisibiltystate" keyto match our db because the api returns 3 or 1
        if ($content["communityvisibilitystate"] === 3) {
            $content["communityvisibilitystate"] = true;
        }
        else {
            $content["communityvisibilitystate"] = false;
        }

        return $content;
    }
    
    public function fetchGamesInfo(string $steamId)
    {
        $response = $this->client->request(
            'GET',
            'http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key=8042AD2C22CFB15EE1A668BACFEB5D27&steamid='.$steamId.'&include_appinfo=true&format=json');

        $content = $response->toArray();

        $games = $content["response"]["games"];

        foreach($games as $currentGame){
            if (!$this->gameRepository->findOneBy(['appid' => $currentGame['appid']])){

                $curentGamePictureUrl = 'http://media.steampowered.com/steamcommunity/public/images/apps/'.$currentGame['appid'].'/'.$currentGame['img_logo_url'].'.jpg';

                $newGame = new Game();
                $newGame
                ->setName($currentGame['name'])
                ->setAppid($currentGame['appid'])
                ->setPicture($curentGamePictureUrl);

                $this->em->persist($newGame);
                $this->em->flush();
            }
            else{
                return 'ok';
            }
        }
    }
}