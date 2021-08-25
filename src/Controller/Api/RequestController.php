<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\RequestRepository;
use App\Entity\Request as EntityRequest;
use App\Service\ResponseHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
    public function sendRequest(Request $request, ValidatorInterface $validator, SerializerInterface $serializer, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        // $user = $this->getUser();
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

    /**
     * @Route("/api/request/{id}", name="api_request_answer", methods={"PATCH"})
     */
    public function sendResponse(Request $request, ValidatorInterface $validator, SerializerInterface $serializer, EntityManagerInterface $em, EntityRequest $entityRequest, ResponseHandler $responseHandler)
    {
        $json = $request->getContent();
        $updatedRequest = $serializer->deserialize($json, EntityRequest::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $entityRequest]);

        $errors = $validator->validate($updatedRequest);

        if (count($errors) > 0) {

            $newErrors = [];

            foreach ($errors as $error) {
                $newErrors[$error->getPropertyPath()][] = $error->getMessage();
            }

            return new JsonResponse(["errors" => $newErrors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $em->flush();
        // dd($updatedRequest);
        if($entityRequest->getGame()) {
            $updatedFriendship = $responseHandler->handleGameRequest($updatedRequest);
            return $this->json($updatedFriendship, Response::HTTP_ACCEPTED, [], ['groups' => 'user_info']);
        }

        // @todo Conditionner le message de retour au cas où
        // l'entité ne serait pas modifiée
        return $this->json($updatedRequest, Response::HTTP_ACCEPTED, [], ['groups' => 'request_info']);
    }
}
