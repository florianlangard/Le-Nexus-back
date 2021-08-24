<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Entity\Request as EntityRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RequestController extends AbstractController
{
    /**
     * @Route("/request", name="request")
     */
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/RequestController.php',
        ]);
    }

    /**
     * @Route("/api/request", name="api_request_send", methods={"POST"})
     */
    public function sendFriendRequest(Request $request, ValidatorInterface $validator, SerializerInterface $serializer, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        // $sender = $serializer->deserialize($user, User::class, 'json');
         
        // dd($sender);


        $jsonContent = $request->toArray();
        // dd($jsonContent);
        $newRequest = new EntityRequest();
        $newRequest->setSender($userRepository->find($jsonContent['sender']));
        $newRequest->setTarget($userRepository->find($jsonContent['target']));
        $newRequest->setFriend($jsonContent['friend']);
        $newRequest->setGame($jsonContent['game']);
        // dd($newRequest);
        // $userRequest = $serializer->deserialize($jsonContent, EntityRequest::class, 'json');


        $errors = $validator->validate($newRequest);
        
        if (count($errors) > 0) {
            // Convertit la variable en chaîne
            $errorsString = (string) $errors;
            
            // return new Response($errorsString);
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $em->persist($newRequest);
        // dd($userRequest);
        $em->flush();
        // dd($newRequest);
        return $this->json($newRequest, Response::HTTP_CREATED, [], ['groups' => 'request_info']);
    }
}
