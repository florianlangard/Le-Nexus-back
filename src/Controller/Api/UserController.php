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
     * @Route("/api/users", name="api_users_get", methods="GET")
     */
    public function browse(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
       
        return $this->json($users, Response::HTTP_OK, [], ['groups' => 'user_info']);
    }

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
        foreach ($this->getUser()->getFriends() as $currentFriendship) {

            $currentfriend = $currentFriendship->getFriend();

            if ($user === $this->getUser() || $user === $currentfriend) {
                return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user_info']);
            }
            return $this->json([], Response::HTTP_FORBIDDEN);
        }
    }

    // /**
    //  * @Route("/api/users/{id<\d+>}/games", name="api_users_get_games", methods="GET")
    //  */
    // public function readGamesByUser(User $user, GameRepository $gameRepository): Response
    // {
    //     $games = $gameRepository->findBy($user);
       
    //     return $this->json($games, Response::HTTP_OK, [], ['groups' => 'game_info']);
    // }

    /**
     * @Route("/api/users/{id<\d+>}/mood", name="api_users_get_mood", methods="GET")
     */
    public function readMoodByUser(User $user, MoodRepository $moodRepository): Response
    {
        $mood = $moodRepository->find($user);
       
        return $this->json($mood, Response::HTTP_OK, [], ['groups' => 'mood_info']);
    }

    /**
     * @Route("/api/users/{steamId<\d+>}/friends", name="api_users_get_friends", methods="GET")
     */
    public function readFriendsByUser(User $user, UserRepository $userRepository): Response
    {
        $friends = [];

        foreach ($user->getFriends() as $currentFriendship){
            $friend = $userRepository->find($currentFriendship->getFriend());
            $friends[] = $friend;
        }
       
        return $this->json($friends, Response::HTTP_OK, [], ['groups' => 'user_info']);
    }

    /**
     * @Route("/api/users/{id<\d+>}/requests", name="api_users_get_requests", methods="GET")
     */
    public function readReceivedRequestsByUser(User $user, RequestRepository $requestRepository): Response
    {
        $requests = $requestRepository->findBy(['target' => $user]);
       
        return $this->json($requests, Response::HTTP_OK, [], ['groups' => 'request_info', 'user_info']);
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

        $userSteamInfos = $steamApi->fetchUserInfo($user->getSteamId());

        $user->setSteamUsername($userSteamInfos["personaname"]);
        $user->setSteamAvatar($userSteamInfos["avatarfull"]);
        $user->setVisibilityState($userSteamInfos["communityvisibilitystate"]);
        
        // If the Steam profile is not set to public
        if (!$user->getVisibilityState()){

            $notice = "votre, compte Steam n'est pas en publique";
        }
        // If the Steam profile is public, we search for user's games and user's friends
        else {
            // $steamApi->fetchGamesInfo($user->getSteamId());
            // $steamApi->fetchFriendsInfo($user->getSteamId());
            $notice = null;
        }

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            // $errorsString = (string) $errors;
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $entityManager->persist($user);
        $entityManager->flush();

        if($notice === null){
            $steamApi->fetchGamesInfo($user->getSteamId());
            $steamApi->fetchFriendsInfo($user->getSteamId());
        }

        // dd($user);

        return $this->json(['user' => $user, 'notice' => $notice], Response::HTTP_CREATED, [], ['groups' => 'user_info', 'user_friends']);

    }

    /**
     * @Route("/api/users/{steamId<\d+>}", name="api_users_patch", methods={"PATCH", "PUT"})
     */
    public function patch(Request $request, User $user = null, UserPasswordHasherInterface $userPasswordHasher, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        if ($user === null) {
            return $this->json(['message' => 'utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $jsonContent = $request->getContent();

        $userUpdated = $serializer->deserialize($jsonContent, User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
        
        $content = $request->toArray();
  
        if ($content['password']) {
            $hashedPassword = $userPasswordHasher->hashPassword($userUpdated, $userUpdated->getPassword());
            $userUpdated->setPassword($hashedPassword);
        }

        $errors = $validator->validate($userUpdated);

        if (count($errors) > 0) {

            $newErrors = [];

            foreach ($errors as $error) {
                $newErrors[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json($newErrors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $entityManager->persist($userUpdated);
        $entityManager->flush();

        return $this->json($userUpdated, Response::HTTP_ACCEPTED, [],  ['groups' => 'user_info']);
        // return $this->redirectToRoute('api_movies_get_item', ['id' => $movieUpdated->getId()], Response::HTTP_ACCEPTED);

        // @todo Conditionner le message de retour au cas où
        // l'entité ne serait pas modifiée
        // return new JsonResponse(['message' => 'Film modifié.', Response::HTTP_OK]);
    }

    /**
     * @Route("/api/users/{id<\d+>}", name="api_users_delete", methods="DELETE")
     */
    public function delete(User $user, FriendshipRepository $friendshipRepository, EntityManagerInterface $em)
    {
        if ($user === $this->getUser()) {
            
            
            $friendshipsReverse = $friendshipRepository->findBy(['friend' => $user]);
            
            foreach ($friendshipsReverse as $currentFriendshipReverse) {
                $em->remove($currentFriendshipReverse);
            }
            
            $em->remove($user);
            $em->flush();
            
            return $this->json(['message' => 'L\'utilisateur a bien été supprimé.'], Response::HTTP_OK);
        }
        
        return $this->json([], Response::HTTP_FORBIDDEN);

    }

    // /**
    //  * @Route("/api/users/{id<\d+>}", name="api_users_delete", methods="DELETE")
    //  */
    // public function delete(User $user = null, FriendshipRepository $friendshipRepository, EntityManagerInterface $em)
    // {
    //     if (null === $user) {
    //         $error = 'Cet utilisateur n\'existe pas';
    //         return $this->json(['error' => $error], Response::HTTP_NOT_FOUND);
    //     }

    //     $friendshipsReverse = $friendshipRepository->findBy(['friend' => $user]);

    //     foreach ($friendshipsReverse as $currentFriendshipReverse) {
    //         $em->remove($currentFriendshipReverse);
    //     }
        
    //     $em->remove($user);
    //     $em->flush();

    //     return $this->json(['message' => 'L\'utilisateur a bien été supprimé.'], Response::HTTP_OK);
    // }
}
