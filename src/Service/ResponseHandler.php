<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Repository\FriendshipRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseHandler
{
    private $userRepository;
    private $friendshipRepository;
    private $em;

    public function __construct(UserRepository $userRepository, FriendshipRepository $friendshipRepository, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->friendshipRepository = $friendshipRepository;
        $this->em = $em;
    }

    public function handleGameRequest($request)
    {
        if ($request->getAcceptedAt()){
            $friendship = $this->friendshipRepository->findOneBy(['user' => $request->getSender()]);
            $friendship->setTimesPlayed($friendship->getTimesPlayed() + 1);
            $friendship->setLastPlayed(new DateTime());
            $this->em->flush();
            return $friendship;
        }
    }

}

