<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\steamApi;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class AuthenticationSuccessListener
{
    private $steamApi;

    public function __construct(steamApi $steamApi)
    {
        $this->steamApi = $steamApi;
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        // $data = $event->getData(); yields $data['token'] = <the token>
        $authenticatedUserId = strval($event->getUser()->getSteamId());
        // $person_id = $authenticatedUser->getSteamId();
        // dd($authenticatedUserId);
        // $person_id = $person->getId();

        $this->steamApi->fetchGamesInfo(strval($event->getUser()->getSteamId()));
        $this->steamApi->fetchFriendsInfo(strval($event->getUser()->getSteamId()));
        $this->steamApi->fetchUserInfo(strval($event->getUser()->getSteamId()));

        $event->setData([
            // 'code' => $event->getResponse()->getStatusCode(),
            'payload' => $event->getData(),
            'authenticatedUserId' => $authenticatedUserId,
        ]);
    }
}