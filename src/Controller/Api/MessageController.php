<?php

namespace App\Controller\Api;

use App\Entity\Message;
use App\Service\Mailing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MessageController extends AbstractController
{
    /**
     * @Route("/api/message", name="api_message", methods="POST")
     */
    public function createMessage(
        Request $request, 
        SerializerInterface $serializer, 
        EntityManagerInterface $entityManager, 
        ValidatorInterface $validator,
        Mailing $mailing): Response
    {
        $jsonContent = $request->getContent();
        
        $message = $serializer->deserialize($jsonContent, Message::class, 'json');

        // Transferring message to Mailbox
        $sender = $message->getEmail();
        $content = $message->getContent();
        
        $errors = $validator->validate($message);

        // If the Message object filled with the received request does not match with constraints validation : send the error(s)
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $entityManager->persist($message);
        $entityManager->flush();

        $mailing->sendContactEmail($sender, $content);

        return $this->json(Response::HTTP_CREATED);
    }
}
