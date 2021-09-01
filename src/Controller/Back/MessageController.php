<?php

namespace App\Controller\Back;

use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/back/message")
 */
class MessageController extends AbstractController
{
    /**
     * @Route("/", name="back_message_index", methods={"GET"})
     */
    public function index(MessageRepository $messageRepository): Response
    {
        return $this->render('back/message/index.html.twig', [
            'messages' => $messageRepository->findAll(),
        ]);
    }

    /**
     * @Route("/{id}", name="back_message_show", methods={"GET"})
     */
    public function show(Message $message): Response
    {
        return $this->render('back/message/show.html.twig', [
            'message' => $message,
        ]);
    }

    /**
     * @Route("/{id}", name="back_message_delete", methods={"POST"})
     */
    public function delete(Request $request, Message $message): Response
    {
        if ($this->isCsrfTokenValid('delete'.$message->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($message);
            $entityManager->flush();
        }

        return $this->redirectToRoute('back_message_index', [], Response::HTTP_SEE_OTHER);
    }
}
