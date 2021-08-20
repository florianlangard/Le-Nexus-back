<?php

namespace App\Controller\Api;

use App\Entity\Friendship;
use App\Entity\User;
use App\Repository\GameRepository;
use App\Repository\MoodRepository;
use App\Repository\UserRepository;
use App\Repository\RequestRepository;
use App\Repository\FriendshipRepository;
use App\Service\steamApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;

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
     * @Route("/api/users/{id<\d+>}", name="api_users_get_item", methods="GET")
     */
    public function read(User $user): Response
    {  
        return $this->json($user, Response::HTTP_OK, [], 
        // ['groups' => 'user_info']
    );
    }

    /**
     * @Route("/api/users/{id<\d+>}/games", name="api_users_get_games", methods="GET")
     */
    public function readGamesByUser(User $user, GameRepository $gameRepository): Response
    {
        $games = $gameRepository->findBy($user);
       
        return $this->json($games, Response::HTTP_OK, [], ['groups' => 'game_info']);
    }

    /**
     * @Route("/api/users/{id<\d+>}/mood", name="api_users_get_mood", methods="GET")
     */
    public function readMoodByUser(User $user, MoodRepository $moodRepository): Response
    {
        $mood = $moodRepository->find($user);
       
        return $this->json($mood, Response::HTTP_OK, [], ['groups' => 'mood_info']);
    }

    /**
     * @Route("/api/users/{id<\d+>}/friends", name="api_users_get_friends", methods="GET")
     */
    public function readFriendsByUser(User $user, FriendshipRepository $friendshipRepository): Response
    {
        $friends = $friendshipRepository->findBy($user);
       
        return $this->json($friends, Response::HTTP_OK, [], ['groups' => 'user_info']);
    }

    /**
     * @Route("/api/users/{id<\d+>}/requests", name="api_users_get_requests", methods="GET")
     */
    public function readReceivedRequestsByUser(User $user, RequestRepository $requestRepository): Response
    {
        $requests = $requestRepository->findBy($user);
       
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

        dd($user);

        return $this->json(['user' => $user, 'notice' => $notice], Response::HTTP_CREATED, ['groups' => 'user_info']);

    }

    /**
     * @Route("/api/users/{id<\d+>}", name="api_users_patch", methods={"PATCH", "PUT"})
     */
    public function patch(Request $request, User $user = null, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        if ($user === null) {
            return $this->json(['message' => 'utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $jsonContent = $request->getContent();

        $userUpdated = $serializer->deserialize($jsonContent, User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);

        $errors = $validator->validate($userUpdated);

        if (count($errors) > 0) {
            // $errorsString = (string) $errors;

            $newErrors = [];

            foreach ($errors as $error) {
                $newErrors[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json($newErrors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $entityManager->persist($userUpdated);
        $entityManager->flush();

        // dd($movie);

        return $this->json($userUpdated, Response::HTTP_ACCEPTED, ['groups' => 'movies_get']);
        // return $this->redirectToRoute('api_movies_get_item', ['id' => $movieUpdated->getId()], Response::HTTP_ACCEPTED);

        // @todo Conditionner le message de retour au cas où
        // l'entité ne serait pas modifiée
        // return new JsonResponse(['message' => 'Film modifié.', Response::HTTP_OK]);
    }

    /**
     * @Route("/api/users/{id<\d+>}", name="api_users_delete", methods="DELETE")
     */
    public function delete(User $user = null, FriendshipRepository $friendshipRepository, EntityManagerInterface $em)
    {
        if (null === $user) {
            $error = 'Cet utilisateur n\'existe pas';
            return $this->json(['error' => $error], Response::HTTP_NOT_FOUND);
        }

        $friendships = $friendshipRepository->findBy(['user' => $user]);
        $friendshipsReverse = $friendshipRepository->findBy(['friend' => $user]);

        foreach ($friendships as $currentFriendship) {
            $em->remove($currentFriendship);
        }

        foreach ($friendshipsReverse as $currentFriendshipReverse) {
            $em->remove($currentFriendshipReverse);
        }
        
        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'L\'utilisateur a bien été supprimé.'], Response::HTTP_OK);
    }
}