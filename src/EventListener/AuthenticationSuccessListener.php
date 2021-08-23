<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use App\Entity\User;

class AuthenticationSuccessListener
{
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        // $data = $event->getData(); yields $data['token'] = <the token>
        $authenticatedUserId = strval($event->getUser()->getSteamId());
        // $person_id = $authenticatedUser->getSteamId();
        // dd($authenticatedUserId);
        // $person_id = $person->getId();


        $event->setData([
            // 'code' => $event->getResponse()->getStatusCode(),
            'payload' => $event->getData(),
            'authenticatedUserId' => $authenticatedUserId,
        ]);
    }
}