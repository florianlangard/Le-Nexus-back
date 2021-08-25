<?php

namespace App\Service;

use App\Entity\Friendship;
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

            $friendshipReverse = $this->friendshipRepository->findOneBy(['user' => $request->getTarget()]);
            $friendshipReverse->setTimesPlayed($friendshipReverse->getTimesPlayed() + 1);
            $friendshipReverse->setLastPlayed(new DateTime());

            $this->em->flush();
            return $friendship;
        }
    }

    public function handleFriendRequest($request)
    {
        if ($request->getAcceptedAt()) {
            $newFriendship = new Friendship();
            $newFriendship
                ->setUser($request->getSender())
                ->setFriend($request->getTarget());
            
            $this->em->persist($newFriendship);

            $newFriendshipReverse = new Friendship();
            $newFriendshipReverse
                ->setUser($request->getTarget())
                ->setFriend($request->getSender());

            $this->em->persist($newFriendshipReverse);

            $this->em->flush();
            return $newFriendship;
        }
    }

}

