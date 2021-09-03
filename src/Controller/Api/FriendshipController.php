<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Friendship;
use App\Repository\FriendshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FriendshipController extends AbstractController
{
    /**
     * @Route("/api/friendship/{id}", name="api_friendship_delete", methods={"DELETE"})
     */
    public function deleteFriendship(Friendship $friendship = null, FriendshipRepository $friendshipRepository, EntityManagerInterface $em): Response
    {
        //If the relation does not exists : send an error
        if (null === $friendship) {
            $error = 'Cette relation n\'existe pas';
            return $this->json(['error' => $error], Response::HTTP_NOT_FOUND);
        }

        //If the connected user is the user in the relationship
        if ($friendship->getUser() === $this->getUser()) {

            $friendshipReverse = $friendshipRepository->findOneByUserAndFriend($friendship->getFriend(),$friendship->getUser());

            $em->remove($friendship);
            $em->remove($friendshipReverse);
            $em->flush();

            return $this->json(['message' => 'La relation a bien été supprimée.'], Response::HTTP_OK);
        }

        // Else return an error because not authorized
        return $this->json([], Response::HTTP_FORBIDDEN);
    }
}
