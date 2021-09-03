<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ErrorController extends AbstractController
{
    /**
     * @Route("/back/error", name="back_error")
     */
    public function show(): Response
    {
        return $this->render('back/error/index.html.twig', [
            'controller_name' => 'ErrorController',
        ]);
    }
}
