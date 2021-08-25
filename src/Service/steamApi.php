<?php

namespace App\Service;

use App\Entity\Friendship;
use App\Entity\Game;
use App\Entity\Library;
use App\Repository\FriendshipRepository;
use App\Repository\GameRepository;
use App\Repository\LibraryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class steamApi
{
    private $client;
    private $gameRepository;
    private $friendshipRepository;
    private $userRepository;
    private $libraryRepository;
    private $em;

    public function __construct(HttpClientInterface $client, LibraryRepository $libraryRepository, GameRepository $gameRepository, FriendshipRepository $friendshipRepository, UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->client = $client;
        $this->gameRepository  = $gameRepository;
        $this->libraryRepository = $libraryRepository;
        $this->friendshipRepository = $friendshipRepository;
        $this->userRepository = $userRepository;
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
    
    // TODO : creer des variables user et games pour simplifier les paramètres 
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

                $newLibrary = new Library();

                $newLibrary
                ->setUser($this->userRepository->findOneBy(['steamId' => $steamId]))
                ->setGame($newGame);

                $this->em->persist($newLibrary);

                $this->em->flush();
            }

            if (!$this->libraryRepository->findOneByGameAndUser($this->gameRepository->findOneBy(['appid' => $currentGame['appid']]), $this->userRepository->findOneBy(['steamId' => $steamId]))) {
               
                $newLibrary = new Library();

                $newLibrary
                ->setUser($this->userRepository->findOneBy(['steamId' => $steamId]))
                ->setGame($this->gameRepository->findOneBy(['appid' => $currentGame['appid']]));

                $this->em->persist($newLibrary);

                $this->em->flush();
            }
            // else{
            //     return 'ok';
            // }
        }
    }

    public function fetchFriendsInfo($steamId)
    {
        $response = $this->client->request(
            'GET',
            'https://api.steampowered.com/ISteamUser/GetFriendList/v0001/?key=8042AD2C22CFB15EE1A668BACFEB5D27&steamid='.$steamId.'&relationship=friend');

        $content = $response->toArray();

        $friends = $content["friendslist"]["friends"];

        // dd($friends);
        // dd($this->userRepository->findOneBy(['steamId' => $friends[1]['steamid'] ]));
        foreach($friends as $currentFriend){
            // dd($this->userRepository->findOneBy(['id' => $currentFriend['steamid'] ]));
            if ($this->userRepository->findOneBy(['steamId' => $currentFriend['steamid'] ]) != null ){

                // dd('hiufhviudhe');
                $actualUser   = $this->userRepository->findOneBy(['steamId' => $steamId]);
                $hisNewFriend = $this->userRepository->findOneBy(['steamId' => $currentFriend['steamid']]);

                // dd($actualUser);
                // dd($hisNewFriend);

                $newFriendship = new Friendship();
                $newFriendship
                ->setFriend($actualUser)
                ->setUser($hisNewFriend);
                $this->em->persist($newFriendship);

                $newFriendshipReverse = new Friendship();
                $newFriendshipReverse
                ->setFriend($hisNewFriend)
                ->setUser($actualUser);
                $this->em->persist($newFriendshipReverse);

                // $hisNewFriend->addFriend($newFriendship);
                // $actualUser->addFriend($newFriendshipReverse);
                               
                $this->em->flush();
            }
        }
    }
}