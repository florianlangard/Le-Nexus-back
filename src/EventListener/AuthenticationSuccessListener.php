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

        $this->steamApi->updateUserInfo(($event->getUser()));

        if (!$event->getUser()->getVisibilityState()) {
            $notice = 'Votre compte est privé. Nous n\'avons pas pu mettre à jour vos informations Nexus.';
        }
        else {
            $successOnGames   = $this->steamApi->fetchGamesInfo(strval($event->getUser()->getSteamId()));
            $successOnFriends = $this->steamApi->fetchFriendsInfo(strval($event->getUser()->getSteamId()));

            if ($successOnGames && $successOnFriends) {
                $notice = 'Mise à jour : OK';
            }
            elseif (!$successOnGames){
                $notice = "Désolé, nous n'avons pas pu mettre à jour vos jeux. Veuillez reessayer plus tard.";
            }
            else {
                $notice = "Désolé, nous n'avons pas pu mettre votre liste d'amis à jour. Veuillez reessayer plus tard";
            }          
        }

        $event->setData([
            // 'code' => $event->getResponse()->getStatusCode(),
            'payload' => $event->getData(),
            'authenticatedUserId' => $authenticatedUserId,
            'update notice' => $notice
        ]);
    }
}