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
     * @Route("/api/request", name="api_request_send", methods={"POST"})
     */
    public function sendRequest(Request $request, ValidatorInterface $validator, SerializerInterface $serializer, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $jsonContent = $request->getContent();

        $newRequest = $serializer->deserialize($jsonContent, EntityRequest::class, 'json');

        if ($newRequest->getSender() === $this->getUser() && $newRequest->getType() === 'friend') {
            $errors = $validator->validate($newRequest);
        
            if (count($errors) > 0) {
                $errorsString = (string) $errors;
            
                return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $em->persist($newRequest);
            $em->flush();

            return $this->json($newRequest, Response::HTTP_CREATED, [], ['groups' => 'request_info']);

        } elseif ($newRequest->getSender() === $this->getUser() && $newRequest->getType() === 'game') {

            foreach ($this->getUser()->getFriends() as $currentFriendship) {

                $currentfriend = $currentFriendship->getFriend();
    
                if ($newRequest->getTarget() === $currentfriend) {
                    $errors = $validator->validate($newRequest);
        
                    if (count($errors) > 0) {
                        $errorsString = (string) $errors;
                    
                        return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                    $em->persist($newRequest);
                    $em->flush();
        
                    return $this->json($newRequest, Response::HTTP_CREATED, [], ['groups' => 'request_info']);
                }
                
            }
        }
        return $this->json([], Response::HTTP_FORBIDDEN);
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
        if($entityRequest->getType() === "game") {
            $updatedFriendship = $responseHandler->handleGameRequest($updatedRequest);
            return $this->json($updatedFriendship, Response::HTTP_ACCEPTED, [], ['groups' => 'user_info']);
        }
        if($entityRequest->getType() === "friend") {
            $newFriendship = $responseHandler->handleFriendRequest($updatedRequest);
            return $this->json($newFriendship, Response::HTTP_CREATED, [], ['groups' => 'user_info']);
        }
    }
}
