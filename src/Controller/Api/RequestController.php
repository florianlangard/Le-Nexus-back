<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
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
     * @Route("/api/request/{targetId}/friend", name="api_request_send", methods={"POST"})
     */
    public function sendFriendRequest(Request $request, ValidatorInterface $validator, SerializerInterface $serializer, EntityManagerInterface $em, UserRepository $userRepository,User $user): Response
    {
        $user = $this->getUser();
        $target = 
        dd($user);


        // $jsonContent = $request->getContent();
        // // dd($jsonContent);
        // $userRequest = $serializer->deserialize($jsonContent, EntityRequest::class, 'json');
        // $errors = $validator->validate($userRequest);
        
        // if (count($errors) > 0) {
        //     // Convertit la variable en chaîne
        //     $errorsString = (string) $errors;
            
        //     // return new Response($errorsString);
        //     return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        // }
        // $em->persist($userRequest);
        // // dd($userRequest);
        // $em->flush($userRequest);
        
        // return $this->json($userRequest, Response::HTTP_CREATED, ['groups' => 'request_info']);
    }
}
