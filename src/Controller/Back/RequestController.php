<?php

namespace App\Controller\Back;

use App\Entity\Request as NexusRequest;
use App\Form\RequestType;
use App\Repository\RequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/back/request")
 */
class RequestController extends AbstractController
{
    /**
     * @Route("/", name="back_request_index", methods={"GET"})
     */
    public function index(RequestRepository $requestRepository): Response
    {
        return $this->render('back/request/index.html.twig', [
            'requests' => $requestRepository->findAll(),
        ]);
    }

    /**
     * @Route("/{id}", name="back_request_delete", methods={"POST"})
     */
    public function delete(Request $request, NexusRequest $nexusRequest): Response
    {
        if( $nexusRequest === null) {
            return $this->redirectToRoute('back_error');
        }
        if ($this->isCsrfTokenValid('delete'.$nexusRequest->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($nexusRequest);
            $entityManager->flush();
        }

        return $this->redirectToRoute('back_request_index', [], Response::HTTP_SEE_OTHER);
    }
}