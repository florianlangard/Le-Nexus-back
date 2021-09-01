<?php

namespace App\Controller\Api;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\Email;

class MessageController extends AbstractController
{
    /**
     * @Route("/api/message", name="api_message", methods="POST")
     */
    public function createMessage(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $jsonContent = $request->getContent();
        
        $message = $serializer->deserialize($jsonContent, Message::class, 'json');

        $errors = $validator->validate($message);

        if (count($errors) > 0) {
            // $errorsString = (string) $errors;
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $entityManager->persist($message);
        $entityManager->flush();

        return $this->json(Response::HTTP_CREATED);
    }
}
