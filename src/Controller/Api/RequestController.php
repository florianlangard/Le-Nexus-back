<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Repository\RequestRepository;
use App\Entity\Request as EntityRequest;
use App\Repository\FriendshipRepository;
use App\Repository\LibraryRepository;
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
    public function sendRequest(FriendshipRepository $friendshipRepository, RequestRepository $requestRepository, LibraryRepository $libraryRepository, Request $request, ValidatorInterface $validator, SerializerInterface $serializer, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $jsonContent = $request->getContent();

        $newRequest = $serializer->deserialize($jsonContent, EntityRequest::class, 'json');

        // If the sender is the user connected and if the request is of type "friend" : handle the request
        if ($newRequest->getSender() === $this->getUser() && $newRequest->getType() === 'friend') {

            // If Users are already friends : send an error
            if ($friendshipRepository->findOneByUserAndFriend($this->getUser(), $newRequest->getTarget())) {
                return $this->json('Vous êtes déjà ami avec cet utilisateur.', Response::HTTP_FORBIDDEN);
            }
            // If the user already sent an invitation to this user : send an error
            if ($requestRepository->findOneBySenderAndTarget($this->getUser(), $newRequest->getTarget())) {
                return $this->json('Vous avez déjà envoyé une invitation à cet utilisateur.', Response::HTTP_FORBIDDEN);
            }

            $errors = $validator->validate($newRequest);

            if (count($errors) > 0) {           
                // If the Request object filled with the received request does not match with constraints validation : send the error(s)
                return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $em->persist($newRequest);
            $em->flush();

            return $this->json($newRequest, Response::HTTP_CREATED, [], ['groups' => 'request_info']);

        } 
        // If the sender is the user connected and if the request is of type "game" : handle the request
        elseif ($newRequest->getSender() === $this->getUser() && $newRequest->getType() === 'game') {

            // If the user connected has not in his frendslist the target of the request : send an error
            if (!$friendshipRepository->findOneByUserAndFriend($this->getUser(), $newRequest->getTarget())) {
                return $this->json('Vous devez être ami avec cet utilisateur pour lui envoyer une invitation.', Response::HTTP_FORBIDDEN);
            }
            // If the user connected has not the game in his library : send an error
            if (!$libraryRepository->findOneByGameAndUser($newRequest->getGame(), $this->getUser())) {
                return $this->json('Vous devez avoir ce jeu dans votre bibliothèque pour envoyer une invitation.', Response::HTTP_FORBIDDEN);
            }
            // If the target of the request has not the game in his library : send an error
            if (!$libraryRepository->findOneByGameAndUser($newRequest->getGame(), $newRequest->getTarget())) {
                return $this->json('Votre ami doit avoir ce jeu dans sa bibliothèque pour lui envoyer une invitation.', Response::HTTP_FORBIDDEN);
            }

            $errors = $validator->validate($newRequest);

            if (count($errors) > 0) {
                // If the Request object filled with the received request does not match with constraints validation : send the error(s)
                return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $em->persist($newRequest);
            $em->flush();

            return $this->json($newRequest, Response::HTTP_CREATED, [], ['groups' => 'request_info']);           
        }
        
        return $this->json([], Response::HTTP_FORBIDDEN);
    }
        
        

    /**
     * @Route("/api/request/{id}", name="api_request_answer", methods={"PATCH"})
     */
    public function sendResponse(Request $request, FriendshipRepository $friendshipRepository, ValidatorInterface $validator, SerializerInterface $serializer, EntityManagerInterface $em, EntityRequest $entityRequest, ResponseHandler $responseHandler)
    {
        $json = $request->getContent();

        $updatedRequest = $serializer->deserialize($json, EntityRequest::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $entityRequest]);

        // If the user connected is the target of the request : handle the response
        if ($updatedRequest->getTarget() === $this->getUser()) {

            // If the user connected has already in his frendslist the user who sent the request : send an error
            if ($friendshipRepository->findOneByUserAndFriend($this->getUser(), $updatedRequest->getSender())) {
                return $this->json('Vous êtes déjà ami avec cet utilisateur.', Response::HTTP_FORBIDDEN);
            }

            $errors = $validator->validate($updatedRequest);

            // If the Request object updated with the received request does not match with constraints validation : send the error(s)
            if (count($errors) > 0) {
                // Making an array like : ["name of the property" => "the error message that corresponds to this property"]
                $newErrors = [];
                
                foreach ($errors as $error) {
                    $newErrors[$error->getPropertyPath()][] = $error->getMessage();
                }

                return new JsonResponse(["errors" => $newErrors], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $em->flush();

            // If the request is of type "game" : call the servive "responseHandler" with method "handleGameReqest"
            if($entityRequest->getType() === "game") {
                $updatedFriendship = $responseHandler->handleGameRequest($updatedRequest);
                return $this->json($updatedFriendship, Response::HTTP_ACCEPTED, [], ['groups' => 'user_info']);
            }
            // If the request is of type "friend" : call the servive "responseHandler" with method "handleFriendReqest"
            if($entityRequest->getType() === "friend") {
                $newFriendship = $responseHandler->handleFriendRequest($updatedRequest);
                return $this->json($newFriendship, Response::HTTP_CREATED, [], ['groups' => 'user_info']);
            }
        }

        return $this->json([], Response::HTTP_FORBIDDEN);      
    }
}
