<?php

namespace App\Controller\Api;

use App\Repository\MoodRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MoodController extends AbstractController
{
    /**
     * @Route("/api/moods", name="api_friendship_browse", methods={"GET"})
     */
    public function browseMoods(MoodRepository $moodRepository): Response
    {
        $moods = $moodRepository->findAll();
       
        return $this->json($moods, Response::HTTP_OK, [], ['groups' => 'mood_info']);
    }
}
