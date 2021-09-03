<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\steamApi;
use App\Entity\Friendship;
use App\Repository\GameRepository;
use App\Repository\MoodRepository;
use App\Repository\UserRepository;
use App\Repository\RequestRepository;
use App\Repository\FriendshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/api/users/pseudo/{searching}", name="api_users_get_collection", methods="GET")
     */
    public function browseUsersByPartOfPseudo($searching, UserRepository $userRepository): Response
    {  
        $users = $userRepository->findByPartOfPseudo($searching);

        return $this->json($users, Response::HTTP_OK, [], ['groups' => 'user_info']);
    }

    /**
     * @Route("/api/users/{steamId<\d+>}", name="api_users_get_item", methods="GET")
     */
    public function read(User $user): Response
    {  
        // Setting by default a variable "allowed" to false
        $allowed = false;

        // If the request is made by the actual connected user or one of his friends : set "allowed" to true
        foreach ($user->getFriends() as $currentFriendship) {
            $currentfriend = $currentFriendship->getFriend();
            if ($user === $this->getUser() || $this->getUser() === $currentfriend) {
                $allowed = true;
            }
        }

        // If the connected user is allowed : send the infos of the requested user
        if ($allowed) {
            return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user_info']);
        }
        // If the connected user is not allowed : send an errors
        else {
            return $this->json([], Response::HTTP_FORBIDDEN);
        }
        
    }

    /**
     * @Route("/api/users/{steamId<\d+>}/mood", name="api_users_get_mood", methods="GET")
     */
    public function readMoodByUser(User $user, MoodRepository $moodRepository): Response
    {
        if ($user === $this->getUser()) {

            $mood = $moodRepository->find($user);
        
            return $this->json($mood, Response::HTTP_OK, [], ['groups' => 'mood_info']);
        }

        return $this->json([], Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("/api/users/{steamId<\d+>}/friends", name="api_users_get_friends", methods="GET")
     */
    public function readFriendsByUser(User $user, UserRepository $userRepository): Response
    {
        if ($user === $this->getUser()) {

            $friends = [];

            // Add to array "friends" each User object that are at the property "friend" of the friendships of the connected user
            // In other words : replacing Friendship object by User object at the property "friends" of the connected user and return this property filled with Users
            foreach ($user->getFriends() as $currentFriendship) {
                $friend = $userRepository->find($currentFriendship->getFriend());
                $friends[] = $friend;
            }
        
            return $this->json($friends, Response::HTTP_OK, [], ['groups' => 'user_info']);
        }

        return $this->json([], Response::HTTP_FORBIDDEN);
        
    }

    /**
     * @Route("/api/users/{steamId<\d+>}/requests", name="api_users_get_requests", methods="GET")
     */
    public function readReceivedRequestsByUser(User $user, RequestRepository $requestRepository): Response
    {
        if ($user === $this->getUser()) {
            $requests = $requestRepository->findBy(['target' => $user]);
       
            return $this->json($requests, Response::HTTP_OK, [], ['groups' => 'request_info', 'user_info']);
        }
        return $this->json([], Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("/api/users", name="api_users_add", methods="POST")
     */
    public function add(steamApi $steamApi, Request $request, UserPasswordHasherInterface $userPasswordHasher, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $jsonContent = $request->getContent();

        $user = $serializer->deserialize($jsonContent, User::class, 'json');
        
        $hashedPassword = $userPasswordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        // Calling the servive "steamApi" to get the Steam infos of the user
        $userSteamInfos = $steamApi->fetchUserInfo($user->getSteamId());

        // Set the infos returned by the service to the user
        $user->setSteamUsername($userSteamInfos["personaname"]);
        $user->setSteamAvatar($userSteamInfos["avatarfull"]);
        $user->setVisibilityState($userSteamInfos["communityvisibilitystate"]);
        
        // If the Steam profile is not set to public : create the corresponding message in "notice"
        if (!$user->getVisibilityState()){

            $notice = "Votre compte Steam n'est pas public.";
        }
        // If the Steam profile is public, we search for user's games and user's friends : set "notice" to null
        else {
            $notice = null;
        }

        $errors = $validator->validate($user);
        // If the User object filled with the received request does not match with constraints validation : send the error(s)
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $entityManager->persist($user);
        $entityManager->flush();

        // If the Steam profile is public : calling the others methods of the service "steamApi" to get Steam games and Steam friends
        if($notice === null){
            $steamApi->fetchGamesInfo($user->getSteamId());
            $steamApi->fetchFriendsInfo($user->getSteamId());
        }

        return $this->json(['user' => $user, 'notice' => $notice], Response::HTTP_CREATED, [], ['groups' => 'user_info', 'user_friends']);
    }

    /**
     * @Route("/api/users/{steamId<\d+>}", name="api_users_patch", methods={"PATCH", "PUT"})
     */
    public function patch(Request $request, User $user, UserPasswordHasherInterface $userPasswordHasher, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        if ($user === $this->getUser()) {
            $jsonContent = $request->getContent();
            
            $userUpdated = $serializer->deserialize($jsonContent, User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
            
            $content = $request->toArray();
            
            // If the password is changed : hash it
            if (array_key_exists('password', $content)) {
                $hashedPassword = $userPasswordHasher->hashPassword($userUpdated, $userUpdated->getPassword());
                $userUpdated->setPassword($hashedPassword);
            }
            // If the role is changed : send an error
            if (array_key_exists('roles', $content)) {
                return $this->json('You are not allowed to change your role', Response::HTTP_FORBIDDEN);
            }
            
            $errors = $validator->validate($userUpdated);
            // If the User object filled with the received request does not match with constraints validation : send the error(s)
            if (count($errors) > 0) {
                // Making an array like : ["name of the property" => "the error message that corresponds to this property"]
                $newErrors = [];
                
                foreach ($errors as $error) {
                    $newErrors[$error->getPropertyPath()][] = $error->getMessage();
                }
                
                return $this->json($newErrors, Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $entityManager->persist($userUpdated);
            $entityManager->flush();
            
            return $this->json($userUpdated, Response::HTTP_ACCEPTED, [], ['groups' => 'user_info']);
            
        }

        return $this->json([], Response::HTTP_FORBIDDEN);
    }
        
        /**
         * @Route("/api/users/{steamId<\d+>}", name="api_users_delete", methods="DELETE")
         */
        public function delete(User $user, FriendshipRepository $friendshipRepository, EntityManagerInterface $em)
    {
        if ($user === $this->getUser()) {
            // Finding the reverse friendships involving the user...    
            $friendshipsReverse = $friendshipRepository->findBy(['friend' => $user]);
            // ... and delete them as well
            foreach ($friendshipsReverse as $currentFriendshipReverse) {
                $em->remove($currentFriendshipReverse);
            }
            
            $em->remove($user);
            $em->flush();
            
            return $this->json(['message' => 'L\'utilisateur a bien été supprimé.'], Response::HTTP_OK);
        }
        
        return $this->json([], Response::HTTP_FORBIDDEN);
    }
}
